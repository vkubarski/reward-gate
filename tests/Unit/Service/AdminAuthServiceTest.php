<?php

declare(strict_types=1);

namespace RewardGate\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use RewardGate\Repository\AdminUserRepositoryInterface;
use RewardGate\Service\AdminAuthService;

final class AdminAuthServiceTest extends TestCase
{
    public function testAuthenticateReturnsNullWhenUserDoesNotExist(): void
    {
        $adminUserRepository = $this->createMock(AdminUserRepositoryInterface::class);
        $adminUserRepository ->expects($this->once()) ->method('findByUsername') ->with('admin') ->willReturn(null);
        $adminUserRepository ->expects($this->never()) ->method('updatePasswordHash');
        $service = new AdminAuthService($adminUserRepository);
        $result = $service->authenticate('admin', 'password');
        $this->assertNull($result);
    } public function testAuthenticateReturnsNullWhenPasswordIsIncorrect(): void
    {
        $passwordHash = password_hash('correct-password', PASSWORD_DEFAULT);
        $adminUserRepository = $this->createMock(AdminUserRepositoryInterface::class);
        $adminUserRepository ->expects($this->once()) ->method('findByUsername') ->with('admin') ->willReturn([ 'id' => 42, 'username' => 'admin', 'password_hash' => $passwordHash, ]);
        $adminUserRepository ->expects($this->never()) ->method('updatePasswordHash');
        $service = new AdminAuthService($adminUserRepository);
        $result = $service->authenticate('admin', 'wrong-password');
        $this->assertNull($result);
    } public function testAuthenticateReturnsAdminUserForCorrectPassword(): void
    {
        $passwordHash = password_hash('correct-password', PASSWORD_DEFAULT);
        $adminUser = [ 'id' => 42, 'username' => 'admin', 'password_hash' => $passwordHash, ];
        $adminUserRepository = $this->createMock(AdminUserRepositoryInterface::class);
        $adminUserRepository ->expects($this->once()) ->method('findByUsername') ->with('admin') ->willReturn($adminUser);
        $adminUserRepository ->expects($this->never()) ->method('updatePasswordHash');
        $service = new AdminAuthService($adminUserRepository);
        $result = $service->authenticate('admin', 'correct-password');
        $this->assertSame($adminUser, $result);
    } public function testAuthenticateRehashesPasswordWhenNeeded(): void
    {
        $oldPasswordHash = password_hash('correct-password', PASSWORD_BCRYPT, [ 'cost' => 4, ]);
        $adminUser = [ 'id' => 42, 'username' => 'admin', 'password_hash' => $oldPasswordHash, ];
        $newPasswordHash = null;
        $adminUserRepository = $this->createMock(AdminUserRepositoryInterface::class);
        $adminUserRepository ->expects($this->once()) ->method('findByUsername') ->with('admin') ->willReturn($adminUser);
        $adminUserRepository ->expects($this->once()) ->method('updatePasswordHash') ->with(42, $this->callback(function (string $passwordHash) use (&$newPasswordHash): bool {
            $newPasswordHash = $passwordHash;
            return password_verify('correct-password', $passwordHash);
        })) ->willReturn(true);
        $service = new AdminAuthService($adminUserRepository);
        $result = $service->authenticate('admin', 'correct-password');
        $this->assertSame($adminUser, $result);
        $this->assertNotNull($newPasswordHash);
        $this->assertNotSame($oldPasswordHash, $newPasswordHash);
        $this->assertFalse(password_needs_rehash($newPasswordHash, PASSWORD_DEFAULT));
    }
}
