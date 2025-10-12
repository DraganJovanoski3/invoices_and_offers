-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CLIENTS TABLE
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

-- SERVICES TABLE
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- INVOICES TABLE
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
    offer_id INT NULL,
    is_vat_obvrznik TINYINT(1) DEFAULT 0,
    global_discount_rate DECIMAL(5,2) DEFAULT 0.00,
    global_discount_amount DECIMAL(10,2) DEFAULT 0.00,
    is_global_discount BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL
);

-- INVOICE ITEMS TABLE
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    service_id INT,
    item_name VARCHAR(100) NOT NULL DEFAULT '',
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    discount_rate DECIMAL(5,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    is_discount BOOLEAN DEFAULT FALSE,
    final_price DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- OFFERS TABLE
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
    payment_type ENUM('full', 'advance') DEFAULT 'full',
    advance_amount DECIMAL(10,2) DEFAULT 0.00,
    remaining_amount DECIMAL(10,2) DEFAULT 0.00,
    is_vat_obvrznik TINYINT(1) DEFAULT 0,
    global_discount_rate DECIMAL(5,2) DEFAULT 0.00,
    global_discount_amount DECIMAL(10,2) DEFAULT 0.00,
    is_global_discount BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- OFFER ITEMS TABLE
CREATE TABLE IF NOT EXISTS offer_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    service_id INT,
    item_name VARCHAR(100) NOT NULL DEFAULT '',
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    discount_rate DECIMAL(5,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    is_discount BOOLEAN DEFAULT FALSE,
    final_price DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- COMPANY SETTINGS TABLE
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- PRE-INVOICES TABLE
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
    notes TEXT,
    status ENUM('draft', 'sent', 'paid', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    final_invoice_id INT DEFAULT NULL,
    remaining_amount DECIMAL(10,2) DEFAULT 0.00,
    is_vat_obvrznik TINYINT(1) DEFAULT 0,
    global_discount_rate DECIMAL(5,2) DEFAULT 0.00,
    global_discount_amount DECIMAL(10,2) DEFAULT 0.00,
    is_global_discount TINYINT(1) DEFAULT 0,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (final_invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
);

-- PRE-INVOICE ITEMS TABLE
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