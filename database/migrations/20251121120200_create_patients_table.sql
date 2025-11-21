CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    middle_name VARCHAR(255),
    birth_date DATE NOT NULL,
    gender ENUM('male', 'female', 'other', 'unknown') NOT NULL,
    phone VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    address TEXT,
    tax_id VARCHAR(255) UNIQUE,
    document_id VARCHAR(255) UNIQUE,
    ehealth_patient_id VARCHAR(36) UNIQUE,
    active BOOLEAN DEFAULT TRUE,
    deceased_date DATE,
    marital_status ENUM('single', 'married', 'divorced', 'widowed', 'unknown'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
