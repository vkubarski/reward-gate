<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration\Repository;

use RewardGate\Repository\CampaignAnalyticsRepository;
use RewardGate\Repository\CampaignRepository;
use RewardGate\Repository\GateImpressionRepository;
use RewardGate\Repository\UnlockCompletionRepository;
use RewardGate\Repository\UnlockSessionRepository;
use RewardGate\Tests\Integration\IntegrationTestCase;

final class CampaignAnalyticsRepositoryTest extends IntegrationTestCase
{
    private function createCampaign(): int
    {
        $repository = new CampaignRepository($this->pdo);

        return $repository->create(
            'Analytics Test Campaign',
            'popup',
        );
    }

    private function createSession(
        int $campaignId,
        string $token,
        int $requiredDurationSeconds,
        string $startedAt,
    ): int {
        $repository = new UnlockSessionRepository($this->pdo);

        $startedAtDate = new \DateTimeImmutable($startedAt);
        $expiresAt = $startedAtDate
            ->modify('+' . ($requiredDurationSeconds + 300) . ' seconds')
            ->format('Y-m-d H:i:s');

        return $repository->create(
            $campaignId,
            hash('sha256', $token),
            'visitor-' . $token,
            $requiredDurationSeconds,
            $startedAt,
            $expiresAt,
        );
    }

    private function createCompletion(
        int $unlockSessionId,
        int $campaignId,
        string $token,
        string $completedAt,
    ): void {
        $repository = new UnlockCompletionRepository($this->pdo);

        $repository->create(
            $unlockSessionId,
            $campaignId,
            'visitor-' . $token,
            $completedAt,
        );
    }

    public function testGetCountsReturnsCorrectMetricsForDateRange(): void
    {
        $campaignId = $this->createCampaign();

        $start = '2026-10-10 00:00:00';
        $end = '2026-10-17 00:00:00';

        $impressionRepository = new GateImpressionRepository($this->pdo);

        // Two impressions inside the reporting window.
        $impressionRepository->record(
            $campaignId,
            str_repeat('a', 64),
            '550e8400-e29b-41d4-a716-446655440001',
            'popup',
            'timer',
        );

        $impressionRepository->record(
            $campaignId,
            str_repeat('b', 64),
            '550e8400-e29b-41d4-a716-446655440002',
            'popup',
            'click',
        );

        // An impression exactly at the exclusive end boundary.
        $this->pdo->prepare(
            'INSERT INTO gate_impressions (
                campaign_id,
                visitor_id,
                page_view_id,
                presentation_type,
                unlock_method,
                created_at
            ) VALUES (
                :campaign_id,
                :visitor_id,
                :page_view_id,
                :presentation_type,
                :unlock_method,
                :created_at
            )'
        )->execute([
            'campaign_id' => $campaignId,
            'visitor_id' => str_repeat('c', 64),
            'page_view_id' => '550e8400-e29b-41d4-a716-446655440003',
            'presentation_type' => 'popup',
            'unlock_method' => 'timer',
            'created_at' => $end,
        ]);

        // Timer session starting exactly at the inclusive start boundary.
        $timerToken = 'timer-inside';
        $timerSessionId = $this->createSession(
            $campaignId,
            $timerToken,
            10,
            $start,
        );

        // Click session starting inside the reporting window.
        $clickToken = 'click-inside';
        $clickSessionId = $this->createSession(
            $campaignId,
            $clickToken,
            0,
            '2026-10-16 23:59:59',
        );

        // Timer session starting before the reporting window.
        $outsideToken = 'session-outside';
        $outsideSessionId = $this->createSession(
            $campaignId,
            $outsideToken,
            10,
            '2026-10-09 23:59:59',
        );

        // Timer session starting exactly at the exclusive end boundary.
        $endToken = 'session-at-end';
        $this->createSession(
            $campaignId,
            $endToken,
            10,
            $end,
        );

        // Both in-window sessions complete.
        // The timer completion occurs after the reporting window ends.
        $this->createCompletion(
            $timerSessionId,
            $campaignId,
            $timerToken,
            '2026-10-17 00:00:05',
        );

        $this->createCompletion(
            $clickSessionId,
            $campaignId,
            $clickToken,
            '2026-10-16 23:59:59',
        );

        // This completion falls inside the window, but its session started
        // before the window. It must not be counted.
        $this->createCompletion(
            $outsideSessionId,
            $campaignId,
            $outsideToken,
            '2026-10-12 12:00:00',
        );

        $repository = new CampaignAnalyticsRepository($this->pdo);

        $counts = $repository->getCounts(
            $campaignId,
            $start,
            $end,
        );

        $this->assertSame([
            'shown' => 2,
            'sessions' => 2,
            'completed' => 2,
            'timer_sessions' => 1,
            'timer_completed' => 1,
        ], $counts);
    }

    public function testGetCountsReturnsZerosWhenNoRecordsExist(): void
    {
        $campaignId = $this->createCampaign();

        $repository = new CampaignAnalyticsRepository($this->pdo);

        $counts = $repository->getCounts(
            $campaignId,
            '2026-10-10 00:00:00',
            '2026-10-17 00:00:00',
        );

        $this->assertSame([
            'shown' => 0,
            'sessions' => 0,
            'completed' => 0,
            'timer_sessions' => 0,
            'timer_completed' => 0,
        ], $counts);
    }
}
