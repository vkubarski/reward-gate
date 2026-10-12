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

    function formatCampaignCreatedAt() {
        document.querySelectorAll('[data-created-at]').forEach(
            (element) => {
                const date = new Date(
                    element.dataset.createdAt
                );

                if (Number.isNaN(date.getTime())) {
                    return;
                }

                const parts = new Intl.DateTimeFormat(
                    'en-US',
                    {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hourCycle: 'h23',
                    }
                ).formatToParts(date);

                const values = Object.fromEntries(
                    parts.map((part) => [
                        part.type,
                        part.value,
                    ])
                );

                element.textContent =
                    `${values.month} ${values.day}, `
                    + `${values.year} `
                    + `${values.hour}:${values.minute}`;
            }
        );
    }

    function restoreListSort({
        path,
        storageKey,
        sortableColumns,
    }) {
        if (window.location.pathname !== path) {
            return;
        }

        const url = new URL(window.location.href);
        const sort = url.searchParams.get('sort');
        const direction = url.searchParams.get('direction');

        if (sort !== null || direction !== null) {
            if (
                sortableColumns.includes(sort)
                && (
                    direction === 'asc'
                    || direction === 'desc'
                )
            ) {
                localStorage.setItem(
                    storageKey,
                    JSON.stringify({
                        column: sort,
                        direction: direction,
                    })
                );
            }

            return;
        }

        const savedSort = localStorage.getItem(
            storageKey
        );

        if (savedSort === null) {
            return;
        }

        let preference;

        try {
            preference = JSON.parse(savedSort);
        } catch {
            localStorage.removeItem(storageKey);

            return;
        }

        if (
            !preference
            || !sortableColumns.includes(
                preference.column
            )
            || (
                preference.direction !== 'asc'
                && preference.direction !== 'desc'
            )
        ) {
            localStorage.removeItem(storageKey);

            return;
        }

        url.searchParams.set(
            'sort',
            preference.column
        );

        url.searchParams.set(
            'direction',
            preference.direction
        );

        window.location.replace(
            url.toString()
        );
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
    formatCampaignCreatedAt();

    restoreListSort({
        path: window.rewardGateUrl('/admin/campaigns'),
        storageKey: 'rg-admin-campaign-list-sort',
        sortableColumns: [
            'name',
            'presentation_type',
            'status',
            'created_at',
        ],
    });

    const presentationType = document.querySelector(
        '#presentation_type'
    );

    const unlockMethod = document.querySelector(
        '#unlock_method'
    );

    const unlockCombinationWarning = document.querySelector(
        '#unlock-combination-warning'
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

            if (unlockCombinationWarning) {
                if (
                    presentationType.value === 'content'
                    && unlockMethod.value === 'timer'
                ) {
                    unlockCombinationWarning.textContent =
                        'Content Gate currently requires the Click unlock method. Please select Click to configure the Content Gate settings.';

                    unlockCombinationWarning.classList.remove('d-none');
                } else if (
                    presentationType.value === 'popup'
                    && unlockMethod.value === 'click'
                ) {
                    unlockCombinationWarning.textContent =
                        'Popup Gate currently requires the Timer unlock method. Please select Timer to configure the Popup Gate settings.';

                    unlockCombinationWarning.classList.remove('d-none');
                } else {
                    unlockCombinationWarning.classList.add('d-none');
                    unlockCombinationWarning.textContent = '';
                }
            }
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
