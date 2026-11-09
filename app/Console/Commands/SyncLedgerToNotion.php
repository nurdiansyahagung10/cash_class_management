<?php

namespace App\Console\Commands;

use App\Http\Resources\PublicLedgerEntryResource;
use App\Models\LedgerEntry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Help;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * Copies new ledger entries into a Notion database so the class keeps a copy outside the app.
 *
 * Only the public shape from PublicLedgerEntryResource is sent. The last synced seq is read back
 * from Notion on every run, so a run that stops halfway resumes on the next run.
 */
#[Signature('notion:sync {--limit=200 : Maximum number of entries to send in one run}')]
#[Description('Copy new ledger entries to the Notion ledger database')]
#[Help('The Notion database needs these properties: Nama (Title), Seq (Number), Tipe (Select), Jumlah (Number), Kategori (Text), Tanggal (Date), Hash (Text), Hash sebelumnya (Text), Dicatat oleh (Text), Payload (Text).')]
class SyncLedgerToNotion extends Command
{
    public function handle(): int
    {
        $databaseId = config('services.notion.ledger_database_id');

        if (blank(config('services.notion.token')) || blank($databaseId)) {
            $this->info('Notion sync skipped: set NOTION_TOKEN and NOTION_LEDGER_DATABASE_ID to enable it.');

            return self::SUCCESS;
        }

        $lastPage = $this->notion()->post("databases/{$databaseId}/query", [
            'sorts' => [['property' => 'Seq', 'direction' => 'descending']],
            'page_size' => 1,
        ]);

        if ($lastPage->failed()) {
            return $this->stopWithError($lastPage, 'reading the last synced seq');
        }

        $entries = LedgerEntry::query()
            ->where('seq', '>', (int) $lastPage->json('results.0.properties.Seq.number'))
            ->orderBy('seq')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($entries as $entry) {
            Sleep::for(350)->milliseconds();

            $response = $this->notion()->post('pages', [
                'parent' => ['database_id' => $databaseId],
                'properties' => $this->properties(PublicLedgerEntryResource::make($entry)->resolve()),
            ]);

            if ($response->failed()) {
                return $this->stopWithError($response, "sending entry #{$entry->seq}");
            }
        }

        $this->info("Sent {$entries->count()} ledger entries to Notion.");

        return self::SUCCESS;
    }

    /**
     * Notion API client that retries rate limits (after Retry-After) and server errors.
     */
    private function notion(): PendingRequest
    {
        return Http::baseUrl('https://api.notion.com/v1')
            ->withToken(config('services.notion.token'))
            ->withHeaders(['Notion-Version' => config('services.notion.version')])
            ->retry(
                3,
                fn (int $attempt, RequestException $exception): int => 1000 * ((int) $exception->response->header('Retry-After') ?: $attempt),
                fn (?Throwable $exception): bool => $exception instanceof RequestException
                    && ($exception->response->status() === 429 || $exception->response->serverError()),
                throw: false,
            );
    }

    /**
     * Page properties for one public ledger entry. The payload JSON keeps unicode escaped,
     * so it is plain ASCII and the cut to Notion's 2000-character text limit is exact.
     *
     * @param  array{seq: int, type: string, amount: ?int, category: ?string, payload: array<string, mixed>, recorded_by: string, created_at: string, prev_hash: ?string, hash: string}  $entry
     * @return array<string, array<string, mixed>>
     */
    private function properties(array $entry): array
    {
        $text = fn (?string $value): array => ['rich_text' => filled($value) ? [['text' => ['content' => mb_substr($value, 0, 2000)]]] : []];

        return [
            'Nama' => ['title' => [['text' => ['content' => "#{$entry['seq']} {$entry['type']}"]]]],
            'Seq' => ['number' => $entry['seq']],
            'Tipe' => ['select' => ['name' => $entry['type']]],
            'Jumlah' => ['number' => $entry['amount']],
            'Kategori' => $text($entry['category']),
            'Tanggal' => ['date' => ['start' => $entry['created_at']]],
            'Hash' => $text($entry['hash']),
            'Hash sebelumnya' => $text($entry['prev_hash']),
            'Dicatat oleh' => $text($entry['recorded_by']),
            'Payload' => $text(json_encode($entry['payload'], JSON_UNESCAPED_SLASHES)),
        ];
    }

    private function stopWithError(Response $response, string $step): int
    {
        $this->error("Notion sync stopped while {$step}: HTTP {$response->status()} ".$response->json('message', $response->reason()));

        if ($response->json('code') === 'validation_error') {
            $this->line($this->getHelp());
        }

        return self::FAILURE;
    }
}
