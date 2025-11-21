CREATE TABLE lab_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    medical_record_id INT NOT NULL,
    order_code VARCHAR(255) NOT NULL,
    status ENUM('ordered', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'ordered',
    qr_code_hash VARCHAR(255) UNIQUE,
    results TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id),
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id)
);
