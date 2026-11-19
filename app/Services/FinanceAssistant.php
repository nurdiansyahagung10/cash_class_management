<?php

namespace App\Services;

use App\Exceptions\FinanceAssistantException;
use App\Models\Expense;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class finance assistant: answers questions about planned expenses using the class's own cash data,
 * through an OpenAI-compatible chat endpoint (e.g. 9router) configured in config/services.php.
 *
 * Only class-level aggregates and expense titles are sent; member names and expense notes never leave the app.
 */
class FinanceAssistant
{
    /**
     * Messages of earlier turns kept for follow-up questions.
     */
    public const HISTORY_LIMIT = 8;

    public function __construct(
        private PublicCashSummary $summary,
        private Ledger $ledger,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('services.finance_assistant.base_url'))
            && filled(config('services.finance_assistant.api_key'))
            && filled(config('services.finance_assistant.model'));
    }

    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $history
     *
     * @throws FinanceAssistantException
     */
    public function ask(string $question, array $history = []): string
    {
        if (! $this->isConfigured()) {
            throw new FinanceAssistantException('Asisten AI belum dikonfigurasi. Isi FINANCE_AI_BASE_URL, FINANCE_AI_API_KEY, dan FINANCE_AI_MODEL di file .env.');
        }

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ...array_slice($history, -self::HISTORY_LIMIT),
            ['role' => 'user', 'content' => $question],
        ];

        try {
            $response = Http::withToken((string) config('services.finance_assistant.api_key'))
                ->acceptJson()
                ->timeout((int) config('services.finance_assistant.timeout', 60))
                ->post(rtrim((string) config('services.finance_assistant.base_url'), '/').'/chat/completions', [
                    'model' => config('services.finance_assistant.model'),
                    'messages' => $messages,
                    'temperature' => 0.3,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Finance assistant unreachable', ['error' => $exception->getMessage()]);

            throw new FinanceAssistantException('Server AI tidak bisa dihubungi. Pastikan layanan AI (9router) sedang berjalan.');
        }

        if ($response->failed()) {
            Log::warning('Finance assistant request failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);

            throw new FinanceAssistantException('Asisten AI sedang tidak bisa menjawab (kode '.$response->status().'). Coba lagi sebentar lagi.');
        }

        $answer = trim((string) $response->json('choices.0.message.content'));

        if ($answer === '') {
            throw new FinanceAssistantException('Asisten AI tidak memberi jawaban. Coba ulangi pertanyaannya.');
        }

        return $answer;
    }

    /**
     * Role, rules and a fresh snapshot of the class finances.
     */
    public function systemPrompt(): string
    {
        return <<<PROMPT
        Kamu adalah asisten keuangan untuk kas kelas 1A (D3 Bahasa Inggris). Pengguna adalah bendahara atau pengurus kelas
        yang sedang merencanakan pengeluaran kas.

        Tugasmu:
        - Membantu menilai apakah sebuah rencana pengeluaran masuk akal dan terjangkau dengan saldo saat ini.
        - Memberi perkiraan sisa saldo, prioritas, alternatif yang lebih hemat, dan langkah pencatatan yang rapi (simpan nota).
        - Mengingatkan bila saldo menipis atau masih banyak iuran yang belum tertagih.

        Aturan:
        - Jawab dalam Bahasa Indonesia yang santai tapi jelas, singkat (maksimal sekitar 150 kata kecuali diminta rinci).
        - Gunakan angka dari DATA KAS di bawah; tulis nominal dalam format Rupiah (contoh Rp150.000).
        - Jangan mengarang data yang tidak ada. Kalau informasinya kurang, katakan dan tanyakan balik.
        - Kamu hanya memberi saran; keputusan dan pencatatan tetap dilakukan pengurus di aplikasi.
        - Tolak dengan sopan pertanyaan di luar keuangan kelas.

        DATA KAS (per {$this->today()}):
        {$this->context()}
        PROMPT;
    }

    /**
     * Plain-text snapshot of class finances for the model.
     */
    public function context(): string
    {
        $summary = $this->summary->toArray();
        $overall = $summary['overall'];
        $rupiah = fn (int $value): string => 'Rp'.number_format($value, 0, ',', '.');

        $lines = [
            '- Saldo kas saat ini: '.$rupiah($summary['balance']),
            '- Total pengeluaran tercatat: '.$rupiah((int) Expense::sum('amount')).' dari '.Expense::count().' transaksi',
            '- Iuran: target '.$rupiah($overall['target']).', terkumpul '.$rupiah($overall['collected']).' ('.$overall['rate'].'%), belum tertagih '.$rupiah($overall['remaining']),
            '- Anggota aktif: '.$summary['activeMembers'].'; tagihan lunas '.$overall['paid_count'].', menyicil '.$overall['partial_count'].', belum bayar '.$overall['unpaid_count'],
        ];

        $lines[] = 'Arus kas 6 bulan terakhir:';
        foreach ($this->ledger->monthlyFlow(now()->startOfMonth()) as $month) {
            $lines[] = '  - '.$month['month']->format('Y-m').': masuk '.$rupiah($month['in']).', keluar '.$rupiah($month['out']);
        }

        $lines[] = 'Periode tagihan terbaru:';
        foreach ($summary['periodSummaries'] as $period) {
            $lines[] = '  - '.$period['title'].' ('.($period['category'] ?? 'Uang Kas').'), tenggat '.$period['due_date']->format('Y-m-d')
                .': terkumpul '.$rupiah($period['collected']).' dari '.$rupiah($period['target']);
        }

        $recent = Expense::query()->latest('spent_at')->latest('id')->limit(15)->get(['title', 'amount', 'spent_at']);
        $lines[] = 'Pengeluaran terakhir:';
        foreach ($recent as $expense) {
            $lines[] = '  - '.$expense->spent_at->format('Y-m-d').': '.$expense->title.' '.$rupiah($expense->amount);
        }
        if ($recent->isEmpty()) {
            $lines[] = '  - (belum ada pengeluaran)';
        }

        return implode("\n", $lines);
    }

    private function today(): string
    {
        return now()->timezone('Asia/Jakarta')->format('Y-m-d');
    }
}
