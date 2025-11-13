-- Complete Database Setup Script
-- This script creates the database and all tables
-- Run this in phpMyAdmin's SQL tab

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `invoices_and_offers` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Select the database
USE `invoices_and_offers`;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create clients table
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    company_name VARCHAR(255),
    logo_path VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    tax_number VARCHAR(100),
    website VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create services table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    article_code VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create invoices table
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL,
    notes TEXT,
    status ENUM('draft', 'sent', 'paid', 'overdue') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Create invoice_items table
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    service_id INT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- Create offers table
CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_number VARCHAR(20) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    issue_date DATE NOT NULL,
    valid_until DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL,
    notes TEXT,
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Create offer_items table
CREATE TABLE IF NOT EXISTS offer_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    service_id INT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- Create company_settings table
CREATE TABLE IF NOT EXISTS company_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(100),
    tax_number VARCHAR(50),
    bank_account VARCHAR(50),
    logo_path VARCHAR(255),
    currency VARCHAR(3) DEFAULT 'MKD',
    vat_registration_number VARCHAR(100) DEFAULT NULL,
    authorized_person VARCHAR(100) DEFAULT NULL,
    legal_footer TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create pre_invoices table
CREATE TABLE IF NOT EXISTS pre_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    preinvoice_number VARCHAR(20) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_type ENUM('full', 'advance') DEFAULT 'full',
    advance_amount DECIMAL(10,2) DEFAULT 0.00,
    remaining_amount DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    status ENUM('draft', 'sent', 'paid', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    final_invoice_id INT DEFAULT NULL,
    is_vat_obvrznik TINYINT(1) DEFAULT 0,
    global_discount_rate DECIMAL(5,2) DEFAULT 0.00,
    global_discount_amount DECIMAL(10,2) DEFAULT 0.00,
    is_global_discount TINYINT(1) DEFAULT 0,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (final_invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
);

-- Create pre_invoice_items table
CREATE TABLE IF NOT EXISTS pre_invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    preinvoice_id INT NOT NULL,
    service_id INT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    discount_rate DECIMAL(5,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    is_discount BOOLEAN DEFAULT FALSE,
    final_price DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (preinvoice_id) REFERENCES pre_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- Add offer_id column to invoices table (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'invoices' 
               AND column_name = 'offer_id');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE invoices ADD COLUMN offer_id INT NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint for offer_id (if not exists)
SET @exist := (SELECT COUNT(*) FROM information_schema.table_constraints 
               WHERE constraint_schema = DATABASE() 
               AND table_name = 'invoices' 
               AND constraint_name = 'fk_invoices_offer');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE invoices ADD CONSTRAINT fk_invoices_offer FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add VAT and discount fields to invoices (if not exist)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'invoices' 
               AND column_name = 'is_vat_obvrznik');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE invoices ADD COLUMN is_vat_obvrznik TINYINT(1) DEFAULT 0, ADD COLUMN global_discount_rate DECIMAL(5,2) DEFAULT 0.00, ADD COLUMN global_discount_amount DECIMAL(10,2) DEFAULT 0.00, ADD COLUMN is_global_discount BOOLEAN DEFAULT FALSE',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add VAT and advance payment fields to offers (if not exist)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'offers' 
               AND column_name = 'is_vat_obvrznik');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE offers ADD COLUMN is_vat_obvrznik TINYINT(1) DEFAULT 0, ADD COLUMN payment_type ENUM(\'full\', \'advance\') DEFAULT \'full\', ADD COLUMN advance_amount DECIMAL(10,2) DEFAULT 0.00, ADD COLUMN remaining_amount DECIMAL(10,2) DEFAULT 0.00, ADD COLUMN global_discount_rate DECIMAL(5,2) DEFAULT 0.00, ADD COLUMN global_discount_amount DECIMAL(10,2) DEFAULT 0.00, ADD COLUMN is_global_discount BOOLEAN DEFAULT FALSE',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add discount fields to invoice_items (if not exist)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'invoice_items' 
               AND column_name = 'discount_rate');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE invoice_items ADD COLUMN discount_rate DECIMAL(5,2) DEFAULT 0.00, ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00, ADD COLUMN is_discount BOOLEAN DEFAULT FALSE, ADD COLUMN final_price DECIMAL(10,2) DEFAULT 0.00',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add discount fields to offer_items (if not exist)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'offer_items' 
               AND column_name = 'discount_rate');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE offer_items ADD COLUMN discount_rate DECIMAL(5,2) DEFAULT 0.00, ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00, ADD COLUMN is_discount BOOLEAN DEFAULT FALSE, ADD COLUMN final_price DECIMAL(10,2) DEFAULT 0.00',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add missing columns to company_settings (if not exist)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'company_settings' 
               AND column_name = 'currency');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE company_settings ADD COLUMN currency VARCHAR(3) DEFAULT ''MKD'', ADD COLUMN vat_registration_number VARCHAR(100) DEFAULT NULL, ADD COLUMN authorized_person VARCHAR(100) DEFAULT NULL, ADD COLUMN legal_footer TEXT DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert default admin user
INSERT IGNORE INTO users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@yourdomain.com');

-- Insert sample services
INSERT IGNORE INTO services (name, price, description) VALUES 
('Web Development', 75.00, 'Custom website development'),
('Logo Design', 150.00, 'Professional logo design'),
('SEO Optimization', 50.00, 'Search engine optimization'),
('Content Writing', 25.00, 'Professional content writing'),
('Maintenance', 30.00, 'Website maintenance and updates');

