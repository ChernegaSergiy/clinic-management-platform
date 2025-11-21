CREATE TABLE attachment_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attachment_id INT NOT NULL,
    version_number INT NOT NULL DEFAULT 1,
    filepath VARCHAR(255) NOT NULL, -- Path to this specific version file
    filename VARCHAR(255) NOT NULL,
    size INT NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attachment_id) REFERENCES attachments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (attachment_id, version_number)
);