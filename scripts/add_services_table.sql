-- Add Services Table to Existing Database
-- Run this in phpMyAdmin or your MySQL client

-- Create services table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample services
INSERT INTO services (name, price, description) VALUES 
('Web Development', 75.00, 'Custom website development'),
('Logo Design', 150.00, 'Professional logo design'),
('SEO Optimization', 50.00, 'Search engine optimization'),
('Content Writing', 25.00, 'Professional content writing'),
('Maintenance', 30.00, 'Website maintenance and updates');

-- Update invoice_items table to add service_id column (if it doesn't exist)
ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS service_id INT;
ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS item_name VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS description TEXT;
ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add foreign key constraint for service_id
ALTER TABLE invoice_items ADD CONSTRAINT IF NOT EXISTS fk_invoice_items_service 
FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL;

-- Update offer_items table to add service_id column (if it doesn't exist)
ALTER TABLE offer_items ADD COLUMN IF NOT EXISTS service_id INT;
ALTER TABLE offer_items ADD COLUMN IF NOT EXISTS item_name VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE offer_items ADD COLUMN IF NOT EXISTS description TEXT;
ALTER TABLE offer_items ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add foreign key constraint for service_id in offer_items
ALTER TABLE offer_items ADD CONSTRAINT IF NOT EXISTS fk_offer_items_service 
FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL; 