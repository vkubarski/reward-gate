<h1>
    <?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?>
</h1>

<p>
    ID: <?= (int)$campaign['id'] ?>
</p>

<p>
    Status:
    <?= htmlspecialchars($campaign['status'], ENT_QUOTES, 'UTF-8') ?>
</p>

<p>
    Presentation type:
    <?= htmlspecialchars($campaign['presentation_type'], ENT_QUOTES, 'UTF-8') ?>
</p>
