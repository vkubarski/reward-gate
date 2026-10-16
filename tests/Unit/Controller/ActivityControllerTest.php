<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use RewardGate\Controller\ActivityController;
use RewardGate\Repository\GateImpressionRepositoryInterface;
use RewardGate\Security\VisitorIdInterface;
use RewardGate\Service\CampaignServiceInterface;

final class ActivityControllerTest extends TestCase
{
    private const CAMPAIGN_ID = 7;

    private const PAGE_VIEW_ID = '550e8400-e29b-41d4-a716-446655440000';

    private const VISITOR_ID = 'aabbccdd';

    private function createController(
        CampaignServiceInterface $campaignService,
        GateImpressionRepositoryInterface $impressionRepository,
        VisitorIdInterface $visitorId,
        string $requestBody,
    ): ActivityController {
        return new ActivityController(
            $campaignService,
            $impressionRepository,
            $visitorId,
            static fn (): string => $requestBody,
        );
    }

    private function captureResponse(callable $callback): array
    {
        http_response_code(200);

        ob_start();

        try {
            $callback();
            $body = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        return [
            'status' => http_response_code(),
            'body' => $body,
        ];
    }

    private function activeCampaign(): array
    {
        return [
            'id' => self::CAMPAIGN_ID,
            'status' => 'active',
            'presentation_type' => 'popup',
            'unlock_method' => 'timer',
        ];
    }

    public function testRecordsImpressionUsingServerSideCampaignSettings(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $impressionRepository = $this->createMock(
            GateImpressionRepositoryInterface::class,
        );
        $visitorId = $this->createMock(VisitorIdInterface::class);

        $campaignService
            ->expects(self::once())
            ->method('findById')
            ->with(self::CAMPAIGN_ID)
            ->willReturn($this->activeCampaign());

        $visitorId
            ->expects(self::once())
            ->method('get')
            ->willReturn(self::VISITOR_ID);

        $impressionRepository
            ->expects(self::once())
            ->method('record')
            ->with(
                self::CAMPAIGN_ID,
                self::VISITOR_ID,
                self::PAGE_VIEW_ID,
                'popup',
                'timer',
            );

        $controller = $this->createController(
            $campaignService,
            $impressionRepository,
            $visitorId,
            json_encode(['page_view_id' => self::PAGE_VIEW_ID]),
        );

        $response = $this->captureResponse(
            fn () => $controller->record(['id' => (string) self::CAMPAIGN_ID]),
        );

        self::assertSame(204, $response['status']);
        self::assertSame('', $response['body']);
    }

    public function testReturns400ForMalformedJson(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $impressionRepository = $this->createMock(
            GateImpressionRepositoryInterface::class,
        );
        $visitorId = $this->createMock(VisitorIdInterface::class);

        $campaignService
            ->expects(self::never())
            ->method('findById');

        $impressionRepository
            ->expects(self::never())
            ->method('record');

        $controller = $this->createController(
            $campaignService,
            $impressionRepository,
            $visitorId,
            '{"page_view_id":',
        );

        $response = $this->captureResponse(
            fn () => $controller->record(['id' => (string) self::CAMPAIGN_ID]),
        );

        self::assertSame(400, $response['status']);
        self::assertSame(
            ['error' => 'Invalid JSON body.'],
            json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testReturns400ForInvalidPageViewId(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $impressionRepository = $this->createMock(
            GateImpressionRepositoryInterface::class,
        );
        $visitorId = $this->createMock(VisitorIdInterface::class);

        $campaignService
            ->expects(self::never())
            ->method('findById');

        $impressionRepository
            ->expects(self::never())
            ->method('record');

        $controller = $this->createController(
            $campaignService,
            $impressionRepository,
            $visitorId,
            json_encode(['page_view_id' => 'not-a-uuid']),
        );

        $response = $this->captureResponse(
            fn () => $controller->record(['id' => (string) self::CAMPAIGN_ID]),
        );

        self::assertSame(400, $response['status']);
        self::assertSame(
            ['error' => 'Invalid page_view_id.'],
            json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testReturns404ForMissingCampaign(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $impressionRepository = $this->createMock(
            GateImpressionRepositoryInterface::class,
        );
        $visitorId = $this->createMock(VisitorIdInterface::class);

        $campaignService
            ->expects(self::once())
            ->method('findById')
            ->with(self::CAMPAIGN_ID)
            ->willReturn(null);

        $impressionRepository
            ->expects(self::never())
            ->method('record');

        $controller = $this->createController(
            $campaignService,
            $impressionRepository,
            $visitorId,
            json_encode(['page_view_id' => self::PAGE_VIEW_ID]),
        );

        $response = $this->captureResponse(
            fn () => $controller->record(['id' => (string) self::CAMPAIGN_ID]),
        );

        self::assertSame(404, $response['status']);
        self::assertSame(
            ['error' => 'Campaign not found.'],
            json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testReturns404ForInactiveCampaign(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $impressionRepository = $this->createMock(
            GateImpressionRepositoryInterface::class,
        );
        $visitorId = $this->createMock(VisitorIdInterface::class);

        $campaign = $this->activeCampaign();
        $campaign['status'] = 'draft';

        $campaignService
            ->expects(self::once())
            ->method('findById')
            ->with(self::CAMPAIGN_ID)
            ->willReturn($campaign);

        $impressionRepository
            ->expects(self::never())
            ->method('record');

        $controller = $this->createController(
            $campaignService,
            $impressionRepository,
            $visitorId,
            json_encode(['page_view_id' => self::PAGE_VIEW_ID]),
        );

        $response = $this->captureResponse(
            fn () => $controller->record(['id' => (string) self::CAMPAIGN_ID]),
        );

        self::assertSame(404, $response['status']);
        self::assertSame(
            ['error' => 'Campaign not found.'],
            json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testReturns400ForInvalidCampaignId(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $impressionRepository = $this->createMock(
            GateImpressionRepositoryInterface::class,
        );
        $visitorId = $this->createMock(VisitorIdInterface::class);

        $campaignService
            ->expects(self::never())
            ->method('findById');

        $impressionRepository
            ->expects(self::never())
            ->method('record');

        $controller = $this->createController(
            $campaignService,
            $impressionRepository,
            $visitorId,
            json_encode(['page_view_id' => self::PAGE_VIEW_ID]),
        );

        $response = $this->captureResponse(
            fn () => $controller->record(['id' => 'invalid']),
        );

        self::assertSame(400, $response['status']);
        self::assertSame(
            ['error' => 'Invalid campaign ID.'],
            json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR),
        );
    }
}
