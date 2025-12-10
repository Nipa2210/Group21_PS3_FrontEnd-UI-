-- Role-specific Views and optional physical tables for the E-Learning schema
-- Recommended approach: create SQL VIEWs that present role-filtered rows from the single `users` table.
-- Views avoid data duplication and keep relationships intact.

-- 1) Create role views (safe, up-to-date with users table)
CREATE OR REPLACE VIEW `students_view` AS
SELECT id, name, email, role, phone, address, created_at, updated_at, is_active
FROM users
WHERE role = 'student';

CREATE OR REPLACE VIEW `instructors_view` AS
SELECT id, name, email, role, phone, address, created_at, updated_at, is_active
FROM users
WHERE role = 'instructor';

CREATE OR REPLACE VIEW `admins_view` AS
SELECT id, name, email, role, phone, address, created_at, updated_at, is_active
FROM users
WHERE role = 'admin';

CREATE OR REPLACE VIEW `course_team_view` AS
SELECT id, name, email, role, phone, address, created_at, updated_at, is_active
FROM users
WHERE role = 'course_team';

CREATE OR REPLACE VIEW `dept_head_view` AS
SELECT id, name, email, role, phone, address, created_at, updated_at, is_active
FROM users
WHERE role = 'dept_head';

CREATE OR REPLACE VIEW `data_analyst_view` AS
SELECT id, name, email, role, phone, address, created_at, updated_at, is_active
FROM users
WHERE role = 'data_analyst';

CREATE OR REPLACE VIEW `office_manager_view` AS
SELECT id, name, email, role, phone, address, created_at, updated_at, is_active
FROM users
WHERE role = 'office_manager';

-- 2) Optional: create physical tables (snapshots) if you really need separate tables
-- NOTE: This duplicates data and is not recommended for production. Use only if you understand the tradeoffs.
-- The statements below will DROP the role tables if they exist and recreate them from the current users table.
-- Uncomment and run if you want physical copies.

-- DROP TABLE IF EXISTS `students`;
-- CREATE TABLE `students` AS
-- SELECT * FROM users WHERE role = 'student';

-- DROP TABLE IF EXISTS `instructors`;
-- CREATE TABLE `instructors` AS
-- SELECT * FROM users WHERE role = 'instructor';

-- DROP TABLE IF EXISTS `admins`;
-- CREATE TABLE `admins` AS
-- SELECT * FROM users WHERE role = 'admin';

-- DROP TABLE IF EXISTS `course_team`;
-- CREATE TABLE `course_team` AS
-- SELECT * FROM users WHERE role = 'course_team';

-- DROP TABLE IF EXISTS `dept_head`;
-- CREATE TABLE `dept_head` AS
-- SELECT * FROM users WHERE role = 'dept_head';

-- DROP TABLE IF EXISTS `data_analyst`;
-- CREATE TABLE `data_analyst` AS
-- SELECT * FROM users WHERE role = 'data_analyst';

-- DROP TABLE IF EXISTS `office_manager`;
-- CREATE TABLE `office_manager` AS
-- SELECT * FROM users WHERE role = 'office_manager';

-- 3) Helpful queries to inspect the views/tables
-- Count by role (from users):
-- SELECT role, COUNT(*) AS cnt FROM users GROUP BY role;

-- Browse first 50 students (from view):
-- SELECT * FROM students_view LIMIT 50;

-- If you created physical tables, browse them similarly:
-- SELECT * FROM students LIMIT 50;

-- End of script
