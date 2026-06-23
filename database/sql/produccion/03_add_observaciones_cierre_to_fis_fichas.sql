-- =====================================================================
-- Healing Hands — Script de producción
-- Agrega la columna `observaciones_cierre` a `fis_fichas`
-- =====================================================================
-- Equivale a la migración Laravel:
--   2026_06_15_120000_add_observaciones_cierre_to_fis_fichas
--
-- observaciones_cierre: texto que el fisio captura al CERRAR / dar de alta
-- un caso clínico (resumen de evolución, resultado del tratamiento,
-- recomendaciones al paciente). La fecha de alta usa la columna existente
-- `fecha_alta`; esta migración solo agrega el campo de observaciones.
--
-- IDEMPOTENTE: se puede ejecutar varias veces sin error. Verifica si la
-- columna ya existe antes de crearla.
--
-- Cómo ejecutar:
--   mysql -u USUARIO -p NOMBRE_BD < 03_add_observaciones_cierre_to_fis_fichas.sql
-- o pegar el contenido en phpMyAdmin / Adminer / MySQL Workbench.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Agregar la columna `observaciones_cierre` solo si NO existe
--    (justo después de `fecha_alta`, igual que la migración Laravel)
-- ---------------------------------------------------------------------
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fis_fichas'
      AND COLUMN_NAME  = 'observaciones_cierre'
);

SET @sql_col = IF(@col_exists = 0,
    'ALTER TABLE `fis_fichas` ADD COLUMN `observaciones_cierre` TEXT NULL DEFAULT NULL AFTER `fecha_alta`',
    'SELECT ''La columna observaciones_cierre ya existe — se omite'' AS aviso'
);
PREPARE stmt FROM @sql_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 2) Registrar la migración en la tabla `migrations` (consistencia con
--    `php artisan migrate:status`). Solo si la tabla existe y aún no
--    está registrada.
-- ---------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_15_120000_add_observaciones_cierre_to_fis_fichas',
       (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m)
FROM DUAL
WHERE EXISTS (SELECT 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations`
                  WHERE `migration` = '2026_06_15_120000_add_observaciones_cierre_to_fis_fichas');

-- Fin del script.
