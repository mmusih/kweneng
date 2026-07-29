<script>
    (() => {
        if (document.documentElement.dataset.themeInitialized === 'true') {
            return;
        }

        try {
            const savedTheme = localStorage.getItem('kweneng-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const useDarkTheme = savedTheme ? savedTheme === 'dark' : prefersDark;

            document.documentElement.classList.toggle('dark', useDarkTheme);
            document.documentElement.style.colorScheme = useDarkTheme ? 'dark' : 'light';
        } catch (error) {
            // Storage may be unavailable in privacy-restricted browsers.
        }

        document.documentElement.dataset.themeInitialized = 'true';
    })();
</script>
