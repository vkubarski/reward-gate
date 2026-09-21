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

return static function (Router $router) use ($pdo, $appConfig): void {
    $campaignRepository = new CampaignRepository($pdo);
    $campaignService = new CampaignService($campaignRepository);

    // For demo campaigns
    $findDemoCampaignId = static function (
        string $name
    ) use ($pdo): int {
        $statement = $pdo->prepare(
            'SELECT id
             FROM campaigns
             WHERE name = :name
               AND status = :status
             LIMIT 1'
        );

        $statement->execute([
            'name' => $name,
            'status' => 'active',
        ]);

        $campaign = $statement->fetch();

        if ($campaign === false) {
            throw new RuntimeException(
                "Demo campaign '{$name}' is not configured. "
                . 'Run: vendor/bin/phinx seed:run'
            );
        }

        return (int)$campaign['id'];
    };

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
        $csrfToken,
        $appConfig['version']
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

    $router->get(
        '/admin/campaigns/{id}/edit',
        $adminAuthGuard->protect(
            [$campaignController, 'edit']
        )
    );

    $router->post(
        '/admin/campaigns/{id}',
        $adminAuthGuard->protect(
            [$campaignController, 'update']
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

    $router->post(
        '/admin/campaigns/{id}/activate',
        $adminAuthGuard->protect(
            [$campaignController, 'activate']
        )
    );

    $router->post(
        '/admin/campaigns/{id}/pause',
        $adminAuthGuard->protect(
            [$campaignController, 'pause']
        )
    );

    $router->post(
        '/admin/campaigns/{id}/archive',
        $adminAuthGuard->protect(
            [$campaignController, 'archive']
        )
    );

    $router->get(
        '/demo-popup',
        static function() use ($findDemoCampaignId): void {
            try {
                $demoCampaignId = $findDemoCampaignId(
                    'Demo Popup Gate'
                );
            } catch (RuntimeException $exception) {
                http_response_code(500);
                echo $exception->getMessage();

                return;
            }

            require __DIR__ . '/../views/demo-popup.php';
        }
    );

    $router->get(
        '/demo-content',
        static function() use ($findDemoCampaignId): void {
            try {
                $demoCampaignId = $findDemoCampaignId(
                    'Demo Content Gate'
                );
            } catch (RuntimeException $exception) {
                http_response_code(500);
                echo $exception->getMessage();

                return;
            }

            require __DIR__ . '/../views/demo-content.php';
        }
    );

    $router->post(
        '/unlock/{id}/start',
        [$unlockController, 'start']
    );

    $router->get(
        '/unlock/{id}/status',
        [$unlockController, 'status']
    );

    $router->post(
        '/unlock/complete',
        [$unlockController, 'complete']
    );
};
