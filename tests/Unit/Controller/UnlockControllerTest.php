<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Controller;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RewardGate\Controller\UnlockController;
use RewardGate\Security\VisitorIdInterface;
use RewardGate\Service\UnlockSessionServiceInterface;
use RuntimeException;
use Throwable;

final class UnlockControllerTest extends TestCase
{
    private function createController(
        UnlockSessionServiceInterface $unlockSessionService,
        VisitorIdInterface $visitorId,
        string $requestBody = ''
    ): UnlockController {
        return new UnlockController(
            $unlockSessionService,
            $visitorId,
            static fn (): string => $requestBody
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

    public function testStartReturnsCreatedSession(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->once())
            ->method('start')
            ->with(42, 'visitor-123')
            ->willReturn([
                'id' => 123,
                'token' => 'session-token',
                'required_duration_seconds' => 10,
                'expires_at' => '2026-08-20 12:00:10',
            ]);

        $visitorId = $this->createMock(VisitorIdInterface::class);

        $visitorId
            ->expects($this->once())
            ->method('get')
            ->willReturn('visitor-123');

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId
        );

        $response = $this->captureResponse(
            fn () => $controller->start(['id' => 42])
        );

        $this->assertSame(201, $response['status']);

        $this->assertSame([
            'success' => true,
            'session' => [
                'id' => 123,
                'token' => 'session-token',
                'required_duration_seconds' => 10,
                'expires_at' => '2026-08-20 12:00:10',
            ],
        ], $response['body']);
    }

    public function testStartCastsCampaignIdToInteger(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->once())
            ->method('start')
            ->with(42, 'visitor-123')
            ->willReturn([]);

        $visitorId = $this->createMock(VisitorIdInterface::class);

        $visitorId
            ->expects($this->once())
            ->method('get')
            ->willReturn('visitor-123');

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId
        );

        $response = $this->captureResponse(
            fn () => $controller->start(['id' => '42'])
        );

        $this->assertSame(201, $response['status']);

        $this->assertSame([
            'success' => true,
            'session' => [],
        ], $response['body']);
    }

    public function testStartReturnsBadRequestForInvalidArgument(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->once())
            ->method('start')
            ->with(42, 'visitor-123')
            ->willThrowException(
                new InvalidArgumentException('Campaign is not active.')
            );

        $visitorId = $this->createMock(VisitorIdInterface::class);

        $visitorId
            ->expects($this->once())
            ->method('get')
            ->willReturn('visitor-123');

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId
        );

        $response = $this->captureResponse(
            fn () => $controller->start(['id' => 42])
        );

        $this->assertSame(400, $response['status']);

        $this->assertSame([
            'success' => false,
            'error' => 'Campaign is not active.',
        ], $response['body']);
    }

    public function testStartReturnsInternalServerErrorForUnexpectedException(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->once())
            ->method('start')
            ->with(42, 'visitor-123')
            ->willThrowException(
                new RuntimeException('Database failure.')
            );

        $visitorId = $this->createMock(VisitorIdInterface::class);

        $visitorId
            ->expects($this->once())
            ->method('get')
            ->willReturn('visitor-123');

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId
        );

        $response = $this->captureResponse(
            fn () => $controller->start(['id' => 42])
        );

        $this->assertSame(500, $response['status']);

        $this->assertSame([
            'success' => false,
            'error' => 'Unable to start unlock session.',
        ], $response['body']);
    }

    public function testStartReturnsInternalServerErrorWhenVisitorIdFails(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->never())
            ->method('start');

        $visitorId = $this->createMock(VisitorIdInterface::class);

        $visitorId
            ->expects($this->once())
            ->method('get')
            ->willThrowException(
                new RuntimeException('Unable to determine visitor ID.')
            );

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId
        );

        $response = $this->captureResponse(
            fn () => $controller->start(['id' => 42])
        );

        $this->assertSame(500, $response['status']);

        $this->assertSame([
            'success' => false,
            'error' => 'Unable to start unlock session.',
        ], $response['body']);
    }

    public function testCompleteReturnsSuccess(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->once())
            ->method('complete')
            ->with('session-token');

        $visitorId = $this->createStub(VisitorIdInterface::class);

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId,
            '{"token":"session-token"}'
        );

        $response = $this->captureResponse(
            fn () => $controller->complete()
        );

        $this->assertSame(200, $response['status']);

        $this->assertSame([
            'success' => true,
        ], $response['body']);
    }

    public function testCompleteReturnsBadRequestForInvalidJson(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->never())
            ->method('complete');

        $visitorId = $this->createStub(VisitorIdInterface::class);

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId,
            '{"token":'
        );

        $response = $this->captureResponse(
            fn () => $controller->complete()
        );

        $this->assertSame(400, $response['status']);

        $this->assertSame([
            'success' => false,
            'error' => 'Invalid request body.',
        ], $response['body']);
    }

    public function testCompleteReturnsBadRequestForNonObjectJson(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->never())
            ->method('complete');

        $visitorId = $this->createStub(VisitorIdInterface::class);

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId,
            'null'
        );

        $response = $this->captureResponse(
            fn () => $controller->complete()
        );

        $this->assertSame(400, $response['status']);

        $this->assertSame([
            'success' => false,
            'error' => 'Invalid request body.',
        ], $response['body']);
    }

    public function testCompleteReturnsBadRequestForInvalidArgument(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->once())
            ->method('complete')
            ->with('invalid-token')
            ->willThrowException(
                new InvalidArgumentException(
                    'Invalid unlock session.'
                )
            );

        $visitorId = $this->createStub(VisitorIdInterface::class);

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId,
            '{"token":"invalid-token"}'
        );

        $response = $this->captureResponse(
            fn () => $controller->complete()
        );

        $this->assertSame(400, $response['status']);

        $this->assertSame([
            'success' => false,
            'error' => 'Invalid unlock session.',
        ], $response['body']);
    }

    public function testCompleteReturnsInternalServerErrorForUnexpectedException(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->once())
            ->method('complete')
            ->with('session-token')
            ->willThrowException(
                new RuntimeException('Database failure.')
            );

        $visitorId = $this->createStub(VisitorIdInterface::class);

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId,
            '{"token":"session-token"}'
        );

        $response = $this->captureResponse(
            fn () => $controller->complete()
        );

        $this->assertSame(500, $response['status']);

        $this->assertSame([
            'success' => false,
            'error' => 'Unable to complete unlock session.',
        ], $response['body']);
    }

    public function testCompleteReturnsBadRequestWhenTokenIsMissing(): void
    {
        $unlockSessionService = $this->createMock(
            UnlockSessionServiceInterface::class
        );

        $unlockSessionService
            ->expects($this->once())
            ->method('complete')
            ->with('')
            ->willThrowException(
                new InvalidArgumentException(
                    'Unlock token is required.'
                )
            );

        $visitorId = $this->createStub(VisitorIdInterface::class);

        $controller = $this->createController(
            $unlockSessionService,
            $visitorId,
            '{}'
        );

        $response = $this->captureResponse(
            fn () => $controller->complete()
        );

        $this->assertSame(400, $response['status']);

        $this->assertSame([
            'success' => false,
            'error' => 'Unlock token is required.',
        ], $response['body']);
    }
}
