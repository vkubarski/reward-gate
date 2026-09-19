<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration\Service;

use InvalidArgumentException;
use RewardGate\Repository\CampaignRepository;
use RewardGate\Repository\UnlockCompletionRepository;
use RewardGate\Repository\UnlockSessionRepository;
use RewardGate\Service\UnlockSessionService;
use RewardGate\Tests\Integration\UnlockSessionServiceIntegrationTestCase;

final class UnlockSessionServiceIntegrationTest extends UnlockSessionServiceIntegrationTestCase
{
    private CampaignRepository $campaignRepository;

    private UnlockSessionRepository $unlockSessionRepository;

    private UnlockCompletionRepository $unlockCompletionRepository;

    private UnlockSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaignRepository = new CampaignRepository(
            $this->pdo
        );

        $this->unlockSessionRepository =
            new UnlockSessionRepository(
                $this->pdo
            );

        $this->unlockCompletionRepository =
            new UnlockCompletionRepository(
                $this->pdo
            );

        $this->service = new UnlockSessionService(
            $this->pdo,
            $this->campaignRepository,
            $this->unlockSessionRepository,
            $this->unlockCompletionRepository,
        );
    }

    public function testStartAndCompleteCreatesCompletion(): void
    {
        $campaignId = $this->campaignRepository->create(
            'Integration Test Campaign',
            'popup',
            null,
            'active',
            'timer',
            1,
        );

        $started = $this->service->start(
            $campaignId,
            'visitor-123'
        );

        sleep(1);

        $this->service->complete(
            $started['token'],
            'visitor-123'
        );

        $session = $this->pdo->prepare(
            'SELECT *
             FROM unlock_sessions
             WHERE id = :id'
        );

        $session->execute([
            'id' => $started['id'],
        ]);

        $session = $session->fetch();

        $this->assertNotFalse($session);
        $this->assertSame('completed', $session['status']);

        $completion = $this->unlockCompletionRepository
            ->findByUnlockSessionId(
                $started['id']
            );

        $this->assertNotNull($completion);
        $this->assertSame(
            $started['id'],
            (int) $completion['unlock_session_id']
        );
        $this->assertSame(
            $campaignId,
            (int) $completion['campaign_id']
        );
        $this->assertSame(
            'visitor-123',
            $completion['visitor_id']
        );
    }

    public function testCompleteRollsBackWhenFrequencyLimitIsViolated(): void
    {
        $campaignId = $this->campaignRepository->create(
            'Frequency Limit Campaign',
            'popup',
            null,
            'active',
            'timer',
            1,
            'content',
            3600,
        );

        $oldSessionId = $this->unlockSessionRepository->create(
            $campaignId,
            hash('sha256', 'old-session-token'),
            'visitor-123',
            1,
            '2026-01-01 10:00:00',
            '2026-01-01 10:10:00',
        );

        $this->unlockCompletionRepository->create(
            $oldSessionId,
            $campaignId,
            'visitor-123',
            date('Y-m-d H:i:s'),
        );

        $started = $this->service->start(
            $campaignId,
            'different-visitor'
        );

        sleep(1);

        $statement = $this->pdo->prepare(
            'UPDATE unlock_sessions
             SET visitor_id = :visitor_id
             WHERE id = :id'
        );

        $statement->execute([
            'visitor_id' => 'visitor-123',
            'id' => $started['id'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Campaign frequency limit has not elapsed.'
        );

        try {
            $this->service->complete(
                $started['token'],
                'visitor-123'
            );
        } finally {
            $session = $this->pdo->prepare(
                'SELECT status
                 FROM unlock_sessions
                 WHERE id = :id'
            );

            $session->execute([
                'id' => $started['id'],
            ]);

            $status = $session->fetchColumn();

            $this->assertSame('active', $status);

            $completion = $this->unlockCompletionRepository
                ->findByUnlockSessionId(
                    $started['id']
                );

            $this->assertNull($completion);
        }
    }

    public function testStartRejectsFrequencyLimitViolation(): void
    {
        $campaignId = $this->campaignRepository->create(
            'Start Frequency Limit Campaign',
            'popup',
            null,
            'active',
            'timer',
            1,
            'content',
            3600,
        );

        $oldSessionId = $this->unlockSessionRepository->create(
            $campaignId,
            hash('sha256', 'old-session-token'),
            'visitor-123',
            1,
            '2026-01-01 10:00:00',
            '2026-01-01 10:10:00',
        );

        $this->unlockCompletionRepository->create(
            $oldSessionId,
            $campaignId,
            'visitor-123',
            date('Y-m-d H:i:s'),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Campaign frequency limit has not elapsed.'
        );

        $this->service->start(
            $campaignId,
            'visitor-123'
        );
    }

    public function testCompleteAllowsCompletionAfterFrequencyLimitHasElapsed(): void
    {
        $campaignId = $this->campaignRepository->create(
            'Expired Frequency Limit Campaign',
            'popup',
            null,
            'active',
            'timer',
            1,
            'content',
            3600,
        );

        $oldSessionId = $this->unlockSessionRepository->create(
            $campaignId,
            hash('sha256', 'old-session-token'),
            'visitor-123',
            1,
            '2026-08-20 10:00:00',
            '2026-08-20 10:10:00',
        );

        $this->unlockCompletionRepository->create(
            $oldSessionId,
            $campaignId,
            'visitor-123',
            '2026-08-20 10:30:00',
        );

        $started = $this->service->start(
            $campaignId,
            'visitor-123'
        );

        sleep(1);

        $this->service->complete(
            $started['token'],
            'visitor-123'
        );

        $session = $this->unlockSessionRepository->findByTokenHash(
            hash('sha256', $started['token'])
        );

        $this->assertNotNull($session);
        $this->assertSame(
            'completed',
            $session['status']
        );

        $completion = $this->unlockCompletionRepository
            ->findByUnlockSessionId(
                $started['id']
            );

        $this->assertNotNull($completion);
        $this->assertSame(
            'visitor-123',
            $completion['visitor_id']
        );
    }

    public function testCompleteRejectsWhenCampaignIsPaused(): void
    {
        $campaignId = $this->campaignRepository->create(
            'Paused Campaign',
            'popup',
            null,
            'active',
            'timer',
            1,
        );

        $started = $this->service->start(
            $campaignId,
            'visitor-123'
        );

        $statement = $this->pdo->prepare(
            'UPDATE unlock_sessions
             SET started_at = :started_at
             WHERE id = :id'
        );

        $statement->execute([
            'started_at' => date(
                'Y-m-d H:i:s',
                time() - 2
            ),
            'id' => $started['id'],
        ]);

        $statement = $this->pdo->prepare(
            'UPDATE campaigns
             SET status = :status
             WHERE id = :id'
        );

        $statement->execute([
            'status' => 'paused',
            'id' => $campaignId,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Campaign is not active.'
        );

        try {
            $this->service->complete(
                $started['token'],
                'visitor-123'
            );
        } finally {
            $session = $this->unlockSessionRepository
                ->findByTokenHash(
                    hash('sha256', $started['token'])
                );

            $this->assertNotNull($session);
            $this->assertSame(
                'active',
                $session['status']
            );

            $completion = $this->unlockCompletionRepository
                ->findByUnlockSessionId(
                    $started['id']
                );

            $this->assertNull($completion);
        }
    }

    public function testCompleteRejectsWhenCampaignIsArchived(): void
    {
        $campaignId = $this->campaignRepository->create(
            'Archived Campaign',
            'popup',
            null,
            'active',
            'timer',
            1,
        );

        $started = $this->service->start(
            $campaignId,
            'visitor-123'
        );

        $statement = $this->pdo->prepare(
            'UPDATE unlock_sessions
             SET started_at = :started_at
             WHERE id = :id'
        );

        $statement->execute([
            'started_at' => date(
                'Y-m-d H:i:s',
                time() - 2
            ),
            'id' => $started['id'],
        ]);

        $statement = $this->pdo->prepare(
            'UPDATE campaigns
             SET status = :status
             WHERE id = :id'
        );

        $statement->execute([
            'status' => 'archived',
            'id' => $campaignId,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Campaign is not active.'
        );

        try {
            $this->service->complete(
                $started['token'],
                'visitor-123'
            );
        } finally {
            $session = $this->unlockSessionRepository
                ->findByTokenHash(
                    hash('sha256', $started['token'])
                );

            $this->assertNotNull($session);
            $this->assertSame(
                'active',
                $session['status']
            );

            $completion = $this->unlockCompletionRepository
                ->findByUnlockSessionId(
                    $started['id']
                );

            $this->assertNull($completion);
        }
    }

    public function testStatusRemainsUnlockedAfterCampaignIsPaused(): void
    {
        $campaignId = $this->campaignRepository->create(
            'Paused After Completion Campaign',
            'popup',
            null,
            'active',
            'timer',
            1,
            'content',
            3600,
        );

        $started = $this->service->start(
            $campaignId,
            'visitor-123'
        );

        sleep(1);

        $this->service->complete(
            $started['token'],
            'visitor-123'
        );

        $statement = $this->pdo->prepare(
            'UPDATE campaigns
             SET status = :status
             WHERE id = :id'
        );

        $statement->execute([
            'status' => 'paused',
            'id' => $campaignId,
        ]);

        $this->assertTrue(
            $this->service->status(
                $campaignId,
                'visitor-123'
            )
        );
    }
}
