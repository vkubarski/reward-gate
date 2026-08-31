<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars(
        $title ?? 'Reward Gate',
        ENT_QUOTES,
        'UTF-8'
    ) ?></title>
</head>
<body>

<form method="post" action="/admin/logout">
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(
            $csrf_token,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <button type="submit">
        Log out
    </button>
</form>

<?= $content ?>

</body>
</html>
