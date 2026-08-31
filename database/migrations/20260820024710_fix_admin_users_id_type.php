<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixAdminUsersIdType extends AbstractMigration
{
        public function change(): void
        {
                $table = $this->table('admin_users');

                $table
                        ->changeColumn('id', 'biginteger', [
                                'signed' => false,
                                'null' => false,
                                'identity' => true,
                        ])
                        ->update();
        }
}
