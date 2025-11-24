CREATE TABLE intervention_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(255) NOT NULL,
    description TEXT,
    UNIQUE KEY code_unique (code)
);
