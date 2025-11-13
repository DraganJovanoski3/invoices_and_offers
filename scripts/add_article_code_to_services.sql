-- Add article_code column to services table
-- Run this in phpMyAdmin after selecting the invoices_and_offers database

USE `invoices_and_offers`;

-- Add article_code column (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'services' 
               AND column_name = 'article_code');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE services ADD COLUMN article_code VARCHAR(100) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


