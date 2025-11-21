CREATE TABLE attachment_acl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attachment_id INT NOT NULL,
    user_id INT NULL, -- NULL means public or inherited access
    role_id INT NULL, -- NULL means public or inherited access
    can_view BOOLEAN NOT NULL DEFAULT FALSE,
    can_edit BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attachment_id) REFERENCES attachments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE (attachment_id, user_id, role_id) -- Ensures unique permissions per attachment for a user/role
);