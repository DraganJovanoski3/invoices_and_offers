-- Add missing columns to company_settings table
-- Run this in phpMyAdmin after selecting the invoices_and_offers database

USE `invoices_and_offers`;

-- Add currency column (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'company_settings' 
               AND column_name = 'currency');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE company_settings ADD COLUMN currency VARCHAR(3) DEFAULT ''MKD''',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add vat_registration_number column (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'company_settings' 
               AND column_name = 'vat_registration_number');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE company_settings ADD COLUMN vat_registration_number VARCHAR(100) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add authorized_person column (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'company_settings' 
               AND column_name = 'authorized_person');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE company_settings ADD COLUMN authorized_person VARCHAR(100) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add legal_footer column (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'company_settings' 
               AND column_name = 'legal_footer');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE company_settings ADD COLUMN legal_footer TEXT DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


