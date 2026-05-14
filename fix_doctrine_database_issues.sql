-- ============================================================================
-- Doctrine Doctor Database Fixes
-- ============================================================================
-- This script fixes database-level issues identified by Doctrine Doctor
-- Run this script with: mysql -u root -p pidevf < fix_doctrine_database_issues.sql
-- ============================================================================

USE pidevf;

-- ============================================================================
-- 1. FIX TIMEZONE MISMATCH
-- ============================================================================
-- Set MySQL timezone to UTC to match PHP timezone
SET GLOBAL time_zone = '+00:00';
SET SESSION time_zone = '+00:00';

-- ============================================================================
-- 2. ENABLE SQL STRICT MODE
-- ============================================================================
-- Enable strict mode to prevent silent data truncation and invalid data
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ============================================================================
-- 3. FIX TABLE COLLATIONS
-- ============================================================================
-- Convert all tables to use the database default collation (utf8mb4_0900_ai_ci)
-- This improves JOIN performance and ensures consistent sorting

ALTER TABLE activite CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE candidat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE candidature CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE conge_tt CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE contract CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE demande_service CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE employe CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE evenement CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE event_participation CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE offre_emploi CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE rating CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE rh CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE service_reaction CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE type_service CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- Add more tables if needed (you mentioned 21 tables total)
-- Check remaining tables with:
-- SELECT TABLE_NAME, TABLE_COLLATION 
-- FROM information_schema.TABLES 
-- WHERE TABLE_SCHEMA = 'pidevf' 
-- AND TABLE_COLLATION != 'utf8mb4_0900_ai_ci';

-- ============================================================================
-- 4. ADD BLAMEABLE FIELDS TO service_reaction
-- ============================================================================
-- Add audit trail fields (created_by, updated_by, updated_at)
ALTER TABLE service_reaction 
    ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER created_at,
    ADD COLUMN created_by BIGINT DEFAULT NULL AFTER updated_at,
    ADD COLUMN updated_by BIGINT DEFAULT NULL AFTER created_by,
    ADD CONSTRAINT FK_service_reaction_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT FK_service_reaction_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================================================
-- 5. FIX CANDIDATURE ID TYPE MISMATCH
-- ============================================================================
-- Change candidature.id from INT to BIGINT to match entity definition
-- WARNING: This may take time on large tables
ALTER TABLE candidature MODIFY COLUMN id BIGINT AUTO_INCREMENT;

-- ============================================================================
-- 6. FIX DEMANDE_SERVICE TYPE_ID NOT NULL CONSTRAINT
-- ============================================================================
-- Ensure type_id is NOT NULL to match orphanRemoval=true in TypeService
-- First, check if there are any NULL values and handle them
UPDATE demande_service SET type_id = 1 WHERE type_id IS NULL AND EXISTS (SELECT 1 FROM type_service WHERE id = 1);

-- Now make the column NOT NULL
ALTER TABLE demande_service MODIFY COLUMN type_id BIGINT NOT NULL;

-- ============================================================================
-- NOTES FOR MANUAL FIXES
-- ============================================================================

-- NOTE 1: SECURITY - Empty Database Password
-- ============================================
-- CRITICAL: Create a strong password for the root user
-- Run these commands in MySQL:
-- ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_strong_password_here';
-- FLUSH PRIVILEGES;
-- Then update your .env file with the new password

-- NOTE 2: SECURITY - Overprivileged Database User
-- ================================================
-- Create a dedicated database user with limited privileges:
-- CREATE USER 'pidevf_user'@'localhost' IDENTIFIED BY 'strong_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON pidevf.* TO 'pidevf_user'@'localhost';
-- FLUSH PRIVILEGES;
-- Then update DATABASE_URL in .env to use this new user

-- NOTE 3: TIMEZONE TABLES
-- =======================
-- Load MySQL timezone tables for better timezone support:
-- On Linux/Mac: mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root -p mysql
-- On Windows: Download and import timezone tables from MySQL website

-- NOTE 4: DEVELOPMENT PERFORMANCE
-- ================================
-- For development only, you can improve write performance:
-- SET GLOBAL innodb_flush_log_at_trx_commit = 2;
-- WARNING: Keep this at 1 in production for data safety!

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Verify timezone settings
SELECT @@global.time_zone, @@session.time_zone;

-- Verify SQL mode
SELECT @@global.sql_mode, @@session.sql_mode;

-- Check table collations
SELECT TABLE_NAME, TABLE_COLLATION 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'pidevf' 
ORDER BY TABLE_NAME;

-- Verify candidature.id type
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'pidevf' 
AND TABLE_NAME = 'candidature' 
AND COLUMN_NAME = 'id';

-- Verify service_reaction audit fields
DESCRIBE service_reaction;

SELECT 'Database fixes applied successfully!' AS status;
