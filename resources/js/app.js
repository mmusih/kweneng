import './bootstrap';
import Alpine from 'alpinejs';

const themeStorageKey = 'kweneng-theme';

function savedTheme() {
    try {
        return localStorage.getItem(themeStorageKey);
    } catch (error) {
        return null;
    }
}

function preferredTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyTheme(theme, persist = false) {
    const isDark = theme === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';

    if (persist) {
        try {
            localStorage.setItem(themeStorageKey, theme);
        } catch (error) {
            // The active page still uses the chosen theme when storage is blocked.
        }
    }

    window.dispatchEvent(new CustomEvent('kweneng:theme-changed', {
        detail: { theme },
    }));
}

window.KwenengTheme = {
    apply: applyTheme,
    current: () => document.documentElement.classList.contains('dark') ? 'dark' : 'light',
    saved: savedTheme,
};

applyTheme(savedTheme() ?? preferredTheme());

window.matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change', (event) => {
    if (!savedTheme()) {
        applyTheme(event.matches ? 'dark' : 'light');
    }
});

window.Alpine = Alpine;

Alpine.start();
