CREATE TABLE bundle_services (
    bundle_id INT NOT NULL,
    service_id INT NOT NULL,
    PRIMARY KEY (bundle_id, service_id),
    FOREIGN KEY (bundle_id) REFERENCES service_bundles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);