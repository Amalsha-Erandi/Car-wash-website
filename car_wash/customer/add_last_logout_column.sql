-- Add last_logout column to customers table
ALTER TABLE `customers` ADD COLUMN `last_logout` timestamp NULL DEFAULT NULL AFTER `last_login`; 