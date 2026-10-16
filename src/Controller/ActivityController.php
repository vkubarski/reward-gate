<?php

declare(strict_types=1);

namespace RewardGate\Controller;

use Closure;
use JsonException;
use RewardGate\Repository\GateImpressionRepositoryInterface;
use RewardGate\Security\VisitorIdInterface;
use RewardGate\Service\CampaignServiceInterface;
use Throwable;

final class ActivityController
{
    private Closure $requestBodyReader;

    public function __construct(
        private CampaignServiceInterface $campaignService,
        private GateImpressionRepositoryInterface $gateImpressionRepository,
        private VisitorIdInterface $visitorId,
        ?Closure $requestBodyReader = null,
    ) {
        $this->requestBodyReader = $requestBodyReader
            ?? static fn (): string => file_get_contents('php://input') ?: '';
    }

    public function record(array $params): void
    {
        $campaignId = filter_var(
            $params['id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if ($campaignId === false) {
            $this->respondError(400, 'Invalid campaign ID.');
            return;
        }

        try {
            $body = json_decode(
                ($this->requestBodyReader)(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $this->respondError(400, 'Invalid JSON body.');
            return;
        }

        if (
            !is_array($body)
            || !isset($body['page_view_id'])
            || !is_string($body['page_view_id'])
            || !preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $body['page_view_id'],
            )
        ) {
            $this->respondError(400, 'Invalid page_view_id.');
            return;
        }

        try {
            $campaign = $this->campaignService->findById((int) $campaignId);

            if ($campaign === null || $campaign['status'] !== 'active') {
                $this->respondError(404, 'Campaign not found.');
                return;
            }

            $this->gateImpressionRepository->record(
                (int) $campaign['id'],
                $this->visitorId->get(),
                strtolower($body['page_view_id']),
                $campaign['presentation_type'],
                $campaign['unlock_method'],
            );

            http_response_code(204);
        } catch (Throwable) {
            $this->respondError(500, 'Unable to record activity.');
        }
    }

    private function respondError(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            ['error' => $message],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
