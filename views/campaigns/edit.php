<?php

declare(strict_types=1);

$form_action = '/admin/campaigns/' . (int)$campaign['id'];
$submit_label = 'Save Changes';
$cancel_url = '/admin/campaigns/' . (int)$campaign['id'];
?>

<div class="admin-page-header">
    <div>
        <h1 class="h2">
            Edit Campaign
        </h1>

        <p class="text-body-secondary mb-0">
            <?= htmlspecialchars(
                $campaign['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>
