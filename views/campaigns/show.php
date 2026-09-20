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
            href="/admin/campaigns/<?= (int)$campaign['id'] ?>/edit"
            class="btn btn-primary"
        >
            Edit Campaign
        </a>

        <?php if ($campaign['status'] === 'active'): ?>
            <form
                method="post"
                action="/admin/campaigns/<?= (int)$campaign['id'] ?>/pause"
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
                    class="btn btn-outline-warning"
                >
                    Pause Campaign
                </button>
            </form>
        <?php elseif ($campaign['status'] === 'draft'): ?>
            <form
                method="post"
                action="/admin/campaigns/<?= (int)$campaign['id'] ?>/activate"
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
                    class="btn btn-success"
                >
                    Activate Campaign
                </button>
            </form>
        <?php elseif ($campaign['status'] === 'paused'): ?>
            <form
                method="post"
                action="/admin/campaigns/<?= (int)$campaign['id'] ?>/activate"
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
                    class="btn btn-success"
                >
                    Activate Campaign
                </button>
            </form>
        <?php endif; ?>

        <?php if ($campaign['status'] !== 'archived'): ?>
            <form
                method="post"
                action="/admin/campaigns/<?= (int)$campaign['id'] ?>/archive"
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
                    class="btn btn-outline-secondary"
                >
                    Archive Campaign
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">
                Status
            </dt>

            <dd class="col-sm-8">
                <?= htmlspecialchars(
                    $campaign['status'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
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

            <dt class="col-sm-4">
                Timer duration
            </dt>

            <dd class="col-sm-8">
                <?= (int)$campaign['timer_duration_seconds'] ?>
                seconds
            </dd>
        </dl>
    </div>
</div>
