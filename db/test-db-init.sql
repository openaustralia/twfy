-- Creates the test database and grants the application user access to it.
-- This file is mounted into /docker-entrypoint-initdb.d/ so it runs automatically
-- when the MySQL container is initialised for the first time.
-- For existing containers, run: make docker-test-db-create
CREATE DATABASE IF NOT EXISTS test_twfy CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
GRANT ALL ON test_twfy.* TO 'twfyuser'@'%';
FLUSH PRIVILEGES;
