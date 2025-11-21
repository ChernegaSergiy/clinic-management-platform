CREATE TABLE waitlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    desired_doctor_id INT NULL,
    desired_start_time DATETIME NULL,
    desired_end_time DATETIME NULL,
    notes TEXT NULL,
    status ENUM('pending', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (desired_doctor_id) REFERENCES users(id) ON DELETE SET NULL
);