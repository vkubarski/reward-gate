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
        $_POST = [ 'csrf_token' => 'valid-csrf-token', 'name' => '', 'presentation_type' => 'popup', 'timer_duration_seconds' => '10', ];
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
        $_POST = [ 'csrf_token' => 'valid-csrf-token', 'name' => 'Test Campaign', 'presentation_type' => 'popup', 'timer_duration_seconds' => '0', ];
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
    } public function testStoreDisplaysServiceValidationError(): void
    {
        $campaignService = $this->createMock(CampaignServiceInterface::class);
        $campaignService ->expects($this->once()) ->method('create') ->with('Test Campaign', 'invalid', null, 'draft', 'timer', 10, 'content') ->willThrowException(new InvalidArgumentException('Invalid presentation type: invalid'));
        $csrfToken = $this->createMock(CsrfTokenInterface::class);
        $csrfToken ->expects($this->once()) ->method('validate') ->with('valid-csrf-token') ->willReturn(true);
        $_POST = [ 'csrf_token' => 'valid-csrf-token', 'name' => 'Test Campaign', 'presentation_type' => 'invalid', 'timer_duration_seconds' => '10', ];
        $controller = $this->createController($campaignService, $csrfToken);
        ob_start();
        try {
            $controller->store();
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        } $this->assertIsString($output);
        $this->assertStringContainsString('Invalid presentation type: invalid', $output);
    }
}
