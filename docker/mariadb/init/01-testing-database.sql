-- Runs on first initialization of the mariadb_data volume only.
-- Creates the dedicated test database used by phpunit.xml (RefreshDatabase
-- wipes it on every test run; it must never be the dev database).
CREATE DATABASE IF NOT EXISTS `walfa_testing`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `walfa_testing`.* TO 'laravel'@'%';
