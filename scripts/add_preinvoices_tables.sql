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
    notes TEXT,
    status ENUM('draft', 'sent', 'paid', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    final_invoice_id INT DEFAULT NULL,
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