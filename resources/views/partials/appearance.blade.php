<script>
    (() => {
        let preference = {{ Illuminate\Support\Js::from($appearance ?? 'system') }};
        try {
            const stored = localStorage.getItem('appearance');
            if (['light', 'dark', 'system'].includes(stored)) preference = stored;
        } catch (_) {}
        const dark = preference === 'dark' || (preference === 'system' && window.matchMedia(
            '(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        document.documentElement.dataset.appearance = preference;
    })();
</script>
