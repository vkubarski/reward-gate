<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reward Gate Popup Gate Demo</title>

    <link
        rel="stylesheet"
        href="/assets/popup/popup.css"
    >
</head>
<body>

<main>
    <h1>Reward Gate Popup Gate Demo</h1>

    <p>
        This page demonstrates the Reward Gate Popup Gate.
    </p>

    <p>
        The protected content below is unlocked after the
        countdown is completed and verified by the server.
    </p>

    <div
        data-reward-gate
        data-campaign-id="<?= $demoCampaignId ?>"
    ></div>

    <div id="protected-content" hidden>
        <h2>Protected Content</h2>

        <p>
            This content is protected by Reward Gate.
        </p>

        <p>
            The visitor can see it after the unlock has been
            successfully verified.
        </p>
    </div>
</main>

<script src="/assets/popup/popup.js"></script>

</body>
</html>
