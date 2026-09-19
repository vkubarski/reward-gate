<?php

declare(strict_types=1);

namespace RewardGate\Controller;

use Closure;
use InvalidArgumentException;
use JsonException;
use RewardGate\Service\UnlockSessionServiceInterface;
use RewardGate\Security\VisitorIdInterface;
use Throwable;

final class UnlockController
{
    private Closure $requestBodyReader;

    public function __construct(
        private UnlockSessionServiceInterface $unlockSessionService,
        private VisitorIdInterface $visitorId,
        ?Closure $requestBodyReader = null
    ) {
        $this->requestBodyReader = $requestBodyReader
            ?? static function (): string {
                return file_get_contents('php://input') ?: '';
            };
    }

    public function start(array $params): void
    {
        try {
            $campaignId = (int) $params['id'];
            $visitorId = $this->visitorId->get();

            $session = $this->unlockSessionService->start(
                $campaignId,
                $visitorId
            );

            $this->json([
                'success' => true,
                'session' => $session,
            ], 201);
        } catch (InvalidArgumentException $exception) {
            $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 400);
        } catch (Throwable $exception) {
            $this->json([
                'success' => false,
                'error' => 'Unable to start unlock session.',
            ], 500);
        }
    }

    public function status(array $params): void
    {
        try {
            $campaignId = (int) $params['id'];
            $visitorId = $this->visitorId->get();

            $unlocked = $this->unlockSessionService->status(
                $campaignId,
                $visitorId
            );

            $this->json([
                'success' => true,
                'unlocked' => $unlocked,
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 400);
        } catch (Throwable $exception) {
            $this->json([
                'success' => false,
                'error' => 'Unable to determine unlock status.',
            ], 500);
        }
    }

    public function complete(): void
    {
        try {
            $input = json_decode(
                $this->readRequestBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($input)) {
                throw new InvalidArgumentException(
                    'Invalid request body.'
                );
            }

            $token = $input['token'] ?? '';
            $visitorId = $this->visitorId->get();

            $this->unlockSessionService->complete($token, $visitorId);

            $this->json([
                'success' => true,
            ]);
        } catch (JsonException $exception) {
            $this->json([
                'success' => false,
                'error' => 'Invalid request body.',
            ], 400);
        } catch (InvalidArgumentException $exception) {
            $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 400);
        } catch (Throwable $exception) {
            $this->json([
                'success' => false,
                'error' => 'Unable to complete unlock session.',
            ], 500);
        }
    }

    private function readRequestBody(): string
    {
        return ($this->requestBodyReader)();
    }

    private function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $data,
            JSON_THROW_ON_ERROR
        );
    }
}
