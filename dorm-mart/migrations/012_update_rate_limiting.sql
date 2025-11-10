-- 012_update_rate_limiting.sql
-- Removes email-based rate limiting columns from user_accounts and drops ip_login_attempts table
-- Rate limiting is now handled via PHP sessions (session-based rate limiting)

DROP TABLE IF EXISTS ip_login_attempts;

ALTER TABLE user_accounts
DROP COLUMN failed_login_attempts,
DROP COLUMN last_failed_attempt,
DROP COLUMN lockout_until;

