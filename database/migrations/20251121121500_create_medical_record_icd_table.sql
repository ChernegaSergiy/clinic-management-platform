CREATE TABLE medical_record_icd (
    medical_record_id INT NOT NULL,
    icd_code_id INT NOT NULL,
    PRIMARY KEY (medical_record_id, icd_code_id),
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
    FOREIGN KEY (icd_code_id) REFERENCES icd_codes(id) ON DELETE CASCADE
);