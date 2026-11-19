//

/**
 * Copies the text of any [data-copy-text] button, falling back to a readonly textarea when the Clipboard API is unavailable.
 */
async function copyText(text) {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        const field = document.createElement('textarea');
        field.value = text;
        field.readOnly = true;
        field.style.position = 'fixed';
        field.style.opacity = '0';
        document.body.appendChild(field);
        field.select();
        const copied = document.execCommand('copy');
        field.remove();
        return copied;
    }
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-text]');
    if (!button) {
        return;
    }

    const copied = await copyText(button.dataset.copyText);
    const label = button.querySelector('[data-copy-label]') ?? button;
    const original = label.dataset.original ?? label.textContent;
    label.dataset.original = original;
    label.textContent = copied ? 'Tersalin' : 'Gagal menyalin';
    document.querySelector('[data-copy-status]')?.replaceChildren(copied ? 'Pesan tersalin ke papan klip.' : 'Pesan gagal disalin.');

    clearTimeout(button.copyTimer);
    button.copyTimer = setTimeout(() => {
        label.textContent = original;
    }, 2000);
});

/**
 * Upgrades every <select data-tom-select> under root to a searchable Tom Select (loaded from the CDN in the layout).
 * Safe to call repeatedly: already-enhanced selects are skipped. Inside Livewire, hosts sit in wire:ignore,
 * and a changed wire:key swaps in a fresh <select> that gets enhanced on the next morph.
 */
function enhanceSelects(root = document) {
    if (typeof window.TomSelect === 'undefined') {
        return;
    }

    root.querySelectorAll('select[data-tom-select]').forEach((select) => {
        if (select.tomselect) {
            return;
        }

        new window.TomSelect(select, {
            create: false,
            allowEmptyOption: true,
            maxOptions: 200,
            dropdownParent: 'body',
            controlInput: select.options.length > 8 ? undefined : null,
            render: {
                no_results: () => '<div class="no-results">Tidak ditemukan</div>',
            },
        });
    });
}

/**
 * Submits the owning form as soon as a [data-autosubmit] field changes (filters without a "Terapkan" click).
 * Fields inside a Livewire component are skipped: wire:model.live already updates them without a reload.
 */
document.addEventListener('change', (event) => {
    const field = event.target.closest?.('[data-autosubmit]');
    if (field?.form && !field.closest('[wire\\:id]')) {
        field.form.requestSubmit();
    }
});

/**
 * Flips between the dark and light theme on any [data-theme-toggle] click and remembers the choice per browser.
 */
document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-theme-toggle]')) {
        return;
    }

    const root = document.documentElement;
    const next = root.dataset.theme === 'light' ? 'dark' : 'light';
    root.classList.add('theme-ready');
    root.dataset.theme = next;

    try {
        localStorage.setItem('theme', next);
    } catch {
        // Private mode / blocked storage: the theme still applies for this page view.
    }
});

/**
 * Pagination dots for swipeable summary rails ([data-stat-rail]); hidden from md up where the rail is a grid.
 * State lives in JS properties (not data-* attributes) so a Livewire morph does not reset it.
 */
function enhanceStatRails(root = document) {
    root.querySelectorAll('[data-stat-rail]').forEach((rail) => {
        const track = rail.querySelector('[data-stat-track]');
        const dotsHost = rail.querySelector('[data-stat-dots]');
        const cards = [...(track?.children ?? [])];

        if (!track || !dotsHost || cards.length < 2 || track.railUpdate) {
            track?.railUpdate?.();
            return;
        }

        dotsHost.replaceChildren();
        const dots = cards.map(() => {
            const dot = document.createElement('span');
            dot.className = 'h-1.5 w-1.5 rounded-full bg-ink/20 transition-all duration-200';
            dotsHost.appendChild(dot);
            return dot;
        });

        track.railUpdate = () => {
            const step = cards[1].offsetLeft - cards[0].offsetLeft || 1;
            const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
            const active = atEnd ? cards.length - 1 : Math.round(track.scrollLeft / step);
            dots.forEach((dot, index) => {
                dot.classList.toggle('w-4', index === active);
                dot.classList.toggle('bg-brand', index === active);
                dot.classList.toggle('bg-ink/20', index !== active);
            });
        };

        track.addEventListener('scroll', track.railUpdate, { passive: true });
        track.railUpdate();
    });
}

/**
 * Upgrades every <input type="file" data-filepond> to a FilePond drop zone with image preview (CDN, see the layout).
 * storeAsFile keeps the file inside the form, so the existing multipart POST + server validation stay unchanged.
 * Accepted types come from the input's `accept`, the size cap from `data-max-size` (e.g. "5MB").
 * Each added file is announced as a bubbling `filepond:addfile` event ({ name, file }) for page scripts.
 */
function enhanceUploads(root = document) {
    const FilePond = window.FilePond;
    if (!FilePond) {
        return;
    }

    if (!enhanceUploads.registered) {
        FilePond.registerPlugin(
            ...[
                window.FilePondPluginFileValidateType,
                window.FilePondPluginFileValidateSize,
                window.FilePondPluginImageExifOrientation,
                window.FilePondPluginImagePreview,
            ].filter(Boolean),
        );
        enhanceUploads.registered = true;
    }

    root.querySelectorAll('input[type="file"][data-filepond]').forEach((input) => {
        if (input.filepondReady || input.closest('.filepond--root')) {
            return;
        }
        input.filepondReady = true;
        const name = input.name;
        const pond = FilePond.create(input, {
            storeAsFile: true,
            credits: false,
            maxFileSize: input.dataset.maxSize || null,
            imagePreviewMaxHeight: 220,
            labelIdle: 'Seret gambar ke sini atau <span class="filepond--label-action">pilih file</span>'
                + `<br><span class="filepond--hint">PNG atau JPG${input.dataset.maxSize ? `, maks ${input.dataset.maxSize}` : ''}</span>`,
            labelFileTypeNotAllowed: 'Hanya PNG atau JPG',
            fileValidateTypeLabelExpectedTypes: 'Gunakan {allButLastType} atau {lastType}',
            fileValidateTypeLabelExpectedTypesMap: { 'image/png': '.png', 'image/jpeg': '.jpg' },
            labelMaxFileSizeExceeded: 'File terlalu besar',
            labelMaxFileSize: 'Maksimal {filesize}',
            labelFileProcessingError: 'Gagal memproses file',
            labelTapToCancel: 'ketuk untuk batal',
            labelTapToRetry: 'ketuk untuk ulang',
            labelTapToUndo: 'ketuk untuk batal',
            labelButtonRemoveItem: 'Hapus',
            labelFileLoading: 'Memuat',
        });

        pond.on('addfile', (error, item) => {
            if (!error) {
                pond.element.dispatchEvent(new CustomEvent('filepond:addfile', { bubbles: true, detail: { name, file: item.file } }));
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    enhanceSelects();
    enhanceStatRails();
    enhanceUploads();
});

/**
 * Re-run the enhancers after every Livewire morph. Livewire's script (end of <body>) usually loads before this
 * deferred module, so hook in directly when it is already there and fall back to livewire:init otherwise.
 */
const registerLivewireHooks = () => {
    window.Livewire.hook('morphed', ({ el }) => {
        enhanceSelects(el);
        enhanceStatRails(el);
        enhanceUploads(el);
    });
};

if (window.Livewire) {
    registerLivewireHooks();
} else {
    document.addEventListener('livewire:init', registerLivewireHooks);
}

/**
 * Top progress bar: starts on internal navigation, regular form posts and Livewire requests, so nothing feels frozen.
 */
const pageProgress = (() => {
    let bar = null;
    let timer = null;
    let pending = 0;

    const element = () => {
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'page-progress';
            bar.setAttribute('aria-hidden', 'true');
            document.body.appendChild(bar);
        }
        return bar;
    };

    const start = () => {
        pending += 1;
        const el = element();
        clearInterval(timer);
        el.dataset.active = '';
        el.style.width = '12%';
        let width = 12;
        timer = setInterval(() => {
            width += (90 - width) * 0.08;
            el.style.width = `${width}%`;
        }, 200);
    };

    const done = () => {
        pending = Math.max(0, pending - 1);
        if (pending > 0 || !bar) {
            return;
        }
        clearInterval(timer);
        bar.style.width = '100%';
        setTimeout(() => {
            delete bar.dataset.active;
            bar.style.width = '0';
        }, 250);
    };

    const reset = () => {
        pending = 0;
        clearInterval(timer);
        if (bar) {
            delete bar.dataset.active;
            bar.style.width = '0';
        }
    };

    return { start, done, reset };
})();

/**
 * Regular (non-Livewire) form posts: busy-state the submit button and block double submits.
 */
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || form.closest('[wire\:id]') || form.target === '_blank' || form.hasAttribute('data-no-loading')) {
        return;
    }

    const button = event.submitter ?? form.querySelector('[type="submit"]');
    if (button) {
        button.setAttribute('aria-busy', 'true');
        // Disable after the browser has read the submitter's name/value for this submission.
        setTimeout(() => {
            button.disabled = true;
        });
    }
    pageProgress.start();
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (
        !link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
        || link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('wire:click')
        || link.getAttribute('href').startsWith('#') || link.getAttribute('href').startsWith('data:')
    ) {
        return;
    }

    const url = new URL(link.href, location.href);
    if (url.origin === location.origin && url.href !== location.href) {
        pageProgress.start();
    }
});

// Coming back through the bfcache: clear busy buttons and the bar.
window.addEventListener('pageshow', () => {
    pageProgress.reset();
    document.querySelectorAll('[aria-busy="true"]').forEach((button) => {
        button.removeAttribute('aria-busy');
        button.disabled = false;
    });
});

const registerLivewireProgress = () => {
    window.Livewire.interceptRequest?.(({ onSuccess, onError, onFailure }) => {
        pageProgress.start();
        onSuccess?.(() => pageProgress.done());
        onError?.(() => pageProgress.done());
        onFailure?.(() => pageProgress.done());
    });
};

if (window.Livewire) {
    registerLivewireProgress();
} else {
    document.addEventListener('livewire:init', registerLivewireProgress);
}
