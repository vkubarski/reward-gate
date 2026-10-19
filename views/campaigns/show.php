<div class="admin-page-header d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
    <div>
        <h1 class="h2">
            <?= htmlspecialchars(
                $campaign['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h1>

        <p class="text-body-secondary mb-0">
            Campaign #<?= (int)$campaign['id'] ?>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a
            href="<?= \RewardGate\Http\Url::to('/admin/campaigns/' . (int)$campaign['id'] . '/edit') ?>"
            class="admin-action-button btn btn-primary"
        >
            Edit Campaign
        </a>

        <?php if ($campaign['status'] === 'active'): ?>
            <form
                method="post"
                action="<?= \RewardGate\Http\Url::to('/admin/campaigns/' . (int)$campaign['id'] . '/pause') ?>"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrf_token,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <button
                    type="submit"
                    class="admin-action-button admin-action-button--pause btn"
                >
                    Pause Campaign
                </button>
            </form>
        <?php elseif ($campaign['status'] === 'draft'): ?>
            <form
                method="post"
                action="<?= \RewardGate\Http\Url::to('/admin/campaigns/' . (int)$campaign['id'] . '/activate') ?>"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrf_token,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <button
                    type="submit"
                    class="admin-action-button admin-action-button--activate btn btn-success"
                >
                    Activate Campaign
                </button>
            </form>
        <?php elseif ($campaign['status'] === 'paused'): ?>
            <form
                method="post"
                action="<?= \RewardGate\Http\Url::to('/admin/campaigns/' . (int)$campaign['id'] . '/activate') ?>"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrf_token,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <button
                    type="submit"
                    class="admin-action-button admin-action-button--activate btn btn-success"
                >
                    Activate Campaign
                </button>
            </form>
        <?php endif; ?>

        <?php if ($campaign['status'] !== 'archived'): ?>
            <form
                method="post"
                action="<?= \RewardGate\Http\Url::to('/admin/campaigns/' . (int)$campaign['id'] . '/archive') ?>"
                onsubmit="return confirm('Archive this campaign? You can no longer activate it after archiving.');"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrf_token,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <button
                    type="submit"
                    class="admin-action-button admin-action-button--archive btn"
                >
                    Archive Campaign
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <dl class="row mb-0 admin-details-list">
            <dt class="col-sm-4">
                Status
            </dt>

            <dd class="col-sm-8">
                <?php
                $statusClass = match ($campaign['status']) {
                    'active' => 'admin-status-badge--active',
                    'paused' => 'admin-status-badge--paused',
                    'draft' => 'admin-status-badge--draft',
                    'archived' => 'admin-status-badge--archived',
                    default => 'admin-status-badge--draft',
                };
                ?>

                <span class="badge admin-status-badge <?= $statusClass ?>">
                    <?= htmlspecialchars(
                        ucfirst($campaign['status']),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </dd>

            <dt class="col-sm-4">
                Presentation type
            </dt>

            <dd class="col-sm-8">
                <?= htmlspecialchars(
                    $campaign['presentation_type'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </dd>

            <?php if ($campaign['unlock_method'] === 'timer'): ?>
                <dt class="col-sm-4">
                    Timer duration
                </dt>

                <dd class="col-sm-8">
                    <?= (int)$campaign['timer_duration_seconds'] ?>
                    seconds
                </dd>
            <?php endif; ?>
        </dl>
    </div>
</div>

<?php
$formatPeriod = static function (array $period): string {
    $start = new DateTimeImmutable(
        $period['start'],
        new DateTimeZone('UTC')
    );

    $end = (new DateTimeImmutable(
        $period['end'],
        new DateTimeZone('UTC')
    ))->modify('-1 day');

    return $start->format('M j')
        . ' – '
        . $end->format('M j, Y')
        . ' UTC';
};

$formatRate = static function (?float $rate): string {
    return $rate === null
        ? '—'
        : number_format($rate, 1) . '%';
};

$analyticsMetrics = [
    'shown' => [
        'label' => 'Shown',
        'description' => 'Number of recorded gate impressions, not unique people. An impression is recorded when the gate is displayed successfully. Already-unlocked content does not generate a new impression.',
    ],
    'sessions' => [
        'label' => 'Sessions',
        'description' => 'Number of unlock sessions started during this period. Multiple sessions may be associated with a single impression.',
    ],
    'completed' => [
        'label' => 'Completed',
        'description' => 'Number of accepted unlock sessions started during this period, including active, expired, and completed sessions. Multiple sessions may be associated with a single impression.',
    ],
    'unlock_rate' => [
        'label' => 'Unlock rate',
        'description' => 'Completed sessions divided by recorded impressions. Displayed only when there are at least 20 impressions and completed sessions do not exceed impressions. A dash means the sample is too small or the rate is not meaningful under these rules.',
    ],
    'timer_sessions' => [
        'label' => 'Timer sessions',
        'description' => 'Number of timer-based unlock sessions started during this period.',
    ],
    'timer_completed' => [
        'label' => 'Timer completed',
        'description' => 'Number of timer-based unlock sessions started during this period that were successfully completed.',
    ],
    'timer_finished_wait_rate' => [
        'label' => 'Timer Finished wait rate',
        'description' => 'Percentage of eligible timer sessions that were successfully completed. Displayed only when there are at least 20 eligible timer sessions and completed sessions do not exceed the eligible total.',
    ],
];

$selectedPeriod = '7_days';
$selectedAnalytics = $analytics[$selectedPeriod];
?>

<div
    class="card shadow-sm mt-4"
    id="campaign-analytics"
>
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h2 class="h5 mb-1">Analytics</h2>
            <p class="text-body-secondary small mb-0">
                All dates use UTC. Rates are hidden when the sample size is too small.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <label for="analytics-period" class="form-label mb-0">
                Period
            </label>

            <select
                id="analytics-period"
                class="form-select form-select-sm"
                aria-label="Analytics reporting period"
            >
                <option value="7_days">Last 7 Days</option>
                <option value="30_days">Last 30 Days</option>
                <option value="90_days">Last 90 Days</option>
                <option value="this_month">This Month</option>
            </select>
        </div>
    </div>

    <div class="card-body">
        <p
            id="analytics-period-dates"
            class="small text-body-secondary mb-4"
        >
            <?= htmlspecialchars(
                $formatPeriod($selectedAnalytics),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <section aria-labelledby="analytics-summary-heading" class="mb-4">
            <h3 id="analytics-summary-heading" class="h6 mb-3">
                Summary
            </h3>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Metric</th>
                            <th scope="col" class="text-end">Value</th>
                        </tr>
                    </thead>

                    <tbody id="analytics-summary-body">
                        <?php foreach ($analyticsMetrics as $key => $metric): ?>
                            <tr>
                                <th scope="row">
                                    <?= htmlspecialchars(
                                        $metric['label'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    <button
                                        type="button"
                                        class="btn btn-link btn-sm p-0 ms-1 text-body-secondary align-baseline"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="<?= htmlspecialchars(
                                            $metric['description'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        aria-label="About <?= htmlspecialchars(
                                            $metric['label'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                        <span aria-hidden="true">ⓘ</span>
                                    </button>
                                </th>

                                <td
                                    class="text-end"
                                    data-analytics-metric="<?= htmlspecialchars(
                                        $key,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >
                                    <?php if (str_ends_with($key, '_rate')): ?>
                                        <?= htmlspecialchars(
                                            $formatRate($selectedAnalytics[$key]),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    <?php else: ?>
                                        <?= (int)$selectedAnalytics[$key] ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section aria-labelledby="analytics-timeline-heading" class="mb-4">
            <h3 id="analytics-timeline-heading" class="h6 mb-3">
                Activity over time
            </h3>

            <div style="position: relative; min-height: 280px;">
                <canvas
                    id="analytics-timeline-chart"
                    role="img"
                    aria-label="Daily gate impressions, unlock sessions, and completed unlocks"
                >
                    Daily gate activity chart.
                </canvas>
            </div>
        </section>

        <section aria-labelledby="analytics-daily-heading">
            <h3 id="analytics-daily-heading" class="h6 mb-3">
                Daily breakdown
            </h3>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">UTC date</th>
                            <th scope="col" class="text-end">Shown</th>
                            <th scope="col" class="text-end">Sessions</th>
                            <th scope="col" class="text-end">Completed</th>
                        </tr>
                    </thead>

                    <tbody id="analytics-daily-body">
                        <?php foreach ($selectedAnalytics['daily'] as $date => $counts): ?>
                            <tr>
                                <th scope="row">
                                    <?= htmlspecialchars(
                                        $date,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </th>
                                <td class="text-end"><?= (int)$counts['shown'] ?></td>
                                <td class="text-end"><?= (int)$counts['sessions'] ?></td>
                                <td class="text-end"><?= (int)$counts['completed'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<script type="application/json" id="campaign-analytics-data"><?= json_encode(
    $analytics,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
) ?></script>

<script>
    document
        .querySelectorAll('#campaign-analytics [data-bs-toggle="tooltip"]')
        .forEach((element) => {
            bootstrap.Tooltip.getOrCreateInstance(element);
        });
</script>
