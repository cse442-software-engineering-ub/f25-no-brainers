START TRANSACTION;
-- ^ Begin a transaction so the insert is all-or-nothing.

DELETE FROM user_accounts
WHERE email = 'testuser@buffalo.edu';

INSERT INTO user_accounts (
  first_name,
  last_name,
  grad_month,
  grad_year,
  email,
  promotional,
  hash_pass,
  hash_auth,
  seller,
  theme
) VALUES (
  'test',
  'general-test-user',
  5,
  2027,
  'testuser@buffalo.edu',
  0,
  '$2y$10$GbrdUE1/URrVdrSoa83d1OMfNWeJAuuzyEU4UvMMANKeub4./C.UO',
  NULL,
  0,
  0
);

COMMIT;


