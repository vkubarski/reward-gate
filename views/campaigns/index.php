<h1>Campaigns</h1>

<?php foreach ($campaigns as $campaign): ?>
    <h2>
        <?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?>
    </h2>

    <p>
        Status:
        <?= htmlspecialchars($campaign['status'], ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endforeach; ?>
