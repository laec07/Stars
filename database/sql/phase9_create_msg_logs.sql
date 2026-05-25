-- =================================================================
-- Fase 9a — Tabla msg_logs para registro de mensajes (WhatsApp/SMS)
--
-- Ejecutar en producción si la migration no está disponible.
-- Idempotente: verifica con INFORMATION_SCHEMA antes de crear.
-- =================================================================

SET @dbname = DATABASE();

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'msg_logs') = 0,
    "CREATE TABLE `msg_logs` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        -- Destinatario
        `patient_id` BIGINT UNSIGNED NULL,
        `to_phone` VARCHAR(32) NULL,
        `to_name` VARCHAR(191) NULL,

        -- Canal y plantilla
        `channel` ENUM('whatsapp','sms','log') NOT NULL DEFAULT 'log',
        `template_key` VARCHAR(64) NULL,
        `body` TEXT NOT NULL,

        -- Estado
        `status` ENUM('queued','sent','failed','delivered','read','cancelled') NOT NULL DEFAULT 'queued',
        `provider` VARCHAR(32) NULL,
        `provider_message_id` VARCHAR(191) NULL,
        `provider_response` TEXT NULL,
        `error` TEXT NULL,

        -- Fechas
        `scheduled_for` TIMESTAMP NULL,
        `sent_at` TIMESTAMP NULL,
        `delivered_at` TIMESTAMP NULL,

        -- Auditoría
        `created_by` BIGINT UNSIGNED NULL,
        `created_at` TIMESTAMP NULL,
        `updated_at` TIMESTAMP NULL,

        PRIMARY KEY (`id`),
        KEY `idx_msg_logs_patient_id` (`patient_id`),
        KEY `idx_msg_logs_status` (`status`),
        KEY `idx_msg_logs_scheduled` (`scheduled_for`),
        KEY `idx_msg_logs_patient_created` (`patient_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    "SELECT 'tabla msg_logs ya existe — sin cambios' AS resultado;"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
