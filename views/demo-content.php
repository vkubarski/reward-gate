<!DOCTYPE html>
<html lang="en" data-base-path="<?= htmlspecialchars(\RewardGate\Http\Url::basePath(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reward Gate Content Gate Demo</title>

    <link
        rel="stylesheet"
        href="<?= \RewardGate\Http\Url::to('/assets/content/content.css') ?>"
    >
</head>
<body>

<main>
    <h1>Reward Gate Content Gate Demo</h1>

    <p>
        This content is visible before the gate.
    </p>

    <p>
        The Content Gate is placed here.
    </p>

    <div
        data-reward-gate
        data-campaign-id="<?= $demoCampaignId ?>"
    ></div>

    <p>
        This content is currently protected.
    </p>

    <p>
        This is another protected paragraph.
    </p>
</main>

<script src="<?= \RewardGate\Http\Url::to('/assets/js/url.js') ?>"></script>
<script src="<?= \RewardGate\Http\Url::to('/assets/js/activity.js') ?>"></script>
<script src="<?= \RewardGate\Http\Url::to('/assets/content/content.js') ?>"></script>

</body>
</html>
