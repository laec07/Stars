-- =====================================================================
-- Fase 3 — Asociar evaluaciones con ficha clínica
-- =====================================================================
-- Agrega la columna `ficha_id` (nullable) y un índice sobre ella en
-- fis_historys (la bitácora que ya alimenta los 12 controladores fisio).
--
-- Diseño:
--   - Sólo se modifica esta tabla; las 11 tablas fis_* específicas
--     quedan intactas.
--   - NULL permitido para no romper datos existentes.
--   - El backend de Laravel ahora captura ficha_id desde el request
--     automáticamente, por lo que cualquier formulario que reciba el
--     campo lo guarda sin cambios adicionales en sus controladores.
--
-- Ejecutar UNA sola vez. El script es idempotente (verifica que la
-- columna y el índice no existan antes de crearlos).
--
-- Si prefieres Laravel artisan: php artisan migrate
-- =====================================================================

-- 1) Añadir columna sólo si no existe -----------------------------------
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fis_historys'
      AND COLUMN_NAME  = 'ficha_id'
);

SET @sql := IF(@col_exists = 0,
    'ALTER TABLE fis_historys ADD COLUMN ficha_id BIGINT UNSIGNED NULL AFTER patient_id',
    'SELECT ''[skip] columna ficha_id ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 2) Crear índice sólo si no existe -------------------------------------
SET @idx_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fis_historys'
      AND INDEX_NAME   = 'idx_fis_historys_ficha_id'
);

SET @sql := IF(@idx_exists = 0,
    'CREATE INDEX idx_fis_historys_ficha_id ON fis_historys (ficha_id)',
    'SELECT ''[skip] índice idx_fis_historys_ficha_id ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 3) (Opcional) Foreign key estricta a fis_fichas -----------------------
-- Descomenta sólo si quieres integridad referencial. Esto bloqueará el
-- INSERT cuando se intente vincular con una ficha que no exista, y al
-- borrar una ficha pondrá NULL en las evaluaciones (no las borra).
--
-- ALTER TABLE fis_historys
--     ADD CONSTRAINT fk_fis_historys_ficha
--     FOREIGN KEY (ficha_id) REFERENCES fis_fichas(id)
--     ON DELETE SET NULL ON UPDATE CASCADE;


-- 4) Verificación visual ------------------------------------------------
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'fis_historys'
  AND COLUMN_NAME IN ('patient_id', 'ficha_id', 'tabla_form', 'id_formulario', 'status')
ORDER BY ORDINAL_POSITION;
