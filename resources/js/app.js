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
