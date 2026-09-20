<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration\Repository;

use InvalidArgumentException;
use RewardGate\Repository\CampaignRepository;
use RewardGate\Tests\Integration\IntegrationTestCase;

final class CampaignRepositoryTest extends IntegrationTestCase
{
    public function testCreateInsertsCampaignAndReturnsId(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $id = $repository->create(
            'Test Campaign',
            'popup',
            null,
            'draft',
            'timer',
            10,
            'content',
            null
        );

        $this->assertGreaterThan(0, $id);

        $campaign = $repository->findById($id);

        $this->assertNotNull($campaign);
        $this->assertSame($id, $campaign['id']);
        $this->assertSame('Test Campaign', $campaign['name']);
        $this->assertSame('draft', $campaign['status']);
        $this->assertSame('popup', $campaign['presentation_type']);
        $this->assertNull($campaign['presentation_settings']);
        $this->assertSame('timer', $campaign['unlock_method']);
        $this->assertSame(
            10,
            $campaign['timer_duration_seconds']
        );
        $this->assertNull(
            $campaign['frequency_limit_seconds']
        );
        $this->assertSame('content', $campaign['reward_type']);
    }

    public function testCreateStoresAndFindByIdHydratesPresentationSettings(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $presentationSettings = [
                'title' => 'Wait to unlock',
                'show_close_button' => false,
                'style' => [
                        'width' => '400px',
                        'position' => 'center',
                ],
        ];

        $id = $repository->create(
            'Test Campaign',
            'popup',
            $presentationSettings
        );

        $campaign = $repository->findById($id);

        $this->assertNotNull($campaign);
        $this->assertSame(
            $presentationSettings,
            $campaign['presentation_settings']
        );
    }

    public function testFindByIdReturnsNullForMissingCampaign(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $campaign = $repository->findById(999999999);

        $this->assertNull($campaign);
    }

    public function testUpdateChangesCampaignFields(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $id = $repository->create(
            'Original Campaign',
            'popup'
        );

        $result = $repository->update(
            $id,
            [
                        'name' => 'Updated Campaign',
                        'status' => 'active',
                        'presentation_type' => 'content',
                        'timer_duration_seconds' => 30,
                        'frequency_limit_seconds' => 3600,
                ]
        );

        $this->assertTrue($result);

        $campaign = $repository->findById($id);

        $this->assertNotNull($campaign);
        $this->assertSame(
            'Updated Campaign',
            $campaign['name']
        );
        $this->assertSame('active', $campaign['status']);
        $this->assertSame(
            'content',
            $campaign['presentation_type']
        );
        $this->assertSame(
            30,
            $campaign['timer_duration_seconds']
        );
        $this->assertSame(
            3600,
            $campaign['frequency_limit_seconds']
        );
    }

    public function testUpdateStoresAndHydratesPresentationSettings(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $id = $repository->create(
            'Test Campaign',
            'popup'
        );

        $presentationSettings = [
                'title' => 'Updated title',
                'theme' => [
                        'size' => 'large',
                ],
        ];

        $result = $repository->update(
            $id,
            [
                        'presentation_settings' => $presentationSettings,
                ]
        );

        $this->assertTrue($result);

        $campaign = $repository->findById($id);

        $this->assertNotNull($campaign);
        $this->assertSame(
            $presentationSettings,
            $campaign['presentation_settings']
        );
    }

    public function testUpdateReturnsFalseWhenNoAllowedFieldsAreProvided(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $id = $repository->create(
            'Test Campaign',
            'popup'
        );

        $result = $repository->update(
            $id,
            [
                        'something_else' => 'value',
                ]
        );

        $this->assertFalse($result);
    }

    public function testUpdateReturnsFalseForMissingCampaign(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $result = $repository->update(
            999999999,
            [
                        'name' => 'Updated Campaign',
                ]
        );

        $this->assertFalse($result);
    }

    public function testFindAllReturnsCampaigns(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $firstId = $repository->create(
            'First Campaign',
            'popup'
        );

        $secondId = $repository->create(
            'Second Campaign',
            'content'
        );

        $campaigns = $repository->findAll(
            'id',
            'ASC',
            10
        );

        $ids = array_column($campaigns, 'id');

        $this->assertContains($firstId, $ids);
        $this->assertContains($secondId, $ids);
    }

    public function testFindAllExcludesArchivedCampaigns(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $activeId = $repository->create(
            'Active Campaign',
            'popup',
            null,
            'active'
        );

        $archivedId = $repository->create(
            'Archived Campaign',
            'popup',
            null,
            'archived'
        );

        $campaigns = $repository->findAll(
            'id',
            'ASC',
            10
        );

        $ids = array_column($campaigns, 'id');

        $this->assertContains($activeId, $ids);
        $this->assertNotContains($archivedId, $ids);
    }

    public function testFindAllOrdersByRequestedField(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $repository->create(
            'Zulu Campaign',
            'popup'
        );

        $repository->create(
            'Alpha Campaign',
            'popup'
        );

        $campaigns = $repository->findAll(
            'name',
            'ASC',
            10
        );

        $this->assertSame(
            'Alpha Campaign',
            $campaigns[0]['name']
        );
        $this->assertSame(
            'Zulu Campaign',
            $campaigns[1]['name']
        );
    }

    public function testFindAllAppliesLimitAndOffset(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $firstId = $repository->create(
            'First Campaign',
            'popup'
        );

        $secondId = $repository->create(
            'Second Campaign',
            'popup'
        );

        $thirdId = $repository->create(
            'Third Campaign',
            'popup'
        );

        $campaigns = $repository->findAll(
            'id',
            'ASC',
            1,
            1
        );

        $this->assertCount(1, $campaigns);
        $this->assertSame(
            $secondId,
            $campaigns[0]['id']
        );

        $this->assertNotSame(
            $firstId,
            $campaigns[0]['id']
        );
        $this->assertNotSame(
            $thirdId,
            $campaigns[0]['id']
        );
    }

    public function testFindAllFallsBackToDefaultOrderAndDirection(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $firstId = $repository->create(
            'First Campaign',
            'popup'
        );

        $secondId = $repository->create(
            'Second Campaign',
            'popup'
        );

        $this->pdo->prepare(
            'UPDATE campaigns
                         SET created_at = :created_at
                         WHERE id = :id'
        )->execute([
                'created_at' => '2026-01-01 12:00:00',
                'id' => $firstId,
        ]);

        $this->pdo->prepare(
            'UPDATE campaigns
                         SET created_at = :created_at
                         WHERE id = :id'
        )->execute([
                'created_at' => '2026-01-01 12:00:01',
                'id' => $secondId,
        ]);

        $campaigns = $repository->findAll(
            'invalid_column',
            'invalid_direction',
            10
        );

        $this->assertSame(
            'Second Campaign',
            $campaigns[0]['name']
        );
        $this->assertSame(
            'First Campaign',
            $campaigns[1]['name']
        );
    }

    public function testFindAllRejectsInvalidLimit(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Limit must be greater than zero.'
        );

        $repository->findAll(
            'id',
            'ASC',
            0
        );
    }

    public function testFindAllRejectsNegativeOffset(): void
    {
        $repository = new CampaignRepository($this->pdo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Offset cannot be negative.'
        );

        $repository->findAll(
            'id',
            'ASC',
            10,
            -1
        );
    }
}
