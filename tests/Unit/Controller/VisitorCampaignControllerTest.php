<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use RewardGate\Controller\VisitorCampaignController;
use RewardGate\Service\CampaignServiceInterface;
use Throwable;

final class VisitorCampaignControllerTest extends TestCase
{
    private function createController(
        CampaignServiceInterface $campaignService
    ): VisitorCampaignController {
        return new VisitorCampaignController(
            $campaignService
        );
    }

    private function captureResponse(callable $callback): array
    {
        http_response_code(200);
        ob_start();

        try {
            $callback();
            $body = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($body);

        return [
                'status' => http_response_code(),
                'body' => json_decode(
                    $body,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ),
        ];
    }

    public function testShowReturnsActiveCampaign(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
                ->expects($this->once())
                ->method('findById')
                ->with(42)
                ->willReturn([
                        'id' => 42,
                        'status' => 'active',
                        'presentation_type' => 'popup',
                        'presentation_settings' => [
                                'title' => 'Unlock this content',
                        ],
                        'unlock_method' => 'timer',
                        'timer_duration_seconds' => 10,
                        'reward_type' => 'content',
                ]);

        $controller = $this->createController(
            $campaignService
        );

        $response = $this->captureResponse(
            fn () => $controller->show(['id' => 42])
        );

        $this->assertSame(200, $response['status']);

        $this->assertSame([
                'success' => true,
                'campaign' => [
                        'id' => 42,
                        'presentation_type' => 'popup',
                        'presentation_settings' => [
                                'title' => 'Unlock this content',
                        ],
                        'unlock_method' => 'timer',
                        'timer_duration_seconds' => 10,
                        'reward_type' => 'content',
                ],
        ], $response['body']);
    }

    public function testShowCastsCampaignIdToInteger(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
                ->expects($this->once())
                ->method('findById')
                ->with(42)
                ->willReturn([
                        'id' => '42',
                        'status' => 'active',
                        'presentation_type' => 'popup',
                        'presentation_settings' => null,
                        'unlock_method' => 'timer',
                        'timer_duration_seconds' => '10',
                        'reward_type' => 'content',
                ]);

        $controller = $this->createController(
            $campaignService
        );

        $response = $this->captureResponse(
            fn () => $controller->show(['id' => '42'])
        );

        $this->assertSame(200, $response['status']);

        $this->assertSame(42, $response['body']['campaign']['id']);
        $this->assertIsInt(
            $response['body']['campaign']['id']
        );

        $this->assertSame(
            10,
            $response['body']['campaign'][
                        'timer_duration_seconds'
                ]
        );

        $this->assertIsInt(
            $response['body']['campaign'][
                        'timer_duration_seconds'
                ]
        );
    }

    public function testShowReturnsNotFoundForMissingCampaign(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
                ->expects($this->once())
                ->method('findById')
                ->with(42)
                ->willReturn(null);

        $controller = $this->createController(
            $campaignService
        );

        $response = $this->captureResponse(
            fn () => $controller->show(['id' => 42])
        );

        $this->assertSame(404, $response['status']);

        $this->assertSame([
                'success' => false,
                'error' => 'Campaign not found.',
        ], $response['body']);
    }

    public function testShowReturnsNotFoundForInactiveCampaign(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
                ->expects($this->once())
                ->method('findById')
                ->with(42)
                ->willReturn([
                        'id' => 42,
                        'status' => 'draft',
                        'presentation_type' => 'popup',
                        'presentation_settings' => null,
                        'unlock_method' => 'timer',
                        'timer_duration_seconds' => 10,
                        'reward_type' => 'content',
                ]);

        $controller = $this->createController(
            $campaignService
        );

        $response = $this->captureResponse(
            fn () => $controller->show(['id' => 42])
        );

        $this->assertSame(404, $response['status']);

        $this->assertSame([
                'success' => false,
                'error' => 'Campaign not found.',
        ], $response['body']);
    }
}
