<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGateImpressionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('gate_impressions', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('id', 'biginteger', [
                'signed' => false,
                'null' => false,
                'identity' => true,
            ])
            ->addColumn('campaign_id', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('visitor_id', 'char', [
                'limit' => 64,
                'null' => false,
                'encoding' => 'ascii',
                'collation' => 'ascii_bin',
            ])
            ->addColumn('page_view_id', 'char', [
                'limit' => 36,
                'null' => false,
                'encoding' => 'ascii',
                'collation' => 'ascii_bin',
            ])
            ->addColumn('presentation_type', 'string', [
                'limit' => 32,
                'null' => false,
            ])
            ->addColumn('unlock_method', 'string', [
                'limit' => 32,
                'null' => false,
            ])
            ->addColumn('created_at', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->addIndex(
                ['campaign_id', 'visitor_id', 'page_view_id'],
                [
                    'unique' => true,
                    'name' => 'uq_gate_impressions_page_view',
                ]
            )
            ->addIndex(
                ['campaign_id', 'created_at'],
                [
                    'name' => 'idx_gate_impressions_campaign_created',
                ]
            )
            ->addForeignKey(
                'campaign_id',
                'campaigns',
                'id',
                [
                    'delete' => 'RESTRICT',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_gate_impressions_campaign',
                ]
            )
            ->create();

        $this->table('unlock_sessions')
            ->addIndex(
                ['campaign_id', 'started_at'],
                [
                    'name' => 'idx_unlock_sessions_campaign_started',
                ]
            )
            ->update();
    }
}
