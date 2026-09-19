<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <script>
        (() => {
            const savedTheme = localStorage.getItem('rg-theme');
            const systemDark = window.matchMedia(
                '(prefers-color-scheme: dark)'
            ).matches;

            const theme = savedTheme === 'light'
                || savedTheme === 'dark'
                ? savedTheme
                : systemDark
                    ? 'dark'
                    : 'light';

            document.documentElement.setAttribute(
                'data-bs-theme',
                theme
            );
        })();
    </script>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="/assets/css/admin.css"
        rel="stylesheet"
    >

    <title><?= htmlspecialchars(
        $title ?? 'Reward Gate',
        ENT_QUOTES,
        'UTF-8'
    ) ?></title>
</head>
<body>
<main class="min-vh-100 d-flex align-items-center">
    <div class="container py-5">
        <?= $content ?>
    </div>
</main>
</body>
</html>
