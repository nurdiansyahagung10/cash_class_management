{{-- Applies the saved theme before first paint so the page never flashes the wrong colors. --}}
<script nonce="{{ Vite::cspNonce() }}">
    (() => {
        let theme = null;
        try {
            theme = localStorage.getItem('theme');
        } catch {}
        document.documentElement.dataset.theme = theme === 'light' ? 'light' : 'dark';
    })();
</script>
