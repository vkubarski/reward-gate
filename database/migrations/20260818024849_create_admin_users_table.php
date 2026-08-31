<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdminUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('admin_users');

        $table
            ->addColumn('username', 'string', [
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('password_hash', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addTimestamps()
            ->addIndex(
                ['username'],
                [
                    'unique' => true,
                    'name' => 'uq_admin_users_username',
                ]
            )
            ->create();
    }
}
