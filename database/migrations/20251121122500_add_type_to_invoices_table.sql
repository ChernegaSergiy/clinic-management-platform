ALTER TABLE invoices
ADD COLUMN type ENUM('invoice', 'inventory_cost', 'inventory_revenue') NOT NULL DEFAULT 'invoice';