-- =====================================================================
-- Healing Hands — Script de producción
-- Agrega la columna `wa_optin` a `cmn_patients`
-- =====================================================================
-- Equivale a la migración Laravel:
--   2026_05_31_120000_add_wa_optin_to_cmn_patients
--
-- wa_optin (opt-in de WhatsApp por paciente):
--   NULL = aún no preguntado (estado inicial)
--   1    = el paciente acepta recibir mensajes por WhatsApp
--   0    = el paciente rechaza explícitamente
--
-- IDEMPOTENTE: se puede ejecutar varias veces sin error. Verifica si la
-- columna/índice ya existen antes de crearlos.
--
-- Cómo ejecutar:
--   mysql -u USUARIO -p NOMBRE_BD < 01_add_wa_optin_to_cmn_patients.sql
-- o pegar el contenido en phpMyAdmin / Adminer / MySQL Workbench.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Agregar la columna `wa_optin` solo si NO existe
-- ---------------------------------------------------------------------
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cmn_patients'
      AND COLUMN_NAME  = 'wa_optin'
);

SET @sql_col = IF(@col_exists = 0,
    'ALTER TABLE `cmn_patients` ADD COLUMN `wa_optin` TINYINT NULL DEFAULT NULL AFTER `phone_no`',
    'SELECT ''La columna wa_optin ya existe — se omite'' AS aviso'
);
PREPARE stmt FROM @sql_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 2) Agregar el índice `idx_cmn_patients_wa_optin` solo si NO existe
-- ---------------------------------------------------------------------
SET @idx_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cmn_patients'
      AND INDEX_NAME   = 'idx_cmn_patients_wa_optin'
);

SET @sql_idx = IF(@idx_exists = 0,
    'ALTER TABLE `cmn_patients` ADD INDEX `idx_cmn_patients_wa_optin` (`wa_optin`)',
    'SELECT ''El índice idx_cmn_patients_wa_optin ya existe — se omite'' AS aviso'
);
PREPARE stmt FROM @sql_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 3) Registrar la migración en la tabla `migrations` (consistencia con
--    `php artisan migrate:status`). Solo si la tabla existe y aún no
--    está registrada.
-- ---------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_05_31_120000_add_wa_optin_to_cmn_patients',
       (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m)
FROM DUAL
WHERE EXISTS (SELECT 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations`
                  WHERE `migration` = '2026_05_31_120000_add_wa_optin_to_cmn_patients');

-- Fin del script.
