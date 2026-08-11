document.addEventListener('DOMContentLoaded', () => {
    const storageKey = 'taskflow-theme';
    const body = document.body;
    const themeButtons = document.querySelectorAll('[data-theme-toggle]');

    const applyTheme = (theme) => {
        const isDark = theme === 'dark';
        body.classList.toggle('dark-mode', isDark);
        themeButtons.forEach((button) => {
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
            }
            button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        });
    };

    const savedTheme = window.localStorage.getItem(storageKey);
    if (savedTheme) {
        applyTheme(savedTheme);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        applyTheme('dark');
    }

    themeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = body.classList.contains('dark-mode') ? 'light' : 'dark';
            window.localStorage.setItem(storageKey, nextTheme);
            applyTheme(nextTheme);
        });
    });
});