<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFrequencyLimiting extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information in the Phinx documentation:
     * https://book.cakephp.org/phinx/0/en/migrations.html
     */
    public function change(): void
    {
        $campaigns = $this->table('campaigns');

        $campaigns
            ->addColumn('frequency_limit_seconds', 'integer', [
                'signed' => false,
                'null' => true,
            ])
            ->update();

        $unlockCompletions = $this->table('unlock_completions');

        $unlockCompletions
            ->addColumn('visitor_id', 'char', [
                'limit' => 64,
                'null' => true,
            ])
            ->addIndex(
                ['campaign_id', 'visitor_id', 'completed_at'],
                [
                    'name' => 'idx_unlock_completions_campaign_visitor_completed',
                ]
            )
            ->update();
    }
}
