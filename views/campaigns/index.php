<div class="admin-page-header d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
    <div>
        <h1 class="h2">
            Campaigns
        </h1>

        <p class="text-body-secondary mb-0">
            Manage your Reward Gate campaigns.
        </p>
    </div>

    <a
        href="/admin/campaigns/create"
        class="btn btn-primary"
    >
        + Create Campaign
    </a>
</div>

<div class="card admin-table-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">
                        Name
                    </th>

                    <th scope="col">
                        Type
                    </th>

                    <th scope="col">
                        Status
                    </th>

                    <th
                        scope="col"
                        class="text-end"
                    >
                        Action
                    </th>
                </tr>
            </thead>

            <tbody>
                <?php if ($campaigns === []): ?>
                    <tr>
                        <td
                            colspan="4"
                            class="text-center text-body-secondary py-5"
                        >
                            No campaigns yet.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($campaigns as $campaign): ?>
                    <?php
                    $statusClass = match ($campaign['status']) {
                        'active' => 'text-bg-success',
                        'paused' => 'text-bg-warning',
                        'archived' => 'text-bg-dark',
                        default => 'text-bg-secondary',
                    };
                    ?>

                    <tr>
                        <td>
                            <a
                                href="/admin/campaigns/<?= (int)$campaign['id'] ?>"
                                class="text-decoration-none fw-semibold"
                            >
                                <?= htmlspecialchars(
                                    $campaign['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </a>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $campaign['presentation_type'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <span
                                class="badge <?= $statusClass ?>"
                            >
                                <?= htmlspecialchars(
                                    ucfirst($campaign['status']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </td>

                        <td class="text-end">
                            <a
                                href="/admin/campaigns/<?= (int)$campaign['id'] ?>"
                                class="btn btn-sm btn-outline-secondary"
                            >
                                View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
