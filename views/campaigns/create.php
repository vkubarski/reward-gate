<?php

declare(strict_types=1);
?>

<h1>Create Campaign</h1>

<?php if ($errors !== []): ?>
    <div>
        <?php foreach ($errors as $error): ?>
            <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="/admin/campaigns">
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(
            $csrf_token,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <div>
        <label for="name">Campaign name</label>

        <input
            type="text"
            id="name"
            name="name"
            value="<?= htmlspecialchars(
                $old['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            required
        >
    </div>

    <div>
        <label for="presentation_type">
            Presentation type
        </label>

        <select
            id="presentation_type"
            name="presentation_type"
            required
        >
            <option
                value="popup"
                <?= $old['presentation_type'] === 'popup'
                    ? 'selected'
                    : '' ?>
            >
                Popup
            </option>

            <option
                value="content"
                <?= $old['presentation_type'] === 'content'
                    ? 'selected'
                    : '' ?>
            >
                Content
            </option>
        </select>
    </div>

    <div>
        <label for="timer_duration_seconds">
            Timer duration (seconds)
        </label>

        <input
            type="number"
            id="timer_duration_seconds"
            name="timer_duration_seconds"
            min="1"
            value="<?= (int)$old['timer_duration_seconds'] ?>"
            required
        >
    </div>

    <button type="submit">
        Create Campaign
    </button>
</form>
