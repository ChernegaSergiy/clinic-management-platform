CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    inn VARCHAR(255), -- International Nonproprietary Name
    batch_number VARCHAR(255),
    expiry_date DATE,
    supplier VARCHAR(255),
    cost DECIMAL(10, 2),
    quantity INT NOT NULL DEFAULT 0,
    min_stock_threshold INT NOT NULL DEFAULT 0,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
