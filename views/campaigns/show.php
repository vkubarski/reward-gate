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
?>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <h2 class="h5 mb-1">Analytics</h2>

        <p class="text-body-secondary small mb-0">
            UTC calendar days. Rates are hidden when the sample size is too small.
        </p>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Metric</th>
                        <th scope="col" class="text-end">
                            Last 7 days
                            <div class="small fw-normal text-body-secondary">
                                <?= htmlspecialchars(
                                    $formatPeriod($analytics['7_days']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>
                        </th>

                        <th scope="col" class="text-end">
                            Last 30 days
                            <div class="small fw-normal text-body-secondary">
                                <?= htmlspecialchars(
                                    $formatPeriod($analytics['30_days']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody>
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

                            <?php foreach (['7_days', '30_days'] as $period): ?>
                                <td class="text-end">
                                    <?php if (str_ends_with($key, '_rate')): ?>
                                        <?= htmlspecialchars(
                                            $formatRate(
                                                $analytics[$period][$key]
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    <?php else: ?>
                                        <?= (int)$analytics[$period][$key] ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document
        .querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach((element) => {
            bootstrap.Tooltip.getOrCreateInstance(element);
        });
</script>
