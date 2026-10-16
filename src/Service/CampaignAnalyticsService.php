<?php

declare(strict_types=1);

namespace RewardGate\Service;

use DateTimeImmutable;
use DateTimeZone;
use RewardGate\Repository\CampaignAnalyticsRepositoryInterface;

final class CampaignAnalyticsService implements CampaignAnalyticsServiceInterface
{
    private const MINIMUM_SAMPLE_SIZE = 20;

    public function __construct(
        private CampaignAnalyticsRepositoryInterface $repository
    ) {
    }

    public function getReport(
        int $campaignId,
        DateTimeImmutable $referenceTime,
    ): array {
        $utc = new DateTimeZone('UTC');

        $tomorrow = $referenceTime
            ->setTimezone($utc)
            ->setTime(0, 0)
            ->modify('+1 day');

        $periods = [
            '7_days' => $tomorrow->modify('-7 days'),
            '30_days' => $tomorrow->modify('-30 days'),
        ];

        $report = [];

        foreach ($periods as $period => $start) {
            $end = $tomorrow;

            $counts = $this->repository->getCounts(
                $campaignId,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
            );

            $report[$period] = [
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
                ...$counts,
                'unlock_rate' => $this->calculateRate(
                    $counts['completed'],
                    $counts['shown'],
                ),
                'timer_finished_wait_rate' => $this->calculateRate(
                    $counts['timer_completed'],
                    $counts['timer_sessions'],
                ),
            ];
        }

        return $report;
    }

    private function calculateRate(
        int $numerator,
        int $denominator,
    ): ?float {
        if (
            $denominator < self::MINIMUM_SAMPLE_SIZE
            || $numerator > $denominator
        ) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 1);
    }
}
