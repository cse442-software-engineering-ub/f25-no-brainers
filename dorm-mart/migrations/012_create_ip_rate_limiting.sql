-- Migration 012: Create IP-based rate limiting table and remove email-based rate limiting
-- Author: Rate Limiting Fix
-- Date: 2025-01-XX
-- Description: Creates ip_login_attempts table for tracking failed login attempts by IP address
--              Removes email-based rate limiting columns from user_accounts table

-- Create ip_login_attempts table for IP-based rate limiting
CREATE TABLE IF NOT EXISTS ip_login_attempts (
  ip_address VARCHAR(45) NOT NULL PRIMARY KEY COMMENT 'Client IP address (supports IPv6)',
  failed_attempts INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of failed login attempts from this IP',
  last_failed_attempt TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp of last failed attempt',
  lockout_until DATETIME NULL DEFAULT NULL COMMENT 'When the IP lockout expires'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remove email-based rate limiting columns from user_accounts table
-- Use safe checks to prevent errors if columns don't exist

-- Remove failed_login_attempts column
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'user_accounts' 
     AND COLUMN_NAME = 'failed_login_attempts') > 0,
    'ALTER TABLE user_accounts DROP COLUMN failed_login_attempts',
    'SELECT "Column failed_login_attempts does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove last_failed_attempt column
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'user_accounts' 
     AND COLUMN_NAME = 'last_failed_attempt') > 0,
    'ALTER TABLE user_accounts DROP COLUMN last_failed_attempt',
    'SELECT "Column last_failed_attempt does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove lockout_until column
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'user_accounts' 
     AND COLUMN_NAME = 'lockout_until') > 0,
    'ALTER TABLE user_accounts DROP COLUMN lockout_until',
    'SELECT "Column lockout_until does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

