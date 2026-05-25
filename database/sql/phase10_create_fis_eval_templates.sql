-- ====================================================================
-- Fase 10 — Tabla fis_eval_templates para plantillas de evaluación
--
-- Ejecutar en producción si la migration no está disponible.
-- Idempotente — verifica antes de crear.
-- ====================================================================

SET @dbname = DATABASE();

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fis_eval_templates') = 0,
    "CREATE TABLE `fis_eval_templates` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `tabla_form` VARCHAR(64) NOT NULL,
        `name` VARCHAR(191) NOT NULL,
        `description` TEXT NULL,
        `scope` ENUM('personal','global') NOT NULL DEFAULT 'personal',
        `payload` LONGTEXT NOT NULL,
        `created_by` BIGINT UNSIGNED NULL,
        `updated_by` BIGINT UNSIGNED NULL,
        `status` TINYINT NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NULL,
        `updated_at` TIMESTAMP NULL,
        PRIMARY KEY (`id`),
        KEY `idx_evtpl_tabla_form` (`tabla_form`),
        KEY `idx_evtpl_created_by` (`created_by`),
        KEY `idx_evtpl_tabla_scope_status` (`tabla_form`, `scope`, `status`),
        KEY `idx_evtpl_tabla_owner_status` (`tabla_form`, `created_by`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    "SELECT 'tabla fis_eval_templates ya existe — sin cambios' AS resultado;"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
