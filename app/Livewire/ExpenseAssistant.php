<?php

namespace App\Livewire;

use App\Exceptions\FinanceAssistantException;
use App\Services\FinanceAssistant;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Floating "asisten keuangan" helper on the expenses page: chat with suggested starter questions.
 */
class ExpenseAssistant extends Component
{
    /**
     * Questions offered when the user does not know what to ask.
     *
     * @var list<string>
     */
    public const SUGGESTIONS = [
        'Saldo kas sekarang cukup untuk beli apa saja?',
        'Aman nggak kalau keluarkan Rp150.000 untuk dekorasi kelas?',
        'Pengeluaran apa yang paling besar bulan ini?',
        'Berapa sisa saldo kalau semua iuran yang belum bayar terkumpul?',
        'Tips hemat untuk acara kelas dengan budget terbatas?',
    ];

    /**
     * Questions per user per hour, to keep AI costs bounded.
     */
    public const HOURLY_LIMIT = 20;

    /**
     * @var list<array{role: 'user'|'assistant', content: string, at: string, error?: bool}>
     */
    public array $messages = [];

    public string $question = '';

    /**
     * Livewire update requests skip route middleware: only staff/admin (who record expenses) may use the assistant.
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->canManageMembers(), 403);
    }

    public function ask(FinanceAssistant $assistant): void
    {
        $this->question = trim($this->question);

        $this->validate(
            ['question' => ['required', 'string', 'max:500']],
            ['question.required' => 'Tulis pertanyaan dulu.', 'question.max' => 'Pertanyaan maksimal 500 karakter.'],
        );

        $question = $this->question;

        $key = 'finance-assistant:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, self::HOURLY_LIMIT)) {
            $this->addError('question', 'Batas '.self::HOURLY_LIMIT.' pertanyaan per jam tercapai. Coba lagi '.ceil(RateLimiter::availableIn($key) / 60).' menit lagi.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $history = array_values(array_map(
            fn (array $message) => ['role' => $message['role'], 'content' => $message['content']],
            array_filter($this->messages, fn (array $message) => empty($message['error'])),
        ));

        $this->messages[] = ['role' => 'user', 'content' => $question, 'at' => $this->clock()];
        $this->question = '';

        try {
            $this->messages[] = ['role' => 'assistant', 'content' => $assistant->ask($question, $history), 'at' => $this->clock()];
        } catch (FinanceAssistantException $exception) {
            $this->messages[] = ['role' => 'assistant', 'content' => $exception->getMessage(), 'error' => true, 'at' => $this->clock()];
        }
    }

    /**
     * Sends a question typed in the composer. The browser shows it immediately and clears the field,
     * so the text arrives as an argument instead of through the (already emptied) `question` property.
     */
    public function send(FinanceAssistant $assistant, string $question): void
    {
        $this->question = $question;
        $this->ask($assistant);
    }

    /**
     * Sends one of the suggested starter questions.
     */
    public function askSuggestion(FinanceAssistant $assistant, int $index): void
    {
        if (! isset(self::SUGGESTIONS[$index])) {
            return;
        }

        $this->question = self::SUGGESTIONS[$index];
        $this->ask($assistant);
    }

    public function clear(): void
    {
        $this->reset('messages', 'question');
        $this->resetErrorBag();
    }

    /**
     * Wall-clock time shown next to each message (class time zone).
     */
    private function clock(): string
    {
        return now()->timezone('Asia/Jakarta')->format('H.i');
    }

    public function render(FinanceAssistant $assistant): View
    {
        return view('livewire.expense-assistant', [
            'suggestions' => self::SUGGESTIONS,
            'configured' => $assistant->isConfigured(),
            'userName' => str(auth()->user()->name)->before(' ')->toString(),
            'userInitials' => str(auth()->user()->name)->explode(' ')->take(2)->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode(''),
        ]);
    }
}
