<?php

declare(strict_types=1);

namespace RewardGate\Service;

use InvalidArgumentException;
use RewardGate\Repository\CampaignRepositoryInterface;

final class CampaignService implements CampaignServiceInterface
{
    private const PRESENTATION_TYPES = [
        'popup',
        'content',
    ];

    private const UNLOCK_METHODS = [
        'timer',
        'click',
    ];

    private const REWARD_TYPES = [
        'content',
    ];

    private const POPUP_DEFAULT_SETTINGS = [
        'title' => 'Unlock Content',
        'message' => 'Please wait while your content is being unlocked.',
        'show_message' => true,
        'content' => '',
    ];

    private const CONTENT_CLICK_DEFAULT_SETTINGS = [
        'cta_label' => 'Continue',
        'destination_url' => '',
    ];

    public function __construct(
        private CampaignRepositoryInterface $campaignRepository
    ) {
    }

    public function create(
        string $name,
        string $presentationType,
        ?array $presentationSettings = null,
        string $status = 'draft',
        string $unlockMethod = 'timer',
        int $timerDurationSeconds = 10,
        string $rewardType = 'content',
        ?int $frequencyLimitSeconds = null,
    ): int {
        $this->assertValidPresentationType($presentationType);
        $this->assertValidUnlockMethod($unlockMethod);
        $this->assertValidPresentationUnlockCombination(
            $presentationType,
            $unlockMethod
        );
        $this->assertValidRewardType($rewardType);
        $this->assertValidFrequencyLimit($frequencyLimitSeconds);

        $presentationSettings = $this->normalizePresentationSettings(
            $presentationType,
            $presentationSettings,
            $unlockMethod
        );

        return $this->campaignRepository->create(
            $name,
            $presentationType,
            $presentationSettings,
            $status,
            $unlockMethod,
            $timerDurationSeconds,
            $rewardType,
            $frequencyLimitSeconds,
        );
    }

    public function findById(int $id): ?array
    {
        $campaign = $this->campaignRepository->findById($id);

        if ($campaign === null) {
            return null;
        }

        $campaign['presentation_settings'] =
            $this->normalizePresentationSettings(
                $campaign['presentation_type'],
                $campaign['presentation_settings'],
                $campaign['unlock_method']
            );

        return $campaign;
    }

    public function findAll(
        string $orderBy = 'created_at',
        string $direction = 'DESC',
        int $limit = 20,
        int $offset = 0,
    ): array {
        return $this->campaignRepository->findAll(
            $orderBy,
            $direction,
            $limit,
            $offset
        );
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['presentation_type'])) {
            $this->assertValidPresentationType(
                $data['presentation_type']
            );
        }

        if (isset($data['unlock_method'])) {
            $this->assertValidUnlockMethod(
                $data['unlock_method']
            );
        }

        if (isset($data['reward_type'])) {
            $this->assertValidRewardType(
                $data['reward_type']
            );
        }

        if (array_key_exists('frequency_limit_seconds', $data)) {
            $this->assertValidFrequencyLimit(
                $data['frequency_limit_seconds']
            );
        }

        /*
         * If both fields are being changed, we already know the
         * resulting presentation/unlock combination.
         */
        if (
            isset($data['presentation_type'])
            && isset($data['unlock_method'])
        ) {
            $this->assertValidPresentationUnlockCombination(
                $data['presentation_type'],
                $data['unlock_method']
            );
        }

        /*
         * If only one of the two fields is being changed, load the
         * existing campaign so we can validate the resulting combination.
         */
        if (
            isset($data['presentation_type'])
            xor isset($data['unlock_method'])
        ) {
            $currentCampaign = $this->campaignRepository->findById($id);

            if ($currentCampaign === null) {
                return false;
            }

            $presentationType = $data['presentation_type']
                ?? $currentCampaign['presentation_type'];

            $unlockMethod = $data['unlock_method']
                ?? $currentCampaign['unlock_method'];

            $this->assertValidPresentationUnlockCombination(
                $presentationType,
                $unlockMethod
            );
        }

        if (array_key_exists('presentation_settings', $data)) {
            if (!isset($data['presentation_type'])) {
                throw new InvalidArgumentException(
                    'Presentation type is required when updating presentation settings.'
                );
            }

            if (!isset($data['unlock_method'])) {
                throw new InvalidArgumentException(
                    'Unlock method is required when updating presentation settings.'
                );
            }

            $data['presentation_settings'] =
                $this->normalizePresentationSettings(
                    $data['presentation_type'],
                    $data['presentation_settings'],
                    $data['unlock_method']
                );
        }

        return $this->campaignRepository->update($id, $data);
    }

    private function assertValidPresentationUnlockCombination(
        string $presentationType,
        string $unlockMethod,
    ): void {
        if (
            ($presentationType === 'popup' && $unlockMethod !== 'timer')
            || ($presentationType === 'content' && $unlockMethod !== 'click')
        ) {
            throw new InvalidArgumentException(
                'Invalid presentation type and unlock method combination.'
            );
        }
    }

    private function normalizePresentationSettings(
        string $presentationType,
        ?array $settings,
        string $unlockMethod
    ): array {
        $settings ??= [];

        if ($presentationType === 'popup') {
            return [
                'title' => isset($settings['title'])
                    && is_string($settings['title'])
                    ? $settings['title']
                    : self::POPUP_DEFAULT_SETTINGS['title'],

                'message' => isset($settings['message'])
                    && is_string($settings['message'])
                    ? $settings['message']
                    : self::POPUP_DEFAULT_SETTINGS['message'],

                'show_message' => isset($settings['show_message'])
                    && is_bool($settings['show_message'])
                    ? $settings['show_message']
                    : self::POPUP_DEFAULT_SETTINGS['show_message'],

                'content' => isset($settings['content'])
                    && is_string($settings['content'])
                    ? $settings['content']
                    : self::POPUP_DEFAULT_SETTINGS['content'],
            ];
        }

        if (
            $presentationType === 'content'
            && $unlockMethod === 'click'
        ) {
            $ctaLabel = isset($settings['cta_label'])
                && is_string($settings['cta_label'])
                ? trim($settings['cta_label'])
                : '';

            $destinationUrl = isset($settings['destination_url'])
                && is_string($settings['destination_url'])
                ? trim($settings['destination_url'])
                : '';

            if ($ctaLabel === '') {
                $ctaLabel =
                    self::CONTENT_CLICK_DEFAULT_SETTINGS['cta_label'];
            }

            $this->assertValidDestinationUrl($destinationUrl);

            return [
                'cta_label' => $ctaLabel,
                'destination_url' => $destinationUrl,
            ];
        }

        return $settings;
    }

    private function assertValidPresentationType(string $type): void
    {
        if (!in_array($type, self::PRESENTATION_TYPES, true)) {
            throw new InvalidArgumentException(
                "Invalid presentation type: {$type}"
            );
        }
    }

    private function assertValidUnlockMethod(string $method): void
    {
        if (!in_array($method, self::UNLOCK_METHODS, true)) {
            throw new InvalidArgumentException(
                "Invalid unlock method: {$method}"
            );
        }
    }

    private function assertValidRewardType(string $type): void
    {
        if (!in_array($type, self::REWARD_TYPES, true)) {
            throw new InvalidArgumentException(
                "Invalid reward type: {$type}"
            );
        }
    }

    private function assertValidFrequencyLimit(?int $seconds): void
    {
        if ($seconds !== null && $seconds < 1) {
            throw new InvalidArgumentException(
                'Frequency limit must be null or greater than zero.'
            );
        }
    }

    private function assertValidDestinationUrl(string $url): void
    {
        if ($url === '') {
            throw new InvalidArgumentException(
                'Destination URL is required for click unlock.'
            );
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException(
                'Destination URL must use HTTP or HTTPS.'
            );
        }
    }
}
