CREATE TABLE campaigns (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        status ENUM('draft', 'active', 'paused', 'archived') NOT NULL DEFAULT 'draft',

        presentation_type VARCHAR(32) NOT NULL,
        presentation_settings JSON NULL,

        unlock_method VARCHAR(32) NOT NULL DEFAULT 'timer',
        timer_duration_seconds INT UNSIGNED NOT NULL DEFAULT 10,

        frequency_limit_seconds INT UNSIGNED NULL,

        reward_type VARCHAR(32) NOT NULL DEFAULT 'content',

        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        INDEX idx_campaigns_status (status)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE unlock_sessions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        campaign_id BIGINT UNSIGNED NOT NULL,

        token_hash CHAR(64) NOT NULL,
        visitor_id CHAR(64) NULL,

        required_duration_seconds INT UNSIGNED NOT NULL,

        status ENUM('active', 'completed', 'expired')
                NOT NULL DEFAULT 'active',

        started_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),

        UNIQUE INDEX uq_unlock_sessions_token_hash (token_hash),
        INDEX idx_unlock_sessions_campaign_id (campaign_id),
        INDEX idx_unlock_sessions_status_expires_at (status, expires_at),
        INDEX idx_unlock_sessions_visitor_campaign
                (visitor_id, campaign_id, created_at),

        CONSTRAINT fk_unlock_sessions_campaign
                FOREIGN KEY (campaign_id)
                REFERENCES campaigns (id)
                ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE unlock_completions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        unlock_session_id BIGINT UNSIGNED NOT NULL,
        campaign_id BIGINT UNSIGNED NOT NULL,
        visitor_id CHAR(64) NULL,
        completed_at DATETIME NOT NULL,

        PRIMARY KEY (id),

        UNIQUE INDEX uq_unlock_completions_session
                (unlock_session_id),

        INDEX idx_unlock_completions_campaign_id
                (campaign_id),

        INDEX idx_unlock_completions_campaign_visitor_completed
                (campaign_id, visitor_id, completed_at),

        CONSTRAINT fk_unlock_completions_session
                FOREIGN KEY (unlock_session_id)
                REFERENCES unlock_sessions (id)
                ON DELETE RESTRICT,

        CONSTRAINT fk_unlock_completions_campaign
                FOREIGN KEY (campaign_id)
                REFERENCES campaigns (id)
                ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE admin_users (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        username VARCHAR(64) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE INDEX uq_admin_users_username (username)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
