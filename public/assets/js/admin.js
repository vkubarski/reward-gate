(() => {
    const storageKey = 'rg-theme';
    const mediaQuery = window.matchMedia(
        '(prefers-color-scheme: dark)'
    );

    function getThemePreference() {
        const savedTheme = localStorage.getItem(storageKey);

        if (
            savedTheme === 'light'
            || savedTheme === 'dark'
            || savedTheme === 'system'
        ) {
            return savedTheme;
        }

        return 'system';
    }

    function resolveTheme(preference) {
        if (preference === 'light') {
            return 'light';
        }

        if (preference === 'dark') {
            return 'dark';
        }

        return mediaQuery.matches
            ? 'dark'
            : 'light';
    }

    function applyTheme(preference) {
        document.documentElement.setAttribute(
            'data-bs-theme',
            resolveTheme(preference)
        );

        document.querySelectorAll('[data-theme-value]').forEach(
            (button) => {
                const isActive =
                    button.dataset.themeValue === preference;

                button.classList.toggle('active', isActive);
            }
        );
    }

    function updateTheme() {
        applyTheme(getThemePreference());
    }

    document.querySelectorAll('[data-theme-value]').forEach(
        (button) => {
            button.addEventListener('click', () => {
                const preference = button.dataset.themeValue;

                localStorage.setItem(
                    storageKey,
                    preference
                );

                applyTheme(preference);
            });
        }
    );

    mediaQuery.addEventListener('change', () => {
        if (getThemePreference() === 'system') {
            updateTheme();
        }
    });

    updateTheme();

    const presentationType = document.querySelector(
        '#presentation_type'
    );

    const unlockMethod = document.querySelector(
        '#unlock_method'
    );

    if (presentationType && unlockMethod) {
        const presentationSections = document.querySelectorAll(
            '[data-presentation-settings]'
        );

        const unlockSections = document.querySelectorAll(
            '[data-unlock-settings]'
        );

        function updateSettings() {
            presentationSections.forEach((section) => {
                section.hidden =
                    section.dataset.presentationSettings
                    !== presentationType.value;
            });

            unlockSections.forEach((section) => {
                const matchesUnlockMethod =
                    section.dataset.unlockSettings
                    === unlockMethod.value;

                if (section.dataset.unlockSettings === 'click') {
                    section.hidden =
                        presentationType.value !== 'content'
                        || !matchesUnlockMethod;

                    return;
                }

                section.hidden = !matchesUnlockMethod;
            });
        }

        presentationType.addEventListener(
            'change',
            updateSettings
        );

        unlockMethod.addEventListener(
            'change',
            updateSettings
        );

        updateSettings();
    }
})();
