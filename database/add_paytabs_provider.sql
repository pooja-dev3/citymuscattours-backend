-- Migration script to add 'paytabs' as a payment provider option
-- Run this script: mysql -u root -p tour_travels < database/add_paytabs_provider.sql
-- Or execute this SQL in phpMyAdmin

USE tour_travels;

-- Modify the payments table to add 'paytabs' to the provider ENUM
-- Note: This may fail if there are existing records with incompatible data
ALTER TABLE payments MODIFY COLUMN provider ENUM('razorpay', 'stripe', 'paytabs') NOT NULL;
