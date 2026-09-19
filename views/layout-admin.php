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
<div class="d-flex min-vh-100">

    <aside
        class="offcanvas-lg offcanvas-start admin-sidebar"
        tabindex="-1"
        id="adminSidebar"
        aria-labelledby="adminSidebarLabel"
    >
        <div class="d-flex flex-column h-100">

            <div class="admin-sidebar-header">
                <div class="d-flex align-items-center justify-content-between">
                    <a
                        href="/admin/campaigns"
                        class="text-decoration-none admin-brand"
                        id="adminSidebarLabel"
                    >
                        Reward Gate
                    </a>

                    <button
                        type="button"
                        class="btn-close d-lg-none"
                        data-bs-dismiss="offcanvas"
                        data-bs-target="#adminSidebar"
                        aria-label="Close navigation"
                    ></button>
                </div>
            </div>

            <nav class="flex-grow-1 px-3 py-4">
                <div class="admin-nav-section">
                    <div class="admin-nav-section-title">
                        Campaigns
                    </div>

                    <a
                        href="/admin/campaigns"
                        class="admin-nav-link"
                    >
                        <span>Campaigns</span>
                    </a>
                </div>
            </nav>

            <div class="admin-sidebar-footer px-3 py-3">
                <form
                    method="post"
                    action="/admin/logout"
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

                    <button
                        type="submit"
                        class="admin-nav-link admin-nav-button w-100"
                    >
                        <span>Log out</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex-grow-1 d-flex flex-column min-vh-100">

        <header class="admin-topbar border-bottom">
            <div class="container-fluid px-3 px-lg-4">
                <div class="d-flex align-items-center justify-content-between gap-3">

                    <div class="d-flex align-items-center gap-3">
                        <button
                            type="button"
                            class="btn btn-outline-secondary d-lg-none"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#adminSidebar"
                            aria-controls="adminSidebar"
                            aria-label="Open navigation"
                        >
                            <span aria-hidden="true">☰</span>
                        </button>

                        <span class="fw-semibold d-none d-sm-inline">
                            Reward Gate
                        </span>
                    </div>

                    <div class="dropdown">
                        <button
                            type="button"
                            class="btn btn-outline-secondary dropdown-toggle"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            id="appearanceMenu"
                        >
                            Appearance
                        </button>

                        <ul
                            class="dropdown-menu dropdown-menu-end"
                            aria-labelledby="appearanceMenu"
                        >
                            <li>
                                <button
                                    type="button"
                                    class="dropdown-item"
                                    data-theme-value="system"
                                >
                                    System
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    class="dropdown-item"
                                    data-theme-value="light"
                                >
                                    Light
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    class="dropdown-item"
                                    data-theme-value="dark"
                                >
                                    Dark
                                </button>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
        </header>

        <main class="flex-grow-1">
            <div class="container-fluid px-3 px-lg-4 py-4">
                <?= $content ?>
            </div>
        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/admin.js"></script>

</body>
</html>
