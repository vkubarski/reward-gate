<?php

declare(strict_types=1);

use RewardGate\Auth\AdminAuthGuard;
use RewardGate\Auth\AdminSession;
use RewardGate\Controller\AdminAuthController;
use RewardGate\Controller\CampaignController;
use RewardGate\Controller\UnlockController;
use RewardGate\Controller\VisitorCampaignController;
use RewardGate\Http\Router;
use RewardGate\Repository\AdminUserRepository;
use RewardGate\Repository\CampaignRepository;
use RewardGate\Repository\UnlockCompletionRepository;
use RewardGate\Repository\UnlockSessionRepository;
use RewardGate\Security\CsrfToken;
use RewardGate\Service\AdminAuthService;
use RewardGate\Service\CampaignService;
use RewardGate\Service\UnlockSessionService;
use RewardGate\Security\VisitorId;

return static function (Router $router) use ($pdo): void {
    $campaignRepository = new CampaignRepository($pdo);
    $campaignService = new CampaignService($campaignRepository);

    $csrfToken = new CsrfToken();

    $campaignController = new CampaignController(
        $campaignService,
        $csrfToken
    );

    $unlockSessionRepository = new UnlockSessionRepository($pdo);
    $unlockCompletionRepository = new UnlockCompletionRepository($pdo);

    $unlockSessionService = new UnlockSessionService(
        $pdo,
        $campaignRepository,
        $unlockSessionRepository,
        $unlockCompletionRepository,
    );

    $visitorId = new VisitorId();
    $unlockController = new UnlockController(
        $unlockSessionService,
        $visitorId
    );

    $visitorCampaignController = new VisitorCampaignController(
        $campaignService
    );

    $adminUserRepository = new AdminUserRepository($pdo);
    $adminAuthService = new AdminAuthService($adminUserRepository);
    $adminSession = new AdminSession();

    $adminAuthController = new AdminAuthController(
        $adminAuthService,
        $adminSession,
        $csrfToken
    );

    $adminAuthGuard = new AdminAuthGuard($adminSession);

    $router->get(
        '/admin/campaigns',
        $adminAuthGuard->protect(
            [$campaignController, 'index']
        )
    );

    $router->get(
        '/admin/campaigns/create',
        $adminAuthGuard->protect(
            [$campaignController, 'create']
        )
    );

    $router->post(
        '/admin/campaigns',
        $adminAuthGuard->protect(
            [$campaignController, 'store']
        )
    );

    $router->get(
        '/admin/campaigns/{id}',
        $adminAuthGuard->protect(
            [$campaignController, 'show']
        )
    );

    $router->post(
        '/unlock/{id}/start',
        [$unlockController, 'start']
    );

    $router->post(
        '/unlock/complete',
        [$unlockController, 'complete']
    );

    $router->get(
        '/campaigns/{id}',
        [$visitorCampaignController, 'show']
    );

    $router->get(
        '/admin/login',
        [$adminAuthController, 'showLogin']
    );

    $router->post(
        '/admin/login',
        [$adminAuthController, 'login']
    );

    $router->post(
        '/admin/logout',
        [$adminAuthController, 'logout']
    );
};
