-- Add missing columns to pre_invoices table
ALTER TABLE pre_invoices 
ADD COLUMN remaining_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN is_vat_obvrznik TINYINT(1) DEFAULT 0,
ADD COLUMN global_discount_rate DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN global_discount_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN is_global_discount TINYINT(1) DEFAULT 0; 