<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration\Repository;

use RewardGate\Repository\CampaignRepository;
use RewardGate\Repository\UnlockSessionRepository;
use RewardGate\Tests\Integration\IntegrationTestCase;

final class UnlockSessionRepositoryTest extends IntegrationTestCase
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

    private function createSession(
        int $campaignId,
        string $token,
        ?string $visitorId = 'visitor-123'
    ): int {
        $repository = new UnlockSessionRepository($this->pdo);

        return $repository->create(
            $campaignId,
            hash('sha256', $token),
            $visitorId,
            10,
            '2026-08-20 12:00:00',
            '2026-08-20 12:00:10'
        );
    }

    public function testCreateInsertsSessionAndReturnsId(): void
    {
        $campaignId = $this->createCampaign();

        $repository = new UnlockSessionRepository($this->pdo);

        $token = 'test-token';
        $tokenHash = hash('sha256', $token);

        $id = $repository->create(
            $campaignId,
            $tokenHash,
            'visitor-123',
            10,
            '2026-08-20 12:00:00',
            '2026-08-20 12:00:10'
        );

        $this->assertGreaterThan(0, $id);

        $session = $repository->findByTokenHash($tokenHash);

        $this->assertNotNull($session);
        $this->assertSame($id, $session['id']);
        $this->assertSame(
            $campaignId,
            $session['campaign_id']
        );
        $this->assertSame(
            $tokenHash,
            $session['token_hash']
        );
        $this->assertSame(
            'visitor-123',
            $session['visitor_id']
        );
        $this->assertSame(
            'active',
            $session['status']
        );
        $this->assertSame(
            10,
            $session['required_duration_seconds']
        );
        $this->assertSame(
            '2026-08-20 12:00:00',
            $session['started_at']
        );
        $this->assertSame(
            '2026-08-20 12:00:10',
            $session['expires_at']
        );
    }

    public function testCreateAllowsNullVisitorId(): void
    {
        $campaignId = $this->createCampaign();

        $repository = new UnlockSessionRepository($this->pdo);

        $token = 'anonymous-token';
        $tokenHash = hash('sha256', $token);

        $id = $repository->create(
            $campaignId,
            $tokenHash,
            null,
            10,
            '2026-08-20 12:00:00',
            '2026-08-20 12:00:10'
        );

        $session = $repository->findByTokenHash($tokenHash);

        $this->assertGreaterThan(0, $id);
        $this->assertNotNull($session);
        $this->assertNull($session['visitor_id']);
    }

    public function testFindByTokenHashReturnsNullForMissingSession(): void
    {
        $repository = new UnlockSessionRepository($this->pdo);

        $session = $repository->findByTokenHash(
            hash('sha256', 'nonexistent-token')
        );

        $this->assertNull($session);
    }

    public function testMarkCompletedChangesActiveSessionToCompleted(): void
    {
        $campaignId = $this->createCampaign();

        $repository = new UnlockSessionRepository($this->pdo);

        $token = 'complete-token';
        $tokenHash = hash('sha256', $token);

        $id = $repository->create(
            $campaignId,
            $tokenHash,
            'visitor-123',
            10,
            '2026-08-20 12:00:00',
            '2026-08-20 12:00:10'
        );

        $result = $repository->markCompleted($id);

        $this->assertTrue($result);

        $session = $repository->findByTokenHash($tokenHash);

        $this->assertNotNull($session);
        $this->assertSame('completed', $session['status']);
    }

    public function testMarkCompletedReturnsFalseForAlreadyCompletedSession(): void
    {
        $campaignId = $this->createCampaign();

        $repository = new UnlockSessionRepository($this->pdo);

        $token = 'already-completed-token';
        $tokenHash = hash('sha256', $token);

        $id = $repository->create(
            $campaignId,
            $tokenHash,
            'visitor-123',
            10,
            '2026-08-20 12:00:00',
            '2026-08-20 12:00:10'
        );

        $this->assertTrue(
            $repository->markCompleted($id)
        );

        $this->assertFalse(
            $repository->markCompleted($id)
        );
    }

    public function testMarkCompletedReturnsFalseForExpiredSession(): void
    {
        $campaignId = $this->createCampaign();

        $repository = new UnlockSessionRepository($this->pdo);

        $token = 'expired-token';
        $tokenHash = hash('sha256', $token);

        $id = $repository->create(
            $campaignId,
            $tokenHash,
            'visitor-123',
            10,
            '2026-08-20 12:00:00',
            '2026-08-20 12:00:10'
        );

        $this->assertTrue(
            $repository->markExpired($id)
        );

        $this->assertFalse(
            $repository->markCompleted($id)
        );

        $session = $repository->findByTokenHash($tokenHash);

        $this->assertNotNull($session);
        $this->assertSame('expired', $session['status']);
    }

    public function testMarkExpiredChangesActiveSessionToExpired(): void
    {
        $campaignId = $this->createCampaign();

        $repository = new UnlockSessionRepository($this->pdo);

        $token = 'expire-token';
        $tokenHash = hash('sha256', $token);

        $id = $repository->create(
            $campaignId,
            $tokenHash,
            'visitor-123',
            10,
            '2026-08-20 12:00:00',
            '2026-08-20 12:00:10'
        );

        $result = $repository->markExpired($id);

        $this->assertTrue($result);

        $session = $repository->findByTokenHash($tokenHash);

        $this->assertNotNull($session);
        $this->assertSame('expired', $session['status']);
    }

    public function testMarkExpiredReturnsFalseForAlreadyExpiredSession(): void
    {
        $campaignId = $this->createCampaign();

        $repository = new UnlockSessionRepository($this->pdo);

        $token = 'already-expired-token';
        $tokenHash = hash('sha256', $token);

        $id = $repository->create(
            $campaignId,
            $tokenHash,
            'visitor-123',
            10,
            '2026-08-20 12:00:00',
            '2026-08-20 12:00:10'
        );

        $this->assertTrue(
            $repository->markExpired($id)
        );

        $this->assertFalse(
            $repository->markExpired($id)
        );
    }

    public function testMarkExpiredReturnsFalseForCompletedSession(): void
    {
        $campaignId = $this->createCampaign();

        $repository = new UnlockSessionRepository($this->pdo);

        $token = 'completed-expire-token';
        $tokenHash = hash('sha256', $token);

        $id = $repository->create(
            $campaignId,
            $tokenHash,
            'visitor-123',
            10,
            '2026-08-20 12:00:00',
            '2026-08-20 12:00:10'
        );

        $this->assertTrue(
            $repository->markCompleted($id)
        );

        $this->assertFalse(
            $repository->markExpired($id)
        );
    }

    public function testMarkCompletedReturnsFalseForMissingSession(): void
    {
        $repository = new UnlockSessionRepository($this->pdo);

        $result = $repository->markCompleted(999999999);

        $this->assertFalse($result);
    }

    public function testMarkExpiredReturnsFalseForMissingSession(): void
    {
        $repository = new UnlockSessionRepository($this->pdo);

        $result = $repository->markExpired(999999999);

        $this->assertFalse($result);
    }
}
