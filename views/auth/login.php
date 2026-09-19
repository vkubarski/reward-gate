<?php

declare(strict_types=1);

$error ??= null;
?>

<div class="admin-auth-card">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">
                    Reward Gate
                </h1>

                <p class="text-body-secondary mb-0">
                    Admin Login
                </p>
            </div>

            <?php if ($error !== null): ?>
                <div
                    class="alert alert-danger"
                    role="alert"
                >
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/admin/login">
                <div class="mb-3">
                    <label
                        for="username"
                        class="form-label"
                    >
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label
                        for="password"
                        class="form-label"
                    >
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-100"
                >
                    Log in
                </button>
            </form>
        </div>
    </div>
</div>

<div class="text-center mt-3 small text-body-secondary">
    Version <?= htmlspecialchars(
        $version,
        ENT_QUOTES,
        'UTF-8'
    ) ?>
</div>
