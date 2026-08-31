<?php

declare(strict_types=1);
?>

<link
    rel="stylesheet"
    href="/assets/popup/popup.css"
>

<h1>Reward Gate Demo</h1>

<p>
    This is publicly visible content.
</p>

<div id="protected-content" hidden>
    <h2>Protected Content</h2>

    <p>
        Congratulations. You completed the unlock process and can now
        see the protected content.
    </p>
</div>

<div
    data-reward-gate
    data-campaign-id="4"
></div>

<script src="/assets/popup/popup.js"></script>
