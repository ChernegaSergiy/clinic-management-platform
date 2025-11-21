CREATE TABLE lab_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(255) NULL, -- e.g., 'machine', 'technician', 'room'
    capacity INT DEFAULT 1, -- How many tasks it can handle simultaneously
    is_available BOOLEAN DEFAULT TRUE,
    notes TEXT NULL
);