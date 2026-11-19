{{--
    Finance assistant, styled after Linear's agent panel: flat thread (avatar · name · time, then text),
    quiet "thinking" row, card composer with an inline round send button.
    Alpine keeps the panel state and echoes the question instantly; Livewire does the round trip.
--}}
<div x-data="{
        open: false,
        pending: '',
        submit(text) {
            const question = (text ?? $wire.question).trim();
            if (! question || this.pending) return;
            this.pending = question;
            $wire.question = '';
            $wire.send(question).finally(() => { this.pending = ''; this.$nextTick(() => this.$refs.input?.focus()); });
        },
        suggest(index, text) {
            if (this.pending) return;
            this.pending = text;
            $wire.askSuggestion(index).finally(() => this.pending = '');
        },
        grow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 160) + 'px'; },
    }"
    x-on:keydown.window.ctrl.j.prevent="open = ! open; if (open) $nextTick(() => $refs.input?.focus())"
    x-on:keydown.escape.window="open = false"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open && window.matchMedia('(max-width: 639px)').matches)"
    class="print:hidden">

    {{-- Launcher --}}
    <button type="button" x-show="! open" x-transition.opacity x-on:click="open = true; $nextTick(() => $refs.input?.focus())"
        class="fixed bottom-5 right-5 z-30 inline-flex h-10 items-center gap-2 rounded-full border border-line bg-surface-2 pl-3 pr-2 text-[13px] font-medium text-ink shadow-md transition-colors hover:border-line-strong hover:bg-surface-3">
        <span class="flex size-5 items-center justify-center rounded-full bg-brand text-white" aria-hidden="true">
            <svg class="size-3" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1.5 9.5 6.5 14.5 8 9.5 9.5 8 14.5 6.5 9.5 1.5 8 6.5 6.5Z" /></svg>
        </span>
        Tanya asisten
        <kbd class="hidden rounded border border-line bg-surface px-1.5 font-mono text-[10px] text-muted sm:inline">Ctrl J</kbd>
    </button>

    {{-- Panel --}}
    <section x-show="open" x-cloak
        x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-100 ease-in" x-transition:leave-end="translate-y-2 opacity-0"
        role="dialog" aria-labelledby="asisten-judul"
        class="fixed inset-0 z-[1100] flex h-dvh flex-col overflow-hidden bg-surface sm:inset-auto sm:bottom-5 sm:right-5 sm:z-40 sm:h-[min(620px,calc(100dvh-2.5rem))] sm:w-[420px] sm:rounded-xl sm:border sm:border-line sm:shadow-lg">

        {{-- Header --}}
        <header class="flex h-14 shrink-0 items-center gap-2 border-b border-line px-4 pt-[env(safe-area-inset-top)] sm:h-11 sm:px-3 sm:pt-0">
            <span class="flex size-5 items-center justify-center rounded-full bg-brand text-white" aria-hidden="true">
                <svg class="size-3" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1.5 9.5 6.5 14.5 8 9.5 9.5 8 14.5 6.5 9.5 1.5 8 6.5 6.5Z" /></svg>
            </span>
            <h2 id="asisten-judul" class="text-[13px] font-medium text-ink">Asisten kas</h2>
            <span class="ui-tag ui-tag-plain h-[18px] px-1.5 text-[10px] text-muted">Beta</span>
            <div class="ml-auto flex items-center gap-0.5">
                @if ($messages !== [])
                    <button type="button" wire:click="clear" title="Obrolan baru" aria-label="Obrolan baru"
                        class="inline-flex size-9 items-center justify-center rounded-md sm:size-7 text-muted transition-colors hover:bg-ink/[0.06] hover:text-ink">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                    </button>
                @endif
                <button type="button" x-on:click="open = false" title="Tutup (Esc)" aria-label="Tutup asisten"
                    class="inline-flex size-9 items-center justify-center rounded-md sm:size-7 text-muted transition-colors hover:bg-ink/[0.06] hover:text-ink">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </header>

        {{-- Thread --}}
        <div class="flex flex-1 flex-col gap-5 overflow-y-auto px-4 py-4 text-[13px] leading-relaxed"
            x-init="new MutationObserver(() => $el.scrollTop = $el.scrollHeight).observe($el, { childList: true, subtree: true, characterData: true })">

            @unless ($configured)
                <p class="flex items-start gap-2 rounded-lg border border-line bg-surface-2 px-3 py-2 text-xs text-muted">
                    <span class="mt-1 size-1.5 shrink-0 rounded-full bg-warning" aria-hidden="true"></span>
                    <span>Asisten belum aktif. Isi <code class="font-mono text-ink">FINANCE_AI_*</code> di file <code class="font-mono text-ink">.env</code>.</span>
                </p>
            @endunless

            @if ($messages === [])
                <div x-show="! pending" class="flex flex-1 flex-col justify-end gap-4">
                    <div class="flex flex-col gap-1">
                        <p class="text-[15px] font-medium text-ink">Ada rencana pengeluaran?</p>
                        <p class="text-muted">Aku cek pakai data kas terbaru: saldo, iuran yang belum masuk, dan pengeluaran sebelumnya.</p>
                    </div>
                    <div class="flex flex-col">
                        <p class="pb-1.5 text-xs text-faint">Saran pertanyaan</p>
                        @foreach ($suggestions as $index => $suggestion)
                            <button type="button" wire:key="saran-{{ $index }}" x-on:click="suggest({{ $index }}, @js($suggestion))"
                                class="group -mx-2 flex items-center gap-2.5 rounded-md px-2 py-1.5 text-left text-body transition-colors hover:bg-ink/[0.05] hover:text-ink">
                                <svg class="size-3.5 shrink-0 text-faint transition-colors group-hover:text-brand" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" /></svg>
                                <span class="min-w-0 flex-1 truncate">{{ $suggestion }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($messages as $index => $chat)
                <article wire:key="pesan-{{ $index }}" class="flex flex-col gap-1">
                    <header class="flex items-center gap-2 text-xs">
                        @if ($chat['role'] === 'user')
                            <span class="flex size-5 items-center justify-center rounded-full bg-surface-3 text-[9px] font-semibold text-body ring-1 ring-line" aria-hidden="true">{{ $userInitials }}</span>
                            <span class="font-medium text-ink">{{ $userName }}</span>
                        @else
                            <span class="flex size-5 items-center justify-center rounded-full bg-brand text-white" aria-hidden="true">
                                <svg class="size-3" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1.5 9.5 6.5 14.5 8 9.5 9.5 8 14.5 6.5 9.5 1.5 8 6.5 6.5Z" /></svg>
                            </span>
                            <span class="font-medium text-ink">Asisten</span>
                        @endif
                        <span class="text-faint">· {{ $chat['at'] ?? '' }}</span>
                    </header>
                    @if ($chat['role'] === 'user')
                        <p class="whitespace-pre-line pl-7 text-ink">{{ $chat['content'] }}</p>
                    @elseif (! empty($chat['error']))
                        <p class="flex items-start gap-2 pl-7 text-danger">{{ $chat['content'] }}</p>
                    @else
                        <div class="ai-answer pl-7 text-body">
                            {!! \Illuminate\Support\Str::markdown($chat['content'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                        </div>
                    @endif
                </article>
            @endforeach

            {{-- Optimistic echo of the question + thinking row while Livewire works --}}
            <template x-if="pending">
                <div class="flex flex-col gap-5">
                    <article class="flex flex-col gap-1">
                        <header class="flex items-center gap-2 text-xs">
                            <span class="flex size-5 items-center justify-center rounded-full bg-surface-3 text-[9px] font-semibold text-body ring-1 ring-line" aria-hidden="true">{{ $userInitials }}</span>
                            <span class="font-medium text-ink">{{ $userName }}</span>
                            <span class="text-faint">· baru saja</span>
                        </header>
                        <p class="whitespace-pre-line pl-7 text-ink" x-text="pending"></p>
                    </article>
                    <div class="flex items-center gap-2 text-xs text-muted">
                        <span class="flex size-5 items-center justify-center rounded-full bg-brand/15 text-brand" aria-hidden="true">
                            <span class="ui-spinner size-3"></span>
                        </span>
                        <span class="ai-thinking">Sedang berpikir…</span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Composer --}}
        <div class="shrink-0 p-3 pt-0 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:pb-3">
            <form x-on:submit.prevent="submit()"
                class="flex flex-col gap-1 rounded-xl border border-line bg-surface-2 px-3 pb-2 pt-2.5 transition-colors focus-within:border-line-strong">
                <label for="asisten-pertanyaan" class="sr-only">Pertanyaan untuk asisten</label>
                <textarea id="asisten-pertanyaan" x-ref="input" wire:model="question" rows="1" maxlength="500"
                    x-on:input="grow($el)" x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); submit(); }"
                    x-bind:disabled="!! pending"
                    placeholder="Tanya asisten kas…"
                    class="max-h-40 min-h-6 w-full resize-none border-0 bg-transparent p-0 text-base leading-6 sm:text-[13px] text-ink placeholder:text-faint focus:outline-none focus:ring-0 disabled:opacity-60"></textarea>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-faint"><kbd class="font-sans">Enter</kbd> kirim · <kbd class="font-sans">Shift+Enter</kbd> baris baru</span>
                    <button type="submit" aria-label="Kirim pertanyaan" x-bind:disabled="!! pending || ! $wire.question.trim()"
                        class="ml-auto inline-flex size-7 items-center justify-center rounded-full bg-brand text-white transition-opacity disabled:bg-ink/10 disabled:text-faint">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0-6.75 6.75M12 4.5l6.75 6.75" /></svg>
                    </button>
                </div>
            </form>
            @error('question')
                <p class="px-1 pt-1.5 text-xs text-danger">{{ $message }}</p>
            @enderror
            <p class="px-1 pt-1.5 text-[11px] text-faint">Saran AI dari data kas kelas; cek ulang sebelum mencatat.</p>
        </div>
    </section>
</div>
