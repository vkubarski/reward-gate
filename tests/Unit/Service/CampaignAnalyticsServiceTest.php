<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Service;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RewardGate\Repository\CampaignAnalyticsRepositoryInterface;
use RewardGate\Service\CampaignAnalyticsService;

final class CampaignAnalyticsServiceTest extends TestCase
{
    public function testGetReportUsesUtcCalendarWindowsAndCalculatesRates(): void
    {
        $repository = $this->createMock(
            CampaignAnalyticsRepositoryInterface::class
        );

        $calls = [];

        $repository
            ->expects($this->exactly(2))
            ->method('getCounts')
            ->willReturnCallback(
                function (
                    int $campaignId,
                    string $start,
                    string $end
                ) use (&$calls): array {
                    $calls[] = [$campaignId, $start, $end];

                    if ($start === '2026-10-11 00:00:00') {
                        return [
                            'shown' => 40,
                            'sessions' => 30,
                            'completed' => 20,
                            'timer_sessions' => 20,
                            'timer_completed' => 0,
                        ];
                    }

                    return [
                        'shown' => 100,
                        'sessions' => 80,
                        'completed' => 50,
                        'timer_sessions' => 40,
                        'timer_completed' => 30,
                    ];
                }
            );

        $service = new CampaignAnalyticsService($repository);

        $report = $service->getReport(
            42,
            new DateTimeImmutable(
                '2026-10-17 12:00:00',
                new DateTimeZone('Europe/Chisinau'),
            ),
        );

        $this->assertSame([
            [42, '2026-10-11 00:00:00', '2026-10-18 00:00:00'],
            [42, '2026-09-18 00:00:00', '2026-10-18 00:00:00'],
        ], $calls);

        $this->assertSame(
            50.0,
            $report['7_days']['unlock_rate'],
        );

        $this->assertSame(
            0.0,
            $report['7_days']['timer_finished_wait_rate'],
        );

        $this->assertSame(
            50.0,
            $report['30_days']['unlock_rate'],
        );

        $this->assertSame(
            75.0,
            $report['30_days']['timer_finished_wait_rate'],
        );
    }

    public function testGetReportSuppressesRatesForInsufficientOrInvalidCounts(): void
    {
        $repository = $this->createMock(
            CampaignAnalyticsRepositoryInterface::class
        );

        $repository
            ->expects($this->exactly(2))
            ->method('getCounts')
            ->willReturn([
                'shown' => 19,
                'sessions' => 30,
                'completed' => 0,
                'timer_sessions' => 20,
                'timer_completed' => 21,
            ]);

        $service = new CampaignAnalyticsService($repository);

        $report = $service->getReport(
            42,
            new DateTimeImmutable(
                '2026-10-17 12:00:00',
                new DateTimeZone('UTC'),
            ),
        );

        $this->assertNull($report['7_days']['unlock_rate']);
        $this->assertNull(
            $report['7_days']['timer_finished_wait_rate']
        );

        $this->assertNull($report['30_days']['unlock_rate']);
        $this->assertNull(
            $report['30_days']['timer_finished_wait_rate']
        );
    }
}
