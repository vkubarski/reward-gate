<?php

declare(strict_types=1);
?>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li>
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form
    method="post"
    action="<?= htmlspecialchars(
        $form_action,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(
            $csrf_token,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <div class="mb-3">
        <label
            for="name"
            class="form-label"
        >
            Campaign name
        </label>

        <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            value="<?= htmlspecialchars(
                $old['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            required
        >
    </div>

    <div class="mb-3">
        <label
            for="presentation_type"
            class="form-label"
        >
            Presentation type
        </label>

        <select
            id="presentation_type"
            name="presentation_type"
            class="form-select"
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

    <div class="mb-3">
        <label
            for="unlock_method"
            class="form-label"
        >
            Unlock method
        </label>

        <select
            id="unlock_method"
            name="unlock_method"
            class="form-select"
            required
        >
            <option
                value="timer"
                <?= ($old['unlock_method'] ?? 'timer') === 'timer'
                    ? 'selected'
                    : '' ?>
            >
                Timer
            </option>

            <option
                value="click"
                <?= ($old['unlock_method'] ?? '') === 'click'
                    ? 'selected'
                    : '' ?>
            >
                Click
            </option>
        </select>
    </div>

    <div
        class="mb-4"
        data-unlock-settings="timer"
        <?= ($old['unlock_method'] ?? 'timer') !== 'timer'
            ? 'hidden'
            : '' ?>
    >
        <label
            for="timer_duration_seconds"
            class="form-label"
        >
            Timer duration (seconds)
        </label>

        <input
            type="number"
            id="timer_duration_seconds"
            name="timer_duration_seconds"
            class="form-control"
            min="1"
            value="<?= (int)$old['timer_duration_seconds'] ?>"
            <?= ($old['unlock_method'] ?? 'timer') === 'timer'
                ? 'required'
                : '' ?>
        >
    </div>

    <div
        class="mb-4"
        data-unlock-settings="click"
        <?= (
            ($old['presentation_type'] ?? 'popup') !== 'content'
            || ($old['unlock_method'] ?? 'timer') !== 'click'
        ) ? 'hidden' : '' ?>
    >
        <hr class="my-4">

        <h2 class="h5 mb-3">
            Content Gate Settings
        </h2>

        <div class="mb-3">
            <label
                for="content_cta_label"
                class="form-label"
            >
                CTA label
            </label>

            <input
                type="text"
                id="content_cta_label"
                name="presentation_settings[cta_label]"
                class="form-control"
                value="<?= htmlspecialchars(
                    $old['presentation_settings']['cta_label']
                        ?? 'Continue',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
        </div>

        <div class="mb-3">
            <label
                for="content_destination_url"
                class="form-label"
            >
                Destination URL
            </label>

            <input
                type="url"
                id="content_destination_url"
                name="presentation_settings[destination_url]"
                class="form-control"
                value="<?= htmlspecialchars(
                    $old['presentation_settings']['destination_url']
                        ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                placeholder="https://example.com/"
            >

            <div class="form-text">
                The page opened when the visitor clicks the CTA.
            </div>
        </div>
    </div>

    <div class="mb-4">
        <label
            for="frequency_limit_seconds"
            class="form-label"
        >
            Frequency limit (seconds)
        </label>

        <input
            type="number"
            id="frequency_limit_seconds"
            name="frequency_limit_seconds"
            class="form-control"
            min="1"
            value="<?= isset($old['frequency_limit_seconds'])
                ? (int)$old['frequency_limit_seconds']
                : '' ?>"
        >

        <div class="form-text">
            Minimum time a visitor must wait before they can start
            this campaign again. Leave empty to allow unlimited
            unlock attempts.
        </div>
    </div>

    <div data-presentation-settings="popup"
         <?= $old['presentation_type'] !== 'popup' ? 'hidden' : '' ?>
    >
        <hr class="my-4">

        <h2 class="h5 mb-3">
            Popup Settings
        </h2>

        <div class="mb-3">
            <label
                for="popup_title"
                class="form-label"
            >
                Title
            </label>

            <input
                type="text"
                id="popup_title"
                name="presentation_settings[title]"
                class="form-control"
                value="<?= htmlspecialchars(
                    $old['presentation_settings']['title']
                        ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
        </div>

        <div class="mb-3">
            <label
                for="popup_message"
                class="form-label"
            >
                Message
            </label>

            <textarea
                id="popup_message"
                name="presentation_settings[message]"
                class="form-control"
                rows="3"
            ><?= htmlspecialchars(
                $old['presentation_settings']['message']
                    ?? '',
                ENT_QUOTES,
                'UTF-8'
            ) ?></textarea>
        </div>

        <div class="form-check mb-3">
            <input
                type="checkbox"
                id="popup_show_message"
                name="presentation_settings[show_message]"
                value="1"
                class="form-check-input"
                <?= !empty(
                    $old['presentation_settings']['show_message']
                )
                    ? 'checked'
                    : '' ?>
            >

            <label
                for="popup_show_message"
                class="form-check-label"
            >
                Show message
            </label>
        </div>

        <div class="mb-3">
            <label
                for="popup_content"
                class="form-label"
            >
                Popup Content / Ad Code
            </label>

            <textarea
                id="popup_content"
                name="presentation_settings[content]"
                class="form-control font-monospace"
                rows="12"
                spellcheck="false"
            ><?= htmlspecialchars(
                $old['presentation_settings']['content']
                    ?? '',
                ENT_QUOTES,
                'UTF-8'
            ) ?></textarea>

            <div class="form-text">
                Paste the HTML, JavaScript, image markup,
                iframe, or other embed code supplied by your
                content or advertising provider.
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button
            type="submit"
            class="btn btn-primary"
        >
            <?= htmlspecialchars(
                $submit_label,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </button>

        <a
            href="<?= htmlspecialchars(
                $cancel_url,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="btn btn-outline-secondary"
        >
            Cancel
        </a>
    </div>
</form>
