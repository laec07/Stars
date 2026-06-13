@extends('layouts.app')
@section('content')
@push("adminScripts")
<link rel="stylesheet" href="{{ dsAsset('css/custom/patient-list.css') }}">
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/patient/patient.js')}}?v=202606131242"></script>
@endpush

<div class="page-inner patient-list-page">

    {{-- ============================================================
         Drawer lateral (Nivel 2.3) — reemplaza al modal anterior.
         Estructura compatible con Bootstrap 4: usamos un offcanvas
         CSS-only (panel + backdrop). El JS controla open/close.
         ============================================================ --}}
    <div id="patientDrawer" class="hh-drawer" aria-hidden="true">
        <div class="hh-drawer__backdrop" data-drawer-close></div>

        <aside class="hh-drawer__panel" role="dialog" aria-labelledby="patientDrawerTitle">
            <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                <header class="hh-drawer__header">
                    <h5 id="patientDrawerTitle" class="hh-drawer__title">
                        <i class="fa fa-user-plus mr-2"></i>
                        <span id="drawerTitleText">{{ translate('New Patient') }}</span>
                    </h5>
                    <button type="button" class="hh-drawer__close" data-drawer-close aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </header>

                <div class="hh-drawer__body">

                    {{-- Panel de detección de duplicados (Nivel 2.4) — oculto hasta tener matches --}}
                    <div id="duplicatePanel" class="duplicate-panel" style="display:none;">
                        <div class="duplicate-panel__header">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>{{ translate('Similar patients found') }}</strong>
                            <small class="text-muted ml-1">{{ translate('Make sure not to duplicate.') }}</small>
                        </div>
                        <div id="duplicateList" class="duplicate-panel__list"></div>
                    </div>

                    {{-- Identidad --}}
                    <div class="hh-drawer__section">
                        <div class="form-group control-group form-inline controls">
                            <label>{{translate('Patient Name')}} *</label>
                            <input type="text" id="full_name" name="full_name"
                                   placeholder="{{translate('Full name')}}" required
                                   data-validation-required-message="Patient name is required"
                                   class="form-control input-full" autocomplete="off" />
                            <span class="help-block"></span>
                        </div>

                        <div class="form-group control-group form-inline controls">
                            <label>NIT / CUI <small class="text-muted">({{ translate('optional') }})</small></label>
                            <div class="input-with-feedback">
                                <input type="text" id="tax_number" name="tax_number"
                                       placeholder="NIT o CUI (13 dígitos)"
                                       class="form-control input-full" autocomplete="off" />
                                <input type="hidden" name="user_id" id="user_id" value="0">
                                <span id="cuiFeedback" class="cui-feedback"></span>
                            </div>
                            <span class="help-block"></span>
                        </div>
                    </div>

                    {{-- Contacto --}}
                    <div class="hh-drawer__section">
                        <h6 class="hh-drawer__section-title">{{ translate('Contact') }}</h6>

                        <div class="form-group control-group form-inline controls">
                            <label class="col-md-12 p-0">{{translate('Patient Phone')}} *</label>
                            <input type="tel" id="phone_no" maxlength="20" name="phone_no"
                                   placeholder="{{translate('Phone Number')}}" autocomplete="tel" required
                                   data-validation-required-message="Phone number is required"
                                   class="form-control input-full w-100" />
                            <span class="help-block"></span>

                            {{-- Opt-in WhatsApp (Nivel 2.6) --}}
                            <div class="wa-optin">
                                <label class="wa-optin__label">
                                    <input type="checkbox" id="wa_optin" name="wa_optin" value="1">
                                    <span class="wa-optin__check"></span>
                                    <span class="wa-optin__text">
                                        <i class="fab fa-whatsapp"></i>
                                        {{ translate('Patient accepts to receive WhatsApp messages') }}
                                    </span>
                                </label>
                                <small class="wa-optin__hint">{{ translate('Reminders, follow-ups and updates. Can be revoked any time.') }}</small>
                            </div>
                        </div>

                        <div class="form-group control-group form-inline controls">
                            <label>{{translate('Patient Email')}}</label>
                            <input type="email" id="email" name="email"
                                   placeholder="email@example.com"
                                   autocomplete="email" inputmode="email"
                                   class="form-control input-full" />
                            <span class="help-block"></span>
                        </div>

                        <div class="form-group control-group form-inline controls">
                            <label for="dob">{{translate('Date of Birth')}}</label>
                            {{-- Campo de texto con máscara dd/mm/aaaa (en vez de <input type=date>):
                                 el datepicker nativo del navegador era lento de usar en tablet.
                                 El valor visible es dd/mm/aaaa y se convierte a ISO (yyyy-mm-dd)
                                 antes de enviar al backend. Ver patient.js (DobMask). --}}
                            <input type="text" id="dob" name="dob" autocomplete="bday"
                                   inputmode="numeric" maxlength="10"
                                   placeholder="dd/mm/aaaa"
                                   style="min-height:44px;font-size:1rem;"
                                   class="form-control input-full" />
                            <small class="text-muted" style="display:block;margin-top:.2rem;font-size:.75rem;">
                                {{ translate('Formato') }}: dd/mm/aaaa ({{ translate('ej.') }} 25/12/1990)
                            </small>
                            <span class="help-block"></span>
                        </div>
                    </div>

                    {{-- Documento adjunto (anteriormente "tiene estudios"; ahora simplemente
                         documento opcional como DPI o estudio previo. Los datos clínicos
                         se capturan en la Ficha clínica, no aquí.) --}}
                    <div class="hh-drawer__section">
                        <h6 class="hh-drawer__section-title">{{ translate('Attached document') }} <small class="text-muted">({{ translate('optional') }})</small></h6>
                        <div class="form-group control-group form-inline">
                            <img id="empimagepreview" width="100%" />
                            <input class="mt-1" type="file" id="image_url" name="image_url" accept="image/*,application/pdf">
                            <small class="form-text text-muted">{{ translate('DPI, ID card, or previous medical study (JPG, PNG, PDF).') }}</small>
                        </div>
                    </div>

                    {{-- Campos legacy ocultos (mantenemos compatibilidad sin mostrar UI):
                         "treated" y "has_study" se manejan ahora desde la Ficha clínica.
                         Quedan como hidden con valor "" para no romper validación backend. --}}
                    <input type="hidden" name="treated" id="treated" value="">
                    <input type="hidden" name="has_study" id="has_study" value="">
                    <input type="hidden" name="state" id="state" value="">

                </div>

                <footer class="hh-drawer__footer">
                    <button type="button" class="btn btn-link text-muted" data-drawer-close>
                        {{translate('Cancel')}}
                    </button>
                    <div class="hh-drawer__footer-actions">
                        <button type="button" id="btnSaveAndOpen" class="btn btn-outline-primary btn-sm" style="display:none;">
                            <i class="fa fa-folder-open"></i> {{translate('Save & open record')}}
                        </button>
                        <button type="submit" id="btnSavePatient" class="btn btn-success btn-sm">
                            <i class="fa fa-save"></i> {{translate('Save Patient')}}
                        </button>
                    </div>
                </footer>

            </form>
        </aside>
    </div>

    {{-- ============================================================
         Modal de envío masivo de WhatsApp (Nivel 3.4)
         ============================================================ --}}
    <div class="modal fade" id="bulkMessageModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fab fa-whatsapp mr-2" style="color:#25D366;"></i>
                        {{ translate('Send mass WhatsApp message') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="bulk-stats">
                        <div class="bulk-stat">
                            <strong id="bulkSelectedCount">0</strong>
                            <span>{{ translate('Selected') }}</span>
                        </div>
                        <div class="bulk-stat bulk-stat--ok">
                            <strong id="bulkEligibleCount">0</strong>
                            <span>{{ translate('Eligible') }}</span>
                        </div>
                        <div class="bulk-stat bulk-stat--skip">
                            <strong id="bulkSkippedCount">0</strong>
                            <span>{{ translate('Will be skipped') }}</span>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>{{ translate('Template') }}</label>
                        <select id="bulkTemplate" class="form-control">
                            <option value="free">{{ translate('Free message (write below)') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            {{ translate('Message') }}
                            <small class="text-muted">
                                {{ translate('Variables:') }}
                                <code>@{{paciente}}</code>,
                                <code>@{{clinic_name}}</code>,
                                <code>@{{fecha}}</code>,
                                <code>@{{hora}}</code>
                            </small>
                        </label>
                        <textarea id="bulkBody" class="form-control" rows="5"
                            placeholder="Hola @{{paciente}}, este es un recordatorio..."></textarea>
                    </div>

                    <div class="bulk-recipients">
                        <div class="bulk-recipients__header" id="bulkRecipientsToggle" role="button">
                            <i class="fa fa-chevron-right toggle-icon"></i>
                            <span>{{ translate('See recipient list') }}</span>
                        </div>
                        <div class="bulk-recipients__list" id="bulkRecipientsList" style="display:none;"></div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="bulkRequireOptin" checked>
                        <label class="form-check-label" for="bulkRequireOptin">
                            <strong>{{ translate('Only send to patients who accepted WhatsApp') }}</strong>
                            <small class="d-block text-muted">{{ translate('Recommended. Pre-marked for LGPD/LOPD compliance.') }}</small>
                        </label>
                    </div>

                    <div id="bulkSendResult" class="alert" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" id="bulkSendBtn" class="btn btn-success btn-sm">
                        <i class="fab fa-whatsapp"></i> <span id="bulkSendLabel">{{ translate('Send') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         Barra contextual de selección (Nivel 3.3)
         Aparece flotante en bottom cuando hay ≥1 paciente seleccionado.
         ============================================================ --}}
    <div id="selectionBar" class="selection-bar" style="display:none;">
        <div class="selection-bar__count">
            <i class="fa fa-check-circle"></i>
            <strong><span id="selectionCount">0</span></strong>
            <span>{{ translate('selected') }}</span>
        </div>
        <div class="selection-bar__actions">
            <button type="button" id="btnBulkMessage" class="btn btn-success btn-sm">
                <i class="fab fa-whatsapp"></i> {{ translate('Send WhatsApp') }}
            </button>
            <button type="button" id="btnBulkExport" class="btn btn-outline-light btn-sm">
                <i class="fa fa-file-csv"></i> {{ translate('Export CSV') }}
            </button>
            <button type="button" id="btnSelectAllVisible" class="btn btn-link btn-sm text-white">
                {{ translate('Select all visible') }}
            </button>
            <button type="button" id="btnClearSelection" class="btn btn-link btn-sm text-white">
                <i class="fa fa-times"></i> {{ translate('Clear') }}
            </button>
        </div>
    </div>

    <!-- Banda de Recientes (Nivel 1.4) -->
    <div id="recentBand" class="recent-band" style="display:none;">
        <div class="recent-band__header">
            <i class="fa fa-clock"></i>
            <span>{{ translate('Recent patients') }}</span>
        </div>
        <div id="recentBandList" class="recent-band__list"></div>
    </div>

    <!-- Card principal con vista híbrida -->
    <div class="row">
        <div class="col-md-12">
            <div class="main-card card patient-list-card">
                <div class="card-header patient-list-header">
                    <div class="patient-list-header__title">
                        <h4 class="card-title mb-0">{{translate('Patient Information')}}</h4>
                        <span id="patientCountBadge" class="patient-count-badge">0</span>
                    </div>

                    <div class="patient-list-header__actions">
                        <div class="btn-group view-toggle" role="group" aria-label="View toggle">
                            <button type="button" id="viewCards" class="btn btn-sm view-toggle__btn" title="{{translate('Cards view')}}">
                                <i class="fa fa-th-large"></i>
                            </button>
                            <button type="button" id="viewTable" class="btn btn-sm view-toggle__btn" title="{{translate('Table view')}}">
                                <i class="fa fa-table"></i>
                            </button>
                        </div>

                        <button id="btnAdd" class="btn btn-primary btn-sm btn-round ml-2">
                            <i class="fa fa-plus"></i> {{translate('Add New Patient')}}
                        </button>
                    </div>
                </div>

                {{-- Chips de filtro rápido (Nivel 1.3 + 2.6 WhatsApp chip) --}}
                <div class="patient-filter-bar">
                    <div class="filter-search">
                        <i class="fa fa-search"></i>
                        <input type="text" id="cardSearch" class="form-control"
                               placeholder="{{translate('Search by name, phone, email...')}}" />
                    </div>
                    <div class="filter-chips" role="tablist">
                        <button class="chip chip--active" data-filter="all">
                            {{ translate('All') }} <span class="chip__count" data-count="all">0</span>
                        </button>
                        <button class="chip" data-filter="open_case">
                            <i class="fa fa-folder-open"></i>
                            {{ translate('Open case') }} <span class="chip__count" data-count="open_case">0</span>
                        </button>
                        <button class="chip" data-filter="this_week">
                            <i class="fa fa-calendar-check"></i>
                            {{ translate('This week') }} <span class="chip__count" data-count="this_week">0</span>
                        </button>
                        <button class="chip" data-filter="inactive">
                            <i class="fa fa-clock"></i>
                            {{ translate('Inactive 90d+') }} <span class="chip__count" data-count="inactive">0</span>
                        </button>
                        <button class="chip" data-filter="birthday">
                            <i class="fa fa-birthday-cake"></i>
                            {{ translate('Birthdays this month') }} <span class="chip__count" data-count="birthday">0</span>
                        </button>
                        <button class="chip" data-filter="wa_optin">
                            <i class="fab fa-whatsapp"></i>
                            {{ translate('Accepts WhatsApp') }} <span class="chip__count" data-count="wa_optin">0</span>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div id="cardsView" class="patient-cards-grid"></div>
                    <div id="cardsEmpty" class="cards-empty-state" style="display:none;">
                        <i class="fa fa-user-friends"></i>
                        <p class="mb-1"><strong>{{ translate('No patients match this filter') }}</strong></p>
                        <p class="text-muted mb-0">{{ translate('Try a different filter or clear the search.') }}</p>
                    </div>

                    <div id="tableView" style="display:none;">
                        <table id="tableElement" class="table table-bordered w100"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
