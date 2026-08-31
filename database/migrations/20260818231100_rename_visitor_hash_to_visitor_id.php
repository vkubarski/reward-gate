<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RenameVisitorHashToVisitorId extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('unlock_sessions');

        $table
            ->renameColumn('visitor_hash', 'visitor_id')
            ->update();
    }
}
