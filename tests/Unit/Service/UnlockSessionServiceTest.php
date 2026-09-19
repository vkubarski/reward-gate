<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RewardGate\Repository\CampaignRepositoryInterface;
use RewardGate\Repository\UnlockCompletionRepositoryInterface;
use RewardGate\Repository\UnlockSessionRepositoryInterface;
use RewardGate\Service\UnlockSessionService;
use RuntimeException;

final class UnlockSessionServiceTest extends TestCase
{
    public function testStartRejectsMissingCampaign(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Campaign not found.');

        $service->start(1);
    }

    public function testStartRejectsInactiveCampaign(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'draft',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Campaign is not active.');

        $service->start(1);
    }

    public function testStartRejectsUnsupportedUnlockMethod(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'something_else',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported unlock method.');

        $service->start(1);
    }

    public function testStartRejectsInvalidTimerDuration(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Campaign timer duration must be greater than zero.'
        );

        $service->start(1);
    }

    public function testStartRejectsFrequencyLimitViolation(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => 3600,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('countRecentByCampaignAndVisitor')
            ->with(
                1,
                'visitor-123',
                $this->isType('string')
            )
            ->willReturn(1);

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Campaign frequency limit has not elapsed.'
        );

        $service->start(1, 'visitor-123');
    }

    public function testStartCreatesUnlockSession(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                1,
                $this->callback(
                    static function (string $tokenHash): bool {
                        return preg_match(
                            '/^[a-f0-9]{64}$/',
                            $tokenHash
                        ) === 1;
                    }
                ),
                'visitor-123',
                10,
                $this->callback(
                    static function (string $startedAt): bool {
                        return DateTimeImmutable::createFromFormat(
                            'Y-m-d H:i:s',
                            $startedAt
                        ) !== false;
                    }
                ),
                $this->callback(
                    static function (string $expiresAt): bool {
                        return DateTimeImmutable::createFromFormat(
                            'Y-m-d H:i:s',
                            $expiresAt
                        ) !== false;
                    }
                ),
            )
            ->willReturn(42);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $result = $service->start(1, 'visitor-123');

        $this->assertSame(42, $result['id']);
        $this->assertIsString($result['token']);
        $this->assertSame(64, strlen($result['token']));
        $this->assertSame(10, $result['required_duration_seconds']);
        $this->assertIsString($result['expires_at']);
    }

    public function testStartCreatesClickUnlockSession(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'click',
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                1,
                $this->callback(
                    static function (string $tokenHash): bool {
                        return preg_match(
                            '/^[a-f0-9]{64}$/',
                            $tokenHash
                        ) === 1;
                    }
                ),
                'visitor-123',
                0,
                $this->callback(
                    static function (string $startedAt): bool {
                        return DateTimeImmutable::createFromFormat(
                            'Y-m-d H:i:s',
                            $startedAt
                        ) !== false;
                    }
                ),
                $this->callback(
                    static function (string $expiresAt): bool {
                        return DateTimeImmutable::createFromFormat(
                            'Y-m-d H:i:s',
                            $expiresAt
                        ) !== false;
                    }
                ),
            )
            ->willReturn(42);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $result = $service->start(1, 'visitor-123');

        $this->assertSame(42, $result['id']);
        $this->assertIsString($result['token']);
        $this->assertSame(64, strlen($result['token']));
        $this->assertSame(0, $result['required_duration_seconds']);
        $this->assertIsString($result['expires_at']);
    }

    public function testCompleteRejectsEmptyToken(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->never())
            ->method('findByTokenHash');

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unlock token is required.'
        );

        $service->complete('');
    }

    public function testCompleteRejectsInvalidToken(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with(
                $this->callback(
                    static function (string $tokenHash): bool {
                        return preg_match(
                            '/^[a-f0-9]{64}$/',
                            $tokenHash
                        ) === 1;
                    }
                )
            )
            ->willReturn(null);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid unlock session.'
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteRejectsInactiveSession(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with(
                $this->callback(
                    static function (string $tokenHash): bool {
                        return preg_match(
                            '/^[a-f0-9]{64}$/',
                            $tokenHash
                        ) === 1;
                    }
                )
            )
            ->willReturn([
                'id' => 1,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'completed',
                'required_duration_seconds' => 10,
                'started_at' => '2026-01-01 12:00:00',
                'expires_at' => '2026-01-01 12:10:00',
            ]);

        $unlockSessionRepository
            ->expects($this->never())
            ->method('markExpired');

        $unlockSessionRepository
            ->expects($this->never())
            ->method('markCompleted');

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unlock session is no longer active.'
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteExpiresExpiredSession(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with(
                $this->callback(
                    static function (string $tokenHash): bool {
                        return preg_match(
                            '/^[a-f0-9]{64}$/',
                            $tokenHash
                        ) === 1;
                    }
                )
            )
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 10,
                'started_at' => '2026-01-01 12:00:00',
                'expires_at' => '2026-01-01 12:00:01',
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markExpired')
            ->with(42)
            ->willReturn(true);

        $unlockSessionRepository
            ->expects($this->never())
            ->method('markCompleted');

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unlock session has expired.'
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteRejectsBeforeRequiredDurationHasElapsed(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'frequency_limit_seconds' => null,
                ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with(
                $this->callback(
                    static function (string $hash): bool {
                        return preg_match(
                            '/^[a-f0-9]{64}$/',
                            $hash
                        ) === 1;
                    }
                )
            )
            ->willReturn([
                'id' => 1,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 3600,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->never())
            ->method('markCompleted');

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('create');

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Required unlock duration has not elapsed.'
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteAllowsClickUnlockWithoutElapsedDuration(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'click',
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 1,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 3600,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markCompleted')
            ->with(1)
            ->willReturn(true);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                1,
                1,
                'visitor-123',
                $this->isType('string')
            );

        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects($this->once())
            ->method('beginTransaction');

        $pdo
            ->expects($this->once())
            ->method('commit');

        $service = new UnlockSessionService(
            $pdo,
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $service->complete(
            'test-token',
            'visitor-123'
        );
    }

    public function testCompleteRejectsMissingCampaign(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn(null);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with(
                $this->callback(
                    static function (string $tokenHash): bool {
                        return preg_match(
                            '/^[a-f0-9]{64}$/',
                            $tokenHash
                        ) === 1;
                    }
                )
            )
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 10,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->never())
            ->method('markExpired');

        $unlockSessionRepository
            ->expects($this->never())
            ->method('markCompleted');

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('create');

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Campaign associated with unlock session was not found.'
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteSuccessfullyCompletesSessionAndCreatesCompletion(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo
            ->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $pdo
            ->expects($this->never())
            ->method('rollBack');

        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 10,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markCompleted')
            ->with(42)
            ->willReturn(true);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                42,
                1,
                'visitor-123',
                $this->callback(
                    static function (string $completedAt): bool {
                        return DateTimeImmutable::createFromFormat(
                            'Y-m-d H:i:s',
                            $completedAt
                        ) !== false;
                    }
                )
            )
            ->willReturn(1);

        $service = new UnlockSessionService(
            $pdo,
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteChecksFrequencyLimitBeforeCreatingCompletion(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo
            ->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $pdo
            ->expects($this->never())
            ->method('rollBack');

        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => 3600,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 10,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markCompleted')
            ->with(42)
            ->willReturn(true);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('countRecentByCampaignAndVisitor')
            ->with(
                1,
                'visitor-123',
                $this->isType('string')
            )
            ->willReturn(0);

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                42,
                1,
                'visitor-123',
                $this->isType('string')
            )
            ->willReturn(1);

        $service = new UnlockSessionService(
            $pdo,
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteRejectsFrequencyLimitViolationAndRollsBack(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo
            ->expects($this->never())
            ->method('commit');

        $pdo
            ->expects($this->once())
            ->method('rollBack');

        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => 3600,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 10,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markCompleted')
            ->with(42)
            ->willReturn(true);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('countRecentByCampaignAndVisitor')
            ->with(
                1,
                'visitor-123',
                $this->isType('string')
            )
            ->willReturn(1);

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('create');

        $service = new UnlockSessionService(
            $pdo,
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Campaign frequency limit has not elapsed.'
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteSkipsFrequencyLimitForAnonymousVisitor(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo
            ->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $pdo
            ->expects($this->never())
            ->method('rollBack');

        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => 3600,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => null,
                'status' => 'active',
                'required_duration_seconds' => 10,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markCompleted')
            ->with(42)
            ->willReturn(true);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('countRecentByCampaignAndVisitor');

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                42,
                1,
                null,
                $this->isType('string')
            )
            ->willReturn(1);

        $service = new UnlockSessionService(
            $pdo,
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteRollsBackWhenMarkCompletedFails(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo
            ->expects($this->never())
            ->method('commit');

        $pdo
            ->expects($this->once())
            ->method('rollBack');

        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 10,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markCompleted')
            ->with(42)
            ->willReturn(false);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('create');

        $service = new UnlockSessionService(
            $pdo,
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unlock session was already completed.'
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteRollsBackWhenCompletionCreationFails(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo
            ->expects($this->never())
            ->method('commit');

        $pdo
            ->expects($this->once())
            ->method('rollBack');

        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 10,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 10,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markCompleted')
            ->with(42)
            ->willReturn(true);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                42,
                1,
                'visitor-123',
                $this->isType('string')
            )
            ->willThrowException(
                new RuntimeException('Database error.')
            );

        $service = new UnlockSessionService(
            $pdo,
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database error.');

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteSuccessfullyCompletesClickSession(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo
            ->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $pdo
            ->expects($this->never())
            ->method('rollBack');

        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findByIdForUpdate')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'click',
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 0,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 1
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 299
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markCompleted')
            ->with(42)
            ->willReturn(true);

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                42,
                1,
                'visitor-123',
                $this->callback(
                    static function (string $completedAt): bool {
                        return DateTimeImmutable::createFromFormat(
                            'Y-m-d H:i:s',
                            $completedAt
                        ) !== false;
                    }
                )
            )
            ->willReturn(1);

        $service = new UnlockSessionService(
            $pdo,
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testCompleteRejectsExpiredClickSession(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->never())
            ->method('findById');

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 0,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 301
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() - 1
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->once())
            ->method('markExpired')
            ->with(42)
            ->willReturn(true);

        $unlockSessionRepository
            ->expects($this->never())
            ->method('markCompleted');

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('create');

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unlock session has expired.'
        );

        $service->complete('test-token', 'visitor-123');
    }

    public function testStatusReturnsFalseForMissingCampaign(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Campaign not found.');

        $service->status(1, 'visitor-123');
    }

    public function testStatusReturnsFalseForAnonymousVisitor(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'click',
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('countRecentByCampaignAndVisitor');

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('hasCompletionByCampaignAndVisitor');

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->assertFalse(
            $service->status(1)
        );
    }

    public function testStatusReturnsTrueForRecentCompletionWithinFrequencyLimit(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'click',
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => 3600,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('countRecentByCampaignAndVisitor')
            ->with(
                1,
                'visitor-123',
                $this->isType('string')
            )
            ->willReturn(1);

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('hasCompletionByCampaignAndVisitor');

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->assertTrue(
            $service->status(1, 'visitor-123')
        );
    }

    public function testStatusReturnsFalseWhenNoRecentCompletionExists(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'click',
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => 3600,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('countRecentByCampaignAndVisitor')
            ->with(
                1,
                'visitor-123',
                $this->isType('string')
            )
            ->willReturn(0);

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('hasCompletionByCampaignAndVisitor');

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->assertFalse(
            $service->status(1, 'visitor-123')
        );
        }

    public function testStatusReturnsTrueForAnyCompletionWhenFrequencyLimitIsNull(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'click',
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('hasCompletionByCampaignAndVisitor')
            ->with(
                1,
                'visitor-123'
            )
            ->willReturn(true);

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('countRecentByCampaignAndVisitor');

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->assertTrue(
            $service->status(1, 'visitor-123')
        );
    }

    public function testStatusReturnsFalseWhenFrequencyLimitIsNullAndNoCompletionExists(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'status' => 'active',
                'unlock_method' => 'click',
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => null,
            ]);

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->once())
            ->method('hasCompletionByCampaignAndVisitor')
            ->with(
                1,
                'visitor-123'
            )
            ->willReturn(false);

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('countRecentByCampaignAndVisitor');

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->assertFalse(
            $service->status(1, 'visitor-123')
        );
    }

    public function testCompleteRejectsWrongVisitor(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $unlockSessionRepository = $this->createMock(
            UnlockSessionRepositoryInterface::class
        );

        $unlockSessionRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn([
                'id' => 42,
                'campaign_id' => 1,
                'visitor_id' => 'visitor-123',
                'status' => 'active',
                'required_duration_seconds' => 10,
                'started_at' => date(
                    'Y-m-d H:i:s',
                    time() - 60
                ),
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + 300
                ),
            ]);

        $unlockSessionRepository
            ->expects($this->never())
            ->method('markCompleted');

        $unlockCompletionRepository = $this->createMock(
            UnlockCompletionRepositoryInterface::class
        );

        $unlockCompletionRepository
            ->expects($this->never())
            ->method('create');

        $service = new UnlockSessionService(
            $this->createStub(PDO::class),
            $campaignRepository,
            $unlockSessionRepository,
            $unlockCompletionRepository,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unlock session does not belong to this visitor.'
        );

        $service->complete(
            'test-token',
            'visitor-456'
        );
    }
}
