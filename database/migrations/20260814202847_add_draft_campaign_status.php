<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDraftCampaignStatus extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $campaigns = $this->table('campaigns');

        $campaigns
            ->changeColumn('status', 'enum', [
                'values' => ['draft', 'active', 'paused', 'archived'],
                'default' => 'draft',
                'null' => false,
            ])
            ->update();
    }
}
