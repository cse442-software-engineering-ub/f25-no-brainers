-- Migration 012: Create IP-based rate limiting table
-- Author: Rate Limiting Fix
-- Date: 2025-01-XX
-- Description: Creates ip_login_attempts table for tracking failed login attempts by IP address

-- Create ip_login_attempts table for IP-based rate limiting
CREATE TABLE IF NOT EXISTS ip_login_attempts (
  ip_address VARCHAR(45) NOT NULL PRIMARY KEY COMMENT 'Client IP address (supports IPv6)',
  failed_attempts INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of failed login attempts from this IP',
  last_failed_attempt TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp of last failed attempt',
  lockout_until DATETIME NULL DEFAULT NULL COMMENT 'When the IP lockout expires'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

