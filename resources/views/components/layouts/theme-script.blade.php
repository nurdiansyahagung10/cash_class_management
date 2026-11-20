{{-- Applies the saved theme before first paint so the page never flashes the wrong colors. Light is the default. --}}
<script nonce="{{ Vite::cspNonce() }}">
    (() => {
        let theme = null;
        try {
            theme = localStorage.getItem('theme');
        } catch {}
        document.documentElement.dataset.theme = theme === 'dark' ? 'dark' : 'light';
    })();
</script>
