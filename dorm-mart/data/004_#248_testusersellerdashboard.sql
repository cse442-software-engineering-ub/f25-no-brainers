START TRANSACTION;
-- ^ Begin a transaction so the insert is all-or-nothing.

DELETE FROM user_accounts
WHERE email = '004#248_testusersellerdashboard@buffalo.edu';

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
  '004#248',
  'testusersellerdashboard',
  5,
  2027,
  '004#248_testusersellerdashboard@buffalo.edu',
  0,
  '$2y$10$ybNS2QE7ybMophy8WuJrAOdC3JxaiyY1MI18Wlha4mxTYVoyLTJFi',
  NULL,
  0,
  0
);

COMMIT;


