@extends('layouts.app')
@section('content')

@push("adminScripts")
<script>
    window.PATIENT_CONTEXT = {
        id: {{ (int) $patient->id }},
        name: @json($patient->full_name),
        uploadUrl: @json(url('seguimiento/upload-image'))
    };
</script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="{{ dsAsset('js/custom/patient/expediente.js') }}"></script>
@endpush

@push("adminCss")
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    /* Fase 1 - Expediente del paciente */
    .expediente-back { color:#6c757d; text-decoration:none; font-size:.9rem; display:inline-flex; align-items:center; gap:.4rem; margin-bottom:.75rem; }
    .expediente-back:hover { color:#495057; text-decoration:none; }

    .patient-header { background:#fff; border-radius:.5rem; padding:1.25rem 1.5rem; box-shadow:0 1px 3px rgba(0,0,0,.06); border:1px solid #e9ecef; }
    .patient-avatar { width:64px; height:64px; border-radius:50%; background:#1572e8; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.5rem; font-weight:600; flex-shrink:0; }
    .patient-meta { color:#6c757d; font-size:.875rem; }
    .patient-meta i { width:1rem; opacity:.65; }
    .patient-tag { display:inline-block; background:#f1f3f5; color:#495057; padding:.2rem .6rem; border-radius:1rem; font-size:.75rem; margin-right:.35rem; }
    .patient-tag.tag-warn { background:#fff4e6; color:#d2691e; }

    .stat-card { background:#fff; border-radius:.5rem; padding:1rem 1.1rem; border:1px solid #e9ecef; height:100%; }
    .stat-card .stat-label { color:#6c757d; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
    .stat-card .stat-value { font-size:1.75rem; font-weight:600; line-height:1.2; color:#212529; }
    .stat-card .stat-sub { font-size:.75rem; color:#adb5bd; }

    .section-card { background:#fff; border-radius:.5rem; border:1px solid #e9ecef; }
    .section-card .section-header { padding:.85rem 1.15rem; border-bottom:1px solid #f1f3f5; font-weight:600; color:#212529; display:flex; align-items:center; justify-content:space-between; }

    .form-count-row { display:flex; align-items:center; padding:.65rem 1.15rem; border-bottom:1px solid #f8f9fa; }
    .form-count-row:last-child { border-bottom:none; }
    .form-count-row .icon-wrap { width:36px; height:36px; border-radius:.4rem; display:flex; align-items:center; justify-content:center; margin-right:.85rem; color:#fff; flex-shrink:0; }
    .form-count-row .label { flex:1; color:#343a40; }
    .form-count-row .count { font-weight:600; color:#212529; margin-right:1rem; }
    .form-count-row a { font-size:.8rem; color:#1572e8; text-decoration:none; }

    .timeline-list { padding:.5rem 0; }
    .timeline-item { display:flex; padding:.75rem 1.15rem; border-bottom:1px solid #f8f9fa; align-items:flex-start; }
    .timeline-item:last-child { border-bottom:none; }
    .timeline-item .ti-icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0; margin-right:.85rem; font-size:.8rem; }
    .timeline-item .ti-body { flex:1; min-width:0; }
    .timeline-item .ti-title { font-weight:500; color:#212529; font-size:.92rem; }
    .timeline-item .ti-meta { font-size:.78rem; color:#868e96; margin-top:.15rem; }
    .timeline-item .ti-date { font-size:.78rem; color:#868e96; white-space:nowrap; margin-left:.75rem; }

    .empty-state { padding:2.5rem 1rem; text-align:center; color:#adb5bd; }
    .empty-state i { font-size:2.5rem; opacity:.4; display:block; margin-bottom:.75rem; }

    .bg-c-primary   { background:#1572e8; }
    .bg-c-success   { background:#31ce36; }
    .bg-c-danger    { background:#f25961; }
    .bg-c-warning   { background:#ffad46; }
    .bg-c-info      { background:#48abf7; }
    .bg-c-secondary { background:#6861ce; }

    /* Fase 2 - Tabs y sesiones */
    .expediente-tabs { background:#fff; border-radius:.5rem 0 0 0; border:1px solid #e9ecef; border-bottom:none; padding:0; }
    .expediente-tabs .nav-tabs { border-bottom:1px solid #e9ecef; padding:0 1rem; margin:0; }
    .expediente-tabs .nav-link { color:#6c757d; border:none; border-bottom:3px solid transparent; padding:.85rem 1.1rem; font-weight:500; }
    .expediente-tabs .nav-link.active { color:#1572e8; background:transparent; border-bottom-color:#1572e8; }
    .expediente-tabs .nav-link:hover:not(.active) { color:#495057; border-bottom-color:#e9ecef; }
    .expediente-tab-content { background:#fff; border:1px solid #e9ecef; border-top:none; border-radius:0 0 .5rem .5rem; padding:1.25rem; }

    .sesion-card { background:#fff; border:1px solid #e9ecef; border-radius:.5rem; padding:.9rem 1.05rem; margin-bottom:.75rem; transition:box-shadow .15s; }
    .sesion-card:hover { box-shadow:0 2px 8px rgba(0,0,0,.05); }
    .sesion-card .sesion-top { display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem; }
    .sesion-card .sesion-top-main { flex:1; min-width:0; }
    .sesion-card .sesion-date { font-weight:600; color:#1572e8; font-size:.85rem; margin-bottom:.15rem; }
    .sesion-card .sesion-date i { margin-right:.3rem; opacity:.75; }
    .sesion-card .sesion-treatment {
        color:#212529; font-size:.95rem; line-height:1.4; font-weight:500;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    }
    .sesion-card .sesion-treatment.empty { color:#adb5bd; font-style:italic; font-weight:400; }
    .sesion-card .sesion-meta-row {
        display:flex; flex-wrap:wrap; align-items:center; gap:.35rem .75rem;
        color:#6c757d; font-size:.82rem; margin-top:.55rem;
    }
    .sesion-card .meta-diag { color:#212529; font-weight:500; }
    .sesion-card .meta-diag i { color:#1572e8; margin-right:.25rem; }
    .sesion-card .meta-user i { opacity:.6; margin-right:.25rem; }
    .sesion-card .sesion-actions { display:flex; gap:.3rem; flex-shrink:0; }
    .sesion-card .sesion-actions .btn { padding:.4rem .55rem; font-size:.8rem; min-width:36px; }
    .sesion-card .sesion-body { margin-top:.7rem; padding-top:.6rem; border-top:1px dashed #e9ecef; }
    .sesion-card .sesion-field { margin-bottom:.5rem; }
    .sesion-card .sesion-field:last-child { margin-bottom:0; }
    .sesion-card .sesion-field .field-label { font-size:.7rem; text-transform:uppercase; color:#adb5bd; letter-spacing:.04em; margin-bottom:.1rem; }
    .sesion-card .sesion-field .field-value { color:#343a40; font-size:.88rem; white-space:pre-wrap; line-height:1.4; }
    .sesion-card .sesion-footer { margin-top:.6rem; display:flex; flex-wrap:wrap; gap:.5rem 1rem; align-items:center; }
    .sesion-card .motivo-toggle {
        background:none; border:none; padding:.1rem 0; color:#1572e8;
        font-size:.78rem; cursor:pointer;
    }
    .sesion-card .motivo-toggle:hover { text-decoration:underline; }
    .sesion-card .motivo-content {
        display:none; width:100%;
        padding:.55rem .8rem; margin-top:.4rem;
        background:#f1f7ff; border-left:3px solid #1572e8; border-radius:.25rem;
        font-size:.83rem; color:#495057; line-height:1.4;
    }
    .sesion-card .motivo-content.open { display:block; }
    .sesion-card .evol-chip { display:inline-block; padding:.15rem .6rem; border-radius:1rem; font-size:.72rem; font-weight:600; text-transform:capitalize; }
    .evol-chip.favorable   { background:#e3f6e6; color:#1d7d2c; }
    .evol-chip.estable     { background:#fff4d6; color:#996800; }
    .evol-chip.desfavorable{ background:#fde2e1; color:#a8201a; }

    .sesion-composer .modal-body label { font-size:.8rem; color:#495057; font-weight:500; margin-bottom:.25rem; }
    .sesion-composer textarea.form-control { min-height:60px; }

    /* Quill — toolbar y editor optimizados para tablet */
    .sesion-composer .nota-actions { display:flex; align-items:center; gap:.5rem; margin-bottom:.3rem; flex-wrap:wrap; }
    .sesion-composer .nota-save-status { font-size:.75rem; color:#6c757d; margin-left:auto; min-width:120px; text-align:right; }
    .sesion-composer .nota-save-status.saving { color:#f5a623; }
    .sesion-composer .nota-save-status.saved  { color:#1d7d2c; }
    .sesion-composer .ql-toolbar.ql-snow { border-radius:.35rem .35rem 0 0; padding:.45rem; background:#fafbfc; }
    .sesion-composer .ql-toolbar.ql-snow .ql-formats { margin-right:.6rem; }
    .sesion-composer .ql-toolbar.ql-snow button {
        min-width:38px; min-height:38px; padding:6px 8px;
        border-radius:.25rem; transition:background .15s;
    }
    .sesion-composer .ql-toolbar.ql-snow button:hover,
    .sesion-composer .ql-toolbar.ql-snow button.ql-active { background:#e9ecef; }
    .sesion-composer .ql-container.ql-snow { border-radius:0 0 .35rem .35rem; font-size:.95rem; }
    .sesion-composer .ql-editor { min-height:160px; max-height:35vh; overflow-y:auto; }
    .sesion-composer .ql-editor img { max-width:100%; height:auto; border-radius:.25rem; display:block; margin:.5rem auto; }
    .sesion-composer .nota-upload-progress {
        display:none; align-items:center; gap:.4rem;
        font-size:.78rem; color:#1572e8; margin-top:.3rem;
    }
    .sesion-composer .nota-upload-progress.active { display:inline-flex; }

    @media (max-width: 768px) {
        .sesion-composer .ql-toolbar.ql-snow button { min-width:44px; min-height:44px; }
        .sesion-composer .ql-editor { min-height:140px; max-height:40vh; }
        .sesion-composer .modal-lg { max-width:96%; margin:.5rem auto; }
    }

    @media (max-width: 768px) {
        .patient-header { padding:1rem; }
        .patient-avatar { width:52px; height:52px; font-size:1.25rem; }
        .stat-card .stat-value { font-size:1.4rem; }
        .expediente-tabs .nav-link { padding:.65rem .75rem; font-size:.85rem; }
    }
</style>
@endpush

<div class="page-inner">

    <a href="{{ route('patient') }}" class="expediente-back">
        <i class="fas fa-arrow-left"></i> {{ translate('Volver a pacientes') }}
    </a>

    {{-- Header del paciente --}}
    <div class="patient-header mb-3">
        <div class="d-flex align-items-center flex-wrap">
            <div class="patient-avatar mr-3">
                @php
                    $initials = collect(preg_split('/\s+/', trim($patient->full_name)))
                        ->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))
                        ->implode('');
                    $initials = $initials !== '' ? mb_strtoupper($initials) : '?';
                @endphp
                {{ $initials }}
            </div>
            <div class="flex-grow-1">
                <h4 class="mb-1">
                    {{ $patient->full_name }}
                    <small class="text-muted" style="font-size:.7rem;font-weight:400;">#{{ $patient->id }}</small>
                </h4>
                <div class="patient-meta">
                    @if($age !== null)
                        <span class="mr-3"><i class="fas fa-birthday-cake"></i> {{ $age }} {{ translate('años') }}</span>
                    @endif
                    @if($patient->tax_number)
                        <span class="mr-3"><i class="fas fa-id-card"></i> {{ $patient->tax_number }}</span>
                    @endif
                    @if($patient->phone_no)
                        <span class="mr-3"><i class="fas fa-phone"></i> {{ $patient->phone_no }}</span>
                    @endif
                    @if($patient->email)
                        <span class="mr-3"><i class="fas fa-envelope"></i> {{ $patient->email }}</span>
                    @endif
                </div>
                <div class="mt-2">
                    @if($patient->treated)
                        <span class="patient-tag">{{ translate('Tratamiento previo') }}: {{ $patient->treated }}</span>
                    @endif
                    @if($patient->has_study)
                        <span class="patient-tag">{{ translate('Estudios') }}: {{ $patient->has_study }}</span>
                    @endif
                    @if($patient->archivo)
                        <a href="{{ url('/' . $patient->archivo) }}" target="_blank" class="patient-tag" style="text-decoration:none;">
                            <i class="fas fa-paperclip"></i> {{ translate('Ver documento') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs del expediente --}}
    <div class="expediente-tabs">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tab-resumen" role="tab">
                    <i class="fas fa-chart-pie mr-1"></i> {{ translate('Resumen') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-sesiones" role="tab" id="tab-sesiones-trigger">
                    <i class="fas fa-stethoscope mr-1"></i> {{ translate('Sesiones') }}
                </a>
            </li>
        </ul>
    </div>

    <div class="expediente-tab-content tab-content">

    {{-- =========================== TAB RESUMEN =========================== --}}
    <div class="tab-pane fade show active" id="tab-resumen" role="tabpanel">

    {{-- Stats cards --}}
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="stat-card">
                <div class="stat-label">{{ translate('Eventos clínicos') }}</div>
                <div class="stat-value">{{ $totalEvents }}</div>
                <div class="stat-sub">{{ translate('en la bitácora') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="stat-card">
                <div class="stat-label">{{ translate('Última atención') }}</div>
                <div class="stat-value">
                    @if($lastEvent)
                        {{ \Carbon\Carbon::parse($lastEvent->fecha)->diffForHumans(null, true) }}
                    @else
                        —
                    @endif
                </div>
                <div class="stat-sub">
                    @if($lastEvent)
                        {{ \Carbon\Carbon::parse($lastEvent->fecha)->format('d/m/Y') }}
                    @else
                        {{ translate('sin registros') }}
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="stat-card">
                <div class="stat-label">{{ translate('Tipos de evaluación') }}</div>
                <div class="stat-value">{{ $counts->count() }}</div>
                <div class="stat-sub">{{ translate('formularios usados') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="stat-card">
                <div class="stat-label">{{ translate('Paciente desde') }}</div>
                <div class="stat-value">
                    {{ $patient->created_at ? \Carbon\Carbon::parse($patient->created_at)->format('M Y') : '—' }}
                </div>
                <div class="stat-sub">
                    @if($patient->created_at)
                        {{ \Carbon\Carbon::parse($patient->created_at)->diffForHumans() }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Conteo por tipo de formulario --}}
        <div class="col-lg-5 mb-3">
            <div class="section-card">
                <div class="section-header">
                    {{ translate('Resumen por tipo de evaluación') }}
                </div>
                @if($counts->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-clipboard"></i>
                        {{ translate('Aún no hay evaluaciones registradas para este paciente.') }}
                    </div>
                @else
                    @foreach($formMeta as $key => $meta)
                        @if(isset($counts[$key]))
                            <div class="form-count-row">
                                <div class="icon-wrap bg-c-{{ $meta['color'] }}">
                                    <i class="fas {{ $meta['icon'] }}"></i>
                                </div>
                                <div class="label">{{ translate($meta['label']) }}</div>
                                <div class="count">{{ $counts[$key] }}</div>
                                @if(\Illuminate\Support\Facades\Route::has($meta['route']))
                                    <a href="{{ route($meta['route']) }}">
                                        {{ translate('Ir') }} <i class="fas fa-arrow-right"></i>
                                    </a>
                                @endif
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <div class="col-lg-7 mb-3">
            <div class="section-card">
                <div class="section-header">
                    {{ translate('Timeline clínico') }}
                    <small class="text-muted" style="font-weight:400;">{{ $totalEvents }} {{ translate('eventos') }}</small>
                </div>
                @if($timeline->isEmpty())
                    <div class="empty-state">
                        <i class="far fa-clock"></i>
                        {{ translate('No hay actividad clínica registrada todavía.') }}
                        <div style="font-size:.7rem;color:#ced4da;margin-top:.5rem;">
                            (consultado: fis_historys.patient_id = {{ $patient->id }})
                        </div>
                    </div>
                @else
                    <div class="timeline-list">
                        @foreach($timeline as $event)
                            @php
                                $meta = $formMeta[$event->tabla_form] ?? ['label' => $event->tabla_form, 'icon' => 'fa-file', 'color' => 'secondary', 'route' => null];
                            @endphp
                            <div class="timeline-item">
                                <div class="ti-icon bg-c-{{ $meta['color'] }}">
                                    <i class="fas {{ $meta['icon'] }}"></i>
                                </div>
                                <div class="ti-body">
                                    <div class="ti-title">{{ translate($meta['label']) }}</div>
                                    <div class="ti-meta">
                                        @if($event->user_name)
                                            {{ translate('por') }} {{ $event->user_name }}
                                        @endif
                                        @if($meta['route'] && \Illuminate\Support\Facades\Route::has($meta['route']))
                                            · <a href="{{ route($meta['route']) }}" style="color:#1572e8;">{{ translate('Abrir formulario') }} <i class="fas fa-external-link-alt" style="font-size:.7rem;"></i></a>
                                        @endif
                                    </div>
                                </div>
                                <div class="ti-date" title="{{ $event->created_at }}">
                                    {{ \Carbon\Carbon::parse($event->fecha)->format('d/m/Y') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    </div> {{-- /tab-resumen --}}

    {{-- =========================== TAB SESIONES =========================== --}}
    <div class="tab-pane fade" id="tab-sesiones" role="tabpanel">

        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
            <div>
                <h5 class="mb-0">{{ translate('Sesiones del paciente') }}</h5>
                <small class="text-muted" id="sesiones-summary">{{ translate('Cargando...') }}</small>
            </div>
            <div class="d-flex" style="gap:.4rem;">
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnDuplicarUltima" disabled>
                    <i class="fas fa-copy mr-1"></i> {{ translate('Duplicar última') }}
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btnNuevaSesion">
                    <i class="fas fa-plus mr-1"></i> {{ translate('Nueva sesión') }}
                </button>
            </div>
        </div>

        <div id="sesiones-list">
            <div class="empty-state">
                <i class="far fa-clock"></i>
                {{ translate('Cargando sesiones...') }}
            </div>
        </div>

    </div> {{-- /tab-sesiones --}}

    </div> {{-- /tab-content --}}

</div>

{{-- ===================== MODAL COMPOSER DE SESIÓN ===================== --}}
<div class="modal fade sesion-composer" id="modalSesion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formSesion" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSesionTitle">{{ translate('Nueva sesión') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sesion_id" id="sesion_id" value="">
                    <input type="hidden" name="patient_id" id="sesion_patient_id" value="{{ $patient->id }}">

                    <div class="row">
                        <div class="col-md-7">
                            <label for="sesion_ficha_id">{{ translate('Ficha clínica') }} <b style="color:#dc3545;">*</b></label>
                            <select id="sesion_ficha_id" name="ficha_id" class="form-control" required>
                                <option value="">{{ translate('Selecciona una ficha...') }}</option>
                            </select>
                            <small id="sesion-ficha-help" class="text-muted" style="display:none;">
                                {{ translate('Este paciente aún no tiene una ficha clínica.') }}
                                <a href="{{ route('ficha.info') }}">{{ translate('Crear ficha') }}</a>
                            </small>
                        </div>
                        <div class="col-md-5">
                            <label for="sesion_fecha">{{ translate('Fecha') }} <b style="color:#dc3545;">*</b></label>
                            <input type="date" id="sesion_fecha" name="fecha" class="form-control"
                                   max="{{ date('Y-m-d') }}" required style="min-height:44px;">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="sesion_tratamiento">{{ translate('Tratamiento realizado') }}</label>
                        <textarea id="sesion_tratamiento" name="tratamiento_realizado" class="form-control" rows="3"
                                  maxlength="1000" placeholder="{{ translate('Ej. TENS 80Hz 20min lumbar, estiramientos isquiotibiales 3×30s...') }}"></textarea>
                    </div>

                    <div class="mt-3">
                        <label for="sesion_observaciones">{{ translate('Observaciones') }}</label>
                        <textarea id="sesion_observaciones" name="observaciones" class="form-control" rows="2"
                                  maxlength="1000" placeholder="{{ translate('Notas clínicas, respuesta del paciente...') }}"></textarea>
                    </div>

                    <div class="mt-3">
                        <label for="sesion_evolucion">{{ translate('Evolución') }}</label>
                        <select id="sesion_evolucion" name="evolucion" class="form-control">
                            <option value="">—</option>
                            <option value="favorable">{{ translate('Favorable') }}</option>
                            <option value="estable">{{ translate('Estable') }}</option>
                            <option value="desfavorable">{{ translate('Desfavorable') }}</option>
                        </select>
                    </div>

                    {{-- Nota detallada con Quill: texto rico + imágenes desde cámara o galería --}}
                    <div class="mt-3">
                        <label>{{ translate('Nota detallada') }} <small class="text-muted">({{ translate('opcional') }})</small></label>

                        <div class="nota-actions">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnSesionCamera">
                                <i class="fas fa-camera mr-1"></i> {{ translate('Tomar foto') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSesionGallery">
                                <i class="fas fa-image mr-1"></i> {{ translate('Galería') }}
                            </button>
                            <span class="nota-save-status" id="notaSaveStatus"></span>
                        </div>

                        <div id="sesionQuillEditor" style="background:#fff;"></div>

                        <div class="nota-upload-progress" id="notaUploadProgress">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>{{ translate('Procesando y subiendo imagen...') }}</span>
                        </div>

                        {{-- Inputs ocultos: el de cámara fuerza la cámara trasera en Android --}}
                        <input type="file" id="sesionCameraInput" accept="image/*" capture="environment" style="display:none;">
                        <input type="file" id="sesionGalleryInput" accept="image/*" style="display:none;">
                        <input type="hidden" name="nota_detallada" id="sesion_nota_detallada" value="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{ translate('Cerrar') }}</button>
                    <button type="submit" class="btn btn-success btn-sm" id="btnGuardarSesion">
                        <i class="fas fa-save mr-1"></i> {{ translate('Guardar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal para mostrar nota detallada (read-only) --}}
<div class="modal fade" id="modalVerNota" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Nota detallada') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="modalVerNotaBody" style="max-height:60vh; overflow-y:auto;"></div>
        </div>
    </div>
</div>

@endsection
