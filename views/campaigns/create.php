<?php

declare(strict_types=1);

$form_action = '/admin/campaigns';
$submit_label = 'Create Campaign';
$cancel_url = '/admin/campaigns';
?>

<div class="admin-page-header">
    <h1 class="h2">
        Create Campaign
    </h1>

    <p class="text-body-secondary mb-0">
        Create a new Reward Gate campaign.
    </p>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>
