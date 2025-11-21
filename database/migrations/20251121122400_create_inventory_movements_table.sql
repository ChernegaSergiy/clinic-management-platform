CREATE TABLE inventory_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_item_id INT NOT NULL,
    user_id INT NULL, -- User who performed the movement
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity_change INT NOT NULL, -- Positive for 'in', negative for 'out'
    new_quantity INT NOT NULL, -- Quantity after this movement
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);