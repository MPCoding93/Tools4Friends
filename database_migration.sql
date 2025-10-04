-- ============================================
-- Tools4Friends Admin System Database Migration
-- ============================================

-- 1. CREATE ORDERS TABLE
-- This table stores the main order information
CREATE TABLE IF NOT EXISTS Orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'denied') DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    total_deposit DECIMAL(10,2) DEFAULT 0.00,
    invoice_number VARCHAR(50) UNIQUE,
    denial_reason TEXT,
    approved_by INT,
    approved_date DATETIME,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (approved_by) REFERENCES Users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. CREATE COMPANY_SETTINGS TABLE
-- This table stores company information for invoices
CREATE TABLE IF NOT EXISTS Company_Settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) DEFAULT 'Tools4Friends',
    company_email VARCHAR(255),
    company_phone VARCHAR(50),
    bank_name VARCHAR(255),
    bank_account VARCHAR(100),
    bank_iban VARCHAR(50),
    bank_swift VARCHAR(50),
    qr_code_image VARCHAR(255),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. INSERT DEFAULT COMPANY SETTINGS
-- Add a default row so the settings page has something to edit
INSERT INTO Company_Settings (company_name, company_email, company_phone) 
VALUES ('Tools4Friends', 'info@tools4friends.com', '+420 XXX XXX XXX')
ON DUPLICATE KEY UPDATE setting_id=setting_id;

-- 4. UPDATE AVAILABILITY TABLE STATUS COLUMN
-- Modify the status column to support new status values
-- Note: This will preserve existing data
ALTER TABLE Availability 
MODIFY COLUMN status ENUM('pending', 'approved', 'denied', 'active', 'completed', 'reserved') 
DEFAULT 'pending';

-- ============================================
-- SUMMARY OF CHANGES:
-- ============================================
-- 
-- NEW TABLES CREATED:
-- 1. Orders - Stores order header information
-- 2. Company_Settings - Stores company details for invoices
--
-- EXISTING TABLES MODIFIED:
-- 1. Availability - Updated status column to include new values
--    - Old: 'reserved' (and possibly others)
--    - New: 'pending', 'approved', 'denied', 'active', 'completed', 'reserved'
--
-- EXISTING TABLES USED (NO CHANGES):
-- 1. Availability.order_id - Will link to Orders.order_id
-- 2. Tools.deposit - Will be used in invoice calculations
-- 3. Tools.manipulation_fee - Will be used in invoice calculations
-- 4. Users.admin - Will be used for admin access control
--
-- ============================================
