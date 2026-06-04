# Healing Hands - Documentación de Handoff entre Sesiones

> **Última actualización:** 2026-05-26
> **Propósito:** Contexto resumido para continuar trabajo en una nueva sesión de Claude Code sin perder hilo.

---

## 1. Contexto del proyecto

**Cliente:** Healing Hands - spa de fisioterapia en Antigua, Guatemala.
**Sistema:** Stars (Laravel) - módulo principal trabajado: **Paciente / Expediente Clínico**.

### Stack técnico
- **Backend:** Laravel 9, PHP 8.1, MySQL, Eloquent ORM
- **Frontend:** jQuery 3, Bootstrap 4, tema Atlantis, DataTables
- **PDF:** mPDF (ya instalado en `vendor/`)
- **Charts:** Chart.js 4.4.0
- **Editor rich text:** Quill 1.3.7
- **Mensajería:** WhatsApp Cloud API (Meta) + Twilio (configurables), `LogProvider` en dev
- **Branding:** Color primario `#9F93E7`, tipografías Carelia (display) + Montserrat (body)
- **Patrón JS:** IIFE `(function ($) { ... })(jQuery)` + Manager pattern (`ManagerName.Method()`)

---

## 2. CONSTRAINTS CRÍTICOS (no negociables)

1. **NO realizar commits.** El usuario los hace manualmente. (Confirmado verbatim: *"de ahora en adelante, no realices commit, eso lo haré manualmente"*)
2. **Trabajar solo en rama `main`.**
3. **No romper funcionalidad existente** que ya usan los usuarios en producción.
4. **mPDF + `artisan serve`** = deadlock si se usan URLs HTTP para imágenes. Usar siempre `public_path()` (filesystem path).
5. **Fotos de evaluaciones** se guardan en `public/uploadfiles/` vía `UtilityRepository::saveFile` (no usar `->store('disco', 'public')`).
6. **CSS namespacing:** El tema Atlantis ya usa `.quick-actions` para su topbar. Para módulos nuevos usar prefijo `.cd-` (clinical-dashboard) u otro namespace.

---

## 3. Fases completadas

| Fase | Descripción | Estado |
|------|-------------|--------|
| 1-3 | Refactor base del módulo paciente, tabs, lazy-load | DONE |
| 4a/b/c | CRUD inline de evaluaciones (edit/delete/compare) | DONE |
| 5 | Branding Healing Hands (colores, fuentes, logo) | DONE |
| 6 | PDF de evaluaciones individuales + expediente completo | DONE |
| 7 | Dashboard clínico con KPIs y charts (`.cd-quick-actions`) | DONE |
| 8 | Búsqueda global Ctrl+K | DONE |
| 9a | Mensajería WhatsApp manual (MVP) | DONE |
| 10 | Plantillas de evaluación (scope personal/global) | DONE |
| 11 | Charts de progreso temporal | DONE |
| Reorg-A | Selector de caso (filtra todas las tabs por ficha) | DONE |
| Quick-add | Modal de nueva ficha clínica sin salir del expediente | DONE |
| Reporte caso | PDF focalizado por caso + ficha completa inline en Resumen | DONE |

---

## 4. Archivos clave (qué hace cada uno)

### Controladores
- **`app/Http/Controllers/Patient/PatientController.php`**
  - `patientSummary()`: carga datos del expediente, incluye `$fichaCompleta` si hay caso seleccionado
  - `downloadPatientExpedientePdf()`: si recibe `?caso=X` delega a `downloadCaseReportPdf`
  - `downloadCaseReportPdf($patientId, $fichaId)`: PDF focalizado de un caso (ficha + sesiones + evaluaciones de ese caso)
  - `patientSesionesData()` y `getPatientEvolution()`: aceptan filtro `ficha_id`

- **`app/Http/Controllers/FormFisios/EvalTemplateController.php`** (Fase 10): CRUD plantillas
- **`app/Http/Controllers/FormFisios/FisEvAlinepsController.php`**: corregido para usar `UtilityRepository::saveFile` en update

### Vistas
- **`resources/views/patient/summary.blade.php`**: Vista principal del expediente
  - Selector de caso (CaseManager)
  - Botón "Descargar expediente" context-aware (cambia label/URL según caso seleccionado)
  - Tarjeta `.ficha-completa-card` colapsable en tab-resumen (solo si caso seleccionado)
  - Tabs: Resumen, Sesiones, Evaluaciones, Mensajes, Evolución
  - Modales: Nueva ficha, Mensajería, Plantillas

- **`resources/views/patient/pdf/expediente.blade.php`**: PDF expediente completo (sin filtro)
- **`resources/views/patient/pdf/case-report.blade.php`**: PDF focalizado por caso (ficha completa + solo sesiones/evaluaciones del caso)
- **`resources/views/patient/pdf/evaluation.blade.php`**: PDF individual de evaluación

### JavaScript
- **`public/js/custom/patient/expediente.js`** — Managers:
  - `CaseManager`: estado de caso activo, persistencia URL via `history.replaceState`
  - `NewCaseManager`: abre modal nueva ficha, valida motivo OR diagnostico, recarga con `?caso=newId`
  - `EvolucionManager`: carga charts vía `patient-evolution`
  - `MessagingManager`: plantillas + envío de mensajes
  - `TemplateManager`: CRUD plantillas evaluación
  - `InlineFormManager.PopulateForm`: reutilizado por todos los managers

### Soporte / Servicios
- **`app/Support/EvaluationMeta.php`**: labels y secciones de los 11 tipos de evaluación
- **`app/Support/EvolutionCharts.php`**: metadata de charts para fis_evdolors, fis_antropometrias, fis_antropoms, fis_goniometrias, fis_cheqmus
- **`app/Services/Messaging/MessagingService.php`**: orquestador de mensajería
- **`app/Services/Messaging/Providers/`**: WhatsApp, Twilio, Log

### Rutas
- **`routes/web.php`** — todas las rutas nuevas dentro de `verifyUserType`:
  - `expediente-pdf/{id}`, `evaluation-pdf/{type}/{id}`
  - `patient-evolution/{id}`, `evaluation-history/{type}/{id}`
  - `global-search`, `messaging/*`, `eval-templates/*`, `panel-clinico`

---

## 5. Gotchas resueltos (no repetir)

1. **mPDF cuelga con `artisan serve`** → usar `public_path()` no `url()` para imágenes en PDFs.
2. **Fotos en disco inconsistente** → `FisEvAlinepsController@updateformEvalineps` ahora usa `UtilityRepository::saveFile` igual que create.
3. **Quick Actions choca con Atlantis** → CSS renombrado a `.cd-quick-actions`.
4. **Links rotos en dashboard** → `/fis-ficha` (singular, no plural), `/fis-seguimientos` no existe.
5. **PDF buttons 404 con prefix `/patient-summary/`** → usar `JsManager.BaseUrl()` para URL absoluta.
6. **Entidades HTML (`&oacute;` etc.) en forms** → `SmartHtmlDecode` mejorado + `DecodeEntitiesDeep` en `getEvaluationRecord`.

---

## 6. Pendiente

### Inmediato (esperando decisión del usuario)
- **Fase 12 — Backup automatizado** (sugerido como siguiente):
  - Cron nocturno: dump MySQL + compresión + upload a Google Drive/S3/Dropbox
  - Política de retención 30/60/90 días
  - Notificación email al admin (success/failure)
  - Estimado: ~6-8h senior / ~12-14h middle

### En cola
- **Fase 9b — Automatización mensajería:** cron para recordatorios; pendiente clarificación del flujo de booking calendar. (Verbatim: *"deja pendiente la 9b pero recuerdalo al finalizar las fases"*)
- **Fase 13 — Mejoras al booking calendar**
- **Fase 14 — Portal del paciente (self-service)**

### Premium (estrategia comercial discutida)
- Charts de progreso temporal multi-paciente
- Backup en cloud con retención extendida
- Portal del paciente
- Mensajería automatizada con cron
- Reportes ejecutivos avanzados

---

## 7. Cotización (referencia)

Cotización en Quetzales (GTQ) ya generada para fases 1-11. Si el usuario pide actualización, recalcular con las fases adicionales (Reorg-A + quick-add + reporte por caso ≈ 6h senior / 11h middle).

---

## 8. Último estado de conversación

- **Última tarea completada:** Reporte por caso (PDF focalizado + ficha completa inline en tab-resumen).
- **Última pregunta abierta del asistente:** *"¿Avanzamos con la Fase 12 (Backup automatizado) o ajustamos algo del reporte?"*
- **Acción esperada en próxima sesión:** Esperar respuesta del usuario antes de iniciar nueva fase.

---

## 9. Comandos útiles

```bash
# Servidor local
php artisan serve

# Limpiar caches (después de cambios en config/rutas/views)
php artisan route:clear && php artisan view:clear && php artisan config:clear

# Verificar rutas nuevas
php artisan route:list | grep -i patient
```

---

## 10. Convenciones del proyecto

- **Idioma UI:** Español (usar `translate()` siempre)
- **Naming JS:** `ManagerName.MethodName` (PascalCase ambos)
- **Naming CSS módulos nuevos:** namespacing con prefijo (ej. `.cd-`, `.hh-`)
- **Forms inline:** declarativos vía config + `InlineFormManager.PopulateForm`
- **Estado UI:** `localStorage` para preferencias, URL `?caso=X` para caso activo
- **Bitácora:** `fis_historys` es el log central de eventos clínicos (todo evento se registra ahí con `ficha_id`)

---

**Para continuar:** Lee este archivo + el último mensaje del usuario en la nueva sesión. Si necesitas detalles exactos de código, abre los archivos clave de la sección 4.
