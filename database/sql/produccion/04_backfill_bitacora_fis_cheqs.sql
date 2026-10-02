-- Registra en la bitácora (fis_historys) los "Chequeo muscular (escala)" guardados
-- antes del fix, que quedaron en fis_cheqs sin entrada de bitácora y por eso
-- no se veían en el expediente. Revisar columnas con DESCRIBE fis_historys antes de ejecutar.
INSERT INTO fis_historys (patient_id, tabla_form, id_formulario, status, fecha, user_id, created_by, updated_by, created_at, updated_at)
SELECT c.patient_id, 'fis_cheqs', c.id, 1, c.fecha, c.user_id, c.user_id, c.user_id, NOW(), NOW()
FROM fis_cheqs c
LEFT JOIN fis_historys h ON h.tabla_form = 'fis_cheqs' AND h.id_formulario = c.id
WHERE c.status = 1 AND h.id IS NULL;
