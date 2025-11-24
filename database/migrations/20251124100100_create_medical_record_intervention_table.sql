CREATE TABLE medical_record_intervention (
    medical_record_id INT NOT NULL,
    intervention_code_id INT NOT NULL,
    PRIMARY KEY (medical_record_id, intervention_code_id),
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
    FOREIGN KEY (intervention_code_id) REFERENCES intervention_codes(id) ON DELETE CASCADE
);
