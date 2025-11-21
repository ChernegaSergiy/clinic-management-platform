CREATE TABLE contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    party_a VARCHAR(255) NULL, -- E.g., Clinic name
    party_b VARCHAR(255) NULL, -- E.g., Patient name, Supplier name, Doctor name
    file_path VARCHAR(255) NULL, -- Path to uploaded contract file
    status ENUM('active', 'expired', 'terminated') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);