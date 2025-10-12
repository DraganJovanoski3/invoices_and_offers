-- Add offer_id column to invoices table to link invoices to offers
ALTER TABLE invoices 
ADD COLUMN offer_id INT NULL;

ALTER TABLE invoices 
ADD CONSTRAINT fk_invoices_offer FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL; 