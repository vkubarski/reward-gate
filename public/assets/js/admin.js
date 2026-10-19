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

                section.hidden = !matchesUnlockMethod;
            });

            const timerDuration = document.querySelector(
                '#timer_duration_seconds'
            );

            if (timerDuration) {
                const timerUnlock = unlockMethod.value === 'timer';

                timerDuration.required = timerUnlock;
                timerDuration.disabled = !timerUnlock;
            }

            document.querySelectorAll('[data-click-setting]').forEach(
                (setting) => {
                    setting.hidden =
                        setting.dataset.clickSetting
                        !== presentationType.value;
                }
            );

            if (unlockCombinationWarning) {
                if (
                    presentationType.value === 'content'
                    && unlockMethod.value === 'timer'
                ) {
                    unlockCombinationWarning.textContent =
                        'Content Gate currently requires the Click unlock method. Please select Click to configure the Content Gate settings.';

                    unlockCombinationWarning.classList.remove('d-none');
                } else {
                    unlockCombinationWarning.classList.add('d-none');
                    unlockCombinationWarning.textContent = '';
                }
            }

            const destinationUrl = document.querySelector(
                '#content_destination_url'
            );

            if (destinationUrl) {
                const clickUnlock = unlockMethod.value === 'click';

                destinationUrl.disabled = !clickUnlock;
                destinationUrl.required = clickUnlock;
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

    function initializeCampaignAnalytics() {
        const dataElement = document.querySelector(
            '#campaign-analytics-data'
        );
        const periodSelect = document.querySelector(
            '#analytics-period'
        );
        const datesElement = document.querySelector(
            '#analytics-period-dates'
        );
        const summaryBody = document.querySelector(
            '#analytics-summary-body'
        );
        const dailyBody = document.querySelector(
            '#analytics-daily-body'
        );
        const chartCanvas = document.querySelector(
            '#analytics-timeline-chart'
        );

        if (
            !dataElement
            || !periodSelect
            || !datesElement
            || !summaryBody
            || !dailyBody
            || !chartCanvas
            || typeof Chart === 'undefined'
        ) {
            return;
        }

        const analytics = JSON.parse(dataElement.textContent);

        const metricLabels = {
            shown: 'Shown',
            sessions: 'Sessions',
            completed: 'Completed',
            unlock_rate: 'Unlock rate',
            timer_sessions: 'Timer sessions',
            timer_completed: 'Timer completed',
            timer_finished_wait_rate: 'Timer Finished wait rate',
        };

        const monthNames = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
        ];

        function formatDate(dateString) {
            const [year, month, day] = dateString
                .split('-')
                .map(Number);

            return `${monthNames[month - 1]} ${day}, ${year}`;
        }

        function formatPeriod(period) {
            const startDate = period.start.slice(0, 10);

            const endDate = new Date(
                `${period.end.slice(0, 10)}T00:00:00Z`
            );

            endDate.setUTCDate(endDate.getUTCDate() - 1);

            const endString = [
                endDate.getUTCFullYear(),
                String(endDate.getUTCMonth() + 1).padStart(2, '0'),
                String(endDate.getUTCDate()).padStart(2, '0'),
            ].join('-');

            const [startYear] = startDate.split('-');
            const [endYear] = endString.split('-');

            const startLabel = formatDate(startDate);
            const endLabel = formatDate(endString);

            if (startYear === endYear) {
                return `${startLabel.slice(0, -6)} – ${endLabel} UTC`;
            }

            return `${startLabel} – ${endLabel} UTC`;
        }

        function formatRate(rate) {
            return rate === null
                ? '—'
                : `${Number(rate).toFixed(1)}%`;
        }

        function formatMetric(key, value) {
            return key.endsWith('_rate')
                ? formatRate(value)
                : String(Number(value));
        }

        function renderSummary(period) {
            Object.keys(metricLabels).forEach((key) => {
                const cell = summaryBody.querySelector(
                    `[data-analytics-metric="${key}"]`
                );

                if (cell) {
                    cell.textContent = formatMetric(
                        key,
                        period[key]
                    );
                }
            });
        }

        function renderDaily(period) {
            const fragment = document.createDocumentFragment();

            Object.entries(period.daily).forEach(([date, counts]) => {
                const row = document.createElement('tr');
                const dateCell = document.createElement('th');

                dateCell.scope = 'row';
                dateCell.textContent = formatDate(date);
                row.appendChild(dateCell);

                ['shown', 'sessions', 'completed'].forEach((key) => {
                    const cell = document.createElement('td');

                    cell.className = 'text-end';
                    cell.textContent = String(Number(counts[key]));
                    row.appendChild(cell);
                });

                fragment.appendChild(row);
            });

            dailyBody.replaceChildren(fragment);
        }

        const rootStyles = getComputedStyle(
            document.documentElement
        );

        const chart = new Chart(chartCanvas, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Shown',
                        data: [],
                        borderColor: rootStyles.getPropertyValue(
                            '--bs-primary'
                        ).trim(),
                        backgroundColor: rootStyles.getPropertyValue(
                            '--bs-primary'
                        ).trim(),
                        tension: 0.25,
                    },
                    {
                        label: 'Sessions',
                        data: [],
                        borderColor: rootStyles.getPropertyValue(
                            '--bs-success'
                        ).trim(),
                        backgroundColor: rootStyles.getPropertyValue(
                            '--bs-success'
                        ).trim(),
                        tension: 0.25,
                    },
                    {
                        label: 'Completed',
                        data: [],
                        borderColor: rootStyles.getPropertyValue(
                            '--bs-warning'
                        ).trim(),
                        backgroundColor: rootStyles.getPropertyValue(
                            '--bs-warning'
                        ).trim(),
                        tension: 0.25,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                    },
                },
            },
        });

        function renderChart(period) {
            const entries = Object.entries(period.daily);

            chart.data.labels = entries.map(([date]) => {
                return formatDate(date).replace(/, \d{4}$/, '');
            });

            chart.data.datasets[0].data = entries.map(
                ([, counts]) => Number(counts.shown)
            );

            chart.data.datasets[1].data = entries.map(
                ([, counts]) => Number(counts.sessions)
            );

            chart.data.datasets[2].data = entries.map(
                ([, counts]) => Number(counts.completed)
            );

            chart.update();
        }

        function updateDashboard() {
            const period = analytics[periodSelect.value];

            if (!period) {
                return;
            }

            datesElement.textContent = formatPeriod(period);
            renderSummary(period);
            renderDaily(period);
            renderChart(period);
        }

        periodSelect.addEventListener(
            'change',
            updateDashboard
        );

        updateDashboard();
    }

    initializeCampaignAnalytics();

})();
