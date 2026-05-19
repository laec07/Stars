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
        suppressAutoSave: false // se activa al cargar contenido programáticamente
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

})(jQuery);
