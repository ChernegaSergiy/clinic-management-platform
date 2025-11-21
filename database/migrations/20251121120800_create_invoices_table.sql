CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_id INT,
    medical_record_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    issued_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id)
);
