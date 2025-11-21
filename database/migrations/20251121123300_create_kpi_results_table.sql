CREATE TABLE kpi_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kpi_id INT NOT NULL,
    user_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    calculated_value DECIMAL(10, 2) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kpi_id) REFERENCES kpi_definitions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (kpi_id, user_id, period_start, period_end) -- Ensure unique result for a KPI, user, and period
);