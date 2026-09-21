<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class DemoSeeder extends AbstractSeed
{
    private const ADMIN_USERNAME = 'admin';

    private const POPUP_CAMPAIGN_NAME = 'Demo Popup Gate';

    private const CONTENT_CAMPAIGN_NAME = 'Demo Content Gate';

    public function run(): void
    {
        $this->createDemoAdmin();
        $this->createDemoPopupCampaign();
        $this->createDemoContentCampaign();
    }

    private function createDemoAdmin(): void
    {
        $existingAdmin = $this->query(
            'SELECT id FROM admin_users WHERE username = :username',
            [
                'username' => self::ADMIN_USERNAME,
            ]
        )->fetch();

        if ($existingAdmin !== false) {
            $this->getOutput()->writeln(
                "Demo admin '" . self::ADMIN_USERNAME . "' already exists; password unchanged."
            );

            return;
        }

        $password = bin2hex(random_bytes(16));

        $this->execute(
            'INSERT INTO admin_users (username, password_hash)
             VALUES (:username, :password_hash)',
            [
                'username' => self::ADMIN_USERNAME,
                'password_hash' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
            ]
        );

        $this->getOutput()->writeln(
            "Created demo admin '" . $this::ADMIN_USERNAME . "'"
        );
        $this->getOutput()->writeln(
            "Demo admin password: {$password}"
        );
    }

    private function createDemoPopupCampaign(): void
    {
        $existingCampaign = $this->query(
            'SELECT id FROM campaigns WHERE name = :name LIMIT 1',
            [
                'name' => self::POPUP_CAMPAIGN_NAME,
            ]
        )->fetch();

        if ($existingCampaign !== false) {
            $this->getOutput()->writeln(
                'Demo Popup Gate already exists; skipped.'
            );

            return;
        }

        $presentationSettings = json_encode(
            [
                'title' => 'Demo Popup Gate',
                'message' => 'This is a Reward Gate Popup demo.',
                'show_message' => true,
                'content' => '<p>Demo popup content.</p>',
            ],
            JSON_THROW_ON_ERROR
        );

        $this->execute(
            'INSERT INTO campaigns (
                name,
                status,
                presentation_type,
                presentation_settings,
                unlock_method,
                timer_duration_seconds,
                frequency_limit_seconds,
                reward_type
            ) VALUES (
                :name,
                :status,
                :presentation_type,
                :presentation_settings,
                :unlock_method,
                :timer_duration_seconds,
                :frequency_limit_seconds,
                :reward_type
            )',
            [
                'name' => self::POPUP_CAMPAIGN_NAME,
                'status' => 'active',
                'presentation_type' => 'popup',
                'presentation_settings' => $presentationSettings,
                'unlock_method' => 'timer',
                'timer_duration_seconds' => 5,
                'frequency_limit_seconds' => null,
                'reward_type' => 'content',
            ]
        );

        $this->getOutput()->writeln(
            'Created Demo Popup Gate.'
        );
    }

    private function createDemoContentCampaign(): void
    {
        $existingCampaign = $this->query(
            'SELECT id FROM campaigns WHERE name = :name LIMIT 1',
            [
                'name' => self::CONTENT_CAMPAIGN_NAME,
            ]
        )->fetch();

        if ($existingCampaign !== false) {
            $this->getOutput()->writeln(
                'Demo Content Gate already exists; skipped.'
            );

            return;
        }

        $presentationSettings = json_encode(
            [
                'cta_label' => 'Continue to Demo',
                'destination_url' => 'https://example.com',
            ],
            JSON_THROW_ON_ERROR
        );

        $this->execute(
            'INSERT INTO campaigns (
                name,
                status,
                presentation_type,
                presentation_settings,
                unlock_method,
                timer_duration_seconds,
                frequency_limit_seconds,
                reward_type
            ) VALUES (
                :name,
                :status,
                :presentation_type,
                :presentation_settings,
                :unlock_method,
                :timer_duration_seconds,
                :frequency_limit_seconds,
                :reward_type
            )',
            [
                'name' => self::CONTENT_CAMPAIGN_NAME,
                'status' => 'active',
                'presentation_type' => 'content',
                'presentation_settings' => $presentationSettings,
                'unlock_method' => 'click',
                'timer_duration_seconds' => 0,
                'frequency_limit_seconds' => null,
                'reward_type' => 'content',
            ]
        );

        $this->getOutput()->writeln(
            'Created Demo Content Gate.'
        );
    }
}
