<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration\Repository;

use RewardGate\Repository\CampaignRepository;
use RewardGate\Repository\GateImpressionRepository;
use RewardGate\Tests\Integration\IntegrationTestCase;

final class GateImpressionRepositoryTest extends IntegrationTestCase
{
    private function createCampaign(): int
    {
        $repository = new CampaignRepository($this->pdo);

        return $repository->create(
            'Analytics Test Campaign',
            'popup',
        );
    }

    public function testRecordInsertsImpression(): void
    {
        $campaignId = $this->createCampaign();
        $repository = new GateImpressionRepository($this->pdo);

        $repository->record(
            $campaignId,
            str_repeat('a', 64),
            '550e8400-e29b-41d4-a716-446655440000',
            'popup',
            'timer',
        );

        $statement = $this->pdo->prepare(
            'SELECT campaign_id, visitor_id, page_view_id,
                    presentation_type, unlock_method
             FROM gate_impressions
             WHERE campaign_id = :campaign_id',
        );

        $statement->execute([
            'campaign_id' => $campaignId,
        ]);

        $impression = $statement->fetch(\PDO::FETCH_ASSOC);

        $this->assertIsArray($impression);
        $this->assertSame($campaignId, (int) $impression['campaign_id']);
        $this->assertSame(str_repeat('a', 64), $impression['visitor_id']);
        $this->assertSame(
            '550e8400-e29b-41d4-a716-446655440000',
            $impression['page_view_id'],
        );
        $this->assertSame('popup', $impression['presentation_type']);
        $this->assertSame('timer', $impression['unlock_method']);
    }

    public function testRecordDoesNotDuplicateSamePageView(): void
    {
        $campaignId = $this->createCampaign();
        $repository = new GateImpressionRepository($this->pdo);

        $visitorId = str_repeat('b', 64);
        $pageViewId = '550e8400-e29b-41d4-a716-446655440000';

        $repository->record(
            $campaignId,
            $visitorId,
            $pageViewId,
            'popup',
            'timer',
        );

        $repository->record(
            $campaignId,
            $visitorId,
            $pageViewId,
            'popup',
            'timer',
        );

        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM gate_impressions
             WHERE campaign_id = :campaign_id
               AND visitor_id = :visitor_id
               AND page_view_id = :page_view_id',
        );

        $statement->execute([
            'campaign_id' => $campaignId,
            'visitor_id' => $visitorId,
            'page_view_id' => $pageViewId,
        ]);

        $this->assertSame(1, (int) $statement->fetchColumn());
    }

    public function testRecordAllowsDifferentPageViews(): void
    {
        $campaignId = $this->createCampaign();
        $repository = new GateImpressionRepository($this->pdo);

        $visitorId = str_repeat('c', 64);

        $repository->record(
            $campaignId,
            $visitorId,
            '550e8400-e29b-41d4-a716-446655440000',
            'popup',
            'timer',
        );

        $repository->record(
            $campaignId,
            $visitorId,
            '550e8400-e29b-41d4-a716-446655440001',
            'popup',
            'timer',
        );

        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM gate_impressions
             WHERE campaign_id = :campaign_id
               AND visitor_id = :visitor_id',
        );

        $statement->execute([
            'campaign_id' => $campaignId,
            'visitor_id' => $visitorId,
        ]);

        $this->assertSame(2, (int) $statement->fetchColumn());
    }
}
