<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Controller;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RewardGate\Controller\CampaignController;
use RewardGate\Security\CsrfTokenInterface;
use RewardGate\Service\CampaignServiceInterface;
use Throwable;

final class CampaignControllerTest extends TestCase
{
    private function createController(CampaignServiceInterface $campaignService, CsrfTokenInterface $csrfToken): CampaignController
    {
        return new CampaignController($campaignService, $csrfToken);
    } private function captureResponse(callable $callback): array
    {
        http_response_code(200);
        ob_start();
        try {
            $callback();
            $body = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        } $this->assertIsString($body);
        return [ 'status' => http_response_code(), 'body' => $body, ];
    } public function testShowReturnsNotFoundForMissingCampaign(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $campaignService ->expects($this->once()) ->method('findById') ->with(42) ->willReturn(null);
        $csrfToken = $this->createStub(CsrfTokenInterface::class);
        $controller = $this->createController($campaignService, $csrfToken);
        $response = $this->captureResponse(fn () => $controller->show(['id' => '42']));
        $this->assertSame(404, $response['status']);
        $this->assertSame('Campaign not found', $response['body']);
    } public function testStoreReturnsForbiddenForInvalidCsrfToken(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $campaignService ->expects($this->never()) ->method('create');
        $csrfToken = $this->createMock(CsrfTokenInterface::class);
        $csrfToken ->expects($this->once()) ->method('validate') ->with(null) ->willReturn(false);
        $_POST = [];
        $controller = $this->createController($campaignService, $csrfToken);
        $response = $this->captureResponse(fn () => $controller->store());
        $this->assertSame(403, $response['status']);
        $this->assertSame('Invalid CSRF token', $response['body']);
    } public function testStoreDoesNotCreateCampaignWhenNameIsMissing(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $campaignService ->expects($this->never()) ->method('create');
        $csrfToken = $this->createMock(CsrfTokenInterface::class);
        $csrfToken ->expects($this->once()) ->method('validate') ->with('valid-csrf-token') ->willReturn(true);
        $_POST = [ 'csrf_token' => 'valid-csrf-token',
            'name' => '', 'presentation_type' => 'popup',
            'unlock_method' => 'timer',
            'timer_duration_seconds' => '10', ];
        $controller = $this->createController($campaignService, $csrfToken);
        ob_start();
        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        } $this->assertIsString($output);
        $this->assertStringContainsString('Campaign name is required.', $output);
    } public function testStoreDoesNotCreateCampaignWhenTimerDurationIsInvalid(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $campaignService ->expects($this->never()) ->method('create');
        $csrfToken = $this->createMock(CsrfTokenInterface::class);
        $csrfToken ->expects($this->once()) ->method('validate') ->with('valid-csrf-token') ->willReturn(true);
        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Test Campaign',
            'presentation_type' => 'popup',
            'unlock_method' => 'timer',
            'timer_duration_seconds' => '0', ];
        $controller = $this->createController($campaignService, $csrfToken);
        ob_start();
        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        } $this->assertIsString($output);
        $this->assertStringContainsString('Timer duration must be greater than zero.', $output);
    }

    public function testStoreDisplaysServiceValidationError(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $campaignService ->expects($this->once()) ->method('create')
            ->with(
                'Test Campaign',
                'popup',
                [
                    'show_message' => false,
                ],
                'draft',
                'timer',
                10,
                'content',
                null
            )
            ->willThrowException(new InvalidArgumentException('Test validation error'));
        $csrfToken = $this->createMock(CsrfTokenInterface::class);
        $csrfToken ->expects($this->once()) ->method('validate') ->with('valid-csrf-token') ->willReturn(true);
        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Test Campaign',
            'presentation_type' => 'popup',
            'unlock_method' => 'timer',
            'timer_duration_seconds' => '10', ];
        $controller = $this->createController($campaignService, $csrfToken);
        ob_start();
        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        } $this->assertIsString($output);

        $this->assertStringContainsString(
            'Test validation error',
            $output
        );
    }

    public function testStorePassesFalseForUncheckedPopupMessageCheckbox(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->once())
            ->method('create')
            ->with(
                'Test Campaign',
                'popup',
                [
                    'title' => 'Test title',
                    'message' => 'Test message',
                    'show_message' => false,
                    'content' => '<div>Ad</div>',
                ],
                'draft',
                'timer',
                10,
                'content',
                null
            )
            ->willThrowException(
                new InvalidArgumentException('Test validation error')
            );

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Test Campaign',
            'presentation_type' => 'popup',
            'unlock_method' => 'timer',
            'timer_duration_seconds' => '10',
            'presentation_settings' => [
                'title' => 'Test title',
                'message' => 'Test message',
                'content' => '<div>Ad</div>',
            ],
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        ob_start();

        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($output);
        $this->assertStringContainsString(
            'Test validation error',
            $output
        );
    }

    public function testStorePassesFrequencyLimitToService(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->once())
            ->method('create')
            ->with(
                'Test Campaign',
                'popup',
                [
                    'title' => 'Test title',
                    'message' => 'Test message',
                    'show_message' => true,
                    'content' => '<div>Ad</div>',
                ],
                'draft',
                'timer',
                10,
                'content',
                3600
            )
            ->willThrowException(
                new InvalidArgumentException('Test validation error')
            );

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Test Campaign',
            'presentation_type' => 'popup',
            'unlock_method' => 'timer',
            'timer_duration_seconds' => '10',
            'frequency_limit_seconds' => '3600',
            'presentation_settings' => [
                'title' => 'Test title',
                'message' => 'Test message',
                'show_message' => '1',
                'content' => '<div>Ad</div>',
            ],
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        ob_start();

        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($output);
        $this->assertStringContainsString(
            'Test validation error',
            $output
        );
    }

    public function testUpdatePassesFrequencyLimitToService(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->once())
            ->method('update')
            ->with(
                42,
                [
                    'name' => 'Updated Campaign',
                    'presentation_type' => 'popup',
                    'unlock_method' => 'timer',
                    'timer_duration_seconds' => 20,
                    'frequency_limit_seconds' => 3600,
                    'presentation_settings' => [
                        'title' => 'Updated title',
                        'message' => 'Updated message',
                        'show_message' => true,
                        'content' => '<div>Updated Ad</div>',
                    ],
                ]
            )
            ->willThrowException(
                new InvalidArgumentException('Test validation error')
            );

        $campaignService
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn([
                'id' => 42,
                'name' => 'Updated Campaign',
                'status' => 'draft',
                'presentation_type' => 'popup',
                'unlock_method' => 'timer',
                'presentation_settings' => [
                    'title' => 'Updated title',
                    'message' => 'Updated message',
                    'show_message' => true,
                    'content' => '<div>Updated Ad</div>',
                ],
                'timer_duration_seconds' => 20,
                'frequency_limit_seconds' => 3600,
            ]);

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Updated Campaign',
            'presentation_type' => 'popup',
            'unlock_method' => 'timer',
            'timer_duration_seconds' => '20',
            'frequency_limit_seconds' => '3600',
            'presentation_settings' => [
                'title' => 'Updated title',
                'message' => 'Updated message',
                'show_message' => '1',
                'content' => '<div>Updated Ad</div>',
            ],
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        ob_start();

        try {
            $controller->update(['id' => '42']);
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($output);
        $this->assertStringContainsString(
            'Test validation error',
            $output
        );
    }

    public function testUpdatePassesNullForEmptyFrequencyLimit(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->once())
            ->method('update')
            ->with(
                42,
                [
                    'name' => 'Updated Campaign',
                    'presentation_type' => 'popup',
                    'unlock_method' => 'timer',
                    'timer_duration_seconds' => 20,
                    'frequency_limit_seconds' => null,
                    'presentation_settings' => [
                        'title' => 'Updated title',
                        'message' => 'Updated message',
                        'show_message' => true,
                        'content' => '<div>Updated Ad</div>',
                    ],
                ]
            )
            ->willThrowException(
                new InvalidArgumentException('Test validation error')
            );

        $campaignService
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn([
                'id' => 42,
                'name' => 'Updated Campaign',
                'status' => 'draft',
                'presentation_type' => 'popup',
                'unlock_method' => 'timer',
                'presentation_settings' => [
                    'title' => 'Updated title',
                    'message' => 'Updated message',
                    'show_message' => true,
                    'content' => '<div>Updated Ad</div>',
                ],
                'timer_duration_seconds' => 20,
                'frequency_limit_seconds' => 3600,
            ]);

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Updated Campaign',
            'presentation_type' => 'popup',
            'unlock_method' => 'timer',
            'timer_duration_seconds' => '20',
            'frequency_limit_seconds' => '',
            'presentation_settings' => [
                'title' => 'Updated title',
                'message' => 'Updated message',
                'show_message' => '1',
                'content' => '<div>Updated Ad</div>',
            ],
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        ob_start();

        try {
            $controller->update(['id' => '42']);
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($output);
        $this->assertStringContainsString(
            'Test validation error',
            $output
        );
    }

    public function testStorePassesClickUnlockMethodToService(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->once())
            ->method('create')
            ->with(
                'Content Campaign',
                'content',
                [
                    'cta_label' => 'Continue',
                    'destination_url' => 'https://example.com/',
                ],
                'draft',
                'click',
                0,
                'content',
                null
            )
            ->willThrowException(
                new InvalidArgumentException('Test validation error')
            );

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Content Campaign',
            'presentation_type' => 'content',
            'unlock_method' => 'click',
            'timer_duration_seconds' => '0',
            'presentation_settings' => [
                'cta_label' => 'Continue',
                'destination_url' => 'https://example.com/',
            ],
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        ob_start();

        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($output);
        $this->assertStringContainsString(
            'Test validation error',
            $output
        );
    }

    public function testStoreRejectsPopupWithClickUnlockMethod(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->never())
            ->method('create');

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Invalid Campaign',
            'presentation_type' => 'popup',
            'unlock_method' => 'click',
            'timer_duration_seconds' => '0',
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        ob_start();

        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($output);
        $this->assertStringContainsString(
            'This presentation type and unlock method combination is not supported.',
            $output
        );
    }

    public function testStoreRejectsContentWithTimerUnlockMethod(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->never())
            ->method('create');

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Invalid Campaign',
            'presentation_type' => 'content',
            'unlock_method' => 'timer',
            'timer_duration_seconds' => '10',
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        ob_start();

        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($output);
        $this->assertStringContainsString(
            'This presentation type and unlock method combination is not supported.',
            $output
        );
    }

    public function testUpdatePassesClickUnlockMethodToService(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->once())
            ->method('update')
            ->with(
                42,
                [
                    'name' => 'Content Campaign',
                    'presentation_type' => 'content',
                    'unlock_method' => 'click',
                    'timer_duration_seconds' => 0,
                    'frequency_limit_seconds' => null,
                    'presentation_settings' => [
                        'cta_label' => 'Continue',
                        'destination_url' => 'https://example.com/',
                    ],
                ]
            )
            ->willThrowException(
                new InvalidArgumentException('Test validation error')
            );

        $campaignService
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn([
                'id' => 42,
                'name' => 'Content Campaign',
                'status' => 'draft',
                'presentation_type' => 'content',
                'unlock_method' => 'click',
                'presentation_settings' => [
                    'cta_label' => 'Continue',
                    'destination_url' => 'https://example.com/',
                ],
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => null,
            ]);

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Content Campaign',
            'presentation_type' => 'content',
            'unlock_method' => 'click',
            'timer_duration_seconds' => '0',
            'presentation_settings' => [
                'cta_label' => 'Continue',
                'destination_url' => 'https://example.com/',
            ],
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        ob_start();

        try {
            $controller->update(['id' => '42']);
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($output);
        $this->assertStringContainsString(
            'Test validation error',
            $output
        );
    }

    public function testStoreRejectsPopupClickCombination(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->never())
            ->method('create');

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
            'name' => 'Popup Campaign',
            'presentation_type' => 'popup',
            'unlock_method' => 'click',
            'timer_duration_seconds' => '0',
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        ob_start();

        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $this->assertIsString($output);
        $this->assertStringContainsString(
            'This presentation type and unlock method combination is not supported.',
            $output
        );
    }

    public function testArchiveRejectsAlreadyArchivedCampaign(): void
    {
        $campaignService = $this->createMock(
            CampaignServiceInterface::class
        );

        $campaignService
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn([
                'id' => 42,
                'status' => 'archived',
            ]);

        $campaignService
            ->expects($this->never())
            ->method('update');

        $csrfToken = $this->createMock(
            CsrfTokenInterface::class
        );

        $csrfToken
            ->expects($this->once())
            ->method('validate')
            ->with('valid-csrf-token')
            ->willReturn(true);

        $_POST = [
            'csrf_token' => 'valid-csrf-token',
        ];

        $controller = $this->createController(
            $campaignService,
            $csrfToken
        );

        $response = $this->captureResponse(
            fn () => $controller->archive(['id' => '42'])
        );

        $this->assertSame(400, $response['status']);
        $this->assertSame(
            'Campaign is already archived.',
            $response['body']
        );
    }
}
