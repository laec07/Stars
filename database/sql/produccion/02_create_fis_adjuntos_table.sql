-- =====================================================================
-- Healing Hands — Script de producción
-- Crea la tabla `fis_adjuntos` (adjuntos de ficha clínica)
-- =====================================================================
-- Equivale a la migración Laravel:
--   2026_06_03_120000_create_fis_adjuntos_table
--
-- Almacena archivos (exámenes, fotos clínicas, documentos, recetas)
-- vinculados a un paciente y, opcionalmente, a una ficha clínica.
--   patient_id  → siempre obligatorio
--   ficha_id    → NULL = adjunto general del paciente (sin caso)
--   categoria   → enum semántico para filtrar/agrupar en la UI
--   file_path   → ruta relativa bajo public/uploadfiles/
--   size_bytes  → para cuotas y métricas
--   status      → 1 = activo, 0 = eliminado lógico
--
-- IDEMPOTENTE: usa CREATE TABLE IF NOT EXISTS. Se puede ejecutar varias
-- veces sin error.
--
-- Cómo ejecutar:
--   mysql -u USUARIO -p NOMBRE_BD < 02_create_fis_adjuntos_table.sql
-- o pegar el contenido en phpMyAdmin / Adminer / MySQL Workbench.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `fis_adjuntos` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `patient_id`  BIGINT UNSIGNED NOT NULL,
    `ficha_id`    BIGINT UNSIGNED NULL DEFAULT NULL,
    `categoria`   ENUM('examenes','fotos_clinicas','documentos','recetas','otros')
                  NOT NULL DEFAULT 'otros',
    `file_name`   VARCHAR(255) NOT NULL,
    `file_path`   VARCHAR(500) NOT NULL,
    `mime`        VARCHAR(128) NULL DEFAULT NULL,
    `size_bytes`  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `descripcion` TEXT NULL DEFAULT NULL,
    `uploaded_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `status`      TINYINT NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fis_adjuntos_patient_id_status_index` (`patient_id`, `status`),
    KEY `fis_adjuntos_patient_id_ficha_id_status_index` (`patient_id`, `ficha_id`, `status`),
    KEY `fis_adjuntos_categoria_status_index` (`categoria`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Registrar la migración en la tabla `migrations` (consistencia con
-- `php artisan migrate:status`). Solo si la tabla existe y aún no está
-- registrada.
-- ---------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_03_120000_create_fis_adjuntos_table',
       (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m)
FROM DUAL
WHERE EXISTS (SELECT 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations`
                  WHERE `migration` = '2026_06_03_120000_create_fis_adjuntos_table');

-- Fin del script.
