<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration\Repository;

use RewardGate\Repository\AdminUserRepository;
use RewardGate\Tests\Integration\IntegrationTestCase;

final class AdminUserRepositoryTest extends IntegrationTestCase
{
    private AdminUserRepository $repository;
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AdminUserRepository($this->pdo);
    } public function testCreateInsertsAdminUserAndReturnsId(): void
    {
        $id = $this->repository->create('test-admin', 'password-hash-123');
        $this->assertGreaterThan(0, $id);
        $adminUser = $this->repository->findById($id);
        $this->assertIsArray($adminUser);
        $this->assertSame($id, (int)$adminUser['id']);
        $this->assertSame('test-admin', $adminUser['username']);
        $this->assertSame('password-hash-123', $adminUser['password_hash']);
    } public function testFindByUsernameReturnsAdminUser(): void
    {
        $id = $this->repository->create('test-admin', 'password-hash-123');
        $adminUser = $this->repository->findByUsername('test-admin');
        $this->assertIsArray($adminUser);
        $this->assertSame($id, (int)$adminUser['id']);
        $this->assertSame('test-admin', $adminUser['username']);
        $this->assertSame('password-hash-123', $adminUser['password_hash']);
    } public function testFindByUsernameReturnsNullForMissingUser(): void
    {
        $adminUser = $this->repository->findByUsername('does-not-exist');
        $this->assertNull($adminUser);
    } public function testFindByIdReturnsAdminUser(): void
    {
        $id = $this->repository->create('test-admin', 'password-hash-123');
        $adminUser = $this->repository->findById($id);
        $this->assertIsArray($adminUser);
        $this->assertSame($id, (int)$adminUser['id']);
        $this->assertSame('test-admin', $adminUser['username']);
    } public function testFindByIdReturnsNullForMissingUser(): void
    {
        $adminUser = $this->repository->findById(999999999);
        $this->assertNull($adminUser);
    } public function testUpdatePasswordHashChangesPasswordHash(): void
    {
        $id = $this->repository->create('test-admin', 'old-password-hash');
        $updated = $this->repository->updatePasswordHash($id, 'new-password-hash');
        $this->assertTrue($updated);
        $adminUser = $this->repository->findById($id);
        $this->assertIsArray($adminUser);
        $this->assertSame('new-password-hash', $adminUser['password_hash']);
    } public function testUpdatePasswordHashReturnsFalseForMissingUser(): void
    {
        $updated = $this->repository->updatePasswordHash(999999999, 'new-password-hash');
        $this->assertFalse($updated);
    }
}
