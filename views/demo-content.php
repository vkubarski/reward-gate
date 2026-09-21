<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reward Gate Content Gate Demo</title>

    <link
        rel="stylesheet"
        href="/assets/content/content.css"
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

<script src="/assets/content/content.js"></script>

</body>
</html>
