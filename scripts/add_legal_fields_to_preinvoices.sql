-- Add legal fields to pre_invoices table (supply_date, authorized_person, payment_method, fiscal_receipt_number)
-- Run this in phpMyAdmin after selecting the invoices_and_offers database

USE `invoices_and_offers`;

-- Add supply_date column (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'pre_invoices' 
               AND column_name = 'supply_date');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE pre_invoices ADD COLUMN supply_date DATE DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add authorized_person column (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'pre_invoices' 
               AND column_name = 'authorized_person');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE pre_invoices ADD COLUMN authorized_person VARCHAR(100) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add payment_method column (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'pre_invoices' 
               AND column_name = 'payment_method');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE pre_invoices ADD COLUMN payment_method VARCHAR(50) DEFAULT ''bank_transfer''',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add fiscal_receipt_number column (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'pre_invoices' 
               AND column_name = 'fiscal_receipt_number');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE pre_invoices ADD COLUMN fiscal_receipt_number VARCHAR(100) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;




