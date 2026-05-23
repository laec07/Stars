/* Fase 2 — Expediente del paciente: pestaña Sesiones
 * Reutiliza endpoints existentes:
 *   POST seguimiento-create
 *   POST seguimiento/update/{id}
 *   POST seguimiento-delete/{id}
 * Endpoint nuevo:
 *   GET  patient-sesiones/{id}
 */
(function ($) {
    "use strict";

    var ctx = window.PATIENT_CONTEXT || { id: null, name: '', uploadUrl: 'seguimiento/upload-image' };
    var state = {
        sesiones: [],
        fichas: [],
        loaded: false,
        loading: false,
        quill: null,            // instancia única de Quill
        currentSesionId: null,  // sesion en edición (null = nueva)
        autoSaveTimer: null,
        suppressAutoSave: false, // se activa al cargar contenido programáticamente

        // Fase 3 - evaluaciones
        evaluaciones: {},
        evaluacionesLoaded: false,
        evaluacionesLoading: false,
        evalFichas: [],
        evalCurrentFilter: 'all',
        evalCollapseState: {}
    };

    var CONFIG = {
        IMAGE_MAX_DIM: 1600,
        IMAGE_QUALITY: 0.82,
        IMAGE_MAX_INPUT_BYTES: 12 * 1024 * 1024, // 12 MB original
        AUTOSAVE_DEBOUNCE_MS: 2000,
        DRAFT_TTL_MS: 7 * 24 * 60 * 60 * 1000 // 7 días
    };

    $(document).ready(function () {

        // Lazy-load al abrir la pestaña Sesiones por primera vez
        $('#tab-sesiones-trigger').on('shown.bs.tab', function () {
            if (!state.loaded && !state.loading) {
                Manager.LoadSesiones();
            }
        });

        // Lazy-load al abrir la pestaña Evaluación
        $('#tab-evaluacion-trigger').on('shown.bs.tab', function () {
            if (!state.evaluacionesLoaded && !state.evaluacionesLoading) {
                EvaluacionManager.Load();
            }
        });

        // Filtro de ficha en la pestaña Evaluación
        $('#evalFichaFilter').on('change', function () {
            state.evalCurrentFilter = $(this).val();
            EvaluacionManager.Load();
        });

        // Toggle de secciones de evaluación
        $(document).on('click', '.eval-section-header', function () {
            var $section = $(this).closest('.eval-section');
            $section.toggleClass('collapsed');
            $(this).toggleClass('collapsed');
            var key = $section.data('key');
            if (key) state.evalCollapseState[key] = $section.hasClass('collapsed');
        });

        // Fase 3b — botón "+ Agregar inline" abre el modal genérico
        $(document).on('click', '.eval-add-inline', function (e) {
            e.preventDefault();
            var key = $(this).data('key');
            InlineFormManager.Open(key);
        });

        // Submit del modal inline
        $('#formEvalInline').on('submit', function (e) {
            e.preventDefault();
            InlineFormManager.Save();
        });

        // Botones
        $('#btnNuevaSesion').on('click', function () {
            Manager.OpenComposer({ mode: 'create' });
        });

        $('#btnDuplicarUltima').on('click', function () {
            if (!state.sesiones.length) return;
            Manager.OpenComposer({ mode: 'duplicate', source: state.sesiones[0] });
        });

        // Submit del modal
        $('#formSesion').on('submit', function (e) {
            e.preventDefault();
            Manager.SaveSesion();
        });

        // Delegated events para acciones de cada tarjeta
        $(document).on('click', '.sesion-edit', function () {
            var id = $(this).data('id');
            var sesion = state.sesiones.find(function (s) { return s.id == id; });
            if (sesion) Manager.OpenComposer({ mode: 'edit', source: sesion });
        });

        $(document).on('click', '.sesion-duplicate', function () {
            var id = $(this).data('id');
            var sesion = state.sesiones.find(function (s) { return s.id == id; });
            if (sesion) Manager.OpenComposer({ mode: 'duplicate', source: sesion });
        });

        $(document).on('click', '.sesion-delete', function () {
            var id = $(this).data('id');
            Manager.DeleteSesion(id);
        });

        $(document).on('click', '.sesion-ver-nota', function () {
            var id = $(this).data('id');
            var sesion = state.sesiones.find(function (s) { return s.id == id; });
            if (!sesion) return;
            var content = Manager.SmartHtmlDecode(sesion.nota_detallada || '').trim();
            $('#modalVerNotaBody').html(content || '<p class="text-muted">Sin nota.</p>');
            $('#modalVerNota').modal('show');
        });

        // Toggle del motivo de consulta colapsable
        $(document).on('click', '.motivo-toggle', function (e) {
            // Evitar disparar si el botón es el de nota detallada (tiene su propio handler)
            if ($(this).hasClass('sesion-ver-nota')) return;
            var target = $(this).data('target');
            if (!target) return;
            var $content = $('#' + target);
            $content.toggleClass('open');
            var icon = $(this).find('i');
            icon.toggleClass('fa-chevron-down').toggleClass('fa-chevron-up');
        });

        // === Quill: inicializar la primera vez que el modal se muestra ===
        $('#modalSesion').on('shown.bs.modal', function () {
            QuillManager.InitOnce();
        });

        // Camera & gallery buttons
        $('#btnSesionCamera').on('click', function () { $('#sesionCameraInput').click(); });
        $('#btnSesionGallery').on('click', function () { $('#sesionGalleryInput').click(); });

        $('#sesionCameraInput, #sesionGalleryInput').on('change', function () {
            if (this.files && this.files[0]) {
                QuillManager.HandleImageUpload(this.files[0]);
                this.value = ''; // permitir re-elegir el mismo archivo
            }
        });

        // Autosave también desde los campos del form (no sólo del editor)
        $('#formSesion input, #formSesion select, #formSesion textarea').on('input change', function () {
            if (state.suppressAutoSave) return;
            DraftManager.Schedule();
        });

        // Limpiar borrador al cerrar el modal SIN guardar
        // (sólo si está vacío; si tenía cambios, lo conservamos para restaurar)
        $('#modalSesion').on('hidden.bs.modal', function () {
            // No limpiamos aquí — la limpieza ocurre en SaveSesion onSuccess
            DraftManager.ShowStatus('');
        });
    });

    var Manager = {

        LoadSesiones: function () {
            if (!ctx.id) return;
            state.loading = true;

            var serviceUrl = 'patient-sesiones/' + ctx.id;
            JsManager.SendJsonAsyncON('GET', serviceUrl, '', onSuccess, onFailed);

            function onSuccess(jsonData) {
                state.loading = false;
                if (jsonData.status == '1' && jsonData.data) {
                    state.sesiones = jsonData.data.sesiones || [];
                    state.fichas = jsonData.data.fichas || [];
                    state.loaded = true;
                    Manager.RenderList();
                    Manager.PopulateFichasDropdown();
                    Manager.UpdateSummary();
                } else {
                    Manager.RenderError('No se pudieron cargar las sesiones.');
                }
            }
            function onFailed(xhr) {
                state.loading = false;
                var debugMsg = '';
                try {
                    var resp = xhr.responseJSON || (xhr.responseText ? JSON.parse(xhr.responseText) : null);
                    if (resp && resp.debug) debugMsg = resp.debug;
                } catch (e) { /* ignore parse error */ }
                Manager.RenderError(
                    'Error de red cargando sesiones (HTTP ' + xhr.status + ').' +
                    (debugMsg ? '<div style="font-size:.7rem;color:#dc3545;margin-top:.5rem;text-align:left;">' +
                                Manager.EscapeHtml(debugMsg) + '</div>' : '')
                );
                // No usamos Message.Exception aquí porque espera estructura de validación.
            }
        },

        UpdateSummary: function () {
            var n = state.sesiones.length;
            var summary;
            if (n === 0) {
                summary = 'Sin sesiones registradas todavía.';
                $('#btnDuplicarUltima').prop('disabled', true);
            } else {
                var last = state.sesiones[0];
                var fechaTxt = Manager.FormatDate(last.fecha);
                summary = n + ' sesión' + (n === 1 ? '' : 'es') + ' · última: ' + fechaTxt;
                $('#btnDuplicarUltima').prop('disabled', false);
            }
            $('#sesiones-summary').text(summary);
        },

        RenderError: function (msgHtml) {
            // msgHtml puede contener HTML controlado por nosotros (no input del usuario).
            $('#sesiones-list').html(
                '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i>' +
                msgHtml + '</div>'
            );
            $('#sesiones-summary').text('');
        },

        RenderList: function () {
            if (!state.sesiones.length) {
                $('#sesiones-list').html(
                    '<div class="empty-state">' +
                    '<i class="far fa-clipboard"></i>' +
                    'Este paciente aún no tiene sesiones registradas.' +
                    (state.fichas.length
                        ? '<div style="margin-top:.75rem;"><button class="btn btn-primary btn-sm" onclick="document.getElementById(\'btnNuevaSesion\').click()">' +
                          '<i class="fas fa-plus mr-1"></i>Crear primera sesión</button></div>'
                        : '<div style="margin-top:.75rem;font-size:.8rem;color:#adb5bd;">' +
                          'Necesitas crear una ficha clínica primero.</div>'
                    ) +
                    '</div>'
                );
                return;
            }

            var html = state.sesiones.map(Manager.RenderCard).join('');
            $('#sesiones-list').html(html);
        },

        RenderCard: function (s) {
            var fecha = Manager.FormatDate(s.fecha);
            var diag = (s.ficha_diagnostico || '').trim();
            var motivo = (s.ficha_motivo || '').trim();
            var user = (s.user_name || '').trim();
            var treatment = (s.tratamiento_realizado || '').trim();
            var observ = (s.observaciones || '').trim();
            var evolValue = (s.evolucion || '').trim();

            // Evolución: chip si es uno de los valores conocidos, texto si es libre
            var evolKnown = ['favorable', 'estable', 'desfavorable'];
            var isEvolChip = evolValue && evolKnown.indexOf(evolValue.toLowerCase()) !== -1;
            var evolChipHtml = isEvolChip
                ? '<span class="evol-chip ' + evolValue.toLowerCase() + '">' + Manager.EscapeHtml(evolValue) + '</span>'
                : '';

            // Treatment summary en el header (truncado a 2 líneas por CSS)
            var treatmentHtml = treatment
                ? '<div class="sesion-treatment">' + Manager.EscapeHtml(treatment) + '</div>'
                : '<div class="sesion-treatment empty">— sin tratamiento registrado</div>';

            // Meta row: diagnóstico + usuario + chip evolución
            var metaPieces = [];
            if (diag)        metaPieces.push('<span class="meta-diag"><i class="fas fa-notes-medical"></i>' + Manager.EscapeHtml(diag) + '</span>');
            if (user)        metaPieces.push('<span class="meta-user"><i class="far fa-user"></i>' + Manager.EscapeHtml(user) + '</span>');
            if (evolChipHtml) metaPieces.push(evolChipHtml);
            var metaHtml = metaPieces.length
                ? '<div class="sesion-meta-row">' + metaPieces.join('') + '</div>'
                : '';

            // Body: evolución (si es texto libre) + observaciones
            var bodyFields = '';
            if (evolValue && !isEvolChip) {
                bodyFields += '<div class="sesion-field"><div class="field-label">Evolución</div>' +
                              '<div class="field-value">' + Manager.EscapeHtml(evolValue) + '</div></div>';
            }
            if (observ) {
                bodyFields += '<div class="sesion-field"><div class="field-label">Observaciones</div>' +
                              '<div class="field-value">' + Manager.EscapeHtml(observ) + '</div></div>';
            }
            var bodyHtml = bodyFields ? '<div class="sesion-body">' + bodyFields + '</div>' : '';

            // Footer: motivo colapsable + nota detallada
            var footerPieces = [];
            var motivoId = 'motivo-' + s.id;
            if (motivo) {
                footerPieces.push(
                    '<button type="button" class="motivo-toggle" data-target="' + motivoId + '">' +
                        '<i class="fas fa-chevron-down mr-1"></i>Motivo de consulta' +
                    '</button>'
                );
            }
            if (s.nota_detallada) {
                footerPieces.push(
                    '<button type="button" class="motivo-toggle sesion-ver-nota" data-id="' + s.id + '">' +
                        '<i class="fas fa-file-alt mr-1"></i>Nota detallada' +
                    '</button>'
                );
            }
            var footerHtml = '';
            if (footerPieces.length || motivo) {
                footerHtml = '<div class="sesion-footer">' + footerPieces.join('') + '</div>';
                if (motivo) {
                    footerHtml += '<div class="motivo-content" id="' + motivoId + '">' + Manager.EscapeHtml(motivo) + '</div>';
                }
            }

            // Actions
            var actionsHtml =
                '<div class="sesion-actions">' +
                    '<button class="btn btn-light sesion-duplicate" data-id="' + s.id + '" title="Duplicar"><i class="fas fa-copy"></i></button>' +
                    '<button class="btn btn-light sesion-edit" data-id="' + s.id + '" title="Editar"><i class="fas fa-edit"></i></button>' +
                    '<button class="btn btn-light sesion-delete" data-id="' + s.id + '" title="Eliminar"><i class="far fa-trash-alt text-danger"></i></button>' +
                '</div>';

            return (
                '<div class="sesion-card">' +
                    '<div class="sesion-top">' +
                        '<div class="sesion-top-main">' +
                            '<div class="sesion-date"><i class="far fa-calendar-alt"></i>' + fecha + '</div>' +
                            treatmentHtml +
                        '</div>' +
                        actionsHtml +
                    '</div>' +
                    metaHtml +
                    bodyHtml +
                    footerHtml +
                '</div>'
            );
        },

        PopulateFichasDropdown: function () {
            var $sel = $('#sesion_ficha_id');
            $sel.find('option:not(:first)').remove();
            if (!state.fichas.length) {
                $('#sesion-ficha-help').show();
                return;
            }
            $('#sesion-ficha-help').hide();

            state.fichas.forEach(function (f) {
                // El diagnóstico identifica la ficha de forma breve y clínica.
                // Motivo de consulta es una narrativa larga que satura el combo.
                var diag = (f.diagnostico || '').trim();
                var motivo = (f.motivo_consulta || '').trim();
                var label;

                if (diag) {
                    label = diag;
                } else if (motivo) {
                    // Fallback: truncar motivo a 60 caracteres si no hay diagnóstico
                    label = motivo.length > 60 ? motivo.substring(0, 60).trim() + '…' : motivo;
                } else {
                    label = 'Ficha #' + f.id;
                }

                if (f.fecha) label += ' · ' + Manager.FormatDate(f.fecha);

                // El title del option preserva el texto completo para tooltip
                var fullText = [diag, motivo].filter(Boolean).join(' — ');
                $sel.append(
                    '<option value="' + f.id + '" title="' + Manager.EscapeHtml(fullText) + '">' +
                    Manager.EscapeHtml(label) + '</option>'
                );
            });
            // Por defecto seleccionar la más reciente
            $sel.val(state.fichas[0].id);
        },

        OpenComposer: function (opts) {
            opts = opts || {};
            var mode = opts.mode || 'create';
            var source = opts.source || {};

            state.suppressAutoSave = true;

            $('#formSesion')[0].reset();
            $('#sesion_patient_id').val(ctx.id);
            $('#sesion_nota_detallada').val('');
            QuillManager.SetContent('');

            var todayStr = new Date().toISOString().slice(0, 10);

            if (mode === 'create') {
                $('#modalSesionTitle').text('Nueva sesión');
                $('#sesion_id').val('');
                $('#sesion_fecha').val(todayStr);
                if (state.fichas.length) $('#sesion_ficha_id').val(state.fichas[0].id);
                state.currentSesionId = null;

                // Intentar restaurar borrador local de "nueva sesión" si existe
                DraftManager.OfferRestoreIfAny(null);
            } else if (mode === 'edit') {
                $('#modalSesionTitle').text('Editar sesión');
                $('#sesion_id').val(source.id);
                $('#sesion_ficha_id').val(source.ficha_id);
                $('#sesion_fecha').val((source.fecha || '').substring(0, 10) || todayStr);
                $('#sesion_tratamiento').val(source.tratamiento_realizado || '');
                $('#sesion_observaciones').val(source.observaciones || '');
                $('#sesion_evolucion').val(source.evolucion || '');
                QuillManager.SetContent(source.nota_detallada || '');
                state.currentSesionId = source.id;

                // Intentar restaurar borrador local de esta sesión específica
                DraftManager.OfferRestoreIfAny(source.id);
            } else if (mode === 'duplicate') {
                $('#modalSesionTitle').text('Duplicar sesión (' + Manager.FormatDate(source.fecha) + ')');
                $('#sesion_id').val(''); // se guardará como nueva
                $('#sesion_ficha_id').val(source.ficha_id);
                $('#sesion_fecha').val(todayStr); // fecha actual, no la original
                $('#sesion_tratamiento').val(source.tratamiento_realizado || '');
                $('#sesion_observaciones').val(source.observaciones || '');
                $('#sesion_evolucion').val(''); // dejar vacía — el fisio decide tras la sesión
                // nota_detallada NO se copia para evitar arrastrar contenido enriquecido viejo
                state.currentSesionId = null;
            }

            DraftManager.ShowStatus('');
            $('#modalSesion').modal('show');

            // Reactivar autosave después de que la UI termine de aplicar valores
            setTimeout(function () { state.suppressAutoSave = false; }, 250);
        },

        SaveSesion: function () {
            var id = $('#sesion_id').val();
            var fichaId = $('#sesion_ficha_id').val();
            var fecha = $('#sesion_fecha').val();

            if (!fichaId) {
                if (window.Message) Message.Notification('warning', 'Selecciona una ficha clínica.');
                return;
            }
            if (!fecha) {
                if (window.Message) Message.Notification('warning', 'Indica la fecha.');
                return;
            }

            // Sincronizar contenido de Quill al hidden input antes de enviar
            QuillManager.SyncToHidden();

            JsManager.StartProcessBar();

            var payload = {
                ficha_id:               fichaId,
                patient_id:             ctx.id,
                fecha:                  fecha,
                tratamiento_realizado:  $('#sesion_tratamiento').val(),
                observaciones:          $('#sesion_observaciones').val(),
                evolucion:              $('#sesion_evolucion').val(),
                nota_detallada:         $('#sesion_nota_detallada').val()
            };

            var url = id ? ('seguimiento/update/' + id) : 'seguimiento-create';

            JsManager.SendJson('POST', url, payload, onSuccess, onFailed);

            function onSuccess(json) {
                JsManager.EndProcessBar();
                if (json.status == '1') {
                    DraftManager.Clear(id);     // limpia el borrador local
                    $('#modalSesion').modal('hide');
                    if (window.Message) Message.Success(id ? 'update' : 'save');
                    state.loaded = false;
                    Manager.LoadSesiones();
                } else {
                    if (window.Message) Message.Error(id ? 'update' : 'save');
                }
            }
            function onFailed(xhr) {
                JsManager.EndProcessBar();
                if (window.Message) Message.Exception(xhr);
            }
        },

        DeleteSesion: function (id) {
            if (!window.Message || !Message.Prompt()) return;

            JsManager.StartProcessBar();
            JsManager.SendJson('POST', 'seguimiento-delete/' + id, {}, onSuccess, onFailed);

            function onSuccess(json) {
                JsManager.EndProcessBar();
                if (json.status == '1') {
                    if (window.Message) Message.Success('delete');
                    state.loaded = false;
                    Manager.LoadSesiones();
                } else {
                    if (window.Message) Message.Error('delete');
                }
            }
            function onFailed(xhr) {
                JsManager.EndProcessBar();
                if (window.Message) Message.Exception(xhr);
            }
        },

        FormatDate: function (val) {
            if (!val) return '—';
            var s = String(val).substring(0, 10);
            var m = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? (m[3] + '/' + m[2] + '/' + m[1]) : s;
        },

        EscapeHtml: function (str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        /**
         * Decodifica entidades HTML si el contenido vino entity-encoded
         * (caso típico cuando un middleware tipo xssProtection escapa el input
         * antes de guardarlo). Idempotente: no toca contenido que ya tiene
         * tags HTML reales.
         */
        SmartHtmlDecode: function (s) {
            if (!s) return '';
            s = String(s);
            // Si ya tiene tags HTML reales, devolverlo tal cual.
            if (/<[a-z!][\s\S]*?>/i.test(s)) return s;
            // Si no hay tags pero sí entidades, decodificar una pasada con textarea.
            if (/&(?:lt|gt|amp|quot|nbsp|#\d+);/.test(s)) {
                var ta = document.createElement('textarea');
                ta.innerHTML = s;
                return ta.value;
            }
            return s;
        }
    };

    // ========================================================================
    // QuillManager: editor rico + upload de imágenes con compresión cliente
    // ========================================================================
    var QuillManager = {

        InitOnce: function () {
            if (state.quill || typeof Quill === 'undefined') return state.quill;

            state.quill = new Quill('#sesionQuillEditor', {
                theme: 'snow',
                placeholder: 'Notas detalladas, observaciones, enlaces, imágenes...',
                modules: {
                    toolbar: {
                        container: [
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'bullet' }, { 'list': 'ordered' }],
                            ['link', 'image'],
                            ['clean']
                        ],
                        handlers: {
                            // Interceptar el botón imagen del toolbar para pasar por la galería
                            image: function () { $('#sesionGalleryInput').click(); }
                        }
                    }
                }
            });

            // Autosave on text-change
            state.quill.on('text-change', function () {
                if (state.suppressAutoSave) return;
                DraftManager.Schedule();
            });

            return state.quill;
        },

        SetContent: function (html) {
            // Decodificar entidades por si el contenido legacy llegó entity-encoded
            var safe = Manager.SmartHtmlDecode(html || '');
            if (!state.quill) {
                // Si aún no se inicializó (modal no abierto), guardamos en el hidden
                $('#sesion_nota_detallada').val(safe);
                return;
            }
            if (safe) {
                state.quill.clipboard.dangerouslyPasteHTML(safe);
            } else {
                state.quill.setText('');
            }
        },

        SyncToHidden: function () {
            if (!state.quill) return;
            var html = state.quill.root.innerHTML;
            // Si Quill está vacío entrega "<p><br></p>" — normalizamos a ''
            if (html === '<p><br></p>') html = '';
            $('#sesion_nota_detallada').val(html);
        },

        HandleImageUpload: function (file) {
            if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                if (window.Message) Message.Notification('warning', 'Sólo se permiten imágenes.');
                return;
            }
            if (file.size > CONFIG.IMAGE_MAX_INPUT_BYTES) {
                if (window.Message) Message.Notification('warning', 'La imagen excede 12 MB.');
                return;
            }

            $('#notaUploadProgress').addClass('active');

            QuillManager.CompressImage(file, CONFIG.IMAGE_MAX_DIM, CONFIG.IMAGE_QUALITY)
                .then(function (blob) {
                    return QuillManager.Upload(blob, file.name);
                })
                .then(function (url) {
                    if (!url) throw new Error('Respuesta sin URL');
                    QuillManager.InsertImage(url);
                    DraftManager.Schedule(); // marcar como cambiado para autosave
                })
                .catch(function (err) {
                    console.error('upload image error', err);
                    if (window.Message) Message.Notification('error', 'No se pudo subir la imagen: ' + (err.message || err));
                })
                .then(function () {
                    $('#notaUploadProgress').removeClass('active');
                });
        },

        /**
         * Redimensiona en canvas a maxDim (mayor dimensión) y comprime a JPEG.
         * Devuelve Promise<Blob>.
         */
        CompressImage: function (file, maxDim, quality) {
            return new Promise(function (resolve, reject) {
                var url = URL.createObjectURL(file);
                var img = new Image();
                img.onload = function () {
                    var w = img.naturalWidth, h = img.naturalHeight;
                    if (w > maxDim || h > maxDim) {
                        if (w >= h) {
                            h = Math.round(h * (maxDim / w));
                            w = maxDim;
                        } else {
                            w = Math.round(w * (maxDim / h));
                            h = maxDim;
                        }
                    }
                    var canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    URL.revokeObjectURL(url);
                    canvas.toBlob(function (blob) {
                        if (!blob) return reject(new Error('toBlob falló'));
                        resolve(blob);
                    }, 'image/jpeg', quality);
                };
                img.onerror = function () {
                    URL.revokeObjectURL(url);
                    reject(new Error('No se pudo leer la imagen'));
                };
                img.src = url;
            });
        },

        Upload: function (blob, originalName) {
            return new Promise(function (resolve, reject) {
                var fd = new FormData();
                var safeName = (originalName || 'foto').replace(/\.[^.]+$/, '') + '_' + Date.now() + '.jpg';
                fd.append('image', blob, safeName);

                var csrf = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: ctx.uploadUrl,
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
                    success: function (json) {
                        if (!json || (!json.url && !json.default)) {
                            return reject(new Error('Respuesta inválida del servidor'));
                        }
                        var url = json.url || json.default;
                        // El backend devuelve la ruta sin slash inicial; normalizamos
                        if (url.charAt(0) !== '/' && url.indexOf('http') !== 0) url = '/' + url;
                        resolve(url);
                    },
                    error: function (xhr) {
                        reject(new Error('HTTP ' + xhr.status));
                    }
                });
            });
        },

        InsertImage: function (url) {
            if (!state.quill) return;
            var range = state.quill.getSelection(true) || { index: state.quill.getLength() };
            state.quill.insertEmbed(range.index, 'image', url, 'user');
            state.quill.setSelection(range.index + 1, 0, 'silent');
        }
    };

    // ========================================================================
    // DraftManager: autoguardado a localStorage + restauración
    // ========================================================================
    var DraftManager = {

        keyFor: function (sesionId) {
            return 'sesion_borrador_' + (ctx.id || 'x') + '_' + (sesionId || 'new');
        },

        Schedule: function () {
            clearTimeout(state.autoSaveTimer);
            DraftManager.ShowStatus('Guardando borrador...', 'saving');
            state.autoSaveTimer = setTimeout(DraftManager.SaveNow, CONFIG.AUTOSAVE_DEBOUNCE_MS);
        },

        SaveNow: function () {
            try {
                QuillManager.SyncToHidden();
                var payload = {
                    sesion_id:              $('#sesion_id').val() || null,
                    patient_id:             ctx.id,
                    ficha_id:               $('#sesion_ficha_id').val(),
                    fecha:                  $('#sesion_fecha').val(),
                    tratamiento_realizado:  $('#sesion_tratamiento').val(),
                    observaciones:          $('#sesion_observaciones').val(),
                    evolucion:              $('#sesion_evolucion').val(),
                    nota_detallada:         $('#sesion_nota_detallada').val(),
                    savedAt:                Date.now()
                };
                var key = DraftManager.keyFor(state.currentSesionId);
                localStorage.setItem(key, JSON.stringify(payload));
                DraftManager.ShowStatus('Borrador guardado', 'saved');
            } catch (e) {
                console.warn('autosave failed', e);
                DraftManager.ShowStatus('Sin guardado local', '');
            }
        },

        OfferRestoreIfAny: function (sesionId) {
            try {
                var key = DraftManager.keyFor(sesionId);
                var raw = localStorage.getItem(key);
                if (!raw) return;
                var draft = JSON.parse(raw);
                if (!draft || !draft.savedAt) return;
                // TTL: ignorar borradores viejos
                if (Date.now() - draft.savedAt > CONFIG.DRAFT_TTL_MS) {
                    localStorage.removeItem(key);
                    return;
                }
                var ageMin = Math.max(1, Math.round((Date.now() - draft.savedAt) / 60000));
                if (confirm('Tienes un borrador local de hace ' + ageMin + ' min para esta sesión. ¿Restaurarlo?')) {
                    state.suppressAutoSave = true;
                    if (draft.ficha_id)              $('#sesion_ficha_id').val(draft.ficha_id);
                    if (draft.fecha)                 $('#sesion_fecha').val(draft.fecha);
                    if (draft.tratamiento_realizado) $('#sesion_tratamiento').val(draft.tratamiento_realizado);
                    if (draft.observaciones)         $('#sesion_observaciones').val(draft.observaciones);
                    if (draft.evolucion)             $('#sesion_evolucion').val(draft.evolucion);
                    if (draft.nota_detallada)        QuillManager.SetContent(draft.nota_detallada);
                    DraftManager.ShowStatus('Borrador restaurado', 'saved');
                    setTimeout(function () { state.suppressAutoSave = false; }, 250);
                } else {
                    localStorage.removeItem(key);
                }
            } catch (e) {
                console.warn('restore draft failed', e);
            }
        },

        Clear: function (sesionId) {
            try {
                var key = DraftManager.keyFor(sesionId || state.currentSesionId);
                localStorage.removeItem(key);
                // También limpia el borrador "new" por si veníamos creando
                if (!sesionId) {
                    localStorage.removeItem(DraftManager.keyFor(null));
                }
            } catch (e) { /* ignore */ }
        },

        ShowStatus: function (text, cls) {
            var $el = $('#notaSaveStatus');
            $el.removeClass('saving saved');
            if (cls) $el.addClass(cls);
            $el.text(text || '');
        }
    };

    // ========================================================================
    // EvaluacionManager (Fase 3): tab Evaluación unificada
    // ========================================================================

    // Metadatos visuales por tipo de evaluación (debe coincidir con tabla_form)
    var EVAL_META = {
        'fis_evdolors':       { label: 'Evaluación de dolor',         icon: 'fa-heart-broken',      color: 'danger',    route: 'evdolors.info'       },
        'fis_goniometrias':   { label: 'Goniometría',                 icon: 'fa-compass',           color: 'secondary', route: 'goniometrias.info'   },
        'fis_cheqmus':        { label: 'Chequeo muscular',            icon: 'fa-dumbbell',          color: 'success',   route: 'cheqmus.info'        },
        'fis_cheqs':          { label: 'Chequeo muscular (escala)',   icon: 'fa-dumbbell',          color: 'success',   route: 'cheqs.info'          },
        'fis_sensitivitys':   { label: 'Sensibilidad',                icon: 'fa-hand-paper',        color: 'warning',   route: 'sensitivitys.info'   },
        'fis_antropometrias': { label: 'Antropometría T.F',           icon: 'fa-balance-scale',     color: 'info',      route: 'antropometrias.info' },
        'fis_antropoms':      { label: 'Antropometría',               icon: 'fa-ruler',             color: 'info',      route: 'antropoms.info'      },
        'fis_evpiels':        { label: 'Evaluación de piel',          icon: 'fa-hand-paper',        color: 'warning',   route: 'evpiels.info'        },
        'fis_evalineps':      { label: 'Alineación postural',         icon: 'fa-walking',           color: 'secondary', route: 'evalineps.info'      },
        'fis_electros':       { label: 'Electroterapia',              icon: 'fa-bolt',              color: 'primary',   route: 'electros.info'       },
        'fis_ultras':         { label: 'Ultrasonido',                 icon: 'fa-broadcast-tower',   color: 'primary',   route: 'ultras.info'         }
    };

    // Orden lógico-clínico de las secciones (las que tienen datos suben primero)
    var EVAL_ORDER = [
        'fis_evdolors',
        'fis_goniometrias',
        'fis_cheqmus',
        'fis_cheqs',
        'fis_sensitivitys',
        'fis_evalineps',
        'fis_antropometrias',
        'fis_antropoms',
        'fis_evpiels',
        'fis_electros',
        'fis_ultras'
    ];

    var EvaluacionManager = {

        Load: function () {
            if (!ctx.id) return;
            state.evaluacionesLoading = true;

            var url = 'patient-evaluaciones/' + ctx.id;
            if (state.evalCurrentFilter && state.evalCurrentFilter !== 'all') {
                url += '?ficha_id=' + encodeURIComponent(state.evalCurrentFilter);
            }

            $('#eval-sections').html(
                '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i> Cargando evaluaciones...</div>'
            );

            JsManager.SendJsonAsyncON('GET', url, '', onSuccess, onFailed);

            function onSuccess(json) {
                state.evaluacionesLoading = false;
                if (json.status == '1' && json.data) {
                    state.evaluaciones = json.data.evaluaciones || {};
                    state.evalFichas   = json.data.fichas || [];
                    state.evaluacionesLoaded = true;
                    EvaluacionManager.PopulateFichaFilter();
                    EvaluacionManager.Render(!!json.data.has_ficha_id_column);
                } else {
                    EvaluacionManager.RenderError('Respuesta inesperada del servidor.');
                }
            }
            function onFailed(xhr) {
                state.evaluacionesLoading = false;
                var dbg = '';
                try {
                    var resp = xhr.responseJSON || (xhr.responseText ? JSON.parse(xhr.responseText) : null);
                    if (resp && resp.debug) dbg = resp.debug;
                } catch (e) { /* ignore */ }
                EvaluacionManager.RenderError(
                    'Error de red cargando evaluaciones (HTTP ' + xhr.status + ').' +
                    (dbg ? '<div style="font-size:.7rem;color:#dc3545;margin-top:.5rem;">' + Manager.EscapeHtml(dbg) + '</div>' : '')
                );
            }
        },

        PopulateFichaFilter: function () {
            var $sel = $('#evalFichaFilter');
            $sel.find('option:not(:first), option[data-dynamic="1"]').remove();
            state.evalFichas.forEach(function (f) {
                var diag = (f.diagnostico || '').trim();
                var motivo = (f.motivo_consulta || '').trim();
                var label = diag || (motivo.length > 60 ? motivo.substring(0, 60).trim() + '…' : motivo) || ('Ficha #' + f.id);
                if (f.fecha) label += ' · ' + Manager.FormatDate(f.fecha);
                $sel.append(
                    '<option value="' + f.id + '" data-dynamic="1">' + Manager.EscapeHtml(label) + '</option>'
                );
            });
            // Opción "sin ficha asignada"
            $sel.append('<option value="unassigned" data-dynamic="1">— Sin ficha asignada —</option>');

            $sel.val(state.evalCurrentFilter || 'all');
        },

        Render: function (hasFichaIdColumn) {
            var totalEvents = 0;
            var typesWithData = 0;
            var lastDate = null;
            var allKeys = EVAL_ORDER.slice();
            // Añadir tipos desconocidos que vengan en data
            Object.keys(state.evaluaciones).forEach(function (k) {
                if (allKeys.indexOf(k) === -1) allKeys.push(k);
            });

            // Calcular métricas resumen
            allKeys.forEach(function (k) {
                var rows = state.evaluaciones[k] || [];
                if (rows.length > 0) {
                    typesWithData++;
                    totalEvents += rows.length;
                    if (rows[0] && rows[0].fecha) {
                        var d = rows[0].fecha;
                        if (!lastDate || d > lastDate) lastDate = d;
                    }
                }
            });

            var summary = totalEvents
                ? (totalEvents + ' evaluación' + (totalEvents === 1 ? '' : 'es') +
                   ' · ' + typesWithData + ' tipo' + (typesWithData === 1 ? '' : 's') +
                   (lastDate ? ' · última: ' + Manager.FormatDate(lastDate) : ''))
                : 'Sin evaluaciones registradas todavía.';
            $('#eval-summary').text(summary);

            if (totalEvents === 0) {
                $('#eval-sections').html(
                    '<div class="empty-state">' +
                    '<i class="far fa-clipboard"></i>' +
                    'No hay evaluaciones registradas para este caso.' +
                    '<div style="font-size:.78rem; color:#adb5bd; margin-top:.6rem;">' +
                    'Puedes crear una desde los formularios clínicos del menú.' +
                    '</div></div>'
                );
                return;
            }

            // Ordenar: secciones con datos primero, luego vacías
            allKeys.sort(function (a, b) {
                var na = (state.evaluaciones[a] || []).length;
                var nb = (state.evaluaciones[b] || []).length;
                if ((na > 0) !== (nb > 0)) return nb - na;
                return EVAL_ORDER.indexOf(a) - EVAL_ORDER.indexOf(b);
            });

            var html = allKeys.map(function (key) {
                return EvaluacionManager.RenderSection(key, state.evaluaciones[key] || [], hasFichaIdColumn);
            }).join('');

            $('#eval-sections').html(html);
        },

        RenderSection: function (key, rows, hasFichaIdColumn) {
            var meta = EVAL_META[key] || { label: key, icon: 'fa-file', color: 'secondary', route: null };
            var count = rows.length;
            var collapsed = state.evalCollapseState[key];
            if (collapsed === undefined) collapsed = (count === 0); // colapsar las vacías por defecto

            var bodyHtml;
            if (count === 0) {
                bodyHtml = '<div class="eval-empty-section">Sin registros de este tipo todavía.</div>';
            } else {
                bodyHtml = rows.map(function (r) {
                    var fecha = Manager.FormatDate(r.fecha);
                    var user = r.user_name ? Manager.EscapeHtml(r.user_name) : '<span class="text-muted">—</span>';
                    var fichaBadge = '';
                    if (hasFichaIdColumn) {
                        if (r.ficha_id) {
                            var fichaInfo = state.evalFichas.find(function (f) { return f.id == r.ficha_id; });
                            var diagShort = fichaInfo
                                ? ((fichaInfo.diagnostico || '').trim() || 'Ficha #' + fichaInfo.id)
                                : ('Ficha #' + r.ficha_id);
                            if (diagShort.length > 25) diagShort = diagShort.substring(0, 25) + '…';
                            fichaBadge = '<span class="eval-row-ficha" title="Asignada a ficha #' + r.ficha_id + '">' +
                                         Manager.EscapeHtml(diagShort) + '</span>';
                        } else {
                            fichaBadge = '<span class="eval-row-ficha unassigned" title="Sin ficha asignada">Sin ficha</span>';
                        }
                    }
                    var formUrl = EvaluacionManager.UrlForForm(key);
                    var viewLink = formUrl
                        ? '<a href="' + formUrl + '" title="Abrir lista del formulario">Ver <i class="fas fa-external-link-alt" style="font-size:.7rem;"></i></a>'
                        : '';
                    return (
                        '<div class="eval-row">' +
                            '<span class="eval-row-date">' + fecha + '</span>' +
                            fichaBadge +
                            '<span class="eval-row-user">' + user + '</span>' +
                            viewLink +
                        '</div>'
                    );
                }).join('');
            }

            // Link/botón para agregar. Si hay config inline → abre modal genérico;
            // si no, navega al formulario standalone (Fase 3a behavior).
            var addLink = '';
            if (InlineFormManager.HasConfigFor(key)) {
                addLink =
                    '<div class="eval-add-row">' +
                        '<a href="#" class="eval-add-inline" data-key="' + key + '">' +
                            '<i class="fas fa-plus-circle"></i> Agregar ' + Manager.EscapeHtml(meta.label.toLowerCase()) +
                            ' <span class="text-muted" style="font-size:.72rem;">(rápido)</span>' +
                        '</a>' +
                    '</div>';
            } else {
                var addUrl = EvaluacionManager.UrlForForm(key);
                if (addUrl) {
                    addLink =
                        '<div class="eval-add-row">' +
                            '<a href="' + addUrl + '" title="Abrir formulario completo">' +
                                '<i class="fas fa-plus-circle"></i> Agregar ' + Manager.EscapeHtml(meta.label.toLowerCase()) +
                                ' <i class="fas fa-external-link-alt" style="font-size:.65rem; opacity:.6; margin-left:.2rem;"></i>' +
                            '</a>' +
                        '</div>';
                }
            }

            return (
                '<div class="eval-section' + (collapsed ? ' collapsed' : '') + '" data-key="' + key + '">' +
                    '<button type="button" class="eval-section-header' + (collapsed ? ' collapsed' : '') + '">' +
                        '<div class="ev-icon bg-c-' + meta.color + '"><i class="fas ' + meta.icon + '"></i></div>' +
                        '<div class="ev-title">' + Manager.EscapeHtml(meta.label) + '</div>' +
                        '<div class="ev-count' + (count === 0 ? ' zero' : '') + '">' + count + '</div>' +
                        '<i class="fas fa-chevron-down ev-toggle"></i>' +
                    '</button>' +
                    '<div class="eval-section-body">' +
                        bodyHtml +
                        addLink +
                    '</div>' +
                '</div>'
            );
        },

        /**
         * Construye URL del formulario a partir de la tabla_form.
         * Mapping: fis_xxx → /fis-xxx
         */
        UrlForForm: function (tableKey) {
            if (!tableKey || tableKey.indexOf('fis_') !== 0) return null;
            var slug = tableKey.replace('fis_', 'fis-');
            return slug;
        },

        RenderError: function (msgHtml) {
            $('#eval-sections').html(
                '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i>' + msgHtml + '</div>'
            );
            $('#eval-summary').text('');
        }
    };

    // ========================================================================
    // Fase 3b — InlineFormManager: editor inline de evaluaciones
    // ========================================================================

    /**
     * Configuración declarativa por tipo de evaluación.
     * Cuando un tipo tiene config aquí, "+ Agregar" abre el modal inline
     * en lugar de redirigir al formulario standalone.
     */
    var EVAL_INLINE_CONFIGS = {

        'fis_evdolors': {
            label: 'Evaluación de dolor',
            endpointCreate: 'evdolors-create',
            fields: [
                { name: 'fecha',                        type: 'date',     label: 'Fecha',                 required: true, default: 'today', col: 6 },
                { name: 'pain_severity',                type: 'eva',      label: 'EVA actual',            col: 6, help: 'Intensidad ahora (0=sin dolor, 10=máximo)' },
                { name: 'pain_usual_intensity',         type: 'eva',      label: 'EVA habitual',          col: 6, help: 'Intensidad típica que reporta el paciente' },
                { name: 'pain_reduction_effectiveness', type: 'eva',      label: 'Efectividad del reductor', col: 6, help: '0=no funciona, 10=elimina el dolor' },
                { name: 'pain_location',                type: 'textarea', label: 'Ubicación del dolor',   col: 12, rows: 2 },
                { name: 'pain_start_when',              type: 'textarea', label: '¿Cuándo comenzó?',      col: 12, rows: 2 },
                { name: 'pain_start_time',              type: 'time',     label: 'Hora de inicio',        col: 6 },
                { name: 'pain_end_time',                type: 'time',     label: 'Hora final',            col: 6 },
                { name: 'pain_place',                   type: 'textarea', label: 'Lugar / contexto',      col: 12, rows: 2 },
                { name: 'pain_activity',                type: 'textarea', label: 'Actividad que lo provoca', col: 12, rows: 2 },
                { name: 'pain_reduction_method',        type: 'textarea', label: 'Qué reduce el dolor',   col: 12, rows: 2 },
                { name: 'observaciones',                type: 'textarea', label: 'Observaciones',         col: 12, rows: 3 }
            ]
        },

        'fis_cheqs': {
            label: 'Chequeo muscular (escala)',
            endpointCreate: 'cheqs-create',
            fields: [
                { name: 'fecha',         type: 'date',   label: 'Fecha', required: true, default: 'today', col: 6 },
                { name: 'escala',        type: 'select', label: 'Escala de fuerza muscular', col: 6, required: true, options: [
                    { value: '',  label: 'Selecciona…' },
                    { value: '0', label: '0 — Nulo' },
                    { value: '1', label: '1 — Vestigio' },
                    { value: '2', label: '2 — Deficiente' },
                    { value: '3', label: '3 — Aceptable' },
                    { value: '4', label: '4 — Bueno' },
                    { value: '5', label: '5 — Normal' }
                ]},
                { name: 'diagnostico',   type: 'text',     label: 'Diagnóstico', col: 12 },
                { name: 'observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 3 }
            ]
        },

        // -------------------------------------------------------------- EVALUACIÓN DE PIEL
        'fis_evpiels': {
            label: 'Evaluación de piel',
            endpointCreate: 'evpiels-create',
            fields: [
                { type: 'note', variant: 'instructions', label: 'NOTA: Toca cada hemicuerpo afectado para marcarlo en rojo. Máximo 2 zonas.' },

                // Silueta interactiva — cada zona marcada llena su textarea correspondiente.
                // Convención anatómica:
                //   Plano anterior  → mitad-izq del SVG = HD (Hemisferio Derecho del paciente)
                //                     mitad-der del SVG = HI (Hemisferio Izquierdo del paciente)
                //   Plano posterior → mitad-izq del SVG = HI (la vista posterior se invierte)
                //                     mitad-der del SVG = HD
                { type: 'body_map',
                  src: '/img/Evpiels.png',
                  alt: 'Silueta — planos anterior y posterior',
                  maxHeight: 420,
                  maxSelections: 2,
                  fillValue: 'Alteración detectada',
                  regions: [
                      // Plano anterior — silueta izquierda del PNG
                      { id: 'd_ant', label: 'Derecho · Anterior',   target: 'estado_piel_derecho_anterior',   left: 6,  top: 4, width: 19, height: 92 },
                      { id: 'i_ant', label: 'Izquierdo · Anterior', target: 'estado_piel_izquierdo_anterior', left: 26, top: 4, width: 19, height: 92 },
                      // Plano posterior — silueta derecha del PNG
                      { id: 'i_pos', label: 'Izquierdo · Posterior', target: 'estado_piel_izquierdo_posterior', left: 54, top: 4, width: 19, height: 92 },
                      { id: 'd_pos', label: 'Derecho · Posterior',   target: 'estado_piel_derecho_posterior',   left: 74, top: 4, width: 19, height: 92 }
                  ]
                },

                { name: 'fecha', type: 'date', label: 'Fecha',          required: true, default: 'today', col: 12 },
                { name: 'zonas', type: 'text', label: 'Zonas evaluadas', col: 12 },

                { type: 'section', label: 'Estado de la piel' },
                { type: 'note', label: 'Puedes editar los hallazgos detectados o agregar detalles adicionales.' },
                { name: 'estado_piel_izquierdo_anterior',  type: 'textarea', label: 'Izquierdo · Anterior',  col: 6, rows: 2 },
                { name: 'estado_piel_derecho_anterior',    type: 'textarea', label: 'Derecho · Anterior',    col: 6, rows: 2 },
                { name: 'estado_piel_izquierdo_posterior', type: 'textarea', label: 'Izquierdo · Posterior', col: 6, rows: 2 },
                { name: 'estado_piel_derecho_posterior',   type: 'textarea', label: 'Derecho · Posterior',   col: 6, rows: 2 },

                { type: 'section', label: 'Cierre' },
                { name: 'diagnostico',   type: 'text',     label: 'Diagnóstico',   col: 12 },
                { name: 'observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 3 }
            ]
        },

        // -------------------------------------------------------------- ANTROPOMETRÍA T.F (Tinetti — equilibrio)
        'fis_antropometrias': {
            label: 'Antropometría T.F (equilibrio Tinetti)',
            endpointCreate: 'antropometrias-create',
            fields: [
                { name: 'fecha', type: 'date', label: 'Fecha', required: true, default: 'today', col: 6 },
                // Total automático (suma de 9 ítems; máx 15)
                { name: 'total_puntaje', type: 'score_total', label: 'Puntaje total', col: 6, max: 15, help: 'Suma automática de los 9 ítems (0–15)' },

                { type: 'note', variant: 'instructions', label: 'Instrucciones: Paciente sentado en silla' },

                { type: 'section', variant: 'danger', label: 'EQUILIBRIO SENTADO' },
                { name: 'equi_s', type: 'select', scoreable: true, col: 12, hideLabel: true, options: [
                    { value: '0', label: '0 - Se inclina o desliza en la silla' },
                    { value: '1', label: '1 - Firme y seguro' }
                ]},

                { type: 'section', variant: 'danger', label: 'LEVANTARSE' },
                { name: 'lev_i', type: 'select', scoreable: true, col: 12, hideLabel: true, options: [
                    { value: '0', label: '0 - Incapaz sin ayuda' },
                    { value: '1', label: '1 - Con ayuda de brazos' },
                    { value: '2', label: '2 - Sin ayuda' }
                ]},

                { type: 'section', variant: 'danger', label: 'INTENTO DE LEVANTARSE' },
                { name: 'int_i', type: 'select', scoreable: true, col: 12, hideLabel: true, options: [
                    { value: '0', label: '0 - Incapaz sin ayuda' },
                    { value: '1', label: '1 - Requiere más de un intento' },
                    { value: '2', label: '2 - Se levanta en un intento' }
                ]},

                { type: 'section', variant: 'danger', label: 'EQUILIBRIO INMEDIATO AL LEVANTARSE' },
                { name: 'equil_i', type: 'select', scoreable: true, col: 12, hideLabel: true, options: [
                    { value: '0', label: '0 - Inestable' },
                    { value: '1', label: '1 - Estable con soporte' },
                    { value: '2', label: '2 - Estable sin soporte' }
                ]},

                { type: 'section', variant: 'danger', label: 'EQUILIBRIO EN BIPEDESTACIÓN' },
                { name: 'equib_i', type: 'select', scoreable: true, col: 12, hideLabel: true, options: [
                    { value: '0', label: '0 - Inestable' },
                    { value: '1', label: '1 - Estable con base amplia' },
                    { value: '2', label: '2 - Base estrecha sin soporte' }
                ]},

                { type: 'section', variant: 'danger', label: 'EMPUJÓN' },
                { name: 'em_t', type: 'select', scoreable: true, col: 12, hideLabel: true, options: [
                    { value: '0', label: '0 - Tiende a caerse' },
                    { value: '1', label: '1 - Se tambalea pero se mantiene' },
                    { value: '2', label: '2 - Firme' }
                ]},

                { type: 'section', variant: 'danger', label: 'OJOS CERRADOS' },
                { name: 'oj_i', type: 'select', scoreable: true, col: 12, hideLabel: true, options: [
                    { value: '0', label: '0 - Inestable' },
                    { value: '1', label: '1 - Estable' }
                ]},

                { type: 'section', variant: 'danger', label: 'GIRO DE 360°' },
                { name: 'gir_p', type: 'select', scoreable: true, col: 12, hideLabel: true, options: [
                    { value: '0', label: '0 - Pasos discontinuos' },
                    { value: '1', label: '1 - Pasos continuos' }
                ]},

                { type: 'section', variant: 'danger', label: 'SENTARSE' },
                { name: 'se_i', type: 'select', scoreable: true, col: 12, hideLabel: true, options: [
                    { value: '0', label: '0 - Inseguro' },
                    { value: '1', label: '1 - Usa brazos o movimiento no suave' },
                    { value: '2', label: '2 - Seguro y movimiento suave' }
                ]},

                { type: 'section', label: 'Cierre' },
                { name: 'diagnostico',   type: 'text',     label: 'Diagnóstico',   col: 12 },
                { name: 'observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 3 }
            ]
        },

        // -------------------------------------------------------------- ANTROPOMETRÍA (perímetros + tono)
        'fis_antropoms': {
            label: 'Antropometría',
            endpointCreate: 'antropoms-create',
            fields: [
                // Imagen anatómica de referencia (mismos números que la tabla)
                { type: 'image', src: '/img/antropometri.png', alt: 'Diagrama de perímetros corporales', maxHeight: 360 },

                // Fecha en su propia fila para que nunca quede colapsada (date pickers necesitan ancho)
                { name: 'fecha', type: 'date',   label: 'Fecha',     required: true, default: 'today', col: 12 },
                { name: 'peso',  type: 'number', label: 'Peso (kg)', col: 6, step: 0.01, min: 0, max: 500 },
                { name: 'talla', type: 'number', label: 'Talla (cm)',col: 6, step: 0.01, min: 0, max: 300 },

                { type: 'section', label: 'Perímetros (cm)' },
                { type: 'bilateral_number', label: '1. Brazo flexionado (máx. tensión)', nameLeft: 'brazo_flex_izq', nameRight: 'brazo_flex_der', col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '2. Brazo relajado',                  nameLeft: 'brazo_rela_izq', nameRight: 'brazo_rela_der', col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '3. Antebrazo',                       nameLeft: 'anteb_izq',      nameRight: 'anteb_der',      col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '4. Muñeca',                          nameLeft: 'mu_izq',         nameRight: 'mu_der',         col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '5. Muslo',                           nameLeft: 'mus_izq',        nameRight: 'mus_der',        col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '6. Pantorrilla',                     nameLeft: 'pant_izq',       nameRight: 'pant_der',       col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '7. Tobillo',                         nameLeft: 'tob_izq',        nameRight: 'tob_der',        col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '8. Cabeza',                          nameLeft: 'cabeza_izq',     nameRight: 'cabeza_der',     col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '9. Cuello',                          nameLeft: 'cue_izq',        nameRight: 'cue_der',        col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '10. Tórax',                          nameLeft: 'tor_izq',        nameRight: 'tor_der',        col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '11. Cintura',                        nameLeft: 'cint_izq',       nameRight: 'cint_der',        col: 12, step: 0.1, min: 0 },
                { type: 'bilateral_number', label: '12. Cadera',                         nameLeft: 'cade_izq',       nameRight: 'cade_der',        col: 12, step: 0.1, min: 0 },

                { name: 'observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 2 },

                { type: 'section', label: 'Edema / inflamación' },
                { name: 'lug',  type: 'text',   label: 'Lugar',         col: 6 },
                { name: 'diam', type: 'number', label: 'Diámetro (cm)', col: 6, step: 0.1, min: 0 },
                { name: 'observaciones2', type: 'textarea', label: 'Observaciones', col: 12, rows: 2 },

                { type: 'section', label: 'Evaluación del tono muscular' },
                // El DB no tiene una columna 'tono_muscular' única; tiene 4 booleanos.
                // mapToFlags traduce el valor del select a los 4 flags reales al guardar.
                { name: 'tono_muscular', type: 'select', label: 'Tono muscular', col: 12,
                  virtual: true,
                  mapToFlags: { '1': 'hipo', '2': 'hipe', '3': 'fluc', '4': 'tm_n' },
                  options: [
                      { value: '',  label: 'Seleccione' },
                      { value: '1', label: '1. Hipotonía' },
                      { value: '2', label: '2. Hipertonía' },
                      { value: '3', label: '3. TM Fluctuante' },
                      { value: '4', label: '4. TM Normal' }
                  ]
                },
                { name: 'observaciones_res', type: 'textarea', label: 'Observaciones y resultados', col: 12, rows: 3 }
            ]
        },

        // -------------------------------------------------------------- GONIOMETRÍA (ROM)
        'fis_goniometrias': {
            label: 'Goniometría',
            endpointCreate: 'goniometrias-create',
            fields: [
                { name: 'fecha', type: 'date', label: 'Fecha', required: true, default: 'today', col: 12 },
                { type: 'note', label: 'Todos los rangos en grados (°). Deja en blanco las articulaciones no evaluadas. Las imágenes son de referencia.' },

                // ===================== HOMBRO =====================
                { type: 'section', label: 'HOMBRO' },
                { type: 'gonio_movement', title: 'Flexión - Extensión', variant: 'warning',
                  range: 'Flexión: 0° a 90°. Extensión: 0° a 45° (o hasta 60°)',
                  imageLeft: '/img/hom_fle_iz.png', imageRight: '/img/hom_fle_iz.png',
                  pairs: [
                      { label: 'FLEX', nameLeft: 'hombro_flex_izq', nameRight: 'hombro_flex_der' },
                      { label: 'EXT',  nameLeft: 'hombro_ext_izq',  nameRight: 'hombro_ext_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Aducción - Abducción', variant: 'warning',
                  range: 'Abducción: 0° a 90° (rotación omóplato 120°-180°). Aducción: 90° a 0°',
                  imageLeft: '/img/hom_aduccion_iz.png',
                  pairs: [
                      { label: 'AD',  nameLeft: 'hombro_ad_izq',  nameRight: 'hombro_ad_der' },
                      { label: 'ABD', nameLeft: 'hombro_abd_izq', nameRight: 'hombro_abd_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Rotación', variant: 'primary',
                  range: 'Codo flexionado 0°-90°. Rot. externa 0°-60°. Rot. interna 0°-80°',
                  imageLeft: '/img/hom_rotacion_iz.png', imageRight: '/img/hom_rotacion_der.png',
                  pairs: [
                      { label: 'Rot. Int.', nameLeft: 'hombro_rot_int_izq', nameRight: 'hombro_rot_int_der' },
                      { label: 'Rot. Ext.', nameLeft: 'hombro_rot_ext_izq', nameRight: 'hombro_rot_ext_der' }
                  ]
                },

                // ===================== CODO =====================
                { type: 'section', label: 'CODO' },
                { type: 'gonio_movement', title: 'Flexión - Extensión', variant: 'warning',
                  range: 'Flexión: 0° a 150°. Extensión: 150° a 0°',
                  imageRight: '/img/cod_flexion_der.png',
                  pairs: [
                      { label: 'FLEX', nameLeft: 'codo_flex_izq', nameRight: 'codo_flex_der' },
                      { label: 'EXT',  nameLeft: 'codo_ext_izq',  nameRight: 'codo_ext_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Pronación - Supinación', variant: 'warning',
                  range: 'Pronación: 0° a 80°. Supinación: 0° a 80°',
                  imageLeft: '/img/cod_supinacion.png', imageRight: '/img/cod_supinacion.png',
                  pairs: [
                      { label: 'PRO', nameLeft: 'codo_pro_izq', nameRight: 'codo_pro_der' },
                      { label: 'SUP', nameLeft: 'codo_sup_izq', nameRight: 'codo_sup_der' }
                  ]
                },

                // ===================== MUÑECA =====================
                { type: 'section', label: 'MUÑECA' },
                { type: 'gonio_movement', title: 'Flexión Dorsal - Palmar', variant: 'info',
                  range: 'Flexión dorsal (extensión): 0° a 70°. Flexión palmar: 0° a 80°',
                  imageLeft: '/img/flexion_dor_iz.png', imageRight: '/img/flexion_dor_iz.png',
                  pairs: [
                      { label: 'FLEX-D', nameLeft: 'muneca_flex_dorsal_izq', nameRight: 'muneca_flex_dorsal_der' },
                      { label: 'FLEX-P', nameLeft: 'muneca_flex_palmar_izq', nameRight: 'muneca_flex_palmar_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Desviación Radial - Cubital', variant: 'warning',
                  range: 'Desviación radial: 0° a 20°. Desviación cubital: 0° a 30°',
                  imageLeft: '/img/dsv_rad_iz.png', imageRight: '/img/dsv_rad_iz.png',
                  pairs: [
                      { label: 'Desv. Radial',  nameLeft: 'muneca_desv_radial_izq',  nameRight: 'muneca_desv_radial_der' },
                      { label: 'Desv. Cubital', nameLeft: 'muneca_desv_cubital_izq', nameRight: 'muneca_desv_cubital_der' }
                  ]
                },

                // ===================== CADERA =====================
                { type: 'section', label: 'CADERA' },
                { type: 'gonio_movement', title: 'Flexión Rodilla Recta', variant: 'warning',
                  range: 'Flexión: 0° a 90°. Extensión: 0° a 45° (o hasta 60°)',
                  imageLeft: '/img/cad_flex_iz.png', imageRight: '/img/cad_flex_der.png',
                  pairs: [
                      { label: 'FL', nameLeft: 'cadera_flex_recta_izq', nameRight: 'cadera_flex_recta_der' },
                      { label: 'EX', nameLeft: 'cadera_ex_recta_izq',   nameRight: 'cadera_ex_recta_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Flexión Rodilla Flexionada', variant: 'danger',
                  range: 'Flexión: 0° a 120°. Extensión: 0° a 45° (o hasta 60°)',
                  imageLeft: '/img/cad_flex_rod_iz.png', imageRight: '/img/cad_flex_rod_der.png',
                  pairs: [
                      { label: 'FLEX', nameLeft: 'cadera_flex_flexionada_izq', nameRight: 'cadera_flex_flexionada_der' },
                      { label: 'EXT',  nameLeft: 'cadera_ext_flexionada_izq',  nameRight: 'cadera_ext_flexionada_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Extensión', variant: 'warning',
                  range: 'Extensión de cadera: 0° a 20°',
                  imageLeft: '/img/cad_ext_iz.png', imageRight: '/img/cad_ext_der.png',
                  pairs: [
                      { label: 'EXT', nameLeft: 'cadera_ext_izq', nameRight: 'cadera_ext_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Abducción - Aducción', variant: 'info',
                  range: 'Abducción: 0° a 45°. Aducción: 0° a 20°',
                  imageLeft: '/img/cad_abd_iz.png', imageRight: '/img/cad_abd_der.png',
                  pairs: [
                      { label: 'ABD', nameLeft: 'cadera_abd_izq', nameRight: 'cadera_abd_der' },
                      { label: 'AD',  nameLeft: 'cadera_ad_izq',  nameRight: 'cadera_ad_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Rotación Externa e Interna', variant: 'primary',
                  range: 'Rotación externa: 0° a 45°. Rotación interna: 0° a 45°',
                  imageLeft: '/img/cad_rot.png', imageRight: '/img/cad_rot.png',
                  pairs: [
                      { label: 'ROT INT', nameLeft: 'cadera_rot_int_izq', nameRight: 'cadera_rot_int_der' },
                      { label: 'ROT EXT', nameLeft: 'cadera_rot_ext_izq', nameRight: 'cadera_rot_ext_der' }
                  ]
                },

                // ===================== RODILLA =====================
                { type: 'section', label: 'RODILLA' },
                { type: 'gonio_movement', title: 'Flexión', variant: 'danger',
                  range: 'Flexión: 0° a 135°',
                  imageLeft: '/img/rod_flex_iz.png', imageRight: '/img/rod_flex_der.png',
                  pairs: [
                      { label: 'FLEXIÓN', nameLeft: 'rodilla_flex_izq', nameRight: 'rodilla_flex_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Extensión', variant: 'success',
                  range: 'Extensión: 0° a 135°',
                  imageRight: '/img/rod_ext_der.png',
                  pairs: [
                      { label: 'EXTENSIÓN', nameLeft: 'rodilla_ext_izq', nameRight: 'rodilla_ext_der' }
                  ]
                },

                // ===================== TOBILLO =====================
                { type: 'section', label: 'TOBILLO' },
                { type: 'gonio_movement', title: 'Flexión Plantar - Dorsal', variant: 'success',
                  range: 'Flexión plantar: 0° a 45°. Flexión dorsal (dorsiflexión): 0° a 45°',
                  imageLeft: '/img/tob_flex_iz.png', imageRight: '/img/tob_flex_der.png',
                  pairs: [
                      { label: 'FL Plantar', nameLeft: 'tobillo_flex_plantar_izq', nameRight: 'tobillo_flex_plantar_der' },
                      { label: 'FL Dorsal',  nameLeft: 'tobillo_flex_dorsal_izq',  nameRight: 'tobillo_flex_dorsal_der' }
                  ]
                },
                { type: 'gonio_movement', title: 'Eversión - Inversión', variant: 'success',
                  range: 'Eversión: 0° a 25°. Inversión: 0° a 35°',
                  imageLeft: '/img/tob_ever.png', imageRight: '/img/tob_ever.png',
                  pairs: [
                      { label: 'INV', nameLeft: 'tobillo_inversion_izq', nameRight: 'tobillo_inversion_der' },
                      { label: 'EV',  nameLeft: 'tobillo_eversion_izq',  nameRight: 'tobillo_eversion_der' }
                  ]
                },

                { type: 'section', label: 'Cierre' },
                { name: 'diagnostico',   type: 'text',     label: 'Diagnóstico',   col: 12 },
                { name: 'observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 3 }
            ]
        },

        // -------------------------------------------------------------- CHEQUEO MUSCULAR (bilateral 0-5)
        'fis_cheqmus': {
            label: 'Chequeo muscular completo',
            endpointCreate: 'cheqmus-create',
            fields: [
                { name: 'fecha', type: 'date', label: 'Fecha', required: true, default: 'today', col: 12 },
                { type: 'scale_legend', label: 'Escala para la valoración de la fuerza muscular',
                  items: [
                      { value: 0, label: 'Nulo',       color: 'danger'  },
                      { value: 1, label: 'Vestigio',   color: 'warning' },
                      { value: 2, label: 'Deficiente', color: 'dark'    },
                      { value: 3, label: 'Aceptable',  color: 'info'    },
                      { value: 4, label: 'Bueno',      color: 'light'   },
                      { value: 5, label: 'Normal',     color: 'success' }
                  ]
                },
                { type: 'note',  label: 'Selecciona el grado para cada movimiento. Deja en blanco lo no evaluado.' },

                { type: 'section', label: 'Cuello' },
                { type: 'bilateral_grade', label: 'Flexión',   nameLeft: 'fcm_cu_if', nameRight: 'fcm_cu_df', col: 6 },
                { type: 'bilateral_grade', label: 'Extensión', nameLeft: 'fcm_cu_ie', nameRight: 'fcm_cu_de', col: 6 },

                { type: 'section', label: 'Trapecio / hombro alto' },
                { type: 'bilateral_grade', label: 'Flexión',   nameLeft: 'fcm_tr_if', nameRight: 'fcm_tr_df', col: 6 },
                { type: 'bilateral_grade', label: 'Extensión', nameLeft: 'fcm_tr_ie', nameRight: 'fcm_tr_de', col: 6 },
                { type: 'bilateral_grade', label: 'Rotación',  nameLeft: 'fcm_tr_ir', nameRight: 'fcm_tr_dr', col: 6 },

                { type: 'section', label: 'Cabeza / cervical' },
                { type: 'bilateral_grade', label: 'Flex/Ext (1)', nameLeft: 'fcm_ca_if', nameRight: 'fcm_ca_ef', col: 6 },
                { type: 'bilateral_grade', label: 'Flex/Ext (2)', nameLeft: 'fcm_ca_ie', nameRight: 'fcm_ca_de', col: 6 },
                { type: 'bilateral_grade', label: 'Lat. (a)',     nameLeft: 'fcm_ca_ia', nameRight: 'fcm_ca_da', col: 6 },
                { type: 'bilateral_grade', label: 'Lat. (n)',     nameLeft: 'fcm_ca_in', nameRight: 'fcm_ca_dn', col: 6 },
                { type: 'bilateral_grade', label: 'Rotación',     nameLeft: 'fcm_ca_ir', nameRight: 'fcm_ca_dr', col: 6 },
                { type: 'bilateral_grade', label: 'Otro',         nameLeft: 'fcm_ca_ix', nameRight: 'fcm_ca_dx', col: 6 },

                { type: 'section', label: 'Hombro' },
                { type: 'bilateral_grade', label: 'Flexión',           nameLeft: 'fcm_ho_if', nameRight: 'fcm_ho_df', col: 6 },
                { type: 'bilateral_grade', label: 'Extensión',         nameLeft: 'fcm_ho_ie', nameRight: 'fcm_ho_de', col: 6 },
                { type: 'bilateral_grade', label: 'Abducción',         nameLeft: 'fcm_ho_ia', nameRight: 'fcm_ho_da', col: 6 },
                { type: 'bilateral_grade', label: 'Aducción',          nameLeft: 'fcm_ho_ic', nameRight: 'fcm_ho_dc', col: 6 },
                { type: 'bilateral_grade', label: 'Rot. interna',      nameLeft: 'fcm_ho_ir', nameRight: 'fcm_ho_dr', col: 6 },
                { type: 'bilateral_grade', label: 'Rot. externa',      nameLeft: 'fcm_ho_ix', nameRight: 'fcm_ho_dx', col: 6 },

                { type: 'section', label: 'Codo' },
                { type: 'bilateral_grade', label: 'Flexión',   nameLeft: 'fcm_co_if', nameRight: 'fcm_co_df', col: 6 },
                { type: 'bilateral_grade', label: 'Extensión', nameLeft: 'fcm_co_ie', nameRight: 'fcm_co_de', col: 6 },

                { type: 'section', label: 'Antebrazo' },
                { type: 'bilateral_grade', label: 'Pronación',  nameLeft: 'fcm_an_ia', nameRight: 'fcm_an_da', col: 6 },
                { type: 'bilateral_grade', label: 'Supinación', nameLeft: 'fcm_an_is', nameRight: 'fcm_an_ds', col: 6 },

                { type: 'section', label: 'Muñeca' },
                { type: 'bilateral_grade', label: 'Flex/Ext (m)', nameLeft: 'fcm_mu_im', nameRight: 'fcm_mu_dm', col: 6 },
                { type: 'bilateral_grade', label: 'Flex/Ext (e)', nameLeft: 'fcm_mu_ie', nameRight: 'fcm_mu_de', col: 6 },

                { type: 'section', label: 'Tronco / espalda' },
                { type: 'bilateral_grade', label: 'Tronco i',    nameLeft: 'fcm_to_ii', nameRight: 'fcm_to_di', col: 6 },
                { type: 'bilateral_grade', label: 'Tronco e',    nameLeft: 'fcm_to_ie', nameRight: 'fcm_to_de', col: 6 },
                { type: 'bilateral_grade', label: 'Tronco f',    nameLeft: 'fcm_to_if', nameRight: 'fcm_to_df', col: 6 },
                { type: 'bilateral_grade', label: 'Tronco d',    nameLeft: 'fcm_to_id', nameRight: 'fcm_to_dd', col: 6 },
                { type: 'bilateral_grade', label: 'Espalda e',   nameLeft: 'fcm_es_ie', nameRight: 'fcm_es_de', col: 6 },
                { type: 'bilateral_grade', label: 'Espalda d',   nameLeft: 'fcm_es_id', nameRight: 'fcm_es_dd', col: 6 },
                { type: 'bilateral_grade', label: 'Espalda a',   nameLeft: 'fcm_es_ia', nameRight: 'fcm_es_da', col: 6 },
                { type: 'bilateral_grade', label: 'Espalda c',   nameLeft: 'fcm_es_ic', nameRight: 'fcm_es_dc', col: 6 },

                { type: 'section', label: 'Rodilla' },
                { type: 'bilateral_grade', label: 'Flexión',   nameLeft: 'fcm_ro_if', nameRight: 'fcm_ro_df', col: 6 },
                { type: 'bilateral_grade', label: 'Extensión', nameLeft: 'fcm_ro_ix', nameRight: 'fcm_ro_dx', col: 6 },

                { type: 'section', label: 'Cierre' },
                { name: 'Diagnostico',   type: 'text',     label: 'Diagnóstico',   col: 12 },
                { name: 'Observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 3 }
            ]
        },

        // -------------------------------------------------------------- SENSIBILIDAD (dermatomas)
        'fis_sensitivitys': {
            label: 'Sensibilidad',
            endpointCreate: 'sensitivitys-create',
            fields: [
                { type: 'note', variant: 'instructions', label: 'INSTRUCCIONES: Marca el estado de cada dermatoma según el mapa anatómico. Los colores agrupan por región vertebral.' },

                // Imagen anatómica de dermatomas
                { type: 'image', src: '/img/FisSensitivitys.png', alt: 'Mapa de dermatomas — referencia anatómica', maxHeight: 420 },

                // Leyenda de regiones con colores que coinciden con el mapa
                { type: 'scale_legend', label: 'Regiones vertebrales',
                  items: [
                      { value: 'C1-C8', label: 'Cervical',  color: 'success' },
                      { value: 'T1-T12', label: 'Torácico', color: 'pink'    },
                      { value: 'L1-L4', label: 'Lumbar',    color: 'info'    },
                      { value: 'S1-S5', label: 'Sacro',     color: 'warning' }
                  ]
                },

                { name: 'fecha', type: 'date', label: 'Fecha', required: true, default: 'today', col: 12 },
                { type: 'note', label: 'Para cada dermatoma elige: Normal · Sensible · Alterada. Deja en blanco lo no evaluado.' },

                // ===== CERVICAL =====
                { type: 'section', variant: 'success', label: 'CERVICAL (C1–C8)' },
                { type: 'dermatome', code: 'c1', label: 'C1', group: 'cervical' },
                { type: 'dermatome', code: 'c2', label: 'C2', group: 'cervical' },
                { type: 'dermatome', code: 'c3', label: 'C3', group: 'cervical' },
                { type: 'dermatome', code: 'c4', label: 'C4', group: 'cervical' },
                { type: 'dermatome', code: 'c5', label: 'C5', group: 'cervical' },
                { type: 'dermatome', code: 'c6', label: 'C6', group: 'cervical' },
                { type: 'dermatome', code: 'c7', label: 'C7', group: 'cervical' },
                { type: 'dermatome', code: 'c8', label: 'C8', group: 'cervical' },

                // ===== TORÁCICO =====
                { type: 'section', variant: 'pink', label: 'TORÁCICO (T1–T12)' },
                { type: 'dermatome', code: 't1',  label: 'T1',  group: 'thoracic' },
                { type: 'dermatome', code: 't2',  label: 'T2',  group: 'thoracic' },
                { type: 'dermatome', code: 't3',  label: 'T3',  group: 'thoracic' },
                { type: 'dermatome', code: 't4',  label: 'T4',  group: 'thoracic' },
                { type: 'dermatome', code: 't5',  label: 'T5',  group: 'thoracic' },
                { type: 'dermatome', code: 't6',  label: 'T6',  group: 'thoracic' },
                { type: 'dermatome', code: 't7',  label: 'T7',  group: 'thoracic' },
                { type: 'dermatome', code: 't8',  label: 'T8',  group: 'thoracic' },
                { type: 'dermatome', code: 't9',  label: 'T9',  group: 'thoracic' },
                { type: 'dermatome', code: 't10', label: 'T10', group: 'thoracic' },
                { type: 'dermatome', code: 't11', label: 'T11', group: 'thoracic' },
                { type: 'dermatome', code: 't12', label: 'T12', group: 'thoracic' },

                // ===== LUMBAR =====
                { type: 'section', variant: 'info', label: 'LUMBAR (L1–L4)' },
                { type: 'dermatome', code: 'l1', label: 'L1', group: 'lumbar' },
                { type: 'dermatome', code: 'l2', label: 'L2', group: 'lumbar' },
                { type: 'dermatome', code: 'l3', label: 'L3', group: 'lumbar' },
                { type: 'dermatome', code: 'l4', label: 'L4', group: 'lumbar' },

                // ===== SACRO =====
                { type: 'section', variant: 'warning', label: 'SACRO (S1–S5)' },
                { type: 'dermatome', code: 's1', label: 'S1', group: 'sacrum' },
                { type: 'dermatome', code: 's2', label: 'S2', group: 'sacrum' },
                { type: 'dermatome', code: 's3', label: 'S3', group: 'sacrum' },
                { type: 'dermatome', code: 's4', label: 'S4', group: 'sacrum' },
                { type: 'dermatome', code: 's5', label: 'S5', group: 'sacrum' },

                { type: 'section', label: 'Cierre' },
                { name: 'Diagnostico',   type: 'text',     label: 'Diagnóstico',   col: 12 },
                { name: 'Observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 3 }
            ]
        },

        // -------------------------------------------------------------- ALINEACIÓN POSTURAL
        'fis_evalineps': {
            label: 'Alineación postural',
            endpointCreate: 'evalineps-create',
            fields: [
                { type: 'note', variant: 'instructions', label: 'INSTRUCCIONES: Observa cada vista postural y anota las desviaciones encontradas en las 4 vistas. Toma fotos si es necesario.' },

                // Imagen de referencia anatómica con las 4 vistas
                { type: 'image', src: '/img/EvAlineps.png', alt: 'Vistas posturales — Lateral derecho · Posterior · Anterior · Lateral izquierdo', maxHeight: 380 },

                // Leyenda de vistas con código de colores
                { type: 'scale_legend', label: 'Vistas posturales',
                  items: [
                      { value: 'LD', label: 'Lateral derecho',   color: 'primary' },
                      { value: 'PO', label: 'Posterior',         color: 'dark'    },
                      { value: 'AN', label: 'Anterior',          color: 'success' },
                      { value: 'LI', label: 'Lateral izquierdo', color: 'info'    }
                  ]
                },

                { name: 'fecha', type: 'date', label: 'Fecha', required: true, default: 'today', col: 12 },

                // Grilla postural — 12 partes × 4 vistas en formato tabla
                { type: 'postural_grid',
                  prefixes: ['ld', 'po', 'an', 'li'],
                  headers:  ['LD',  'PO',  'AN',  'LI' ],
                  bodyParts: [
                      { key: 'cabeza',    label: 'Cabeza' },
                      { key: 'hombros',   label: 'Hombros' },
                      { key: 'codos',     label: 'Codos' },
                      { key: 'torax',     label: 'Tórax' },
                      { key: 'omoplatos', label: 'Omóplatos' },
                      { key: 'columna',   label: 'Columna' },
                      { key: 'abdomen',   label: 'Abdomen' },
                      { key: 'pelvis',    label: 'Pelvis' },
                      { key: 'muslos',    label: 'Muslos' },
                      { key: 'rodillas',  label: 'Rodillas' },
                      { key: 'piernas',   label: 'Piernas' },
                      { key: 'pies',      label: 'Pies' }
                  ]
                },

                { type: 'section', label: 'Fotografías posturales (opcional)' },
                { type: 'file_uploads',
                  slots: [
                      { name: 'foto1', label: 'Lateral derecho' },
                      { name: 'foto2', label: 'Posterior' },
                      { name: 'foto3', label: 'Anterior' },
                      { name: 'foto4', label: 'Lateral izquierdo' }
                  ],
                  accept: 'image/*',
                  capture: 'environment'
                },

                { type: 'section', label: 'Cierre' },
                { name: 'diagnostico',   type: 'text',     label: 'Diagnóstico',   col: 12 },
                { name: 'observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 3 }
            ]
        },

        // -------------------------------------------------------------- ELECTROTERAPIA
        'fis_electros': {
            label: 'Electroterapia',
            endpointCreate: 'electros-create',
            fields: [
                { type: 'note', variant: 'instructions', label: 'Registra una sesión por zona estimulada (cara, muscular o nervioso). La imagen cambia según la sección elegida.' },

                { name: 'fecha',   type: 'date', label: 'Fecha', required: true, default: 'today', col: 12 },

                // Sección anatómica como SELECT — controla qué imagen se muestra
                // y queda persistida en la columna `seccion` de fis_electros.
                { name: 'seccion', type: 'select', label: 'Sección anatómica', col: 12, required: true,
                  options: [
                      { value: '',         label: 'Selecciona una sección…' },
                      { value: 'cara',     label: 'PUNTOS MOTORES DE LA CARA' },
                      { value: 'muscular', label: 'PUNTOS MOTORES MUSCULARES' },
                      { value: 'nervioso', label: 'PUNTOS MOTORES NERVIOSOS' }
                  ]
                },

                // Imagen dinámica — cambia al elegir la sección
                { type: 'image',
                  srcBy: 'seccion',
                  srcMap: {
                      cara:     '/img/Puntos motores.png',
                      muscular: '/img/Puntos_motores_musculares.png',
                      nervioso: '/img/Puntos_motores_nerviosos.png'
                  },
                  defaultSrc: '/img/Puntos motores.png',
                  alt: 'Puntos motores de referencia',
                  maxHeight: 420
                },

                { type: 'section', label: 'Parámetros de la corriente' },
                { name: 'current_type', type: 'text', label: 'Tipo de corriente', col: 12, help: 'Ej. TENS, EMS, Interferencial, Rusa, etc.' },
                { name: 'waveform',     type: 'text', label: 'Waveform (forma de onda)', col: 6 },
                { name: 'display',      type: 'text', label: 'Display',                  col: 6 },
                { name: 'cc_cv',        type: 'select', label: 'CC / CV', col: 6, options: [
                    { value: '',   label: '—' },
                    { value: 'CC', label: 'CC (corriente constante)' },
                    { value: 'CV', label: 'CV (voltaje constante)' }
                ]},
                { name: 'method',           type: 'text', label: 'Method (método)',         col: 6 },
                { name: 'carrier_frequency',type: 'text', label: 'Carrier Frecuencia',      col: 6 },
                { name: 'channel_mode',     type: 'text', label: 'Channel Mode',            col: 6 },
                { name: 'frequency_mhz',    type: 'text', label: 'Frecuencia (MHz)',        col: 6 },
                { name: 'burst_frequency',  type: 'text', label: 'Burst Freq.',             col: 6 },
                { name: 'vector_scan',      type: 'text', label: 'Vector Scan',             col: 6 },
                { name: 'duty_cycle',       type: 'text', label: 'Duty Cycle',              col: 6 },

                { type: 'section', label: 'Tiempo / modulación' },
                { name: 'treatment_time',       type: 'text', label: 'Treatment Time',          col: 6 },
                { name: 'anti_fatigue',         type: 'text', label: 'Anti-Fatigue',            col: 6 },
                { name: 'cycle_time',           type: 'text', label: 'Cycle Time',              col: 6 },
                { name: 'frequency_modulation', type: 'text', label: 'Freq. Mod.',              col: 6 },
                { name: 'polarity',             type: 'text', label: 'Polarity (polaridad)',    col: 6 },
                { name: 'amplitude_modulation', type: 'text', label: 'Amplish. Mod.',           col: 6 },
                { name: 'ramp',                 type: 'text', label: 'Ramp (rampa)',            col: 6 },
                { name: 'phase_duration',       type: 'text', label: 'Phase Duration',          col: 6 },

                { type: 'section', label: 'Cierre' },
                { name: 'diagnostico',   type: 'text',     label: 'Diagnóstico',   col: 12 },
                { name: 'observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 3 }
            ]
        },

        // -------------------------------------------------------------- ULTRASONIDO
        'fis_ultras': {
            label: 'Ultrasonido',
            endpointCreate: 'ultras-create',
            fields: [
                { name: 'fecha', type: 'date', label: 'Fecha', required: true, default: 'today', col: 12 },

                { type: 'section', label: 'Parámetros del ultrasonido' },
                { name: 'current_type',     type: 'text', label: 'Tipo',                col: 6 },
                { name: 'waveform',         type: 'text', label: 'Forma de onda',       col: 6 },
                { name: 'display',          type: 'text', label: 'Display',             col: 6 },
                { name: 'cc_cv',            type: 'text', label: 'CC / CV',             col: 6 },
                { name: 'method',           type: 'text', label: 'Método',              col: 6 },
                { name: 'carrier_frequency',type: 'text', label: 'Frecuencia portadora',col: 6 },
                { name: 'channel_mode',     type: 'text', label: 'Modo de canal',       col: 6 },
                { name: 'frequency_mhz',    type: 'text', label: 'Frecuencia (MHz)',    col: 6 },
                { name: 'burst_frequency',  type: 'text', label: 'Frec. de ráfaga',     col: 6 },
                { name: 'vector_scan',      type: 'text', label: 'Vector scan',         col: 6 },

                { type: 'section', label: 'Tiempo / modulación' },
                { name: 'duty_cycle',           type: 'text', label: 'Ciclo de trabajo',     col: 6 },
                { name: 'treatment_time',       type: 'text', label: 'Tiempo de tratamiento',col: 6 },
                { name: 'anti_fatigue',         type: 'text', label: 'Anti-fatiga',          col: 6 },
                { name: 'cycle_time',           type: 'text', label: 'Tiempo de ciclo',      col: 6 },
                { name: 'frequency_modulation', type: 'text', label: 'Modulación de frec.', col: 6 },
                { name: 'polarity',             type: 'text', label: 'Polaridad',           col: 6 },
                { name: 'amplitude_modulation', type: 'text', label: 'Mod. de amplitud',    col: 6 },
                { name: 'ramp',                 type: 'text', label: 'Rampa',               col: 6 },
                { name: 'phase_duration',       type: 'text', label: 'Duración de fase',    col: 6 },

                { type: 'section', label: 'Cierre' },
                { name: 'diagnostico',   type: 'text',     label: 'Diagnóstico',   col: 12 },
                { name: 'observaciones', type: 'textarea', label: 'Observaciones', col: 12, rows: 3 }
            ]
        }
    };

    var InlineFormManager = {

        currentKey: null,

        Open: function (tableKey) {
            var cfg = EVAL_INLINE_CONFIGS[tableKey];
            if (!cfg) {
                console.warn('No inline config for', tableKey);
                return false;
            }
            InlineFormManager.currentKey = tableKey;

            $('#modalEvalInlineTitle').text('Nueva ' + cfg.label.toLowerCase());

            // Render fields
            var $container = $('#evalInlineFields');
            $container.empty();
            cfg.fields.forEach(function (f) {
                $container.append(InlineFormManager.RenderField(f));
            });

            // Populate ficha dropdown
            InlineFormManager.PopulateFichaSelect();

            // Bind sliders EVA con su bubble
            $('#evalInlineFields .eva-slider-wrap input[type=range]').each(function () {
                InlineFormManager.BindEvaSlider($(this));
            });

            // Bind auto-suma de "score_total" (Tinetti y similares)
            InlineFormManager.BindScoreTotal();

            // Bind silueta interactiva (Evaluación de piel y similares)
            InlineFormManager.BindBodyMap();

            // Bind imágenes dinámicas (cambian según otro campo, ej. Electroterapia)
            InlineFormManager.BindDynamicImages();

            // Click en el código de un dermatoma (izq o der) limpia la selección.
            // Como el modal se reusa, removemos handlers viejos antes de añadir.
            $('#evalInlineFields').off('click.dermatomeClear')
                .on('click.dermatomeClear', '.dermatome-code', function () {
                    var $row = $(this).closest('.dermatome-row');
                    $row.find('input[type="radio"]').prop('checked', false);
                });

            // Bind file_uploads: preview + clear
            InlineFormManager.BindFileUploads();

            $('#modalEvalInline').modal('show');
            return true;
        },

        BindFileUploads: function () {
            $('#evalInlineFields .fu-slot').each(function () {
                var $slot = $(this);
                var $input = $slot.find('.fu-input');
                var $preview = $slot.find('[data-fu-preview]');
                var $clear = $slot.find('[data-fu-clear]');

                $input.off('change.fu').on('change.fu', function (e) {
                    var file = this.files && this.files[0];
                    if (!file) {
                        $preview.html('<i class="fas fa-camera"></i>').removeClass('has-image');
                        $clear.hide();
                        return;
                    }
                    // Preview con FileReader
                    var reader = new FileReader();
                    reader.onload = function (ev) {
                        $preview.html('<img src="' + ev.target.result + '" alt="preview">').addClass('has-image');
                        $clear.show();
                    };
                    reader.readAsDataURL(file);
                });

                $clear.hide().off('click.fu').on('click.fu', function () {
                    $input.val('');
                    $preview.html('<i class="fas fa-camera"></i>').removeClass('has-image');
                    $clear.hide();
                });
            });
        },

        // Para field type 'image' con srcBy/srcMap: cambia el src de la imagen
        // cada vez que cambia el campo controlador.
        BindDynamicImages: function () {
            var $imgs = $('#evalInlineFields .field-image-wrap[data-src-by]');
            if (!$imgs.length) return;

            $imgs.each(function () {
                var $wrap = $(this);
                var srcBy = $wrap.data('src-by');
                var defaultSrc = $wrap.data('default-src') || '';
                var srcMap = {};
                try {
                    var raw = $wrap.attr('data-src-map');
                    srcMap = raw ? JSON.parse(raw) : {};
                } catch (e) { srcMap = {}; }

                var $controller = $('#formEvalInline [name="' + srcBy + '"]');
                var $img = $wrap.find('img');

                var update = function () {
                    var v = $controller.val();
                    var newSrc = srcMap[v] || defaultSrc;
                    if (newSrc && $img.attr('src') !== newSrc) {
                        $img.attr('src', newSrc);
                    }
                };

                $controller.off('change.dynImg input.dynImg')
                           .on('change.dynImg input.dynImg', update);
                update(); // sincronizar al abrir
            });
        },

        BindBodyMap: function () {
            var $maps = $('#evalInlineFields [data-body-map]');
            if (!$maps.length) return;

            $maps.each(function () {
                var $map = $(this);
                var maxSel = parseInt($map.data('max-selections'), 10) || 99;
                var fillValue = $map.data('fill-value') || 'Alteración detectada';
                // Cola FIFO de regiones seleccionadas para enforzar el máximo
                var selectedQueue = [];

                $map.off('click.bodyMap').on('click.bodyMap', '.body-map-region', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var regionId = $btn.data('region-id');
                    var targetName = $btn.data('region-target');
                    var $target = $('#formEvalInline [name="' + targetName + '"]');
                    var wasSelected = $btn.hasClass('selected');

                    if (wasSelected) {
                        // Deseleccionar
                        $btn.removeClass('selected').attr('aria-pressed', 'false');
                        selectedQueue = selectedQueue.filter(function (x) { return x !== regionId; });
                        // Si el textarea contiene exactamente el fillValue, limpiarlo.
                        // Si el usuario agregó detalle propio, respetarlo.
                        if ($target.length && $target.val().trim() === fillValue) {
                            $target.val('');
                        }
                    } else {
                        // Seleccionar — primero validar tope
                        if (selectedQueue.length >= maxSel) {
                            // Política: deseleccionar el más antiguo para hacer espacio
                            var oldest = selectedQueue.shift();
                            var $oldBtn = $map.find('.body-map-region[data-region-id="' + oldest + '"]');
                            $oldBtn.removeClass('selected').attr('aria-pressed', 'false');
                            var oldTarget = $oldBtn.data('region-target');
                            var $oldTa = $('#formEvalInline [name="' + oldTarget + '"]');
                            if ($oldTa.length && $oldTa.val().trim() === fillValue) {
                                $oldTa.val('');
                            }
                            if (window.Message) {
                                Message.Notification('info', 'Máximo ' + maxSel + ' zonas. Se desmarcó "' + ($oldBtn.data('region-label') || '') + '".');
                            }
                        }
                        $btn.addClass('selected').attr('aria-pressed', 'true');
                        selectedQueue.push(regionId);
                        // Llenar textarea solo si está vacío (no sobreescribir notas del usuario)
                        if ($target.length && !$target.val().trim()) {
                            $target.val(fillValue);
                        }
                    }
                });
            });
        },

        BindScoreTotal: function () {
            var $total = $('#evalInlineFields [data-score-total]');
            if (!$total.length) return;
            var recompute = function () {
                var sum = 0;
                $('#evalInlineFields select[data-scoreable]').each(function () {
                    var v = parseInt($(this).val(), 10);
                    if (!isNaN(v)) sum += v;
                });
                $total.find('[data-score-value]').text(sum);
                $('#evalInlineFields [data-score-hidden]').val(sum);
            };
            $('#evalInlineFields').off('change.scoreTotal').on('change.scoreTotal', 'select[data-scoreable]', recompute);
            recompute();
        },

        PopulateFichaSelect: function () {
            var $sel = $('#evalInline_ficha_id');
            var $label = $('#evalInlineFichaLabel');
            $sel.empty();

            if (!state.evalFichas.length) {
                $sel.hide();
                $label.html('<span class="ficha-warn">' +
                    'Este paciente no tiene una ficha clínica activa. ' +
                    '<a href="' + EvaluacionManager.UrlForForm('fis_fichas') + '" target="_blank">Crear ficha</a>' +
                    '</span>');
                return;
            }

            $sel.show();
            state.evalFichas.forEach(function (f) {
                var diag = (f.diagnostico || '').trim();
                var motivo = (f.motivo_consulta || '').trim();
                var lbl = diag || (motivo.length > 50 ? motivo.substring(0, 50).trim() + '…' : motivo) || ('Ficha #' + f.id);
                if (f.fecha) lbl += ' · ' + Manager.FormatDate(f.fecha);
                $sel.append('<option value="' + f.id + '">' + Manager.EscapeHtml(lbl) + '</option>');
            });

            // Selección: la ficha del filtro activo si es numérica; si no, la más reciente
            var preferred = null;
            if (state.evalCurrentFilter && /^\d+$/.test(state.evalCurrentFilter)) {
                preferred = state.evalCurrentFilter;
            } else if (state.evalFichas[0]) {
                preferred = state.evalFichas[0].id;
            }
            if (preferred) $sel.val(preferred);
            $label.text(''); // el dropdown lo dice todo
        },

        RenderField: function (f) {
            // Tipos sin label/control normal — devuelven directo su markup
            if (f.type === 'section') {
                var helpFs = f.help ? ' <span class="fs-help">' + Manager.EscapeHtml(f.help) + '</span>' : '';
                var sectCls = 'field-section-header';
                if (f.variant) sectCls += ' field-section-' + f.variant;
                return '<div class="' + sectCls + '">' + Manager.EscapeHtml(f.label) + helpFs + '</div>';
            }
            if (f.type === 'note') {
                var noteCls = 'field-note' + (f.variant === 'instructions' ? ' field-note-instructions' : '');
                var noteIcon = (f.variant === 'instructions') ? '' : '<i class="fas fa-info-circle mr-1"></i>';
                return '<div class="' + noteCls + '">' + noteIcon + Manager.EscapeHtml(f.label) + '</div>';
            }
            // Imagen de referencia decorativa (no produce dato).
            // Soporta dos modos:
            //   - estático: { type:'image', src:'/img/x.png' }
            //   - dinámico: { type:'image', srcBy:'seccion', srcMap:{ k1:'/a.png', k2:'/b.png' }, defaultSrc:'/x.png' }
            //     (se actualiza al cambiar el valor del campo indicado por srcBy)
            if (f.type === 'image') {
                var imgSrc = f.src || f.defaultSrc || '';
                var imgAlt = Manager.EscapeHtml(f.alt || '');
                var imgMax = f.maxHeight ? ('max-height:' + f.maxHeight + 'px;') : 'max-height:360px;';
                var imgAttrs = '';
                if (f.srcBy && f.srcMap) {
                    imgAttrs =
                        ' data-src-by="' + Manager.EscapeHtml(f.srcBy) + '"' +
                        ' data-src-map="' + Manager.EscapeHtml(JSON.stringify(f.srcMap)) + '"' +
                        ' data-default-src="' + Manager.EscapeHtml(f.defaultSrc || '') + '"';
                }
                return (
                    '<div class="field-image-wrap"' + imgAttrs + '>' +
                        '<img src="' + imgSrc + '" alt="' + imgAlt + '" style="' + imgMax + ' max-width:100%; height:auto;">' +
                    '</div>'
                );
            }
            // Tabla postural: filas = partes del cuerpo, columnas = vistas (LD/PO/AN/LI)
            if (f.type === 'postural_grid') {
                var prefixes = f.prefixes || [];
                var headers  = f.headers  || prefixes.map(function (p) { return p.toUpperCase(); });
                var headerHtml = '<tr><th class="pg-part">Parte</th>' +
                    headers.map(function (h) { return '<th>' + Manager.EscapeHtml(h) + '</th>'; }).join('') +
                    '</tr>';
                var rowsHtml = (f.bodyParts || []).map(function (bp) {
                    var cells = prefixes.map(function (prefix) {
                        var fieldName = prefix + '_' + bp.key;
                        return '<td class="pg-cell"><input type="text" name="' + fieldName + '" class="form-control"></td>';
                    }).join('');
                    return '<tr><td class="pg-part-label">' + Manager.EscapeHtml(bp.label) + '</td>' + cells + '</tr>';
                }).join('');
                return (
                    '<div class="field-postural-grid">' +
                        '<div class="pg-scroll">' +
                            '<table class="pg-table">' +
                                '<thead>' + headerHtml + '</thead>' +
                                '<tbody>' + rowsHtml + '</tbody>' +
                            '</table>' +
                        '</div>' +
                    '</div>'
                );
            }
            // 4 slots de upload de fotos (multipart, con preview)
            if (f.type === 'file_uploads') {
                var accept  = f.accept || 'image/*';
                var capture = f.capture ? (' capture="' + f.capture + '"') : '';
                var slots = (f.slots || []).map(function (s, idx) {
                    return (
                        '<div class="fu-slot" data-fu-slot="' + Manager.EscapeHtml(s.name) + '">' +
                            '<div class="fu-label">' + Manager.EscapeHtml(s.label || ('Foto ' + (idx + 1))) + '</div>' +
                            '<div class="fu-preview" data-fu-preview><i class="fas fa-camera"></i></div>' +
                            '<input type="file" name="' + Manager.EscapeHtml(s.name) + '" accept="' + accept + '"' + capture + ' class="fu-input">' +
                            '<button type="button" class="fu-clear" data-fu-clear title="Quitar foto"><i class="fas fa-times"></i></button>' +
                        '</div>'
                    );
                }).join('');
                return (
                    '<div class="field-file-uploads" data-file-uploads="1">' +
                        '<div class="fu-grid">' + slots + '</div>' +
                    '</div>'
                );
            }
            // Leyenda de escala con badges de colores (ej. Daniels 0-5)
            if (f.type === 'scale_legend') {
                var items = (f.items || []).map(function (it) {
                    var color = it.color || 'secondary';
                    return '<span class="scale-badge scale-' + color + '">' +
                                '<span class="scale-badge-label">' + Manager.EscapeHtml(it.label) + '</span>' +
                                '<span class="scale-badge-value">' + Manager.EscapeHtml(String(it.value)) + '</span>' +
                           '</span>';
                }).join('');
                return (
                    '<div class="field-scale-legend">' +
                        (f.label ? '<div class="scale-title">' + Manager.EscapeHtml(f.label) + '</div>' : '') +
                        '<div class="scale-items">' + items + '</div>' +
                    '</div>'
                );
            }
            // Bloque de movimiento goniométrico: título + rango + imágenes + pares bilaterales
            if (f.type === 'gonio_movement') {
                var variant = f.variant || 'warning'; // warning, primary, danger, success, info
                var imgLeft = f.imageLeft
                    ? '<div class="gm-img"><img src="' + f.imageLeft + '" alt="IZQ"></div>'
                    : '<div class="gm-img gm-img-placeholder"></div>';
                var imgRight = f.imageRight
                    ? '<div class="gm-img"><img src="' + f.imageRight + '" alt="DER"></div>'
                    : '<div class="gm-img gm-img-placeholder"></div>';

                // Construir tabla de pares: cabecera "IZQ DER" y filas por par
                var pairsBody = (f.pairs || []).map(function (p) {
                    return (
                        '<tr>' +
                            '<td class="gm-pair-label">' + Manager.EscapeHtml(p.label) + '</td>' +
                            '<td class="gm-input"><input type="number" name="' + p.nameLeft  + '" class="form-control" min="0" max="360" step="1"><span class="gm-unit">°</span></td>' +
                            '<td class="gm-input"><input type="number" name="' + p.nameRight + '" class="form-control" min="0" max="360" step="1"><span class="gm-unit">°</span></td>' +
                        '</tr>'
                    );
                }).join('');

                return (
                    '<div class="gonio-movement gonio-' + variant + '">' +
                        '<div class="gm-header">' + Manager.EscapeHtml(f.title || '') + '</div>' +
                        (f.range ? '<div class="gm-range">' + Manager.EscapeHtml(f.range) + '</div>' : '') +
                        '<div class="gm-body">' +
                            imgLeft +
                            '<div class="gm-table-wrap">' +
                                '<table class="gm-table">' +
                                    '<thead><tr><th></th><th>IZQ</th><th>DER</th></tr></thead>' +
                                    '<tbody>' + pairsBody + '</tbody>' +
                                '</table>' +
                            '</div>' +
                            imgRight +
                        '</div>' +
                    '</div>'
                );
            }
            // Mapa corporal interactivo — clicks llenan textareas asociadas
            if (f.type === 'body_map') {
                var bmSrc = f.src || '';
                var bmAlt = Manager.EscapeHtml(f.alt || '');
                var bmMax = f.maxHeight ? ('max-height:' + f.maxHeight + 'px;') : 'max-height:420px;';
                var regions = (f.regions || []).map(function (r) {
                    return (
                        '<button type="button" class="body-map-region"' +
                            ' data-region-id="' + Manager.EscapeHtml(r.id || r.target) + '"' +
                            ' data-region-target="' + Manager.EscapeHtml(r.target) + '"' +
                            ' data-region-label="' + Manager.EscapeHtml(r.label || '') + '"' +
                            ' style="left:' + r.left + '%;top:' + r.top + '%;width:' + r.width + '%;height:' + r.height + '%;"' +
                            ' aria-pressed="false" aria-label="' + Manager.EscapeHtml(r.label || '') + '">' +
                            '<span class="body-map-region-label">' + Manager.EscapeHtml(r.label || '') + '</span>' +
                        '</button>'
                    );
                }).join('');
                return (
                    '<div class="field-body-map"' +
                        ' data-body-map="1"' +
                        ' data-max-selections="' + (f.maxSelections || 99) + '"' +
                        ' data-fill-value="' + Manager.EscapeHtml(f.fillValue || 'Alteración detectada') + '">' +
                        '<div class="body-map-canvas">' +
                            '<img src="' + bmSrc + '" alt="' + bmAlt + '" style="' + bmMax + '">' +
                            regions +
                        '</div>' +
                    '</div>'
                );
            }
            // Total automático (Tinetti)
            if (f.type === 'score_total') {
                var maxLbl = (f.max !== undefined) ? (' / ' + f.max) : '';
                var helpSt = f.help ? '<div class="field-help">' + Manager.EscapeHtml(f.help) + '</div>' : '';
                return (
                    '<div class="field-group col-' + (f.col || 12) + '">' +
                        '<label>' + Manager.EscapeHtml(f.label) + '</label>' +
                        '<div class="field-score-total" data-score-total data-score-max="' + (f.max || 0) + '">' +
                            '<span data-score-value>0</span>' +
                            '<span class="field-score-max">' + maxLbl + '</span>' +
                        '</div>' +
                        '<input type="hidden" name="' + f.name + '" data-score-hidden value="0">' +
                        helpSt +
                    '</div>'
                );
            }

            var colClass = 'col-' + (f.col || 12);
            var required = f.required ? '<span class="req">*</span>' : '';
            var help = f.help ? '<div class="field-help">' + Manager.EscapeHtml(f.help) + '</div>' : '';
            var control = '';

            switch (f.type) {
                case 'date':
                    var def = (f.default === 'today') ? new Date().toISOString().slice(0, 10) : (f.default || '');
                    control = '<input type="date" name="' + f.name + '" class="form-control"' +
                              ' max="' + new Date().toISOString().slice(0,10) + '"' +
                              ' value="' + def + '"' +
                              (f.required ? ' required' : '') + '>';
                    break;

                case 'time':
                    control = '<input type="time" name="' + f.name + '" class="form-control"' +
                              (f.required ? ' required' : '') + '>';
                    break;

                case 'number':
                    control = '<input type="number" name="' + f.name + '" class="form-control"' +
                              (f.min !== undefined ? ' min="' + f.min + '"' : '') +
                              (f.max !== undefined ? ' max="' + f.max + '"' : '') +
                              (f.step !== undefined ? ' step="' + f.step + '"' : '') +
                              (f.required ? ' required' : '') + '>';
                    break;

                case 'text':
                    control = '<input type="text" name="' + f.name + '" class="form-control"' +
                              (f.maxlength ? ' maxlength="' + f.maxlength + '"' : '') +
                              (f.required ? ' required' : '') + '>';
                    break;

                case 'textarea':
                    control = '<textarea name="' + f.name + '" class="form-control" rows="' + (f.rows || 2) + '"' +
                              (f.maxlength ? ' maxlength="' + f.maxlength + '"' : '') +
                              (f.required ? ' required' : '') + '></textarea>';
                    break;

                case 'select':
                    var scoreAttr = f.scoreable ? ' data-scoreable="1"' : '';
                    control = '<select name="' + f.name + '" class="form-control"' + scoreAttr + (f.required ? ' required' : '') + '>';
                    (f.options || []).forEach(function (opt) {
                        control += '<option value="' + Manager.EscapeHtml(opt.value) + '">' + Manager.EscapeHtml(opt.label) + '</option>';
                    });
                    control += '</select>';
                    break;

                case 'eva':
                    var defVal = (f.default !== undefined) ? f.default : 0;
                    control =
                        '<div class="eva-slider-wrap">' +
                            '<input type="range" name="' + f.name + '" min="0" max="10" step="1" value="' + defVal + '" data-eva="1">' +
                            '<div class="eva-value-bubble" data-eva-bubble>' + defVal + '</div>' +
                        '</div>';
                    break;

                case 'bilateral_number':
                    var unit = f.unit ? '<span class="text-muted ml-1" style="font-size:.75rem;">' + Manager.EscapeHtml(f.unit) + '</span>' : '';
                    control =
                        '<div class="bilateral-pair">' +
                            '<div class="side-block"><span class="side-label">Izq</span>' +
                                '<input type="number" name="' + f.nameLeft + '" class="form-control"' +
                                (f.min !== undefined ? ' min="' + f.min + '"' : '') +
                                (f.max !== undefined ? ' max="' + f.max + '"' : '') +
                                (f.step !== undefined ? ' step="' + f.step + '"' : '') + '>' + unit +
                            '</div>' +
                            '<div class="side-block"><span class="side-label">Der</span>' +
                                '<input type="number" name="' + f.nameRight + '" class="form-control"' +
                                (f.min !== undefined ? ' min="' + f.min + '"' : '') +
                                (f.max !== undefined ? ' max="' + f.max + '"' : '') +
                                (f.step !== undefined ? ' step="' + f.step + '"' : '') + '>' + unit +
                            '</div>' +
                        '</div>';
                    break;

                case 'bilateral_grade':
                    // Labels compactos (sólo el dígito) para que el valor seleccionado
                    // sea visible en celdas estrechas. La leyenda de colores explica
                    // qué significa cada número.
                    var gradeOpts = f.options || [
                        { value: '',  label: '—' },
                        { value: '0', label: '0' },
                        { value: '1', label: '1' },
                        { value: '2', label: '2' },
                        { value: '3', label: '3' },
                        { value: '4', label: '4' },
                        { value: '5', label: '5' }
                    ];
                    var optsHtml = gradeOpts.map(function (o) {
                        return '<option value="' + Manager.EscapeHtml(o.value) + '">' + Manager.EscapeHtml(o.label) + '</option>';
                    }).join('');
                    // bilateral-pair-compact → labels arriba del select (no colapsa en col-6)
                    control =
                        '<div class="bilateral-pair bilateral-pair-compact">' +
                            '<div class="side-block"><span class="side-label">IZQ</span>' +
                                '<select name="' + f.nameLeft + '" class="form-control">' + optsHtml + '</select>' +
                            '</div>' +
                            '<div class="side-block"><span class="side-label">DER</span>' +
                                '<select name="' + f.nameRight + '" class="form-control">' + optsHtml + '</select>' +
                            '</div>' +
                        '</div>';
                    break;

                case 'bilateral_text':
                    control =
                        '<div class="bilateral-pair">' +
                            '<div class="side-block"><span class="side-label">Izq</span>' +
                                '<input type="text" name="' + f.nameLeft + '" class="form-control">' +
                            '</div>' +
                            '<div class="side-block"><span class="side-label">Der</span>' +
                                '<input type="text" name="' + f.nameRight + '" class="form-control">' +
                            '</div>' +
                        '</div>';
                    break;

                case 'dermatome':
                    // dermatome devuelve un row completo (span 12) — overrideamos colClass al final
                    var code = f.code; // ej. 'c1'
                    var labelOpts = f.labels || { zn: 'Normal', zs: 'Sensible', za: 'Alterada' };
                    var groupCls = f.group ? ' dermatome-row-' + f.group : '';
                    var codeText = Manager.EscapeHtml(f.label || code.toUpperCase());
                    // 3 opciones Normal/Sensible/Alterada + el código de la fila también
                    // a la derecha como cierre visual. Click en el código (izquierdo o
                    // derecho) limpia la selección para volver a "no evaluado".
                    return (
                        '<div class="dermatome-row' + groupCls + '" data-dermatome-code="' + Manager.EscapeHtml(code) + '">' +
                            '<div class="dermatome-code dermatome-code-left" title="Click para limpiar">' + codeText + '</div>' +
                            '<div class="dermatome-options">' +
                                '<label><input type="radio" name="dermatome_' + code + '" value="zn"> ' + Manager.EscapeHtml(labelOpts.zn) + '</label>' +
                                '<label><input type="radio" name="dermatome_' + code + '" value="zs"> ' + Manager.EscapeHtml(labelOpts.zs) + '</label>' +
                                '<label><input type="radio" name="dermatome_' + code + '" value="za"> ' + Manager.EscapeHtml(labelOpts.za) + '</label>' +
                            '</div>' +
                            '<div class="dermatome-code dermatome-code-right" title="Click para limpiar">' + codeText + '</div>' +
                        '</div>'
                    );

                default:
                    control = '<input type="text" name="' + f.name + '" class="form-control">';
            }

            var labelHtml = f.hideLabel
                ? ''
                : '<label>' + Manager.EscapeHtml(f.label) + required + '</label>';

            return (
                '<div class="field-group ' + colClass + '">' +
                    labelHtml +
                    control +
                    help +
                '</div>'
            );
        },

        BindEvaSlider: function ($range) {
            var $bubble = $range.closest('.eva-slider-wrap').find('[data-eva-bubble]');
            var update = function () {
                var v = parseInt($range.val(), 10);
                $bubble.text(v);
                $bubble.removeClass('low mid high');
                if (v <= 3)      $bubble.addClass('low');
                else if (v <= 6) $bubble.addClass('mid');
                else             $bubble.addClass('high');
            };
            $range.off('input change').on('input change', update);
            update();
        },

        Save: function () {
            var cfg = EVAL_INLINE_CONFIGS[InlineFormManager.currentKey];
            if (!cfg) return;

            var fichaId = $('#evalInline_ficha_id').val();
            if (!fichaId) {
                if (window.Message) Message.Notification('warning', 'Selecciona o crea una ficha clínica primero.');
                return;
            }

            // Construir payload con valores actuales.
            // IMPORTANTE: omitir valores vacíos para evitar errores de MySQL strict mode
            // ('' → INT column lanza excepción). Si la columna debe quedar NULL, simplemente
            // no la enviamos y Eloquent la deja en NULL por defecto.
            var payload = {
                patient_id: ctx.id,
                ficha_id:   fichaId
            };

            // Helper: ¿el valor es "no enviar"?
            function isEmptyVal(v) {
                return v === null || v === undefined || v === '';
            }

            // Detectar si hay file_uploads → switch a multipart FormData
            var hasFileUploads = cfg.fields.some(function (f) { return f.type === 'file_uploads'; });

            cfg.fields.forEach(function (f) {
                // Decorativos no aportan valor
                if (f.type === 'section' || f.type === 'note' || f.type === 'image' || f.type === 'body_map' || f.type === 'scale_legend') return;

                // Bloque goniométrico: recolectar cada par interno
                if (f.type === 'gonio_movement') {
                    (f.pairs || []).forEach(function (p) {
                        var lv = $('#formEvalInline [name="' + p.nameLeft  + '"]').val();
                        var rv = $('#formEvalInline [name="' + p.nameRight + '"]').val();
                        if (!isEmptyVal(lv)) payload[p.nameLeft]  = lv;
                        if (!isEmptyVal(rv)) payload[p.nameRight] = rv;
                    });
                    return;
                }

                // Postural grid: iterar prefijos × bodyParts y recoger cada input
                if (f.type === 'postural_grid') {
                    (f.prefixes || []).forEach(function (prefix) {
                        (f.bodyParts || []).forEach(function (bp) {
                            var name = prefix + '_' + bp.key;
                            var v = $('#formEvalInline [name="' + name + '"]').val();
                            if (!isEmptyVal(v)) payload[name] = v;
                        });
                    });
                    return;
                }

                // file_uploads: las propias entradas <input type="file"> se agregan
                // luego al construir el FormData. Aquí solo nos saltamos el campo.
                if (f.type === 'file_uploads') return;

                // Tipos bilaterales: dos inputs físicos con sus nombres reales
                if (f.type === 'bilateral_number' || f.type === 'bilateral_grade' || f.type === 'bilateral_text') {
                    var lv = $('#formEvalInline [name="' + f.nameLeft + '"]').val();
                    var rv = $('#formEvalInline [name="' + f.nameRight + '"]').val();
                    if (!isEmptyVal(lv)) payload[f.nameLeft]  = lv;
                    if (!isEmptyVal(rv)) payload[f.nameRight] = rv;
                    return;
                }

                // Dermatoma: un radio → 3 columnas booleanas.
                // Si nada seleccionado o '— ', no enviamos nada (queda NULL en DB).
                if (f.type === 'dermatome') {
                    var sel = $('#formEvalInline input[name="dermatome_' + f.code + '"]:checked').val() || '';
                    if (sel === '') return;
                    payload[f.code + '_zn'] = (sel === 'zn') ? 1 : 0;
                    payload[f.code + '_zs'] = (sel === 'zs') ? 1 : 0;
                    payload[f.code + '_za'] = (sel === 'za') ? 1 : 0;
                    return;
                }

                // Campo regular
                var $el = $('#formEvalInline [name="' + f.name + '"]');
                if (!$el.length) return;
                var v = $el.val();

                // mapToFlags: el campo es un select cuya selección debe expandirse
                // a varios flags 0/1 en columnas del DB (cuando no existe la
                // columna unificada). Ejemplo: tono_muscular → hipo/hipe/fluc/tm_n
                if (f.mapToFlags) {
                    // Inicializar todos los flags conocidos en 0
                    Object.keys(f.mapToFlags).forEach(function (k) {
                        payload[f.mapToFlags[k]] = 0;
                    });
                    // Marcar el seleccionado en 1
                    if (!isEmptyVal(v) && f.mapToFlags[v]) {
                        payload[f.mapToFlags[v]] = 1;
                    }
                    // Si el campo es virtual (no existe esa columna en el DB),
                    // no enviar el propio name. De lo contrario, sí enviarlo.
                    if (!f.virtual && !isEmptyVal(v)) payload[f.name] = v;
                    return;
                }

                if (isEmptyVal(v)) return; // omitir vacíos

                // Campo virtual: presente solo en el UI (ej. selectores que controlan
                // imágenes dinámicas o secciones cuya columna no existe en la BD).
                // No se incluye en el payload.
                if (f.virtual) return;

                payload[f.name] = v;
            });

            // Validación cliente mínima — sólo campos required con `name` simple.
            // Para campos virtuales (no van al payload) se valida el valor del DOM.
            for (var i = 0; i < cfg.fields.length; i++) {
                var fld = cfg.fields[i];
                if (!fld.required) continue;
                if (fld.type === 'section' || fld.type === 'note' || fld.type === 'image' || fld.type === 'body_map' || fld.type === 'gonio_movement' || fld.type === 'scale_legend' || fld.type === 'postural_grid' || fld.type === 'file_uploads') continue;
                if (fld.type === 'bilateral_number' || fld.type === 'bilateral_grade' || fld.type === 'bilateral_text' || fld.type === 'dermatome') continue;

                var present = fld.virtual
                    ? !!($('#formEvalInline [name="' + fld.name + '"]').val() || '').trim()
                    : !!payload[fld.name];

                if (!present) {
                    if (window.Message) Message.Notification('warning', 'Completa: ' + fld.label);
                    $('#formEvalInline [name="' + fld.name + '"]').focus();
                    return;
                }
            }

            JsManager.StartProcessBar();

            // Si el formulario incluye file_uploads, usar FormData (multipart).
            // De lo contrario, payload plano normal (form-urlencoded).
            if (hasFileUploads) {
                var fd = new FormData();
                Object.keys(payload).forEach(function (k) {
                    fd.append(k, payload[k]);
                });
                // CSRF token desde el meta tag de Laravel
                var token = $('meta[name="csrf-token"]').attr('content');
                if (token) fd.append('_token', token);
                // Adjuntar cada archivo si fue elegido
                cfg.fields.forEach(function (f) {
                    if (f.type !== 'file_uploads') return;
                    (f.slots || []).forEach(function (s) {
                        var fileInput = $('#formEvalInline input[name="' + s.name + '"]')[0];
                        if (fileInput && fileInput.files && fileInput.files[0]) {
                            fd.append(s.name, fileInput.files[0]);
                        }
                    });
                });
                JsManager.SendJsonWithFile('POST', cfg.endpointCreate, fd, onSuccess, onFailed);
            } else {
                JsManager.SendJson('POST', cfg.endpointCreate, payload, onSuccess, onFailed);
            }

            function onSuccess(json) {
                JsManager.EndProcessBar();
                if (json && (json.status == '1' || json.status === 1)) {
                    if (window.Message) Message.Success('save');
                    $('#modalEvalInline').modal('hide');
                    // Reload evaluaciones para que aparezca la nueva entrada
                    state.evaluacionesLoaded = false;
                    EvaluacionManager.Load();
                } else {
                    if (window.Message) Message.Error('save');
                }
            }
            function onFailed(xhr) {
                JsManager.EndProcessBar();
                console.error('eval save failed', xhr.status, xhr.responseText, xhr);
                console.log('payload enviado:', payload);

                var msg = 'No se pudo guardar la evaluación.';
                try {
                    var resp = xhr.responseJSON || JSON.parse(xhr.responseText || '{}');
                    // Laravel validator -> resp.data = { campo: [errores] }
                    if (resp && resp.data) {
                        if (typeof resp.data === 'string') {
                            msg += ' ' + resp.data;
                        } else if (typeof resp.data === 'object') {
                            // Errores de validación
                            var firstErr = '';
                            for (var k in resp.data) {
                                if (Array.isArray(resp.data[k])) { firstErr = resp.data[k][0]; break; }
                                if (typeof resp.data[k] === 'string') { firstErr = resp.data[k]; break; }
                            }
                            if (firstErr) msg += ' ' + firstErr;
                        }
                    }
                } catch (e) { /* ignore */ }
                if (window.Message) Message.Notification('error', msg);
            }
        },

        HasConfigFor: function (tableKey) {
            return !!EVAL_INLINE_CONFIGS[tableKey];
        }
    };

})(jQuery);
