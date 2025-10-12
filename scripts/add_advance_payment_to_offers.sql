-- Add advance payment fields to offers table
ALTER TABLE offers 
ADD COLUMN payment_type ENUM('full', 'advance') DEFAULT 'full',
ADD COLUMN advance_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN remaining_amount DECIMAL(10,2) DEFAULT 0.00; 