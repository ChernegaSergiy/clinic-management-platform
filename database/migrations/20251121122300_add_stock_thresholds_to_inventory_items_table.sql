ALTER TABLE inventory_items
ADD COLUMN min_stock_level INT NOT NULL DEFAULT 0,
ADD COLUMN max_stock_level INT NOT NULL DEFAULT 0;