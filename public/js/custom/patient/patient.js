(function ($) {
    "use strict";

    /* ====================================================================
       Healing Hands — Patient list (Niveles 1 + 2)
       Vista híbrida tabla/cards + filtros + recientes + drawer lateral
       + detección de duplicados + validación CUI + opt-in WhatsApp.
       ==================================================================== */

    var dTable = null;
    var _id = null;
    var initTelephone;

    var state = {
        allPatients: [],
        activeFilter: 'all',
        searchTerm: '',
        viewMode: 'cards',
        tableInitialized: false,
        saveAndOpen: false,
        editingId: null,
        selectedIds: new Set(),   // Nivel 3 — pacientes seleccionados
        templatesLoaded: false,   // Nivel 3 — plantillas cargadas en bulkModal
    };

    var STORAGE_KEYS = {
        view: 'hh.patientList.view',
        recents: 'hh.patientList.recents',
    };
    var RECENTS_MAX = 6;

    $(document).ready(function () {

        // Vista persistida
        var savedView = localStorage.getItem(STORAGE_KEYS.view);
        if (savedView === 'table' || savedView === 'cards') {
            state.viewMode = savedView;
        }
        ViewManager.ApplyView(state.viewMode);

        // Cargar pacientes + recientes
        Manager.GetDataList();
        Manager.LoadUserDropdown();
        RecentManager.Render();

        // Toggle vista
        $('#viewCards').on('click', function () { ViewManager.SetView('cards'); });
        $('#viewTable').on('click', function () { ViewManager.SetView('table'); });

        // Chips
        $(document).on('click', '.chip', function () {
            $('.chip').removeClass('chip--active');
            $(this).addClass('chip--active');
            state.activeFilter = $(this).data('filter');
            FilterManager.ApplyFilters();
        });

        // Búsqueda con debounce
        var searchTimer = null;
        $('#cardSearch').on('input', function () {
            var v = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                state.searchTerm = (v || '').trim().toLowerCase();
                FilterManager.ApplyFilters();
            }, 180);
        });

        // ===== Drawer (Nivel 2.3) =====
        $('#btnAdd').on('click', function () {
            DrawerManager.OpenForCreate();
        });
        $(document).on('click', '[data-drawer-close]', function () {
            DrawerManager.Close();
        });
        // Escape cierra drawer
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $('#patientDrawer').hasClass('is-open')) {
                DrawerManager.Close();
            }
        });

        // Editar (delegado desde cards y tabla)
        $(document).on('click', '.patient-edit-trigger, .dTableEdit', function (e) {
            e.stopPropagation();
            var raw = $(this).data('payload');
            if (!raw && dTable) {
                raw = dTable.row($(this).closest('tr')).data();
            }
            if (!raw) return;
            DrawerManager.OpenForEdit(raw);
        });

        // Eliminar
        $(document).on('click', '.patient-delete-trigger, .dTableDelete', function (e) {
            e.stopPropagation();
            var raw = $(this).data('payload');
            if (!raw && dTable) {
                raw = dTable.row($(this).closest('tr')).data();
            }
            if (!raw) return;
            Manager.Delete(raw.id);
        });

        // Click sobre la card
        $(document).on('click', '.patient-card', function (e) {
            if ($(e.target).closest('button, a, .patient-card__actions, .patient-card__check').length) return;
            // Si hay selección activa, click toggleará selección en vez de navegar
            if (state.selectedIds.size > 0) {
                SelectionManager.Toggle($(this).data('id'));
                return;
            }
            var id = $(this).data('id');
            if (id) {
                RecentManager.Push($(this).data('payload'));
                window.location.href = 'patient-summary/' + id;
            }
        });

        // Checkbox de selección — Nivel 3.2
        $(document).on('click', '.patient-card__check', function (e) {
            e.stopPropagation();
            var id = $(this).closest('.patient-card').data('id');
            SelectionManager.Toggle(id);
        });

        // Acciones de la barra contextual — Nivel 3.3
        $('#btnSelectAllVisible').on('click', SelectionManager.SelectAllVisible);
        $('#btnClearSelection').on('click', SelectionManager.Clear);
        $('#btnBulkExport').on('click', BulkExport.RunCsv);
        $('#btnBulkMessage').on('click', BulkMessage.Open);

        // Modal bulk wiring
        $('#bulkRecipientsToggle').on('click', function () {
            var $w = $('#bulkRecipients').length ? $('#bulkRecipients') : $(this).closest('.bulk-recipients');
            $w.toggleClass('is-open');
            $('#bulkRecipientsList').slideToggle(150);
        });
        $('#bulkTemplate').on('change', BulkMessage.OnTemplateChange);
        $('#bulkRequireOptin').on('change', BulkMessage.RefreshStats);
        $('#bulkSendBtn').on('click', BulkMessage.Send);

        // Atajos de teclado — Nivel 3.6
        $(document).on('keydown', KeyboardManager.Handle);
        $(document).on('click', '.btn-open-record', function (e) {
            e.preventDefault();
            var payload = $(this).data('payload');
            if (payload) RecentManager.Push(payload);
            var id = $(this).data('id');
            window.location.href = 'patient-summary/' + id;
        });

        // ===== Máscara de fecha de nacimiento (dd/mm/aaaa) =====
        // Reemplaza el datepicker nativo: el usuario escribe solo dígitos y las
        // barras se insertan automáticamente. Ver Manager.DobMask / DobToIso.
        $('#dob').on('input', function () {
            Manager.DobMask(this);
        });

        // ===== Validación submit =====
        JsManager.JqBootstrapValidation('#inputForm', (form, event) => {
            event.preventDefault();

            // Validar y convertir la fecha de nacimiento (dd/mm/aaaa → yyyy-mm-dd)
            // ANTES de armar el FormData, para que el backend reciba formato ISO.
            var dobResult = Manager.DobToIso($('#dob').val());
            if (dobResult.error) {
                if (window.Message) Message.Notification('warning', dobResult.error);
                $('#dob').focus();
                return;
            }

            var formData = new FormData(document.querySelector('#inputForm'));
            formData.set('dob', dobResult.iso); // sobrescribir con el valor ISO normalizado
            formData.append("phone_no", initTelephone.getNumber());
            // Asegurar que wa_optin = '0' si el checkbox no está marcado
            if (!document.getElementById('wa_optin').checked) {
                formData.set('wa_optin', '0');
            }
            if ($('#user_id').val() == null || $('#user_id').val() == 0) {
                Manager.Save(formData);
            } else {
                Manager.Update(formData, _id);
            }
        });

        $('#btnSaveAndOpen').on('click', function () {
            state.saveAndOpen = true;
            var formEl = document.getElementById('inputForm');
            if (formEl.requestSubmit) {
                formEl.requestSubmit(document.getElementById('btnSavePatient'));
            } else {
                $('#inputForm').trigger('submit');
            }
        });

        // ===== Detección de duplicados en vivo (Nivel 2.4) =====
        var dupTimer = null;
        var triggerDup = function () {
            if (state.editingId) return; // en edición no buscamos duplicados
            clearTimeout(dupTimer);
            dupTimer = setTimeout(DuplicateManager.Check, 320);
        };
        $('#full_name, #phone_no').on('input', triggerDup);

        // ===== Validación CUI (Nivel 2.5) =====
        $('#tax_number').on('input', function () {
            CuiValidator.OnInput($(this).val());
        });

        // ===== Phone input =====
        initTelephone = window.intlTelInput(document.querySelector("#phone_no"), {
            allowDropdown: true,
            autoHideDialCode: false,
            dropdownContainer: document.body,
            excludeCountries: [],
            formatOnDisplay: false,
            geoIpLookup: function (callback) {
                JsManager.SendJson('GET', 'get-requested-country-code', '',
                    function (jd) { callback(jd && jd.status == 1 ? jd.data : 'GT'); },
                    function () { callback('GT'); });
            },
            initialCountry: "auto",
            nationalMode: true,
            placeholderNumberType: "MOBILE",
            separateDialCode: true,
            utilsScript: "js/lib/tel-input/js/utils.js",
        });
    });


    /* ============================================================
       DrawerManager — abrir/cerrar drawer lateral
       ============================================================ */
    var DrawerManager = {
        OpenForCreate: function () {
            _id = null;
            state.editingId = null;
            state.saveAndOpen = false;
            Manager.ResetForm();
            $('#drawerTitleText').text('Nuevo paciente');
            $('#btnSaveAndOpen').show();
            $('#duplicatePanel').hide();
            $('#cuiFeedback').removeClass('is-valid is-invalid').empty();
            DrawerManager.Open();
        },
        OpenForEdit: function (rowData) {
            _id = rowData.id;
            state.editingId = rowData.id;
            state.saveAndOpen = false;
            $('#drawerTitleText').text('Editar paciente');
            $('#btnSaveAndOpen').hide();
            $('#duplicatePanel').hide();
            FormManager.PopulatePatientForm(rowData);
            CuiValidator.OnInput(rowData.tax_number || '');
            DrawerManager.Open();
        },
        Open: function () {
            $('#patientDrawer').addClass('is-open').attr('aria-hidden', 'false');
            $('body').css('overflow', 'hidden');
            setTimeout(function () { $('#full_name').focus(); }, 200);
        },
        Close: function () {
            $('#patientDrawer').removeClass('is-open').attr('aria-hidden', 'true');
            $('body').css('overflow', '');
            state.saveAndOpen = false;
        }
    };


    /* ============================================================
       FormManager — poblar form
       ============================================================ */
    var FormManager = {
        PopulatePatientForm: function (rowData) {
            Manager.ResetForm();
            $('#full_name').val(rowData.full_name || '');
            $('#user_id').val(rowData.id);
            if (rowData.phone_no) {
                initTelephone.setNumber('+' + rowData.phone_no);
            } else {
                initTelephone.setNumber('');
            }
            $('#email').val(rowData.email || '');
            $('#dob').val(Manager.DobToDisplay(rowData.dob));
            $('#tax_number').val(rowData.tax_number || '');
            $('#state').val(rowData.state || '');
            $('#treated').val(rowData.treated || '');
            $('#has_study').val(rowData.has_study || '');
            // Opt-in WhatsApp
            var wa = rowData.wa_optin_yes === true || rowData.wa_optin == 1;
            $('#wa_optin').prop('checked', wa);
        }
    };


    /* ============================================================
       ViewManager — toggle vista
       ============================================================ */
    var ViewManager = {
        SetView: function (mode) {
            state.viewMode = mode;
            localStorage.setItem(STORAGE_KEYS.view, mode);
            ViewManager.ApplyView(mode);
            if (mode === 'table' && !state.tableInitialized && state.allPatients.length) {
                Manager.RenderTable(state.allPatients);
            } else if (mode === 'table' && state.tableInitialized) {
                FilterManager.ApplyFilters();
            }
        },
        ApplyView: function (mode) {
            $('#viewCards, #viewTable').removeClass('is-active');
            if (mode === 'cards') {
                $('#viewCards').addClass('is-active');
                $('#cardsView').show();
                $('#tableView').hide();
            } else {
                $('#viewTable').addClass('is-active');
                $('#cardsView').hide();
                $('#tableView').show();
            }
        }
    };


    /* ============================================================
       RecentManager — recientes
       ============================================================ */
    var RecentManager = {
        Get: function () {
            try {
                var raw = localStorage.getItem(STORAGE_KEYS.recents);
                return raw ? JSON.parse(raw) : [];
            } catch (e) { return []; }
        },
        Push: function (patient) {
            if (!patient || !patient.id) return;
            var list = RecentManager.Get();
            list = list.filter(function (p) { return p.id !== patient.id; });
            list.unshift({
                id: patient.id,
                full_name: patient.full_name,
                initials: patient.initials,
                avatar_color: patient.avatar_color,
            });
            list = list.slice(0, RECENTS_MAX);
            localStorage.setItem(STORAGE_KEYS.recents, JSON.stringify(list));
            RecentManager.Render();
        },
        Render: function () {
            var list = RecentManager.Get();
            if (!list.length) { $('#recentBand').hide(); return; }
            var html = list.map(function (p) {
                return '<a class="recent-chip" href="patient-summary/' + p.id + '">'
                    + '<span class="avatar avatar--' + (p.avatar_color || 1) + '">' + (p.initials || '?') + '</span>'
                    + '<span>' + Manager.Escape(p.full_name || '') + '</span>'
                    + '</a>';
            }).join('');
            $('#recentBandList').html(html);
            $('#recentBand').show();
        }
    };


    /* ============================================================
       DuplicateManager — Nivel 2.4
       ============================================================ */
    var DuplicateManager = {
        Check: function () {
            var name = ($('#full_name').val() || '').trim();
            var phone = ($('#phone_no').val() || '').trim();
            // Solo si tenemos al menos algo significativo
            if (name.length < 3 && phone.replace(/\D/g, '').length < 4) {
                DuplicateManager.Hide();
                return;
            }
            var params = '?name=' + encodeURIComponent(name) + '&phone=' + encodeURIComponent(phone);
            if (state.editingId) params += '&exclude_id=' + state.editingId;
            JsManager.SendJson('GET', 'patient-duplicates' + params, '',
                function (jd) {
                    if (jd.status == 1 && Array.isArray(jd.data) && jd.data.length) {
                        DuplicateManager.Render(jd.data);
                    } else {
                        DuplicateManager.Hide();
                    }
                },
                function () { DuplicateManager.Hide(); }
            );
        },
        Render: function (matches) {
            var html = matches.map(function (p) {
                var phone = p.phone_no ? Manager.Escape(p.phone_no) : '';
                var email = p.email ? Manager.Escape(p.email) : '';
                var meta = [phone, email].filter(Boolean).join(' · ') || '—';
                return ''
                    + '<a class="duplicate-row" href="patient-summary/' + p.id + '" target="_blank">'
                    + '  <span class="avatar avatar--' + (p.avatar_color || 1) + '">' + Manager.Escape(p.initials || '?') + '</span>'
                    + '  <div class="duplicate-row__info">'
                    + '    <div class="duplicate-row__name">' + Manager.Escape(p.full_name || '') + '</div>'
                    + '    <div class="duplicate-row__meta">' + meta + '</div>'
                    + '  </div>'
                    + '  <span class="duplicate-row__action"><i class="fa fa-external-link-alt"></i> Ver</span>'
                    + '</a>';
            }).join('');
            $('#duplicateList').html(html);
            $('#duplicatePanel').show();
        },
        Hide: function () { $('#duplicatePanel').hide(); }
    };


    /* ============================================================
       CuiValidator — Nivel 2.5
       Validación de CUI guatemalteco (13 dígitos con checksum).
       Estructura:  PPPPPPPP C DD MM
       - 8 dígitos: correlativo
       - 1 dígito: verificador (módulo 11)
       - 2 dígitos: código de municipio
       - 2 dígitos: código de departamento
       ============================================================ */
    var CuiValidator = {
        OnInput: function (raw) {
            var $fb = $('#cuiFeedback');
            $fb.removeClass('is-valid is-invalid').empty();
            var v = String(raw || '').replace(/\s/g, '');
            if (!v) return; // vacío = sin feedback (campo opcional)

            // Si parece NIT (no es 13 dígitos), no validamos como CUI.
            // El usuario puede ingresar NIT (8-10 dígitos + guión-1-dígito).
            var digits = v.replace(/\D/g, '');
            if (digits.length !== 13) {
                if (digits.length > 13) {
                    $fb.addClass('is-invalid').html('<i class="fa fa-times-circle"></i> Demasiado largo');
                } else if (digits.length >= 8 && digits.length <= 11) {
                    // probablemente un NIT — no marcar nada (ok)
                }
                return;
            }
            // Validación CUI
            if (CuiValidator.IsValidCui(digits)) {
                $fb.addClass('is-valid').html('<i class="fa fa-check-circle"></i> CUI válido');
            } else {
                $fb.addClass('is-invalid').html('<i class="fa fa-times-circle"></i> CUI inválido');
            }
        },
        IsValidCui: function (cui) {
            if (!/^\d{13}$/.test(cui)) return false;
            var depto = parseInt(cui.substring(11, 13), 10);
            var muni  = parseInt(cui.substring(9, 11), 10);
            // Departamentos GT: 01-22
            if (depto < 1 || depto > 22) return false;
            if (muni < 1) return false;

            // Verificador módulo 11 sobre los primeros 8 dígitos + check
            var num = cui.substring(0, 8);
            var verifier = parseInt(cui.charAt(8), 10);
            var sum = 0;
            for (var i = 0; i < 8; i++) {
                sum += parseInt(num.charAt(i), 10) * (i + 2);
            }
            var mod = sum % 11;
            return mod === verifier;
        }
    };


    /* ============================================================
       FilterManager
       ============================================================ */
    var FilterManager = {
        Matches: function (p) {
            switch (state.activeFilter) {
                case 'open_case':
                    if (!p.has_open_case) return false; break;
                case 'this_week':
                    if (!p.seen_this_week) return false; break;
                case 'inactive':
                    if (!p.inactive_long) return false; break;
                case 'birthday':
                    if (!p.birthday_this_month) return false; break;
                case 'wa_optin':
                    if (!p.wa_optin_yes) return false; break;
            }
            if (state.searchTerm) {
                var hay = ((p.full_name || '') + ' ' + (p.phone_no || '') + ' ' + (p.email || '')).toLowerCase();
                if (hay.indexOf(state.searchTerm) === -1) return false;
            }
            return true;
        },
        ApplyFilters: function () {
            var filtered = state.allPatients.filter(FilterManager.Matches);
            if (state.viewMode === 'cards') {
                Manager.RenderCards(filtered);
            } else {
                if (dTable) dTable.draw();
            }
            $('#patientCountBadge').text(filtered.length);
        },
        UpdateChipCounts: function () {
            var counts = { all: 0, open_case: 0, this_week: 0, inactive: 0, birthday: 0, wa_optin: 0 };
            state.allPatients.forEach(function (p) {
                counts.all++;
                if (p.has_open_case)      counts.open_case++;
                if (p.seen_this_week)     counts.this_week++;
                if (p.inactive_long)      counts.inactive++;
                if (p.birthday_this_month) counts.birthday++;
                if (p.wa_optin_yes)       counts.wa_optin++;
            });
            Object.keys(counts).forEach(function (k) {
                $('.chip__count[data-count="' + k + '"]').text(counts[k]);
            });
        }
    };


    /* ============================================================
       Manager — CRUD + render
       ============================================================ */
    var Manager = {

        ResetForm: function () {
            $('#inputForm').trigger('reset');
            $('#user_id').val(0);
            $('#wa_optin').prop('checked', false);
            $('#cuiFeedback').removeClass('is-valid is-invalid').empty();
            $('#duplicatePanel').hide();
        },

        NormalizeDob: function (value) {
            if (!value) return '';
            var s = String(value).trim();
            if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.substring(0, 10);
            var m = s.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/);
            if (m) return m[3] + '-' + m[2] + '-' + m[1];
            var d = new Date(s);
            if (!isNaN(d.getTime())) {
                var mm = String(d.getMonth() + 1).padStart(2, '0');
                var dd = String(d.getDate()).padStart(2, '0');
                return d.getFullYear() + '-' + mm + '-' + dd;
            }
            return '';
        },

        // Aplica la máscara dd/mm/aaaa en vivo: deja solo dígitos (máx 8) e
        // inserta las barras automáticamente mientras el usuario escribe.
        DobMask: function (input) {
            var digits = String(input.value || '').replace(/\D/g, '').substring(0, 8);
            var out = digits;
            if (digits.length > 4) {
                out = digits.substring(0, 2) + '/' + digits.substring(2, 4) + '/' + digits.substring(4);
            } else if (digits.length > 2) {
                out = digits.substring(0, 2) + '/' + digits.substring(2);
            }
            input.value = out;
        },

        // Convierte el texto dd/mm/aaaa a ISO (yyyy-mm-dd) validando que sea una
        // fecha real, dentro de rango y no futura.
        // Devuelve { iso: 'yyyy-mm-dd'|'' , error: string|null }.
        // Vacío se considera válido (el campo es opcional).
        DobToIso: function (val) {
            var s = String(val || '').trim();
            if (s === '') return { iso: '', error: null };

            var m = s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            if (!m) return { iso: null, error: 'Fecha de nacimiento inválida. Usa el formato dd/mm/aaaa.' };

            var dd = parseInt(m[1], 10);
            var mm = parseInt(m[2], 10);
            var yyyy = parseInt(m[3], 10);
            var nowY = new Date().getFullYear();

            if (mm < 1 || mm > 12) return { iso: null, error: 'El mes debe estar entre 01 y 12.' };
            if (dd < 1 || dd > 31) return { iso: null, error: 'El día debe estar entre 01 y 31.' };
            if (yyyy < 1900 || yyyy > nowY) return { iso: null, error: 'El año debe estar entre 1900 y ' + nowY + '.' };

            // Validar que la fecha exista realmente (ej. 31/02 no existe)
            var dt = new Date(yyyy, mm - 1, dd);
            if (dt.getFullYear() !== yyyy || dt.getMonth() !== (mm - 1) || dt.getDate() !== dd) {
                return { iso: null, error: 'Esa fecha no existe. Verifica el día y el mes.' };
            }
            // No permitir fechas futuras
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            if (dt > today) return { iso: null, error: 'La fecha de nacimiento no puede ser futura.' };

            var pad = function (n) { return String(n).padStart(2, '0'); };
            return { iso: yyyy + '-' + pad(mm) + '-' + pad(dd), error: null };
        },

        // Convierte cualquier valor de dob (ISO o dd/mm/yyyy) a dd/mm/aaaa para
        // mostrarlo en el input de texto al editar un paciente.
        DobToDisplay: function (value) {
            if (!value) return '';
            var s = String(value).trim();
            var iso = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (iso) return iso[3] + '/' + iso[2] + '/' + iso[1];
            if (/^\d{2}\/\d{2}\/\d{4}$/.test(s)) return s; // ya está en dd/mm/yyyy
            // Último recurso: intentar parsear y formatear
            var d = new Date(s);
            if (!isNaN(d.getTime())) {
                var pad = function (n) { return String(n).padStart(2, '0'); };
                return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
            }
            return '';
        },

        Save: function (form) {
            if (Message.Prompt()) {
                JsManager.StartProcessBar();
                JsManager.SendJsonWithFile("POST", "patient-create", form, onSuccess, onFailed);

                function onSuccess(jsonData) {
                    if (jsonData.status == "1") {
                        Message.Success("save");
                        if (state.saveAndOpen && jsonData.data && jsonData.data.cmn_patient_id) {
                            JsManager.EndProcessBar();
                            window.location.href = 'patient-summary/' + jsonData.data.cmn_patient_id;
                            return;
                        }
                        Manager.ResetForm();
                        DrawerManager.Close();
                        Manager.GetDataList();
                    } else {
                        Message.Error("save");
                    }
                    JsManager.EndProcessBar();
                    state.saveAndOpen = false;
                }
                function onFailed(xhr) {
                    JsManager.EndProcessBar();
                    state.saveAndOpen = false;
                    Message.Exception(xhr);
                }
            }
        },

        Update: function (form, id) {
            if (Message.Prompt()) {
                JsManager.StartProcessBar();
                JsManager.SendJsonWithFile("POST", "patient-update", form, onSuccess, onFailed);

                function onSuccess(jsonData) {
                    if (jsonData.status == "1") {
                        Message.Success("update");
                        _id = null;
                        Manager.ResetForm();
                        DrawerManager.Close();
                        Manager.GetDataList();
                    } else {
                        Message.Error("update");
                    }
                    JsManager.EndProcessBar();
                }
                function onFailed(xhr) {
                    JsManager.EndProcessBar();
                    Message.Exception(xhr);
                }
            }
        },

        Delete: function (id) {
            if (Message.Prompt()) {
                JsManager.StartProcessBar();
                JsManager.SendJson("POST", "patient-delete", { id: id }, onSuccess, onFailed);

                function onSuccess(jsonData) {
                    if (jsonData.status == "1") {
                        Message.Success("delete");
                        Manager.GetDataList();
                    } else {
                        Message.Error("delete");
                    }
                    JsManager.EndProcessBar();
                }
                function onFailed(xhr) {
                    JsManager.EndProcessBar();
                    Message.Exception(xhr);
                }
            }
        },

        LoadUserDropdown: function () {
            JsManager.SendJson('GET', 'get-patient-user', '', function () {
                $("#user_id2").html('<option value="0">Patient</option>');
            }, function () {});
        },

        GetDataList: function () {
            JsManager.SendJsonAsyncON('GET', 'get-patient', '', onSuccess, function (xhr) {
                Message.Exception(xhr);
            });
            function onSuccess(jsonData) {
                state.allPatients = (jsonData.data || []).slice();
                state.allPatients.sort(function (a, b) {
                    if (a.has_open_case !== b.has_open_case) return b.has_open_case ? 1 : -1;
                    var la = a.last_visit || '';
                    var lb = b.last_visit || '';
                    if (la !== lb) return la < lb ? 1 : -1;
                    return (a.full_name || '').localeCompare(b.full_name || '');
                });
                FilterManager.UpdateChipCounts();
                FilterManager.ApplyFilters();
                if (state.viewMode === 'table') Manager.RenderTable(state.allPatients);
            }
        },

        /* ---------- Cards ---------- */
        RenderCards: function (list) {
            var $grid = $('#cardsView');
            if (!list.length) {
                $grid.empty().hide();
                $('#cardsEmpty').show();
                return;
            }
            $('#cardsEmpty').hide();
            $grid.show();
            var html = list.map(Manager.BuildCardHtml).join('');
            $grid.html(html);
        },

        BuildCardHtml: function (p) {
            var name = Manager.Escape(p.full_name || '—');
            var initials = p.initials || '?';
            var color = p.avatar_color || 1;
            var age = (p.age !== null && p.age !== undefined) ? p.age + ' años' : '—';
            var phone = p.phone_no || '';
            var phoneClean = phone.replace(/\D/g, '');
            var waLink = phoneClean ? ('https://wa.me/' + phoneClean) : '';
            var statusBadge = Manager.BuildStatusBadge(p);
            var birthdayBadge = p.birthday_this_month
                ? '<span class="status-badge birthday-badge"><i class="fa fa-birthday-cake"></i> Cumple</span>'
                : '';
            var waPill = p.wa_optin_yes
                ? '<span class="wa-pill" title="Acepta WhatsApp"><i class="fab fa-whatsapp"></i> WA</span>'
                : '';
            var lastVisitTxt = p.last_visit ? Manager.HumanDays(p.days_since_visit) : 'Sin visitas';
            var cases = p.case_count || 0;
            var evals = p.evaluation_count || 0;
            var payload = Manager.PayloadAttr(p);

            var isSel = state.selectedIds.has(p.id) ? ' is-selected' : '';
            return ''
                + '<div class="patient-card' + isSel + '" data-id="' + p.id + '" data-payload=\'' + payload + '\'>'
                + '  <span class="patient-card__check" title="Seleccionar"></span>'
                + '  <div class="patient-card__top">'
                + '    <span class="avatar avatar--' + color + '">' + Manager.Escape(initials) + '</span>'
                + '    <div class="patient-card__identity">'
                + '      <p class="patient-card__name" title="' + name + '">' + name + '</p>'
                + '      <div class="patient-card__meta">'
                + '        <span>' + age + '</span>'
                + (statusBadge ? '<span class="dot">·</span>' + statusBadge : '')
                + (birthdayBadge ? ' ' + birthdayBadge : '')
                + (waPill ? ' ' + waPill : '')
                + '      </div>'
                + '    </div>'
                + '  </div>'
                + '  <div class="patient-card__contact">'
                + '    <i class="fa fa-phone text-muted"></i>'
                + '    <span class="phone">' + (phone ? Manager.Escape(phone) : '<em class="text-muted">Sin teléfono</em>') + '</span>'
                + (waLink ? '    <a class="btn-whatsapp" href="' + waLink + '" target="_blank" title="WhatsApp" onclick="event.stopPropagation();"><i class="fab fa-whatsapp"></i></a>' : '')
                + '  </div>'
                + '  <div class="patient-card__stats">'
                + '    <span class="stat"><strong>' + cases + '</strong> caso' + (cases === 1 ? '' : 's') + '</span>'
                + '    <span class="stat"><strong>' + evals + '</strong> evaluación' + (evals === 1 ? '' : 'es') + '</span>'
                + '    <span class="stat" style="grid-column:1/-1;color:#999;"><i class="fa fa-clock"></i> ' + lastVisitTxt + '</span>'
                + '  </div>'
                + '  <div class="patient-card__actions">'
                + '    <a class="btn btn-primary btn-sm btn-open-record" data-id="' + p.id + '" data-payload=\'' + payload + '\' href="patient-summary/' + p.id + '">'
                + '      <i class="fa fa-folder-open"></i> Expediente'
                + '    </a>'
                + '    <button type="button" class="btn btn-outline-secondary btn-sm btn-icon patient-edit-trigger" data-payload=\'' + payload + '\' title="Editar"><i class="fa fa-edit"></i></button>'
                + '    <button type="button" class="btn btn-outline-danger btn-sm btn-icon patient-delete-trigger" data-payload=\'' + payload + '\' title="Eliminar"><i class="fa fa-trash"></i></button>'
                + '  </div>'
                + '</div>';
        },

        BuildStatusBadge: function (p) {
            switch (p.status_label) {
                case 'caso_abierto':
                    return '<span class="status-badge status-badge--caso_abierto"><i class="fa fa-circle"></i> Caso abierto</span>';
                case 'inactivo':
                    return '<span class="status-badge status-badge--inactivo"><i class="fa fa-pause-circle"></i> Inactivo</span>';
                case 'sin_visitas':
                    return '<span class="status-badge status-badge--sin_visitas">Sin visitas</span>';
                case 'activo':
                default:
                    return '<span class="status-badge status-badge--activo">Activo</span>';
            }
        },

        HumanDays: function (d) {
            if (d === null || d === undefined) return 'Sin visitas';
            d = parseInt(d, 10);
            if (isNaN(d)) return 'Sin visitas';
            if (d === 0) return 'Visto hoy';
            if (d === 1) return 'Visto ayer';
            if (d < 7) return 'Visto hace ' + d + ' días';
            if (d < 30) return 'Visto hace ' + Math.round(d / 7) + ' sem';
            if (d < 365) return 'Visto hace ' + Math.round(d / 30) + ' meses';
            return 'Visto hace ' + Math.round(d / 365) + ' años';
        },

        Escape: function (s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        },

        PayloadAttr: function (p) {
            var slim = {
                id: p.id,
                full_name: p.full_name,
                phone_no: p.phone_no,
                email: p.email,
                dob: p.dob,
                treated: p.treated,
                has_study: p.has_study,
                state: p.state,
                tax_number: p.tax_number,
                initials: p.initials,
                avatar_color: p.avatar_color,
                wa_optin: p.wa_optin,
                wa_optin_yes: p.wa_optin_yes,
            };
            return Manager.Escape(JSON.stringify(slim));
        },

        /* ---------- Tabla ---------- */
        RenderTable: function (data) {
            if (state.tableInitialized) {
                dTable.clear().rows.add(data).draw();
                return;
            }
            state.tableInitialized = true;

            $.fn.dataTable.ext.search.push(function (settings, _searchData, _index, rowData) {
                if (settings.nTable.id !== 'tableElement') return true;
                return FilterManager.Matches(rowData);
            });

            dTable = $('#tableElement').DataTable({
                dom: "<'row'<'col-md-6'B><'col-md-3'l><'col-md-3'f>>" + "<'row'<'col-md-12'tr>>" + "<'row'<'col-md-5'i><'col-md-7 mt-7'p>>",
                initComplete: function () { dTableManager.Border(this, 350); },
                buttons: [
                    { text: '<i class="fa fa-file-pdf"></i> PDF',    className: 'btn btn-sm', extend: 'pdfHtml5',   exportOptions: { columns: [2, 3, 4, 5, 6] }, title: 'Patient List' },
                    { text: '<i class="fa fa-print"></i> Print',     className: 'btn btn-sm', extend: 'print',      exportOptions: { columns: [2, 3, 4, 5, 6] }, title: 'Patient List' },
                    { text: '<i class="fa fa-file-excel"></i> Excel', className: 'btn btn-sm', extend: 'excelHtml5', exportOptions: { columns: [2, 3, 4, 5, 6] }, title: 'Patient List' }
                ],
                scrollY: "350px",
                scrollX: true,
                scrollCollapse: true,
                lengthMenu: [[50, 100, 500, -1], [50, 100, 500, "All"]],
                columnDefs: [
                    { visible: false, targets: [] },
                    { "className": "dt-center", "targets": [3] }
                ],
                columns: [
                    { data: null, name: '', orderable: false, searchable: false, title: '#SL', width: 8, render: function () { return ''; } },
                    {
                        name: 'Option', title: 'Opciones', width: 130,
                        render: function (data, type, row) {
                            var summary = '<a href="patient-summary/' + row.id + '" class="btn btn-secondary btn-datatable btn-round float-left mr-2" title="Ver expediente"><i class="fas fa-folder-open"></i></a>';
                            return summary + EventManager.DataTableCommonButton();
                        }
                    },
                    {
                        data: 'full_name', name: 'full_name', title: 'Paciente',
                        render: function (data, type, row) {
                            var color = row.avatar_color || 1;
                            var wa = row.wa_optin_yes ? ' <span class="wa-pill" title="Acepta WhatsApp" style="margin-left:6px;"><i class="fab fa-whatsapp"></i></span>' : '';
                            return '<span class="d-inline-flex align-items-center">'
                                + '<span class="avatar avatar--' + color + '" style="width:32px;height:32px;font-size:.75rem;margin-right:8px;">' + Manager.Escape(row.initials || '?') + '</span>'
                                + Manager.Escape(data || '—') + wa
                                + '</span>';
                        }
                    },
                    { data: 'email', name: 'email', title: 'Email' },
                    { data: 'phone_no', name: 'phone_no', title: 'Teléfono' },
                    { data: 'age', name: 'age', title: 'Edad', render: function (data) { return (data !== null && data !== undefined) ? data + ' años' : '—'; } },
                    { data: 'status_label', name: 'status_label', title: 'Estado', render: function (data, type, row) { return Manager.BuildStatusBadge(row); } },
                    { data: 'last_visit', name: 'last_visit', title: 'Última visita', render: function (data, type, row) { return Manager.HumanDays(row.days_since_visit); } },
                    { data: 'archivo', name: 'archivo', title: 'Documento', render: function (data) { return data ? '<a href="/' + data + '" target="_blank" download>Descargar</a>' : '<span class="text-muted"></span>'; } }
                ],
                fixedColumns: false,
                data: data
            });
        }
    };

    /* ============================================================
       SelectionManager — Nivel 3.2 + 3.3
       Mantiene state.selectedIds y refleja en UI.
       ============================================================ */
    var SelectionManager = {
        Toggle: function (id) {
            if (!id) return;
            id = parseInt(id, 10);
            if (state.selectedIds.has(id)) state.selectedIds.delete(id);
            else state.selectedIds.add(id);
            SelectionManager.RefreshUi();
        },
        Clear: function () {
            state.selectedIds.clear();
            SelectionManager.RefreshUi();
        },
        SelectAllVisible: function () {
            var visible = state.allPatients.filter(FilterManager.Matches);
            visible.forEach(function (p) { state.selectedIds.add(parseInt(p.id, 10)); });
            SelectionManager.RefreshUi();
        },
        RefreshUi: function () {
            // Marcar/desmarcar cards
            $('.patient-card').each(function () {
                var id = parseInt($(this).data('id'), 10);
                $(this).toggleClass('is-selected', state.selectedIds.has(id));
            });
            // Mostrar/ocultar barra
            var n = state.selectedIds.size;
            $('#selectionCount').text(n);
            if (n > 0) {
                $('#selectionBar').show();
                setTimeout(function () { $('#selectionBar').addClass('is-visible'); }, 10);
            } else {
                $('#selectionBar').removeClass('is-visible');
                setTimeout(function () { if (state.selectedIds.size === 0) $('#selectionBar').hide(); }, 250);
            }
        },
        GetSelectedPatients: function () {
            return state.allPatients.filter(function (p) {
                return state.selectedIds.has(parseInt(p.id, 10));
            });
        }
    };


    /* ============================================================
       BulkExport — Nivel 3.5 (exportar CSV en cliente)
       ============================================================ */
    var BulkExport = {
        RunCsv: function () {
            var patients = SelectionManager.GetSelectedPatients();
            if (!patients.length) return;
            var headers = ['Nombre', 'Telefono', 'Email', 'Edad', 'Casos', 'Evaluaciones', 'Ultima visita', 'Estado', 'Acepta WA'];
            var rows = patients.map(function (p) {
                return [
                    BulkExport.EscapeCsv(p.full_name),
                    BulkExport.EscapeCsv(p.phone_no),
                    BulkExport.EscapeCsv(p.email),
                    p.age != null ? p.age : '',
                    p.case_count || 0,
                    p.evaluation_count || 0,
                    BulkExport.EscapeCsv(p.last_visit || ''),
                    BulkExport.EscapeCsv(p.status_label || ''),
                    p.wa_optin_yes ? 'Si' : 'No'
                ].join(',');
            });
            var csv = '﻿' + headers.join(',') + '\n' + rows.join('\n'); // BOM para Excel
            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var ts = new Date().toISOString().slice(0, 10);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'pacientes_' + ts + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            // Toast / feedback
            if (typeof Message !== 'undefined' && Message.Success) {
                // No usamos Success() porque dispara confirm de save; mostramos toast simple si existe
            }
        },
        EscapeCsv: function (v) {
            if (v == null) return '';
            var s = String(v);
            if (/[",\n]/.test(s)) return '"' + s.replace(/"/g, '""') + '"';
            return s;
        }
    };


    /* ============================================================
       BulkMessage — Nivel 3.4
       ============================================================ */
    var BulkMessage = {
        Open: function () {
            var patients = SelectionManager.GetSelectedPatients();
            if (!patients.length) return;

            // Carga templates (1 vez)
            if (!state.templatesLoaded) {
                JsManager.SendJson('GET', 'message-templates', '', function (jd) {
                    if (jd.status == 1 && jd.data && Array.isArray(jd.data.templates)) {
                        var opts = '<option value="free">Mensaje libre (escribe abajo)</option>';
                        jd.data.templates.forEach(function (t) {
                            var key = t.key || t.id || '';
                            var label = t.name || t.label || key;
                            opts += '<option value="' + Manager.Escape(key) + '">' + Manager.Escape(label) + '</option>';
                        });
                        $('#bulkTemplate').html(opts);
                        state.templatesLoaded = true;
                    }
                    BulkMessage.RefreshStats();
                }, function () { BulkMessage.RefreshStats(); });
            } else {
                BulkMessage.RefreshStats();
            }

            $('#bulkSendResult').hide().removeClass('alert-success alert-warning alert-danger').empty();
            $('#bulkBody').val('');
            $('#bulkMessageModal').modal('show');
        },

        OnTemplateChange: function () {
            var key = $('#bulkTemplate').val();
            if (!key || key === 'free') return;
            // Pre-render usando un paciente dummy (sin patient_id) para mostrar la estructura
            JsManager.SendJson('GET', 'message-render?template_key=' + encodeURIComponent(key), '', function (jd) {
                if (jd.status == 1 && jd.data && jd.data.body) {
                    $('#bulkBody').val(jd.data.body);
                }
            }, function () {});
        },

        RefreshStats: function () {
            var patients = SelectionManager.GetSelectedPatients();
            var requireOptin = $('#bulkRequireOptin').is(':checked');
            var eligible = 0, skipped = 0;
            var listHtml = patients.map(function (p) {
                var reason = '';
                if (!p.phone_no) reason = 'Sin teléfono';
                else if (requireOptin && !p.wa_optin_yes) reason = 'No aceptó WhatsApp';

                var ok = !reason;
                if (ok) eligible++; else skipped++;

                var color = p.avatar_color || 1;
                return ''
                    + '<div class="bulk-recipient">'
                    + '  <span class="avatar avatar--' + color + '">' + Manager.Escape(p.initials || '?') + '</span>'
                    + '  <div class="bulk-recipient__name">' + Manager.Escape(p.full_name || '') + '</div>'
                    + (ok
                        ? '  <span class="bulk-recipient__status bulk-recipient__status--ok"><i class="fa fa-check"></i> Recibirá</span>'
                        : '  <span class="bulk-recipient__status bulk-recipient__status--skip">' + Manager.Escape(reason) + '</span>')
                    + '</div>';
            }).join('');

            $('#bulkSelectedCount').text(patients.length);
            $('#bulkEligibleCount').text(eligible);
            $('#bulkSkippedCount').text(skipped);
            $('#bulkRecipientsList').html(listHtml);
            $('#bulkSendLabel').text('Enviar a ' + eligible + (eligible === 1 ? ' paciente' : ' pacientes'));
            $('#bulkSendBtn').prop('disabled', eligible === 0);
        },

        Send: function () {
            var patients = SelectionManager.GetSelectedPatients();
            if (!patients.length) return;
            var body = ($('#bulkBody').val() || '').trim();
            var template = $('#bulkTemplate').val();
            if (template === 'free' && !body) {
                BulkMessage.ShowResult('warning', 'Escribe el mensaje antes de enviar.');
                return;
            }

            var payload = {
                patient_ids: patients.map(function (p) { return p.id; }),
                channel: 'whatsapp',
                require_optin: $('#bulkRequireOptin').is(':checked') ? 1 : 0,
            };
            if (template && template !== 'free') payload.template_key = template;
            if (body) payload.body = body;

            $('#bulkSendBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');
            JsManager.SendJson('POST', 'mass-message-send', payload, function (jd) {
                if (jd.status == 1) {
                    var d = jd.data || {};
                    var msg = 'Enviados: ' + (d.sent || 0)
                            + ' · Omitidos: ' + (d.skipped || 0)
                            + (d.failed ? ' · Fallidos: ' + d.failed : '');
                    var cls = d.failed > 0 ? 'warning' : 'success';
                    BulkMessage.ShowResult(cls, msg);
                    // Cerrar selección
                    setTimeout(function () {
                        SelectionManager.Clear();
                        $('#bulkMessageModal').modal('hide');
                    }, 1800);
                } else {
                    BulkMessage.ShowResult('danger', 'No se pudo enviar el mensaje. Intenta de nuevo.');
                }
                $('#bulkSendBtn').prop('disabled', false).html('<i class="fab fa-whatsapp"></i> <span id="bulkSendLabel">Enviar</span>');
                BulkMessage.RefreshStats();
            }, function (xhr) {
                $('#bulkSendBtn').prop('disabled', false).html('<i class="fab fa-whatsapp"></i> <span id="bulkSendLabel">Enviar</span>');
                BulkMessage.RefreshStats();
                BulkMessage.ShowResult('danger', 'Error de red al enviar.');
                if (typeof Message !== 'undefined' && Message.Exception) Message.Exception(xhr);
            });
        },

        ShowResult: function (kind, msg) {
            $('#bulkSendResult')
                .removeClass('alert-success alert-warning alert-danger')
                .addClass('alert-' + kind)
                .html(msg)
                .show();
        }
    };


    /* ============================================================
       KeyboardManager — Nivel 3.6
       N = nuevo · / = buscar · Esc = cerrar drawer/selección
       ============================================================ */
    var KeyboardManager = {
        Handle: function (e) {
            var tag = (e.target && e.target.tagName || '').toLowerCase();
            var isTyping = (tag === 'input' || tag === 'textarea' || tag === 'select' || (e.target && e.target.isContentEditable));

            // Escape: cierra drawer, limpia selección, o cierra modal de bulk
            if (e.key === 'Escape') {
                if ($('#patientDrawer').hasClass('is-open')) {
                    DrawerManager.Close();
                    e.preventDefault();
                } else if (state.selectedIds.size > 0) {
                    SelectionManager.Clear();
                    e.preventDefault();
                }
                return;
            }

            if (isTyping) return;

            // N = nuevo paciente
            if (e.key === 'n' || e.key === 'N') {
                e.preventDefault();
                DrawerManager.OpenForCreate();
                return;
            }

            // / = focus en búsqueda
            if (e.key === '/') {
                e.preventDefault();
                $('#cardSearch').focus().select();
                return;
            }

            // Ctrl+A en grid = seleccionar visibles
            if (e.key === 'a' && (e.ctrlKey || e.metaKey) && state.viewMode === 'cards') {
                if ($(e.target).closest('input, textarea').length) return;
                e.preventDefault();
                SelectionManager.SelectAllVisible();
            }
        }
    };

})(jQuery);
