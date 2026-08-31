<?php

declare(strict_types=1);

$error ??= null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login - Reward Gate</title>
</head>
<body>

<h1>Reward Gate</h1>

<h2>Admin Login</h2>

<?php if ($error !== null): ?>
    <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="/admin/login">
    <div>
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            autocomplete="username"
            required
        >
    </div>

    <div>
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >
    </div>

    <button type="submit">Log in</button>
</form>

</body>
</html>
