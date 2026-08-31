<?php

declare(strict_types=1);

namespace RewardGate\Controller;

use RewardGate\Service\CampaignServiceInterface;

final class VisitorCampaignController
{
    public function __construct(
        private CampaignServiceInterface $campaignService
    ) {
    }

    public function show(array $params): void
    {
        $campaign = $this->campaignService->findById(
            (int)$params['id']
        );

        if ($campaign === null || $campaign['status'] !== 'active') {
            $this->json(
                [
                    'success' => false,
                    'error' => 'Campaign not found.',
                ],
                404
            );

            return;
        }

        $this->json([
            'success' => true,
            'campaign' => [
                'id' => (int)$campaign['id'],
                'presentation_type' => $campaign['presentation_type'],
                'presentation_settings' =>
                    $campaign['presentation_settings'],
                'unlock_method' => $campaign['unlock_method'],
                'timer_duration_seconds' =>
                    (int)$campaign['timer_duration_seconds'],
                'reward_type' => $campaign['reward_type'],
            ],
        ]);
    }

    private function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $data,
            JSON_THROW_ON_ERROR
        );
    }
}
