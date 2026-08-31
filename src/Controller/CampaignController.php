<?php

declare(strict_types=1);

namespace RewardGate\Controller;

use RewardGate\Security\CsrfTokenInterface;
use RewardGate\Service\CampaignServiceInterface;

final class CampaignController
{
    public function __construct(
        private CampaignServiceInterface $campaignService,
        private CsrfTokenInterface $csrfToken
    ) {
    }

    public function index(): void
    {
        $campaigns = $this->campaignService->findAll();

        $this->render('campaigns/index', [
            'title' => 'Campaigns',
            'campaigns' => $campaigns,
        ]);
    }

    public function show(array $params): void
    {
        $campaign = $this->campaignService->findById(
            (int)$params['id']
        );

        if ($campaign === null) {
            http_response_code(404);
            echo 'Campaign not found';

            return;
        }

        $this->render('campaigns/show', [
            'title' => $campaign['name'],
            'campaign' => $campaign,
        ]);
    }

    public function create(): void
    {
        $this->render('campaigns/create', [
            'title' => 'Create Campaign',
            'errors' => [],
            'old' => [
                'name' => '',
                'presentation_type' => 'popup',
                'timer_duration_seconds' => 10,
            ],
        ]);
    }

    public function store(): void
    {
        if (
            !$this->csrfToken->validate(
                $_POST['csrf_token'] ?? null
            )
        ) {
            http_response_code(403);
            echo 'Invalid CSRF token';

            return;
        }

        $name = trim($_POST['name'] ?? '');
        $presentationType = $_POST['presentation_type'] ?? '';
        $timerDurationSeconds = (int)(
            $_POST['timer_duration_seconds'] ?? 0
        );

        $errors = [];

        if ($name === '') {
            $errors[] = 'Campaign name is required.';
        }

        if ($timerDurationSeconds < 1) {
            $errors[] = 'Timer duration must be greater than zero.';
        }

        if ($errors !== []) {
            $this->render('campaigns/create', [
                'title' => 'Create Campaign',
                'errors' => $errors,
                'old' => [
                    'name' => $name,
                    'presentation_type' => $presentationType,
                    'timer_duration_seconds' => $timerDurationSeconds,
                ],
            ]);

            return;
        }

        try {
            $id = $this->campaignService->create(
                $name,
                $presentationType,
                null,
                'draft',
                'timer',
                $timerDurationSeconds,
                'content',
            );
        } catch (\InvalidArgumentException $exception) {
            $this->render('campaigns/create', [
                'title' => 'Create Campaign',
                'errors' => [$exception->getMessage()],
                'old' => [
                    'name' => $name,
                    'presentation_type' => $presentationType,
                    'timer_duration_seconds' => $timerDurationSeconds,
                ],
            ]);

            return;
        }

        header("Location: /admin/campaigns/{$id}");
        exit;
    }

    private function render(string $view, array $data = []): void
    {
        $data['csrf_token'] = $this->csrfToken->get();

        extract($data);

        ob_start();

        require __DIR__ . "/../../views/{$view}.php";

        $content = ob_get_clean();

        require __DIR__ . "/../../views/layout.php";
    }
}
