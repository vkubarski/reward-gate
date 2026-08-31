<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixAdminUsersTimestamps extends AbstractMigration
{
        public function change(): void
        {
                $this->execute(
                        'UPDATE admin_users
                         SET updated_at = created_at
                         WHERE updated_at IS NULL'
                );

                $table = $this->table('admin_users');

                $table
                        ->changeColumn('created_at', 'datetime', [
                                'null' => false,
                                'default' => 'CURRENT_TIMESTAMP',
                        ])
                        ->changeColumn('updated_at', 'datetime', [
                                'null' => false,
                                'default' => 'CURRENT_TIMESTAMP',
                                'update' => 'CURRENT_TIMESTAMP',
                        ])
                        ->update();
        }
}
