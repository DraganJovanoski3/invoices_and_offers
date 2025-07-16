-- Add discount fields to invoices table
ALTER TABLE invoices 
ADD COLUMN global_discount_rate DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN global_discount_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN is_global_discount BOOLEAN DEFAULT FALSE;

-- Add discount fields to offers table
ALTER TABLE offers 
ADD COLUMN global_discount_rate DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN global_discount_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN is_global_discount BOOLEAN DEFAULT FALSE;

-- Add discount fields to invoice_items table
ALTER TABLE invoice_items 
ADD COLUMN discount_rate DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN is_discount BOOLEAN DEFAULT FALSE,
ADD COLUMN final_price DECIMAL(10,2) DEFAULT 0.00;

-- Add discount fields to offer_items table
ALTER TABLE offer_items 
ADD COLUMN discount_rate DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN is_discount BOOLEAN DEFAULT FALSE,
ADD COLUMN final_price DECIMAL(10,2) DEFAULT 0.00; 