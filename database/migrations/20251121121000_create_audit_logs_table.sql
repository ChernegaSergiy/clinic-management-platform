CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(255) NOT NULL,
    entity_id INT NOT NULL,
    user_id INT NULL, -- Assuming user_id can be null if action is automated or unauthenticated
    action VARCHAR(255) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);