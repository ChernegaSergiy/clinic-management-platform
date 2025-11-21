CREATE TABLE lab_order_resources (
    lab_order_id INT NOT NULL,
    lab_resource_id INT NOT NULL,
    PRIMARY KEY (lab_order_id, lab_resource_id),
    FOREIGN KEY (lab_order_id) REFERENCES lab_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (lab_resource_id) REFERENCES lab_resources(id) ON DELETE CASCADE
);