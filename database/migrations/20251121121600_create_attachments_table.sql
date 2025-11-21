CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(255) NOT NULL, -- e.g., 'medical_record', 'patient'
    entity_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(255) NOT NULL, -- Relative path to storage
    mime_type VARCHAR(255) NOT NULL,
    size INT NOT NULL, -- File size in bytes
    created_by INT NULL, -- User who uploaded the attachment
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);