<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInitialSchema extends AbstractMigration
{
        public function change(): void
        {
                $campaigns = $this->table('campaigns', [
                        'id' => false,
                        'primary_key' => ['id'],
                ]);

                $campaigns
                        ->addColumn('id', 'biginteger', [
                                'identity' => true,
                                'signed' => false,
                                'null' => false,
                        ])
                        ->addColumn('name', 'string', [
                                'limit' => 255,
                                'null' => false,
                        ])
                        ->addColumn('status', 'enum', [
                                'values' => ['active', 'paused', 'archived'],
                                'default' => 'active',
                                'null' => false,
                        ])
                        ->addColumn('presentation_type', 'string', [
                                'limit' => 32,
                                'null' => false,
                        ])
                        ->addColumn('presentation_settings', 'json', [
                                'null' => true,
                        ])
                        ->addColumn('unlock_method', 'string', [
                                'limit' => 32,
                                'default' => 'timer',
                                'null' => false,
                        ])
                        ->addColumn('timer_duration_seconds', 'integer', [
                                'signed' => false,
                                'default' => 10,
                                'null' => false,
                        ])
                        ->addColumn('reward_type', 'string', [
                                'limit' => 32,
                                'default' => 'content',
                                'null' => false,
                        ])
                        ->addColumn('created_at', 'datetime', [
                                'default' => 'CURRENT_TIMESTAMP',
                                'null' => false,
                        ])
                        ->addColumn('updated_at', 'datetime', [
                                'default' => 'CURRENT_TIMESTAMP',
                                'update' => 'CURRENT_TIMESTAMP',
                                'null' => false,
                        ])
                        ->addIndex(['status'], [
                                'name' => 'idx_campaigns_status',
                        ])
                        ->create();


                $unlockSessions = $this->table('unlock_sessions', [
                        'id' => false,
                        'primary_key' => ['id'],
                ]);

                $unlockSessions
                        ->addColumn('id', 'biginteger', [
                                'identity' => true,
                                'signed' => false,
                                'null' => false,
                        ])
                        ->addColumn('campaign_id', 'biginteger', [
                                'signed' => false,
                                'null' => false,
                        ])
                        ->addColumn('token_hash', 'char', [
                                'limit' => 64,
                                'null' => false,
                        ])
                        ->addColumn('visitor_hash', 'char', [
                                'limit' => 64,
                                'null' => true,
                        ])
                        ->addColumn('required_duration_seconds', 'integer', [
                                'signed' => false,
                                'null' => false,
                        ])
                        ->addColumn('status', 'enum', [
                                'values' => ['active', 'completed', 'expired'],
                                'default' => 'active',
                                'null' => false,
                        ])
                        ->addColumn('started_at', 'datetime', [
                                'null' => false,
                        ])
                        ->addColumn('expires_at', 'datetime', [
                                'null' => false,
                        ])
                        ->addColumn('created_at', 'datetime', [
                                'default' => 'CURRENT_TIMESTAMP',
                                'null' => false,
                        ])
                        ->addIndex(['token_hash'], [
                                'name' => 'uq_unlock_sessions_token_hash',
                                'unique' => true,
                        ])
                        ->addIndex(['campaign_id'], [
                                'name' => 'idx_unlock_sessions_campaign_id',
                        ])
                        ->addIndex(['status', 'expires_at'], [
                                'name' => 'idx_unlock_sessions_status_expires_at',
                        ])
                        ->addIndex(
                                ['visitor_hash', 'campaign_id', 'created_at'],
                                [
                                        'name' => 'idx_unlock_sessions_visitor_campaign',
                                ]
                        )
                        ->addForeignKey(
                                'campaign_id',
                                'campaigns',
                                'id',
                                [
                                        'constraint' => 'fk_unlock_sessions_campaign',
                                        'delete' => 'RESTRICT',
                                        'update' => 'NO_ACTION',
                                ]
                        )
                        ->create();


                $unlockCompletions = $this->table('unlock_completions', [
                        'id' => false,
                        'primary_key' => ['id'],
                ]);

                $unlockCompletions
                        ->addColumn('id', 'biginteger', [
                                'identity' => true,
                                'signed' => false,
                                'null' => false,
                        ])
                        ->addColumn('unlock_session_id', 'biginteger', [
                                'signed' => false,
                                'null' => false,
                        ])
                        ->addColumn('campaign_id', 'biginteger', [
                                'signed' => false,
                                'null' => false,
                        ])
                        ->addColumn('completed_at', 'datetime', [
                                'null' => false,
                        ])
                        ->addIndex(['unlock_session_id'], [
                                'name' => 'uq_unlock_completions_session',
                                'unique' => true,
                        ])
                        ->addIndex(['campaign_id'], [
                                'name' => 'idx_unlock_completions_campaign_id',
                        ])
                        ->addForeignKey(
                                'unlock_session_id',
                                'unlock_sessions',
                                'id',
                                [
                                        'constraint' => 'fk_unlock_completions_session',
                                        'delete' => 'RESTRICT',
                                        'update' => 'NO_ACTION',
                                ]
                        )
                        ->addForeignKey(
                                'campaign_id',
                                'campaigns',
                                'id',
                                [
                                        'constraint' => 'fk_unlock_completions_campaign',
                                        'delete' => 'RESTRICT',
                                        'update' => 'NO_ACTION',
                                ]
                        )
                        ->create();
        }
}
