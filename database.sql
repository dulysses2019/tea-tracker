-- Tea Business Database Schema
-- Run this in your MySQL database to create all tables

CREATE DATABASE IF NOT EXISTS tea_tracker;
USE tea_tracker;

-- Table to store tea product information
CREATE TABLE tea_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    vendor_cost DECIMAL(10, 2) NOT NULL COMMENT 'Cost per unit from vendor',
    selling_price DECIMAL(10, 2) NOT NULL COMMENT 'Default price per unit when selling',
    profit_per_unit DECIMAL(10, 2) GENERATED ALWAYS AS (selling_price - vendor_cost) STORED,
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = active, 0 = deleted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table to track daily inventory purchases/stock
CREATE TABLE daily_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tea_product_id INT NOT NULL,
    market_date DATE NOT NULL,
    quantity_purchased INT NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,
    quantity_remaining INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tea_product_id) REFERENCES tea_products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_date (tea_product_id, market_date)
);

-- Table to track individual sales transactions
CREATE TABLE sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tea_product_id INT NOT NULL,
    market_date DATE NOT NULL,
    quantity_sold INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL COMMENT 'Actual price sold for (may be discounted)',
    total_revenue DECIMAL(10, 2) NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL COMMENT 'Cost basis for profit calculation',
    profit DECIMAL(10, 2) GENERATED ALWAYS AS (unit_price - unit_cost) STORED,
    sale_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tea_product_id) REFERENCES tea_products(id) ON DELETE CASCADE,
    INDEX idx_market_date (market_date),
    INDEX idx_tea_product_date (tea_product_id, market_date)
);

-- Table to track daily summaries (for reporting)
CREATE TABLE daily_summary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    market_date DATE NOT NULL UNIQUE,
    total_inventory_cost DECIMAL(10, 2) DEFAULT 0,
    total_revenue DECIMAL(10, 2) DEFAULT 0,
    total_profit DECIMAL(10, 2) DEFAULT 0,
    total_units_sold INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample tea products
INSERT INTO tea_products (name, description, vendor_cost, selling_price) VALUES
('Earl Grey', 'Classic black tea with bergamot', 2.50, 5.00),
('Chamomile', 'Relaxing herbal tea', 3.00, 6.00),
('Green Tea', 'Antioxidant-rich green tea', 2.00, 4.50),
('Peppermint', 'Refreshing mint herbal tea', 2.25, 4.75),
('Oolong', 'Traditional Chinese tea', 4.00, 8.00),
('Rooibos', 'Caffeine-free red bush tea', 2.75, 5.50);

-- Stored procedure to update daily summary
DELIMITER //
CREATE PROCEDURE UpdateDailySummary(IN p_market_date DATE)
BEGIN
    DECLARE total_cost DECIMAL(10,2) DEFAULT 0;
    DECLARE total_rev DECIMAL(10,2) DEFAULT 0;
    DECLARE total_prof DECIMAL(10,2) DEFAULT 0;
    DECLARE total_sold INT DEFAULT 0;
    
    -- Calculate totals
    SELECT 
        COALESCE(SUM(di.total_cost), 0),
        COALESCE(SUM(s.total_revenue), 0),
        COALESCE(SUM(s.profit), 0),
        COALESCE(SUM(s.quantity_sold), 0)
    INTO total_cost, total_rev, total_prof, total_sold
    FROM daily_inventory di
    LEFT JOIN sales s ON di.tea_product_id = s.tea_product_id AND di.market_date = s.market_date
    WHERE di.market_date = p_market_date;
    
    -- Update or insert daily summary
    INSERT INTO daily_summary (market_date, total_inventory_cost, total_revenue, total_profit, total_units_sold)
    VALUES (p_market_date, total_cost, total_rev, total_prof, total_sold)
    ON DUPLICATE KEY UPDATE
        total_inventory_cost = total_cost,
        total_revenue = total_rev,
        total_profit = total_prof,
        total_units_sold = total_sold,
        updated_at = CURRENT_TIMESTAMP;
END//
DELIMITER ;

-- Trigger to update inventory when sale is made
DELIMITER //
CREATE TRIGGER update_inventory_on_sale
AFTER INSERT ON sales
FOR EACH ROW
BEGIN
    UPDATE daily_inventory 
    SET quantity_remaining = quantity_remaining - NEW.quantity_sold,
        updated_at = CURRENT_TIMESTAMP
    WHERE tea_product_id = NEW.tea_product_id 
    AND market_date = NEW.market_date;
    
    -- Update daily summary
    CALL UpdateDailySummary(NEW.market_date);
END//
DELIMITER ;

-- Trigger to update daily summary when inventory is added/updated
DELIMITER //
CREATE TRIGGER update_summary_on_inventory
AFTER INSERT ON daily_inventory
FOR EACH ROW
BEGIN
    CALL UpdateDailySummary(NEW.market_date);
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER update_summary_on_inventory_update
AFTER UPDATE ON daily_inventory
FOR EACH ROW
BEGIN
    CALL UpdateDailySummary(NEW.market_date);
END//
DELIMITER ;

-- Create user for the application (optional but recommended)
-- CREATE USER 'tea_app'@'localhost' IDENTIFIED BY 'your_secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON tea_tracker.* TO 'tea_app'@'localhost';
-- FLUSH PRIVILEGES;