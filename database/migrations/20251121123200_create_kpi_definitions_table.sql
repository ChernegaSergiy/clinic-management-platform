CREATE TABLE kpi_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    kpi_type ENUM('appointments_count', 'revenue_generated', 'patient_satisfaction') NOT NULL, -- Example types
    target_value DECIMAL(10, 2) NULL, -- E.g., target appointments per month
    unit VARCHAR(50) NULL, -- E.g., 'count', 'UAH', '%'
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);