<?php
$sortableColumns = [
    'name' => 'Name',
    'presentation_type' => 'Type',
    'status' => 'Status',
    'created_at' => 'Created',
];

$currentSortColumn = $sort['column'] ?? 'created_at';
$currentSortDirection = strtolower(
    $sort['direction'] ?? 'DESC'
);

$buildSortUrl = static function (
    string $column
) use (
    $currentSortColumn,
    $currentSortDirection,
    $search
): string {
    $direction = (
        $column === $currentSortColumn
        && $currentSortDirection === 'asc'
    )
        ? 'desc'
        : 'asc';

    $query = [
        'sort' => $column,
        'direction' => $direction,
    ];

    if ($search !== '') {
        $query['search'] = $search;
    }

    return \RewardGate\Http\Url::to('/admin/campaigns')
        . '?'
        . http_build_query($query);
};

$currentPage = $pagination['current_page'] ?? 1;
$totalPages = $pagination['total_pages'] ?? 0;

$buildPageUrl = static function (
    int $page
) use (
    $currentSortColumn,
    $currentSortDirection,
    $search
): string {
    $query = [
        'page' => $page,
        'sort' => $currentSortColumn,
        'direction' => $currentSortDirection,
    ];

    if ($search !== '') {
        $query['search'] = $search;
    }

    return \RewardGate\Http\Url::to('/admin/campaigns')
        . '?'
        . http_build_query($query);
};
?>

<div class="admin-page-header d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
    <div>
        <h1 class="h2">
            Campaigns
        </h1>

        <p class="text-body-secondary mb-0">
            Manage your Reward Gate campaigns.
        </p>
    </div>

    <div class="d-flex flex-column flex-sm-row gap-2">
        <form
            method="get"
            action="<?= \RewardGate\Http\Url::to('/admin/campaigns') ?>"
            class="d-flex"
        >
            <div class="input-group">
                <div class="position-relative">
                    <input
                        type="search"
                        name="search"
                        value="<?= htmlspecialchars(
                            $search,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        class="form-control pe-5"
                        placeholder="Search campaigns..."
                        aria-label="Search campaigns"
                        style="width: 220px;"
                    >

                    <?php if ($search !== ''): ?>
                        <a
                            href="<?= \RewardGate\Http\Url::to('/admin/campaigns') ?>"
                            class="position-absolute top-50 end-0 translate-middle-y me-2 text-body-secondary text-decoration-none"
                            aria-label="Clear search"
                            title="Clear search"
                        >
                            ×
                        </a>
                    <?php endif; ?>
                </div>

                <button
                    type="submit"
                    class="btn border"
                    aria-label="Search"
                    title="Search"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        fill="currentColor"
                        viewBox="0 0 16 16"
                        aria-hidden="true"
                    >
                        <path
                            d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"
                        />
                    </svg>
                </button>
            </div>

            <?php if (
                isset($sort['column'], $sort['direction'])
            ): ?>
                <input
                    type="hidden"
                    name="sort"
                    value="<?= htmlspecialchars(
                        $sort['column'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="direction"
                    value="<?= htmlspecialchars(
                        $sort['direction'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            <?php endif; ?>
        </form>

        <a
            href="<?= \RewardGate\Http\Url::to('/admin/campaigns/create') ?>"
            class="btn btn-primary"
        >
            + Create Campaign
        </a>
    </div>
</div>

<div class="card admin-table-card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">
                        <a
                            href="<?= htmlspecialchars(
                                $buildSortUrl('name'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="text-decoration-none text-body"
                        >
                            Name

                            <?php if ($currentSortColumn === 'name'): ?>
                                <?= $currentSortDirection === 'asc' ? '↑' : '↓' ?>
                            <?php endif; ?>
                        </a>
                    </th>

                    <th scope="col">
                        <a
                            href="<?= htmlspecialchars(
                                $buildSortUrl('presentation_type'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="text-decoration-none text-body"
                        >
                            Type

                            <?php if (
                                $currentSortColumn === 'presentation_type'
                            ): ?>
                                <?= $currentSortDirection === 'asc' ? '↑' : '↓' ?>
                            <?php endif; ?>
                        </a>
                    </th>

                    <th scope="col">
                        <a
                            href="<?= htmlspecialchars(
                                $buildSortUrl('status'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="text-decoration-none text-body"
                        >
                            Status

                            <?php if ($currentSortColumn === 'status'): ?>
                                <?= $currentSortDirection === 'asc' ? '↑' : '↓' ?>
                            <?php endif; ?>
                        </a>
                    </th>

                    <th scope="col">
                        <a
                            href="<?= htmlspecialchars(
                                $buildSortUrl('created_at'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            class="text-decoration-none text-body"
                        >
                            Created

                            <?php if ($currentSortColumn === 'created_at'): ?>
                                <?= $currentSortDirection === 'asc' ? '↑' : '↓' ?>
                            <?php endif; ?>
                        </a>
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
                            colspan="5"
                            class="text-center text-body-secondary py-5"
                        >
                            <div class="text-center py-5">
                                <div class="fw-semibold mb-1">
                                    No campaigns yet.
                                </div>

                                <div class="text-body-secondary small">
                                    Create your first campaign to get started.
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($campaigns as $campaign): ?>
                    <?php
                    $statusClass = match ($campaign['status']) {
                        'active' => 'admin-status-badge--active',
                        'paused' => 'admin-status-badge--paused',
                        'draft' => 'admin-status-badge--draft',
                        'archived' => 'admin-status-badge--archived',
                        default => 'admin-status-badge--draft',
                    };
                    ?>

                    <?php
                    $createdAt = DateTimeImmutable::createFromFormat(
                        'Y-m-d H:i:s',
                        $campaign['created_at'],
                        new DateTimeZone('UTC')
                    );

                    $createdAtIso = $createdAt?->format(DateTimeInterface::ATOM);
                    ?>

                    <tr>
                        <td>
                            <a
                                href="<?= \RewardGate\Http\Url::to('/admin/campaigns/' . (int)$campaign['id']) ?>"
                                class="admin-campaign-link"
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
                                class="badge admin-status-badge <?= $statusClass ?>"
                            >
                                <?= htmlspecialchars(
                                    ucfirst($campaign['status']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </td>

                        <td>
                            <time
                                datetime="<?= htmlspecialchars(
                                    $createdAtIso ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                data-created-at="<?= htmlspecialchars(
                                    $createdAtIso ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <?= htmlspecialchars(
                                    $createdAtIso ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </time>
                        </td>

                        <td class="text-end">
                            <a
                                href="<?= \RewardGate\Http\Url::to('/admin/campaigns/' . (int)$campaign['id']) ?>"
                                class="admin-view-button btn btn-sm btn-outline-secondary"
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

<?php if ($totalPages > 1): ?>
    <nav
        class="d-flex justify-content-center mt-4"
        aria-label="Campaign pagination"
    >
        <ul class="pagination mb-0">
            <li
                class="page-item <?= $currentPage <= 1
                    ? 'disabled'
                    : '' ?>"
            >
                <?php if ($currentPage > 1): ?>
                    <a
                        class="page-link"
                        href="<?= htmlspecialchars(
                            $buildPageUrl($currentPage - 1),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        aria-label="Previous page"
                    >
                        Previous
                    </a>
                <?php else: ?>
                    <span class="page-link">
                        Previous
                    </span>
                <?php endif; ?>
            </li>

            <?php
            $pages = [];

            $pages[] = 1;

            for (
                $page = max(2, $currentPage - 1);
                $page <= min(
                    $totalPages - 1,
                    $currentPage + 1
                );
                $page++
            ) {
                $pages[] = $page;
            }

            if ($totalPages > 1) {
                $pages[] = $totalPages;
            }

            $pages = array_values(
                array_unique($pages)
            );
            ?>

            <?php $previousPage = null; ?>

            <?php foreach ($pages as $page): ?>
                <?php if (
                    $previousPage !== null
                    && $page > $previousPage + 1
                ): ?>
                    <li class="page-item disabled">
                        <span class="page-link">
                            …
                        </span>
                    </li>
                <?php endif; ?>

                <li
                    class="page-item <?= $page === $currentPage
                        ? 'active'
                        : '' ?>"
                >
                    <?php if ($page === $currentPage): ?>
                        <span
                            class="page-link"
                            aria-current="page"
                        >
                            <?= $page ?>
                        </span>
                    <?php else: ?>
                        <a
                            class="page-link"
                            href="<?= htmlspecialchars(
                                $buildPageUrl($page),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <?= $page ?>
                        </a>
                    <?php endif; ?>
                </li>

                <?php $previousPage = $page; ?>
            <?php endforeach; ?>

            <li
                class="page-item <?= $currentPage >= $totalPages
                    ? 'disabled'
                    : '' ?>"
            >
                <?php if ($currentPage < $totalPages): ?>
                    <a
                        class="page-link"
                        href="<?= htmlspecialchars(
                            $buildPageUrl($currentPage + 1),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        aria-label="Next page"
                    >
                        Next
                    </a>
                <?php else: ?>
                    <span class="page-link">
                        Next
                    </span>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
<?php endif; ?>
