<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Service;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RewardGate\Repository\CampaignRepositoryInterface;
use RewardGate\Service\CampaignService;

final class CampaignServiceTest extends TestCase
{
    public function testCreateRejectsInvalidPresentationType(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $service = new CampaignService(
            $campaignRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid presentation type: invalid'
        );

        $service->create(
            'Test Campaign',
            'invalid'
        );
    }

    public function testCreateRejectsInvalidUnlockMethod(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $service = new CampaignService(
            $campaignRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid unlock method: invalid'
        );

        $service->create(
            'Test Campaign',
            'popup',
            null,
            'draft',
            'invalid'
        );
    }

    public function testCreateRejectsInvalidRewardType(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $service = new CampaignService(
            $campaignRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid reward type: invalid'
        );

        $service->create(
            'Test Campaign',
            'popup',
            null,
            'draft',
            'timer',
            10,
            'invalid'
        );
    }

    public function testCreateRejectsInvalidFrequencyLimit(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $service = new CampaignService(
            $campaignRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Frequency limit must be null or greater than zero.'
        );

        $service->create(
            'Test Campaign',
            'popup',
            null,
            'draft',
            'timer',
            10,
            'content',
            0
        );
    }

    public function testCreateDelegatesValidDataAndReturnsId(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $presentationSettings = [
            'title' => 'Wait to unlock',
            'show_close_button' => false,
        ];

        $campaignRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                'Test Campaign',
                'popup',
                $presentationSettings,
                'draft',
                'timer',
                15,
                'content',
                3600
            )
            ->willReturn(42);

        $service = new CampaignService(
            $campaignRepository
        );

        $result = $service->create(
            'Test Campaign',
            'popup',
            $presentationSettings,
            'draft',
            'timer',
            15,
            'content',
            3600
        );

        $this->assertSame(42, $result);
    }

    public function testCreateAcceptsNullFrequencyLimit(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                'Test Campaign',
                'popup',
                null,
                'draft',
                'timer',
                10,
                'content',
                null
            )
            ->willReturn(1);

        $service = new CampaignService(
            $campaignRepository
        );

        $result = $service->create(
            'Test Campaign',
            'popup',
            null,
            'draft',
            'timer',
            10,
            'content',
            null
        );

        $this->assertSame(1, $result);
    }

    public function testCreateAcceptsValidFrequencyLimit(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                'Test Campaign',
                'popup',
                null,
                'draft',
                'timer',
                10,
                'content',
                3600
            )
            ->willReturn(1);

        $service = new CampaignService(
            $campaignRepository
        );

        $result = $service->create(
            'Test Campaign',
            'popup',
            null,
            'draft',
            'timer',
            10,
            'content',
            3600
        );

        $this->assertSame(1, $result);
    }

    public function testFindByIdReturnsRepositoryResult(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaign = [
            'id' => 42,
            'name' => 'Test Campaign',
            'status' => 'active',
        ];

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($campaign);

        $service = new CampaignService(
            $campaignRepository
        );

        $result = $service->findById(42);

        $this->assertSame($campaign, $result);
    }

    public function testFindByIdReturnsNullWhenRepositoryReturnsNull(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $service = new CampaignService(
            $campaignRepository
        );

        $result = $service->findById(999);

        $this->assertNull($result);
    }

    public function testFindAllDelegatesArgumentsAndReturnsRepositoryResult(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaigns = [
            [
                'id' => 2,
                'name' => 'Second Campaign',
            ],
            [
                'id' => 1,
                'name' => 'First Campaign',
            ],
        ];

        $campaignRepository
            ->expects($this->once())
            ->method('findAll')
            ->with(
                'name',
                'DESC',
                10,
                5
            )
            ->willReturn($campaigns);

        $service = new CampaignService(
            $campaignRepository
        );

        $result = $service->findAll(
            'name',
            'DESC',
            10,
            5
        );

        $this->assertSame($campaigns, $result);
    }

    public function testUpdateRejectsInvalidPresentationType(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->never())
            ->method('update');

        $service = new CampaignService(
            $campaignRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid presentation type: invalid'
        );

        $service->update(
            1,
            [
                'presentation_type' => 'invalid',
            ]
        );
    }

    public function testUpdateRejectsInvalidUnlockMethod(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->never())
            ->method('update');

        $service = new CampaignService(
            $campaignRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid unlock method: invalid'
        );

        $service->update(
            1,
            [
                'unlock_method' => 'invalid',
            ]
        );
    }

    public function testUpdateRejectsInvalidRewardType(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->never())
            ->method('update');

        $service = new CampaignService(
            $campaignRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid reward type: invalid'
        );

        $service->update(
            1,
            [
                'reward_type' => 'invalid',
            ]
        );
    }

    public function testUpdateRejectsInvalidFrequencyLimit(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->never())
            ->method('update');

        $service = new CampaignService(
            $campaignRepository
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Frequency limit must be null or greater than zero.'
        );

        $service->update(
            1,
            [
                'frequency_limit_seconds' => 0,
            ]
        );
    }

    public function testUpdateAcceptsNullFrequencyLimit(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                [
                    'frequency_limit_seconds' => null,
                ]
            )
            ->willReturn(true);

        $service = new CampaignService(
            $campaignRepository
        );

        $result = $service->update(
            1,
            [
                'frequency_limit_seconds' => null,
            ]
        );

        $this->assertTrue($result);
    }

    public function testUpdateAcceptsValidData(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                [
                    'name' => 'Updated Campaign',
                    'presentation_type' => 'content',
                    'unlock_method' => 'timer',
                    'reward_type' => 'content',
                    'frequency_limit_seconds' => 3600,
                ]
            )
            ->willReturn(true);

        $service = new CampaignService(
            $campaignRepository
        );

        $result = $service->update(
            1,
            [
                'name' => 'Updated Campaign',
                'presentation_type' => 'content',
                'unlock_method' => 'timer',
                'reward_type' => 'content',
                'frequency_limit_seconds' => 3600,
            ]
        );

        $this->assertTrue($result);
    }

    public function testUpdateReturnsRepositoryResult(): void
    {
        $campaignRepository = $this->createMock(
            CampaignRepositoryInterface::class
        );

        $campaignRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                [
                    'name' => 'Updated Campaign',
                ]
            )
            ->willReturn(false);

        $service = new CampaignService(
            $campaignRepository
        );

        $result = $service->update(
            1,
            [
                'name' => 'Updated Campaign',
            ]
        );

        $this->assertFalse($result);
    }
}
