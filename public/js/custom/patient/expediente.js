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

    var ctx = window.PATIENT_CONTEXT || { id: null, name: '' };
    var state = {
        sesiones: [],
        fichas: [],
        loaded: false,
        loading: false
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
            if (sesion) {
                $('#modalVerNotaBody').html(sesion.nota_detallada || '<p class="text-muted">Sin nota.</p>');
                $('#modalVerNota').modal('show');
            }
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
                Manager.RenderError('Error de red cargando sesiones.');
                if (window.Message) Message.Exception(xhr);
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

        RenderError: function (msg) {
            $('#sesiones-list').html(
                '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i>' +
                Manager.EscapeHtml(msg) + '</div>'
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
            var ficha = '';
            if (s.ficha_motivo || s.ficha_diagnostico) {
                ficha = 'Ficha: ' + Manager.EscapeHtml(s.ficha_motivo || s.ficha_diagnostico || '');
                if (s.ficha_fecha) ficha += ' (' + Manager.FormatDate(s.ficha_fecha) + ')';
            }
            var user = s.user_name ? ' · ' + Manager.EscapeHtml(s.user_name) : '';

            var evolChip = '';
            if (s.evolucion) {
                var cls = (s.evolucion || '').toLowerCase();
                evolChip = '<span class="evol-chip ' + cls + '">' + Manager.EscapeHtml(s.evolucion) + '</span>';
            }

            var fields = '';
            if (s.tratamiento_realizado) {
                fields += '<div class="sesion-field"><div class="field-label">Tratamiento realizado</div>' +
                          '<div class="field-value">' + Manager.EscapeHtml(s.tratamiento_realizado) + '</div></div>';
            }
            if (s.observaciones) {
                fields += '<div class="sesion-field"><div class="field-label">Observaciones</div>' +
                          '<div class="field-value">' + Manager.EscapeHtml(s.observaciones) + '</div></div>';
            }

            var notaBtn = s.nota_detallada
                ? '<button class="btn btn-link btn-sm sesion-ver-nota p-0" data-id="' + s.id + '">' +
                  '<i class="fas fa-file-alt mr-1"></i>Ver nota detallada</button>'
                : '';

            return (
                '<div class="sesion-card">' +
                    '<div class="sesion-head">' +
                        '<div>' +
                            '<div class="sesion-date">' + fecha + ' ' + evolChip + '</div>' +
                            '<div class="sesion-ficha">' + ficha + user + '</div>' +
                        '</div>' +
                        '<div class="sesion-actions">' +
                            '<button class="btn btn-light sesion-duplicate" data-id="' + s.id + '" title="Duplicar"><i class="fas fa-copy"></i></button>' +
                            '<button class="btn btn-light sesion-edit" data-id="' + s.id + '" title="Editar"><i class="fas fa-edit"></i></button>' +
                            '<button class="btn btn-light sesion-delete" data-id="' + s.id + '" title="Eliminar"><i class="far fa-trash-alt text-danger"></i></button>' +
                        '</div>' +
                    '</div>' +
                    (fields || notaBtn
                        ? '<div class="sesion-body">' + fields + (notaBtn ? '<div class="mt-2">' + notaBtn + '</div>' : '') + '</div>'
                        : ''
                    ) +
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
                var label = (f.motivo_consulta || f.diagnostico || 'Ficha #' + f.id);
                if (f.fecha) label += ' — ' + Manager.FormatDate(f.fecha);
                $sel.append('<option value="' + f.id + '">' + Manager.EscapeHtml(label) + '</option>');
            });
            // Por defecto seleccionar la más reciente
            $sel.val(state.fichas[0].id);
        },

        OpenComposer: function (opts) {
            opts = opts || {};
            var mode = opts.mode || 'create';
            var source = opts.source || {};

            $('#formSesion')[0].reset();
            $('#sesion_patient_id').val(ctx.id);
            $('#sesion_nota_detallada').val('');

            var todayStr = new Date().toISOString().slice(0, 10);

            if (mode === 'create') {
                $('#modalSesionTitle').text('Nueva sesión');
                $('#sesion_id').val('');
                $('#sesion_fecha').val(todayStr);
                if (state.fichas.length) $('#sesion_ficha_id').val(state.fichas[0].id);
            } else if (mode === 'edit') {
                $('#modalSesionTitle').text('Editar sesión');
                $('#sesion_id').val(source.id);
                $('#sesion_ficha_id').val(source.ficha_id);
                $('#sesion_fecha').val((source.fecha || '').substring(0, 10) || todayStr);
                $('#sesion_tratamiento').val(source.tratamiento_realizado || '');
                $('#sesion_observaciones').val(source.observaciones || '');
                $('#sesion_evolucion').val(source.evolucion || '');
                $('#sesion_nota_detallada').val(source.nota_detallada || '');
            } else if (mode === 'duplicate') {
                $('#modalSesionTitle').text('Duplicar sesión (' + Manager.FormatDate(source.fecha) + ')');
                $('#sesion_id').val(''); // se guardará como nueva
                $('#sesion_ficha_id').val(source.ficha_id);
                $('#sesion_fecha').val(todayStr); // fecha actual, no la original
                $('#sesion_tratamiento').val(source.tratamiento_realizado || '');
                $('#sesion_observaciones').val(source.observaciones || '');
                $('#sesion_evolucion').val(''); // dejar vacía — el fisio decide tras la sesión
                // nota_detallada NO se copia para evitar arrastrar contenido enriquecido viejo
            }

            $('#modalSesion').modal('show');
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
        }
    };

})(jQuery);
