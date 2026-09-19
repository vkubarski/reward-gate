<?php

declare(strict_types=1);

namespace RewardGate\Controller;

use InvalidArgumentException;
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
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => null,
                'presentation_settings' => [
                    'title' => 'Unlock Content',
                    'message' => 'Please wait while your content is being unlocked.',
                    'show_message' => true,
                    'content' => '',
                ],
            ],
        ]);
    }

    public function edit(array $params): void
    {
        $campaign = $this->campaignService->findById(
            (int)$params['id']
        );

        if ($campaign === null) {
            http_response_code(404);
            echo 'Campaign not found';

            return;
        }

        $this->render('campaigns/edit', [
            'title' => 'Edit Campaign',
            'errors' => [],
            'campaign' => $campaign,
            'old' => [
                'name' => $campaign['name'],
                'presentation_type' => $campaign['presentation_type'],
                'unlock_method' => $campaign['unlock_method'],
                'timer_duration_seconds' => (int)$campaign['timer_duration_seconds'],
                'frequency_limit_seconds' => $campaign['frequency_limit_seconds'],
                'presentation_settings' => $campaign['presentation_settings'],
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

        $name = trim((string)($_POST['name'] ?? ''));
        $presentationType = (string)(
            $_POST['presentation_type'] ?? ''
        );
        $unlockMethod = (string)(
            $_POST['unlock_method'] ?? ''
        );
        $timerDurationSeconds = (int)(
            $_POST['timer_duration_seconds'] ?? 0
        );
        $frequencyLimitSeconds =
            isset($_POST['frequency_limit_seconds'])
            && $_POST['frequency_limit_seconds'] !== ''
                ? (int)$_POST['frequency_limit_seconds']
                : null;
        $presentationSettings = is_array(
            $_POST['presentation_settings'] ?? null
        ) ? $_POST['presentation_settings'] : [];

        if ($presentationType === 'popup') {
            $presentationSettings['show_message'] = isset(
                $_POST['presentation_settings']['show_message']
            );
        }

        $errors = [];

        if ($name === '') {
            $errors[] = 'Campaign name is required.';
        }

        if ($unlockMethod === 'timer' && $timerDurationSeconds < 1) {
            $errors[] = 'Timer duration must be greater than zero.';
        }

        if (
            !(
                ($presentationType === 'popup'
                    && $unlockMethod === 'timer')
                || (
                    $presentationType === 'content'
                    && $unlockMethod === 'click'
                )
            )
        ) {
            $errors[] = 'This presentation type and unlock method combination is not supported.';
        }

        if ($errors !== []) {
            $this->render('campaigns/create', [
                'title' => 'Create Campaign',
                'errors' => $errors,
                'old' => [
                    'name' => $name,
                    'presentation_type' => $presentationType,
                    'unlock_method' => $unlockMethod,
                    'timer_duration_seconds' => $timerDurationSeconds,
                    'frequency_limit_seconds' => $frequencyLimitSeconds,
                    'presentation_settings' => $presentationSettings,
                ],
            ]);

            return;
        }

        try {
            $id = $this->campaignService->create(
                $name,
                $presentationType,
                $presentationSettings,
                'draft',
                $unlockMethod,
                $timerDurationSeconds,
                'content',
                $frequencyLimitSeconds,
            );
        } catch (InvalidArgumentException $exception) {
            $this->render('campaigns/create', [
                'title' => 'Create Campaign',
                'errors' => [$exception->getMessage()],
                'old' => [
                    'name' => $name,
                    'presentation_type' => $presentationType,
                    'unlock_method' => $unlockMethod,
                    'timer_duration_seconds' => $timerDurationSeconds,
                    'frequency_limit_seconds' => $frequencyLimitSeconds,
                    'presentation_settings' => $presentationSettings,
                ],
            ]);

            return;
        }

        header("Location: /admin/campaigns/{$id}");
        exit;
    }

    public function update(array $params): void
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

        $campaignId = (int)$params['id'];

        $name = trim((string)($_POST['name'] ?? ''));
        $presentationType = (string)(
            $_POST['presentation_type'] ?? ''
        );
        $unlockMethod = (string)(
            $_POST['unlock_method'] ?? ''
        );
        $timerDurationSeconds = (int)(
            $_POST['timer_duration_seconds'] ?? 0
        );
        $frequencyLimitSeconds =
            isset($_POST['frequency_limit_seconds'])
            && $_POST['frequency_limit_seconds'] !== ''
                ? (int)$_POST['frequency_limit_seconds']
                : null;
        $presentationSettings = is_array(
            $_POST['presentation_settings'] ?? null
        )
            ? $_POST['presentation_settings']
            : [];

        if ($presentationType === 'popup') {
            $presentationSettings['show_message'] = isset(
                $_POST['presentation_settings']['show_message']
            );
        }

        $errors = [];

        if ($name === '') {
            $errors[] = 'Campaign name is required.';
        }

        if ($unlockMethod === 'timer' && $timerDurationSeconds < 1) {
            $errors[] = 'Timer duration must be greater than zero.';
        }

        if (
            !(
                ($presentationType === 'popup'
                    && $unlockMethod === 'timer')
                || (
                    $presentationType === 'content'
                    && $unlockMethod === 'click'
                )
            )
        ) {
            $errors[] = 'This presentation type and unlock method combination is not supported.';
        }

        if ($errors !== []) {
            $this->renderEditForm(
                $campaignId,
                $errors,
                $name,
                $presentationType,
                $unlockMethod,
                $timerDurationSeconds,
                $frequencyLimitSeconds,
                $presentationSettings
            );

            return;
        }

        try {
            $this->campaignService->update(
                $campaignId,
                [
                    'name' => $name,
                    'presentation_type' => $presentationType,
                    'unlock_method' => $unlockMethod,
                    'timer_duration_seconds' => $timerDurationSeconds,
                    'frequency_limit_seconds' => $frequencyLimitSeconds,
                    'presentation_settings' => $presentationSettings,
                ]
            );
        } catch (InvalidArgumentException $exception) {
            $this->renderEditForm(
                $campaignId,
                [$exception->getMessage()],
                $name,
                $presentationType,
                $unlockMethod,
                $timerDurationSeconds,
                $frequencyLimitSeconds,
                $presentationSettings
            );

            return;
        }

        header(
            "Location: /admin/campaigns/{$campaignId}"
        );

        exit;
    }

    public function activate(array $params): void
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

        $campaignId = (int)$params['id'];

        try {
            $updated = $this->campaignService->update(
                $campaignId,
                [
                    'status' => 'active',
                ]
            );
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            echo $exception->getMessage();

            return;
        }

        if (!$updated) {
            http_response_code(404);
            echo 'Campaign not found';

            return;
        }

        header(
            "Location: /admin/campaigns/{$campaignId}"
        );

        exit;
    }

    public function pause(array $params): void
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

        $campaignId = (int)$params['id'];

        try {
            $updated = $this->campaignService->update(
                $campaignId,
                [
                    'status' => 'paused',
                ]
            );
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            echo $exception->getMessage();

            return;
        }

        if (!$updated) {
            http_response_code(404);
            echo 'Campaign not found';

            return;
        }

        header(
            "Location: /admin/campaigns/{$campaignId}"
        );

        exit;
    }

    public function archive(array $params): void
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

        $campaignId = (int)$params['id'];

        try {
            $campaign = $this->campaignService->findById(
                $campaignId
            );

            if ($campaign === null) {
                http_response_code(404);
                echo 'Campaign not found';

                return;
            }

            if ($campaign['status'] !== 'paused') {
                http_response_code(400);
                echo 'Only paused campaigns can be archived.';

                return;
            }

            $updated = $this->campaignService->update(
                $campaignId,
                [
                    'status' => 'archived',
                ]
            );
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            echo $exception->getMessage();

            return;
        }

        if (!$updated) {
            http_response_code(404);
            echo 'Campaign not found';

            return;
        }

        header(
            "Location: /admin/campaigns/{$campaignId}"
        );

        exit;
    }

    private function renderEditForm(
        int $campaignId,
        array $errors,
        string $name,
        string $presentationType,
        string $unlockMethod,
        int $timerDurationSeconds,
        ?int $frequencyLimitSeconds,
        array $presentationSettings
    ): void {
        $campaign = $this->campaignService->findById(
            $campaignId
        );

        if ($campaign === null) {
            http_response_code(404);
            echo 'Campaign not found';

            return;
        }

        $this->render('campaigns/edit', [
            'title' => 'Edit Campaign',
            'errors' => $errors,
            'campaign' => $campaign,
            'old' => [
                'name' => $name,
                'presentation_type' => $presentationType,
                'unlock_method' => $unlockMethod,
                'timer_duration_seconds' =>
                    $timerDurationSeconds,
                'frequency_limit_seconds' =>
                    $frequencyLimitSeconds,
                'presentation_settings' =>
                    $presentationSettings,
            ],
        ]);
    }

    private function render(
        string $view,
        array $data = []
    ): void {
        $data['csrf_token'] = $this->csrfToken->get();

        extract($data);

        ob_start();

        require __DIR__ . "/../../views/{$view}.php";

        $content = ob_get_clean();

        require __DIR__ . '/../../views/layout-admin.php';
    }
}
