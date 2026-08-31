<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration\Repository;

use RewardGate\Repository\CampaignRepository;
use RewardGate\Repository\UnlockCompletionRepository;
use RewardGate\Repository\UnlockSessionRepository;
use RewardGate\Tests\Integration\IntegrationTestCase;

final class UnlockCompletionRepositoryTest extends IntegrationTestCase
{
    private function createCampaign(
        string $name = 'Test Campaign'
    ): int {
        $repository = new CampaignRepository($this->pdo);

        return $repository->create(
            $name,
            'popup'
        );
    }

    private function createUnlockSession(
        int $campaignId,
        string $visitorId = 'visitor-123'
    ): int {
        $repository = new UnlockSessionRepository($this->pdo);

        return $repository->create(
            $campaignId,
            hash(
                'sha256',
                uniqid('test-token-', true)
            ),
            $visitorId,
            10,
            '2026-08-20 11:59:00',
            '2026-08-20 12:10:00'
        );
    }

    public function testCreateInsertsCompletionAndReturnsId(): void
    {
        $campaignId = $this->createCampaign();

        $unlockSessionId = $this->createUnlockSession(
            $campaignId
        );

        $repository = new UnlockCompletionRepository($this->pdo);

        $id = $repository->create(
            $unlockSessionId,
            $campaignId,
            'visitor-123',
            '2026-08-20 12:00:10'
        );

        $this->assertGreaterThan(0, $id);

        $completion = $repository->findByUnlockSessionId(
            $unlockSessionId
        );

        $this->assertNotNull($completion);
        $this->assertSame($id, $completion['id']);
        $this->assertSame(
            $unlockSessionId,
            $completion['unlock_session_id']
        );
        $this->assertSame(
            $campaignId,
            $completion['campaign_id']
        );
        $this->assertSame(
            'visitor-123',
            $completion['visitor_id']
        );
        $this->assertSame(
            '2026-08-20 12:00:10',
            $completion['completed_at']
        );
    }

    public function testCreateAllowsNullVisitorId(): void
    {
        $campaignId = $this->createCampaign();

        $unlockSessionId = $this->createUnlockSession(
            $campaignId
        );

        $repository = new UnlockCompletionRepository($this->pdo);

        $id = $repository->create(
            $unlockSessionId,
            $campaignId,
            null,
            '2026-08-20 12:00:10'
        );

        $completion = $repository->findByUnlockSessionId(
            $unlockSessionId
        );

        $this->assertGreaterThan(0, $id);
        $this->assertNotNull($completion);
        $this->assertNull($completion['visitor_id']);
    }

    public function testFindByUnlockSessionIdReturnsNullForMissingCompletion(): void
    {
        $repository = new UnlockCompletionRepository($this->pdo);

        $completion = $repository->findByUnlockSessionId(
            999999999
        );

        $this->assertNull($completion);
    }

    public function testCountRecentByCampaignAndVisitorCountsOnlyMatchingCompletions(): void
    {
        $repository = new UnlockCompletionRepository($this->pdo);

        $campaignId = $this->createCampaign(
            'Campaign One'
        );

        $secondCampaignId = $this->createCampaign(
            'Campaign Two'
        );

        $firstSessionId = $this->createUnlockSession(
            $campaignId
        );

        $secondSessionId = $this->createUnlockSession(
            $campaignId
        );

        $thirdSessionId = $this->createUnlockSession(
            $campaignId,
            'another-visitor'
        );

        $fourthSessionId = $this->createUnlockSession(
            $secondCampaignId
        );

        $repository->create(
            $firstSessionId,
            $campaignId,
            'visitor-123',
            '2026-08-20 12:00:00'
        );

        $repository->create(
            $secondSessionId,
            $campaignId,
            'visitor-123',
            '2026-08-20 12:30:00'
        );

        $repository->create(
            $thirdSessionId,
            $campaignId,
            'another-visitor',
            '2026-08-20 12:30:00'
        );

        $repository->create(
            $fourthSessionId,
            $secondCampaignId,
            'visitor-123',
            '2026-08-20 12:30:00'
        );

        $count = $repository->countRecentByCampaignAndVisitor(
            $campaignId,
            'visitor-123',
            '2026-08-20 11:00:00'
        );

        $this->assertSame(2, $count);
    }

    public function testCountRecentByCampaignAndVisitorExcludesCompletionsAtCutoff(): void
    {
        $repository = new UnlockCompletionRepository($this->pdo);

        $campaignId = $this->createCampaign();

        $firstSessionId = $this->createUnlockSession(
            $campaignId
        );

        $secondSessionId = $this->createUnlockSession(
            $campaignId
        );

        $repository->create(
            $firstSessionId,
            $campaignId,
            'visitor-123',
            '2026-08-20 12:00:00'
        );

        $repository->create(
            $secondSessionId,
            $campaignId,
            'visitor-123',
            '2026-08-20 12:00:01'
        );

        $count = $repository->countRecentByCampaignAndVisitor(
            $campaignId,
            'visitor-123',
            '2026-08-20 12:00:00'
        );

        $this->assertSame(1, $count);
    }

    public function testCountRecentByCampaignAndVisitorReturnsZeroWhenNothingMatches(): void
    {
        $repository = new UnlockCompletionRepository($this->pdo);

        $campaignId = $this->createCampaign();

        $sessionId = $this->createUnlockSession(
            $campaignId
        );

        $repository->create(
            $sessionId,
            $campaignId,
            'visitor-123',
            '2026-08-20 12:00:00'
        );

        $count = $repository->countRecentByCampaignAndVisitor(
            $campaignId,
            'different-visitor',
            '2026-08-20 11:00:00'
        );

        $this->assertSame(0, $count);
    }
}
