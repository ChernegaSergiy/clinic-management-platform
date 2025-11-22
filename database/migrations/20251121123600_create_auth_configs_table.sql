CREATE TABLE auth_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(255) NOT NULL UNIQUE, -- e.g., 'google', 'facebook', 'mfa_totp'
    client_id VARCHAR(255) NULL,
    client_secret VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT FALSE,
    config TEXT NULL, -- JSON for additional settings
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);