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

    .patient-header {
        background: linear-gradient(135deg, rgba(159, 147, 231, .12) 0%, rgba(199, 217, 229, .25) 100%);
        border-radius:.5rem; padding:1.25rem 1.5rem;
        box-shadow: var(--brand-shadow-sm);
        border:1px solid rgba(159, 147, 231, .25);
    }
    .patient-avatar {
        width:64px; height:64px; border-radius:50%;
        background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-dark) 100%);
        color:#fff; display:flex; align-items:center; justify-content:center;
        font-size:1.5rem; font-weight:700; flex-shrink:0;
        font-family: var(--brand-font-display);
        box-shadow: 0 4px 12px rgba(159, 147, 231, .35);
    }
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
    .form-count-row a { font-size:.8rem; color:var(--brand-primary-darker); text-decoration:none; }

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

    .bg-c-primary   { background:var(--brand-primary-darker); }
    .bg-c-success   { background:#31ce36; }
    .bg-c-danger    { background:#f25961; }
    .bg-c-warning   { background:#ffad46; }
    .bg-c-info      { background:#48abf7; }
    .bg-c-secondary { background:#6861ce; }

    /* Fase 2 - Tabs y sesiones */
    .expediente-tabs { background:#fff; border-radius:.5rem 0 0 0; border:1px solid #e9ecef; border-bottom:none; padding:0; }
    .expediente-tabs .nav-tabs { border-bottom:1px solid #e9ecef; padding:0 1rem; margin:0; }
    .expediente-tabs .nav-link { color:#6c757d; border:none; border-bottom:3px solid transparent; padding:.85rem 1.1rem; font-weight:500; }
    .expediente-tabs .nav-link.active { color:var(--brand-primary-darker); background:transparent; border-bottom-color:var(--brand-primary-darker); }
    .expediente-tabs .nav-link:hover:not(.active) { color:#495057; border-bottom-color:#e9ecef; }
    .expediente-tab-content { background:#fff; border:1px solid #e9ecef; border-top:none; border-radius:0 0 .5rem .5rem; padding:1.25rem; }

    .sesion-card { background:#fff; border:1px solid #e9ecef; border-radius:.5rem; padding:.9rem 1.05rem; margin-bottom:.75rem; transition:box-shadow .15s; }
    .sesion-card:hover { box-shadow:0 2px 8px rgba(0,0,0,.05); }
    .sesion-card .sesion-top { display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem; }
    .sesion-card .sesion-top-main { flex:1; min-width:0; }
    .sesion-card .sesion-date { font-weight:600; color:var(--brand-primary-darker); font-size:.85rem; margin-bottom:.15rem; }
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
    .sesion-card .meta-diag i { color:var(--brand-primary-darker); margin-right:.25rem; }
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
        background:none; border:none; padding:.1rem 0; color:var(--brand-primary-darker);
        font-size:.78rem; cursor:pointer;
    }
    .sesion-card .motivo-toggle:hover { text-decoration:underline; }
    .sesion-card .motivo-content {
        display:none; width:100%;
        padding:.55rem .8rem; margin-top:.4rem;
        background:rgba(159, 147, 231, .10); border-left:3px solid var(--brand-primary-darker); border-radius:.25rem;
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
        font-size:.78rem; color:var(--brand-primary-darker); margin-top:.3rem;
    }
    .sesion-composer .nota-upload-progress.active { display:inline-flex; }

    @media (max-width: 768px) {
        .sesion-composer .ql-toolbar.ql-snow button { min-width:44px; min-height:44px; }
        .sesion-composer .ql-editor { min-height:140px; max-height:40vh; }
        .sesion-composer .modal-lg { max-width:96%; margin:.5rem auto; }
    }

    /* Fase 3 - Tab Evaluación */
    .eval-header { display:flex; flex-wrap:wrap; align-items:center; gap:.6rem 1rem; margin-bottom:1rem; }
    .eval-header h5 { margin:0; }
    .eval-filter { display:flex; align-items:center; gap:.5rem; margin-left:auto; flex-wrap:wrap; }
    .eval-filter label { margin:0; font-size:.78rem; color:#6c757d; text-transform:uppercase; letter-spacing:.04em; }
    .eval-filter select { font-size:.85rem; max-width:340px; }

    .eval-summary-pill { background:rgba(159, 147, 231, .10); color:var(--brand-primary-darker); border:1px solid rgba(159, 147, 231, .35); padding:.25rem .7rem; border-radius:1rem; font-size:.78rem; font-weight:500; }

    .eval-section { background:#fff; border:1px solid #e9ecef; border-radius:.5rem; margin-bottom:.6rem; overflow:hidden; }
    .eval-section-header {
        display:flex; align-items:center; padding:.7rem 1rem;
        cursor:pointer; user-select:none;
        background:#fafbfc; transition:background .15s;
        border:none; width:100%; text-align:left;
    }
    .eval-section-header:hover { background:#f1f3f5; }
    .eval-section-header .ev-icon {
        width:32px; height:32px; border-radius:.4rem;
        display:flex; align-items:center; justify-content:center;
        color:#fff; margin-right:.7rem; flex-shrink:0; font-size:.85rem;
    }
    .eval-section-header .ev-title { flex:1; font-weight:500; color:#212529; }
    .eval-section-header .ev-count {
        background:#e9ecef; color:#495057;
        padding:.1rem .55rem; border-radius:1rem;
        font-size:.75rem; font-weight:600; margin-right:.6rem;
    }
    .eval-section-header .ev-count.zero { background:transparent; color:#adb5bd; }
    .eval-section-header .ev-toggle { color:#adb5bd; font-size:.85rem; transition:transform .15s; }
    .eval-section-header.collapsed .ev-toggle { transform:rotate(-90deg); }

    .eval-section-body { padding:.4rem 1rem 1rem 4rem; border-top:1px solid #f1f3f5; }
    .eval-section.collapsed .eval-section-body { display:none; }
    .eval-section-body .eval-row {
        display:flex; align-items:center; padding:.45rem 0;
        border-bottom:1px solid #f8f9fa; font-size:.85rem;
    }
    .eval-section-body .eval-row:last-child { border-bottom:none; }
    .eval-section-body .eval-row-date { color:#212529; font-weight:500; width:110px; flex-shrink:0; }
    .eval-section-body .eval-row-user { color:#6c757d; flex:1; }
    .eval-section-body .eval-row-ficha {
        font-size:.72rem; padding:.1rem .55rem; border-radius:1rem;
        background:#e3f6e6; color:#1d7d2c; margin-right:.5rem;
    }
    .eval-section-body .eval-row-ficha.unassigned { background:#fff4d6; color:#996800; }
    .eval-section-body .eval-row a { color:var(--brand-primary-darker); font-size:.8rem; text-decoration:none; white-space:nowrap; }
    .eval-section-body .eval-row a:hover { text-decoration:underline; }

    /* Fase 4a — botón Editar inline */
    .eval-section-body .eval-row-edit {
        background:transparent; border:1px solid var(--brand-primary-darker);
        color:var(--brand-primary-darker); cursor:pointer;
        border-radius:.3rem; padding:.2rem .5rem;
        font-size:.75rem; line-height:1;
        margin-right:.35rem;
        min-width:36px; min-height:32px;
        display:inline-flex; align-items:center; justify-content:center;
        transition:background .15s ease, color .15s ease;
    }
    .eval-section-body .eval-row-edit:hover {
        background:var(--brand-primary-darker); color:#fff;
    }
    .eval-section-body .eval-row-edit i { font-size:.85rem; }

    /* Fase 4b — botón Eliminar */
    .eval-section-body .eval-row-delete {
        background:transparent; border:1px solid #dc3545;
        color:#dc3545; cursor:pointer;
        border-radius:.3rem; padding:.2rem .5rem;
        font-size:.75rem; line-height:1;
        margin-right:.5rem;
        min-width:36px; min-height:32px;
        display:inline-flex; align-items:center; justify-content:center;
        transition:background .15s ease, color .15s ease;
    }
    .eval-section-body .eval-row-delete:hover {
        background:#dc3545; color:#fff;
    }
    .eval-section-body .eval-row-delete i { font-size:.85rem; }

    .eval-add-row {
        margin-top:.4rem; padding-top:.5rem;
        border-top:1px dashed #e9ecef;
    }
    .eval-add-row a {
        display:inline-flex; align-items:center; gap:.35rem;
        color:var(--brand-primary-darker); font-size:.82rem; text-decoration:none;
        padding:.25rem 0;
    }
    .eval-add-row a:hover { text-decoration:underline; }

    .eval-empty-section { color:#adb5bd; font-size:.85rem; font-style:italic; padding:.5rem 0; }

    @media (max-width: 768px) {
        .eval-section-body { padding:.4rem .8rem .8rem 1rem; }
        .eval-section-body .eval-row { flex-wrap:wrap; }
        .eval-section-body .eval-row-date { width:auto; margin-right:.5rem; }
        .eval-filter { width:100%; margin-left:0; }
        .eval-filter select { flex:1; }
    }

    /* Fase 3b - Modal inline genérico para crear evaluaciones */
    .inline-eval-modal .modal-body label { font-size:.78rem; color:#495057; font-weight:600; margin-bottom:.2rem; text-transform:uppercase; letter-spacing:.03em; }
    .inline-eval-modal .modal-body label .req { color:#dc3545; margin-left:.15rem; }
    .inline-eval-modal .modal-body .field-help { font-size:.72rem; color:#adb5bd; margin-top:.15rem; }
    .inline-eval-modal .modal-body .form-control { font-size:.92rem; }
    .inline-eval-modal .modal-body textarea.form-control { min-height:56px; }
    .inline-eval-modal .modal-body input[type=date].form-control,
    .inline-eval-modal .modal-body input[type=time].form-control { min-height:42px; }
    .inline-eval-modal .form-row-grid { display:grid; grid-template-columns:repeat(12, 1fr); gap:.65rem .85rem; }
    .inline-eval-modal .col-12 { grid-column: span 12; }
    .inline-eval-modal .col-6  { grid-column: span 6; }
    .inline-eval-modal .col-4  { grid-column: span 4; }
    .inline-eval-modal .col-8  { grid-column: span 8; }
    .inline-eval-modal .field-group { display:flex; flex-direction:column; min-width:0; width:100%; }
    /* Asegurar que cualquier input/select dentro del field-group ocupe todo el ancho
       de su columna. Sin esto, algunos navegadores (especialmente con type=date)
       respetan el ancho intrínseco del control y se ven colapsados.
       Usamos !important porque Bootstrap aplica width:100% pero algunos browsers
       (Chrome con type=date/time/number) sobreescriben con su propio shrink-to-fit. */
    .inline-eval-modal .field-group > .form-control,
    .inline-eval-modal .field-group > input,
    .inline-eval-modal .field-group > select,
    .inline-eval-modal .field-group > textarea {
        width:100% !important;
        min-width:0 !important;
        max-width:100%;
        box-sizing:border-box !important;
        display:block;
    }
    /* Específicamente para date/time/number en Chrome donde el ancho intrínseco
       depende del valor mostrado */
    .inline-eval-modal .field-group > input[type=date],
    .inline-eval-modal .field-group > input[type=time],
    .inline-eval-modal .field-group > input[type=number] {
        min-width:0 !important;
        width:100% !important;
    }
    .inline-eval-modal .ficha-context {
        background:rgba(159, 147, 231, .10); border:1px solid rgba(159, 147, 231, .35);
        padding:.55rem .8rem; border-radius:.4rem;
        font-size:.85rem; color:var(--brand-primary-darker);
        margin-bottom:1rem;
    }
    .inline-eval-modal .ficha-context .ficha-warn { color:#996800; }
    .inline-eval-modal .ficha-context select { font-size:.85rem; padding:.2rem .4rem; }

    /* EVA slider táctil */
    .eva-slider-wrap { display:flex; align-items:center; gap:.8rem; padding:.2rem 0; }
    .eva-slider-wrap input[type=range] {
        flex:1; height:42px;
        appearance:none; -webkit-appearance:none;
        background:transparent;
    }
    .eva-slider-wrap input[type=range]::-webkit-slider-runnable-track {
        height:8px; border-radius:4px;
        background:linear-gradient(to right, #31ce36 0%, #ffad46 50%, #f25961 100%);
    }
    .eva-slider-wrap input[type=range]::-moz-range-track {
        height:8px; border-radius:4px;
        background:linear-gradient(to right, #31ce36 0%, #ffad46 50%, #f25961 100%);
    }
    .eva-slider-wrap input[type=range]::-webkit-slider-thumb {
        appearance:none; -webkit-appearance:none;
        width:28px; height:28px; border-radius:50%;
        background:#fff; border:3px solid var(--brand-primary-darker);
        margin-top:-10px;
        cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,.15);
    }
    .eva-slider-wrap input[type=range]::-moz-range-thumb {
        width:28px; height:28px; border-radius:50%;
        background:#fff; border:3px solid var(--brand-primary-darker);
        cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,.15);
    }
    .eva-value-bubble {
        min-width:48px; height:36px;
        border-radius:.4rem; padding:0 .65rem;
        display:flex; align-items:center; justify-content:center;
        font-weight:700; font-size:1.05rem;
        background:#e9ecef; color:#212529;
        transition:background .2s, color .2s;
    }
    .eva-value-bubble.low  { background:#e3f6e6; color:#1d7d2c; }
    .eva-value-bubble.mid  { background:#fff4d6; color:#996800; }
    .eva-value-bubble.high { background:#fde2e1; color:#a8201a; }

    @media (max-width: 768px) {
        .inline-eval-modal .col-6,
        .inline-eval-modal .col-4,
        .inline-eval-modal .col-8 { grid-column: span 12; }
        .inline-eval-modal .modal-lg { max-width:96%; margin:.5rem auto; }
    }

    /* === Tipos de campo compuestos (Fase 3b extendida) === */
    .inline-eval-modal .field-section-header {
        grid-column: span 12;
        margin: 1rem 0 .15rem;
        font-size: .82rem; font-weight: 700;
        color: var(--brand-primary-darker);
        padding-bottom: .35rem;
        border-bottom: 1px solid rgba(159, 147, 231, .35);
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .inline-eval-modal .field-section-header:first-child { margin-top:0; }
    .inline-eval-modal .field-section-header .fs-help {
        font-size:.7rem; font-weight:400; color:#6c757d;
        text-transform:none; letter-spacing:0;
        margin-left:.5rem;
    }

    .inline-eval-modal .field-note {
        grid-column: span 12;
        background:#fff8e1; border-left:3px solid #ffad46;
        padding:.55rem .8rem; border-radius:.25rem;
        font-size:.8rem; color:#6c4500;
    }

    /* Section headers de colores — Tinetti (danger) + dermatomas (cervical/torácico/lumbar/sacro) */
    .inline-eval-modal .field-section-header.field-section-danger,
    .inline-eval-modal .field-section-header.field-section-success,
    .inline-eval-modal .field-section-header.field-section-info,
    .inline-eval-modal .field-section-header.field-section-warning,
    .inline-eval-modal .field-section-header.field-section-pink {
        grid-column: span 12;
        border:none; border-radius:.25rem;
        padding:.55rem .8rem;
        font-size:.85rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.04em;
        margin:.85rem 0 .25rem;
        color:#fff;
    }
    .inline-eval-modal .field-section-header.field-section-danger  { background:#e23a3a; }
    .inline-eval-modal .field-section-header.field-section-success { background:#7cbf3a; } /* verde dermatoma cervical */
    .inline-eval-modal .field-section-header.field-section-pink    { background:#f29ab3; color:#5a1f2f; } /* rosa torácico */
    .inline-eval-modal .field-section-header.field-section-info    { background:#5bbfd6; } /* cian lumbar */
    .inline-eval-modal .field-section-header.field-section-warning { background:#f0c33c; color:#5c4400; } /* amarillo sacro */
    .inline-eval-modal .field-section-header.field-section-danger .fs-help { color:#ffe5e5; }
    .inline-eval-modal .field-section-header.field-section-pink .fs-help   { color:#5a1f2f; opacity:.7; }
    .inline-eval-modal .field-section-header.field-section-warning .fs-help { color:#5c4400; opacity:.7; }

    /* Note "Instrucciones" estilo alert info */
    .inline-eval-modal .field-note.field-note-instructions {
        background:#e9f3ff; border-left:4px solid var(--brand-primary-darker);
        color:#0b3d8c; font-weight:600; font-size:.9rem;
        text-align:center; padding:.75rem 1rem;
    }

    /* Imagen decorativa de referencia anatómica */
    .inline-eval-modal .field-image-wrap {
        grid-column: span 12;
        display:flex; justify-content:center;
        background:#f8f9fc; border:1px solid #e9ecef;
        border-radius:.4rem; padding:.5rem;
        margin-bottom:.25rem;
    }
    .inline-eval-modal .field-image-wrap img {
        display:block;
    }

    /* Silueta interactiva (body_map) — click en zonas para marcar alteraciones */
    .inline-eval-modal .field-body-map {
        grid-column: span 12;
        background:#f8f9fc; border:1px solid #e9ecef;
        border-radius:.4rem; padding:.5rem;
        margin-bottom:.25rem;
        display:flex; justify-content:center;
    }
    .inline-eval-modal .body-map-canvas {
        position:relative;
        display:inline-block;     /* el contenedor se ajusta al ancho de la imagen */
        max-width:100%;
    }
    .inline-eval-modal .body-map-canvas img {
        display:block; max-width:100%; height:auto;
        user-select:none; -webkit-user-drag:none;
        pointer-events:none;       /* deja pasar todos los clicks a los botones */
    }
    .inline-eval-modal .body-map-region {
        position:absolute;
        background:transparent;
        border:2px dashed rgba(108, 117, 125, .35);
        border-radius:.35rem;
        padding:0; margin:0;
        cursor:pointer;
        transition:background .15s ease, border-color .15s ease;
        display:flex; align-items:flex-end; justify-content:center;
        overflow:hidden;
    }
    .inline-eval-modal .body-map-region:hover {
        background:rgba(255, 89, 97, .12);
        border-color:rgba(255, 89, 97, .65);
    }
    .inline-eval-modal .body-map-region:focus {
        outline:none;
        box-shadow:0 0 0 3px rgba(21, 114, 232, .25);
    }
    .inline-eval-modal .body-map-region.selected {
        background:rgba(242, 89, 97, .35);
        border-color:rgba(220, 53, 69, .85);
        border-style:solid;
    }
    .inline-eval-modal .body-map-region-label {
        font-size:.65rem; font-weight:700;
        color:#dc3545;
        background:rgba(255, 255, 255, .85);
        padding:.1rem .35rem; border-radius:.2rem;
        margin-bottom:.25rem;
        opacity:0;            /* visible solo al hover/seleccionado */
        transition:opacity .15s ease;
        pointer-events:none;
        text-align:center;
        white-space:nowrap;
    }
    .inline-eval-modal .body-map-region:hover .body-map-region-label,
    .inline-eval-modal .body-map-region.selected .body-map-region-label {
        opacity:1;
    }

    @media (max-width: 768px) {
        .inline-eval-modal .body-map-region-label { font-size:.6rem; padding:.05rem .25rem; }
    }

    /* ========== Leyenda de escala con badges de colores (ej. Daniels 0-5) ========== */
    .inline-eval-modal .field-scale-legend {
        grid-column: span 12;
        background:#fff; border:1px solid rgba(159, 147, 231, .35);
        border-radius:.4rem; padding:.6rem .75rem;
        margin-bottom:.25rem;
    }
    .inline-eval-modal .field-scale-legend .scale-title {
        font-size:.78rem; font-weight:700; color:var(--brand-primary-darker);
        text-align:center; text-transform:uppercase;
        letter-spacing:.04em; margin-bottom:.5rem;
        padding-bottom:.35rem; border-bottom:1px dashed rgba(159, 147, 231, .35);
    }
    .inline-eval-modal .field-scale-legend .scale-items {
        display:flex; flex-wrap:wrap; gap:.4rem;
        justify-content:center;
    }
    .inline-eval-modal .scale-badge {
        display:inline-flex; align-items:center;
        gap:.4rem; padding:.25rem .5rem .25rem .65rem;
        border-radius:1rem;
        font-size:.75rem; font-weight:600;
        line-height:1;
    }
    .inline-eval-modal .scale-badge-label { white-space:nowrap; }
    .inline-eval-modal .scale-badge-value {
        background:rgba(0,0,0,.18);
        padding:.15rem .4rem; border-radius:.8rem;
        font-weight:700; font-size:.75rem;
    }
    /* Variantes de color */
    .inline-eval-modal .scale-danger  { background:#f25961; color:#fff; }
    .inline-eval-modal .scale-warning { background:#ffad46; color:#000; }
    .inline-eval-modal .scale-dark    { background:#212529; color:#fff; }
    .inline-eval-modal .scale-info    { background:#48abf7; color:#fff; }
    .inline-eval-modal .scale-light   { background:#f3f5f8; color:#212529; border:1px solid #dee2e6; }
    .inline-eval-modal .scale-light .scale-badge-value { background:rgba(0,0,0,.08); }
    .inline-eval-modal .scale-success { background:#31ce36; color:#fff; }
    .inline-eval-modal .scale-primary { background:var(--brand-primary-darker); color:#fff; }
    .inline-eval-modal .scale-pink    { background:#f29ab3; color:#5a1f2f; }
    .inline-eval-modal .scale-pink .scale-badge-value { background:rgba(0,0,0,.08); }

    /* ========== Reforzar visibilidad del valor en selects bilaterales ==========
       Sin estas reglas, los selects nativos de Chrome colapsan verticalmente
       cuando viven en un contenedor flex muy estrecho y el dígito seleccionado
       queda cortado por arriba/abajo. Forzamos altura, line-height y font-size. */
    .inline-eval-modal .bilateral-pair select.form-control {
        height:38px !important;
        min-height:38px !important;
        min-width:62px !important;
        padding:0 1.4rem 0 .55rem !important;
        font-size:1rem !important;
        font-weight:700 !important;
        line-height:1.2 !important;
        text-align:center;
        text-align-last:center;
        -webkit-appearance:menulist;
        appearance:menulist;
        background-color:#fff;
        color:#212529;
    }
    .inline-eval-modal .bilateral-pair input.form-control {
        height:38px !important;
        min-height:38px !important;
        min-width:62px !important;
        font-size:.95rem;
        text-align:center;
    }
    .inline-eval-modal .bilateral-pair .side-block {
        gap:.3rem;
    }
    .inline-eval-modal .bilateral-pair .side-label {
        font-size:.7rem;
        width:28px;
    }

    @media (max-width:768px) {
        .inline-eval-modal .bilateral-pair select.form-control {
            min-width:54px !important; font-size:.95rem !important;
        }
        .inline-eval-modal .bilateral-pair .side-label { width:24px; font-size:.65rem; }
    }

    /* ===== bilateral-pair-compact (para grades en col-6 donde no caben labels al lado) =====
       Layout en grid 2-columnas, con la etiqueta IZQ/DER arriba del select centrada. */
    .inline-eval-modal .bilateral-pair.bilateral-pair-compact {
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:.5rem;
        align-items:start;
    }
    .inline-eval-modal .bilateral-pair-compact .side-block {
        flex-direction:column;
        align-items:center;
        gap:.15rem;
        min-width:0;
    }
    .inline-eval-modal .bilateral-pair-compact .side-label {
        width:auto;
        text-align:center;
        font-size:.65rem;
        color:#6c757d;
        letter-spacing:.04em;
    }
    .inline-eval-modal .bilateral-pair-compact select.form-control {
        width:100% !important;
        min-width:0 !important;
    }

    /* ========== Postural grid — tabla parte × vista (Alineación postural) ========== */
    .inline-eval-modal .field-postural-grid {
        grid-column: span 12;
        margin-bottom:.4rem;
    }
    .inline-eval-modal .field-postural-grid .pg-scroll {
        overflow-x:auto; -webkit-overflow-scrolling:touch;
        border:1px solid #e9ecef; border-radius:.4rem;
    }
    .inline-eval-modal .field-postural-grid .pg-table {
        width:100%; border-collapse:separate; border-spacing:0;
        font-size:.78rem;
    }
    .inline-eval-modal .field-postural-grid .pg-table thead th {
        background:var(--brand-primary-darker); color:#fff;
        padding:.5rem .5rem;
        font-weight:700; font-size:.75rem;
        text-align:center;
        position:sticky; top:0;
        letter-spacing:.04em;
    }
    .inline-eval-modal .field-postural-grid .pg-table th.pg-part {
        background:var(--brand-primary-darker);
        text-align:left;
        min-width:90px;
    }
    .inline-eval-modal .field-postural-grid .pg-table tbody tr:nth-child(even) td { background:#f8f9fc; }
    .inline-eval-modal .field-postural-grid .pg-table tbody td {
        padding:.25rem .35rem;
        border-bottom:1px solid #f1f3f5;
        vertical-align:middle;
    }
    .inline-eval-modal .field-postural-grid .pg-part-label {
        font-weight:600; color:#212529;
        white-space:nowrap;
        padding-right:.5rem !important;
        background:#fff !important;
        position:sticky; left:0;
        border-right:1px solid #e9ecef;
    }
    .inline-eval-modal .field-postural-grid .pg-cell input.form-control {
        height:32px; min-height:32px;
        padding:.15rem .4rem;
        font-size:.8rem;
        min-width:90px;
    }

    @media (max-width:768px) {
        .inline-eval-modal .field-postural-grid .pg-cell input.form-control { min-width:80px; font-size:.75rem; }
        .inline-eval-modal .field-postural-grid .pg-table { font-size:.7rem; }
    }

    /* ========== File uploads — 4 slots con preview (Alineación postural) ========== */
    .inline-eval-modal .field-file-uploads {
        grid-column: span 12;
        margin-bottom:.4rem;
    }
    .inline-eval-modal .field-file-uploads .fu-grid {
        display:grid;
        grid-template-columns:repeat(4, 1fr);
        gap:.5rem;
    }
    .inline-eval-modal .field-file-uploads .fu-slot {
        position:relative;
        background:#f8f9fc; border:1px solid #e9ecef;
        border-radius:.4rem; padding:.5rem;
        display:flex; flex-direction:column; align-items:center;
    }
    .inline-eval-modal .field-file-uploads .fu-label {
        font-size:.7rem; font-weight:700; color:#495057;
        text-transform:uppercase; letter-spacing:.03em;
        margin-bottom:.4rem; text-align:center;
    }
    .inline-eval-modal .field-file-uploads .fu-preview {
        width:100%; aspect-ratio:1/1; max-height:140px;
        background:#fff; border:2px dashed #cfd8e3;
        border-radius:.3rem;
        display:flex; align-items:center; justify-content:center;
        cursor:pointer;
        color:#adb5bd; font-size:1.4rem;
        overflow:hidden;
        transition:border-color .15s ease, background .15s ease;
    }
    .inline-eval-modal .field-file-uploads .fu-preview:hover {
        border-color:var(--brand-primary-darker);
        background:#f0f7ff;
        color:var(--brand-primary-darker);
    }
    .inline-eval-modal .field-file-uploads .fu-preview.has-image {
        border-style:solid; border-color:#31ce36;
        background:#fff;
    }
    .inline-eval-modal .field-file-uploads .fu-preview img {
        width:100%; height:100%; object-fit:cover;
    }
    .inline-eval-modal .field-file-uploads .fu-input {
        position:absolute; inset:0;
        opacity:0; cursor:pointer;
        width:100%; height:100%;
    }
    .inline-eval-modal .field-file-uploads .fu-clear {
        position:absolute; top:.35rem; right:.35rem;
        z-index:2;
        background:#dc3545; color:#fff;
        border:none; border-radius:50%;
        width:24px; height:24px;
        font-size:.65rem;
        display:flex; align-items:center; justify-content:center;
        cursor:pointer;
        box-shadow:0 1px 3px rgba(0,0,0,.2);
    }
    .inline-eval-modal .field-file-uploads .fu-clear:hover { background:#c82333; }

    @media (max-width:768px) {
        .inline-eval-modal .field-file-uploads .fu-grid {
            grid-template-columns:repeat(2, 1fr);
        }
    }

    /* ========== Goniometría — bloque de movimiento con imágenes ========== */
    .inline-eval-modal .gonio-movement {
        grid-column: span 12;
        border:1px solid #e9ecef;
        border-radius:.4rem;
        margin-bottom:.6rem;
        overflow:hidden;
        background:#fff;
    }
    .inline-eval-modal .gonio-movement .gm-header {
        padding:.4rem .75rem;
        font-weight:700; font-size:.85rem; color:#212529;
        text-align:center;
        text-transform:uppercase; letter-spacing:.03em;
    }
    .inline-eval-modal .gonio-movement .gm-range {
        background:#f8f9fc; color:#495057;
        padding:.3rem .75rem;
        font-size:.72rem;
        text-align:center;
        border-top:1px solid #e9ecef;
        border-bottom:1px solid #e9ecef;
    }
    .inline-eval-modal .gonio-movement .gm-body {
        display:flex; align-items:stretch;
        padding:.5rem;
        gap:.5rem;
    }
    .inline-eval-modal .gonio-movement .gm-img {
        flex:0 0 110px;
        background:#f8f9fc;
        border:1px solid #e9ecef;
        border-radius:.3rem;
        padding:.25rem;
        display:flex; align-items:center; justify-content:center;
    }
    .inline-eval-modal .gonio-movement .gm-img img {
        max-width:100%; max-height:130px; height:auto;
        object-fit:contain;
    }
    .inline-eval-modal .gonio-movement .gm-img-placeholder {
        background:transparent; border:1px dashed #dee2e6;
    }
    .inline-eval-modal .gonio-movement .gm-table-wrap {
        flex:1; min-width:0;
    }
    .inline-eval-modal .gonio-movement table.gm-table {
        width:100%; border-collapse:collapse; font-size:.8rem;
    }
    .inline-eval-modal .gonio-movement .gm-table thead th {
        background:#f1f3f5; color:#495057;
        padding:.25rem .35rem; font-weight:600;
        font-size:.7rem; text-align:center;
        border-bottom:1px solid #dee2e6;
    }
    .inline-eval-modal .gonio-movement .gm-table tbody td {
        padding:.25rem .35rem;
        border-bottom:1px solid #f1f3f5;
        vertical-align:middle;
    }
    .inline-eval-modal .gonio-movement .gm-table tbody tr:last-child td {
        border-bottom:none;
    }
    .inline-eval-modal .gonio-movement .gm-pair-label {
        font-weight:600; font-size:.72rem;
        color:#495057;
        text-transform:uppercase; letter-spacing:.02em;
        white-space:nowrap; width:1%;
    }
    .inline-eval-modal .gonio-movement .gm-input {
        position:relative;
    }
    .inline-eval-modal .gonio-movement .gm-input input.form-control {
        text-align:center;
        font-size:.85rem;
        padding-right:1.4rem;
        height:32px;
    }
    .inline-eval-modal .gonio-movement .gm-input .gm-unit {
        position:absolute; right:.5rem; top:50%;
        transform:translateY(-50%);
        font-size:.7rem; color:#adb5bd; pointer-events:none;
    }

    /* Variantes de color para el header */
    .inline-eval-modal .gonio-warning .gm-header { background:#fff3cd; color:#7a5a00; }
    .inline-eval-modal .gonio-primary .gm-header { background:#cfe2ff; color:#0a3d8c; }
    .inline-eval-modal .gonio-info    .gm-header { background:#cff4fc; color:#055160; }
    .inline-eval-modal .gonio-success .gm-header { background:#d1e7dd; color:#0a3622; }
    .inline-eval-modal .gonio-danger  .gm-header { background:#f8d7da; color:#842029; }

    @media (max-width: 768px) {
        .inline-eval-modal .gonio-movement .gm-body { flex-direction:column; }
        .inline-eval-modal .gonio-movement .gm-img { flex:0 0 auto; }
        .inline-eval-modal .gonio-movement .gm-img img { max-height:110px; }
        .inline-eval-modal .gonio-movement .gm-img-placeholder { display:none; } /* en mobile, sin imagen no ocupa espacio */
    }

    /* Total automático (Tinetti) */
    .inline-eval-modal .field-score-total {
        background:#930a8d; color:#fff;
        padding:.65rem 1rem; border-radius:.25rem;
        font-size:1.2rem; font-weight:700;
        display:flex; align-items:center; justify-content:flex-end;
        min-height:44px;
    }
    .inline-eval-modal .field-score-total .field-score-max {
        opacity:.85; font-weight:500; margin-left:.15rem;
    }

    .inline-eval-modal .bilateral-pair {
        display:flex; align-items:center; gap:.45rem; flex-wrap:wrap;
    }
    .inline-eval-modal .bilateral-pair .side-block {
        flex:1; min-width:0; display:flex; align-items:center; gap:.35rem;
    }
    .inline-eval-modal .bilateral-pair .side-label {
        font-size:.7rem; color:#6c757d; text-transform:uppercase;
        width:30px; text-align:right; font-weight:600; flex-shrink:0;
    }
    .inline-eval-modal .bilateral-pair input.form-control,
    .inline-eval-modal .bilateral-pair select.form-control { flex:1; min-width:0; }

    .inline-eval-modal .dermatome-row {
        grid-column: span 12;
        display:flex; align-items:center;
        gap:.85rem; padding:.35rem .6rem;
        border-bottom:1px solid #f1f3f5;
        border-left:4px solid transparent;
        border-radius:.2rem;
    }
    .inline-eval-modal .dermatome-row:last-child { border-bottom:none; }
    .inline-eval-modal .dermatome-row .dermatome-code {
        font-weight:700; width:46px; flex-shrink:0; color:#212529;
        cursor:pointer; user-select:none;
        text-align:center;
        padding:.15rem .25rem;
        border-radius:.25rem;
        transition:background .15s ease;
    }
    .inline-eval-modal .dermatome-row .dermatome-code:hover {
        background:rgba(0,0,0,.06);
    }
    .inline-eval-modal .dermatome-row .dermatome-code-right {
        text-align:center; margin-left:auto;
    }
    .inline-eval-modal .dermatome-row .dermatome-options {
        flex:1; justify-content:flex-start;
    }

    /* Fondo + acento lateral por grupo (matchea la imagen anatómica) */
    .inline-eval-modal .dermatome-row-cervical {
        background:#e8f5d5; border-left-color:#7cbf3a;
    }
    .inline-eval-modal .dermatome-row-cervical .dermatome-code { color:#3d5e1d; }
    .inline-eval-modal .dermatome-row-thoracic {
        background:#fde7ee; border-left-color:#f29ab3;
    }
    .inline-eval-modal .dermatome-row-thoracic .dermatome-code { color:#7a2540; }
    .inline-eval-modal .dermatome-row-lumbar {
        background:#dff3f8; border-left-color:#5bbfd6;
    }
    .inline-eval-modal .dermatome-row-lumbar .dermatome-code { color:#1d5e6b; }
    .inline-eval-modal .dermatome-row-sacrum {
        background:#fdf3d1; border-left-color:#f0c33c;
    }
    .inline-eval-modal .dermatome-row-sacrum .dermatome-code { color:#7a5a00; }
    .inline-eval-modal .dermatome-row .dermatome-options {
        display:flex; gap:1rem; flex-wrap:wrap; flex:1;
    }
    .inline-eval-modal .dermatome-row .dermatome-options label {
        display:inline-flex; align-items:center; gap:.3rem;
        margin:0; font-size:.8rem; cursor:pointer;
        text-transform:none; font-weight:500; color:#495057;
        letter-spacing:0;
    }
    .inline-eval-modal .dermatome-row .dermatome-options input[type=radio] {
        width:18px; height:18px; cursor:pointer;
    }

    @media (max-width: 768px) {
        .inline-eval-modal .bilateral-pair .side-label { width:24px; font-size:.65rem; }
        .inline-eval-modal .dermatome-row { flex-wrap:wrap; }
        .inline-eval-modal .dermatome-row .dermatome-options { gap:.65rem; }
    }

    @media (max-width: 768px) {
        .patient-header { padding:1rem; }
        .patient-avatar { width:52px; height:52px; font-size:1.25rem; }
        .stat-card .stat-value { font-size:1.4rem; }
        .expediente-tabs .nav-link { padding:.65rem .75rem; font-size:.85rem; }
    }

    /* ============== Fase 4c — Botón Comparar en cada sección ============== */
    .eval-compare-row {
        display:flex; justify-content:flex-end;
        padding:.25rem 0 .55rem 0;
        margin-bottom:.35rem;
        border-bottom:1px dashed #e9ecef;
    }
    .eval-compare-btn {
        background:transparent; border:1px solid #1abc9c;
        color:#0e7263; cursor:pointer;
        border-radius:.3rem; padding:.25rem .55rem;
        font-size:.72rem; font-weight:600; line-height:1;
        display:inline-flex; align-items:center; gap:.3rem;
        transition:background .15s ease, color .15s ease;
        text-transform:uppercase; letter-spacing:.04em;
    }
    .eval-compare-btn:hover { background:#1abc9c; color:#fff; }
    .eval-compare-btn i { font-size:.85rem; }

    /* ============== Fase 4c — Modal comparativo ============== */
    .comparison-modal .modal-xl { max-width:1100px; }
    .comparison-modal .cmp-legend {
        background:rgba(159, 147, 231, .10); border:1px solid rgba(159, 147, 231, .35);
        padding:.55rem .8rem; border-radius:.4rem;
        font-size:.82rem; color:var(--brand-primary-darker);
        display:flex; align-items:center; flex-wrap:wrap; gap:.5rem;
        margin-bottom:.85rem;
    }
    .comparison-modal .cmp-legend-badges {
        display:inline-flex; gap:.4rem; margin-left:auto;
    }
    .comparison-modal .cmp-section-title {
        font-weight:700; font-size:.85rem;
        text-transform:uppercase; letter-spacing:.04em;
        color:var(--brand-primary-darker);
        margin:1.1rem 0 .35rem;
        padding-bottom:.3rem; border-bottom:1px solid rgba(159, 147, 231, .35);
    }
    .comparison-modal .cmp-section-title:first-child { margin-top:0; }
    .comparison-modal .cmp-noncomp-note {
        background:#fff8e1; border-left:3px solid #ffad46;
        padding:.55rem .8rem; border-radius:.25rem;
        font-size:.85rem; color:#6c4500;
        margin-bottom:.6rem;
    }
    .comparison-modal .cmp-scroll {
        overflow-x:auto; -webkit-overflow-scrolling:touch;
        border:1px solid #e9ecef; border-radius:.4rem;
        margin-bottom:1rem;
    }
    .comparison-modal table.cmp-table {
        width:100%; border-collapse:separate; border-spacing:0;
        font-size:.8rem;
    }
    .comparison-modal table.cmp-table thead th {
        background:var(--brand-primary-darker); color:#fff;
        padding:.55rem .65rem;
        font-weight:700; font-size:.72rem;
        text-align:center; vertical-align:middle;
        position:sticky; top:0;
        white-space:nowrap;
        border-right:1px solid rgba(255,255,255,.15);
    }
    .comparison-modal table.cmp-table thead th.cmp-th-field {
        background:var(--brand-primary-darker);
        text-align:left;
        position:sticky; left:0; z-index:2;
        min-width:200px;
    }
    .comparison-modal table.cmp-table thead th .cmp-th-fecha {
        display:block; font-size:.78rem;
    }
    .comparison-modal table.cmp-table thead th .cmp-th-meta {
        display:block; font-size:.65rem; font-weight:500; opacity:.85;
        margin-top:.1rem;
    }
    .comparison-modal table.cmp-table tbody td {
        padding:.4rem .55rem;
        border-bottom:1px solid #f1f3f5;
        text-align:center;
        vertical-align:middle;
    }
    .comparison-modal table.cmp-table tbody tr:nth-child(even) td { background:#f8f9fc; }
    .comparison-modal table.cmp-table tbody td.cmp-td-field {
        font-weight:600; color:#212529;
        text-align:left;
        background:#fff !important;
        position:sticky; left:0;
        min-width:200px; max-width:260px;
        border-right:1px solid #e9ecef;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .comparison-modal table.cmp-table tbody tr:nth-child(even) td.cmp-td-field { background:#f8f9fc !important; }
    .comparison-modal .cmp-section-row td {
        background:var(--brand-primary-darker) !important;
        color:#fff !important;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.04em;
        font-size:.72rem;
        text-align:left !important;
        padding:.35rem .65rem !important;
        position:sticky; left:0;
    }
    .comparison-modal .cmp-cell-value {
        display:inline-block; font-weight:600; color:#212529;
    }
    .comparison-modal .cmp-cell-empty { color:#adb5bd; font-weight:400; }
    .comparison-modal .cmp-delta {
        display:inline-block;
        padding:.05rem .35rem; border-radius:.8rem;
        font-size:.65rem; font-weight:700;
        margin-left:.3rem;
    }
    .comparison-modal .cmp-delta-up   { background:#d1e7dd; color:#0a3622; }
    .comparison-modal .cmp-delta-down { background:#f8d7da; color:#842029; }
    .comparison-modal .cmp-delta-same { background:#e9ecef; color:#495057; }
    .comparison-modal .cmp-empty-state {
        text-align:center; padding:2rem 1rem;
        color:#adb5bd; font-size:.9rem;
    }
    .comparison-modal .cmp-empty-state i {
        font-size:2.5rem; color:#dee2e6; margin-bottom:.5rem; display:block;
    }

    @media (max-width: 768px) {
        .comparison-modal .modal-xl { max-width:96%; margin:.5rem auto; }
        .comparison-modal table.cmp-table { font-size:.72rem; }
        .comparison-modal table.cmp-table thead th.cmp-th-field,
        .comparison-modal table.cmp-table tbody td.cmp-td-field { min-width:140px; max-width:160px; }
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
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-evaluacion" role="tab" id="tab-evaluacion-trigger">
                    <i class="fas fa-clipboard-list mr-1"></i> {{ translate('Evaluación') }}
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
                                            · <a href="{{ route($meta['route']) }}" style="color:var(--brand-primary-darker);">{{ translate('Abrir formulario') }} <i class="fas fa-external-link-alt" style="font-size:.7rem;"></i></a>
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

    {{-- =========================== TAB EVALUACIÓN =========================== --}}
    <div class="tab-pane fade" id="tab-evaluacion" role="tabpanel">

        <div class="eval-header">
            <div>
                <h5>{{ translate('Evaluación unificada') }}</h5>
                <small class="text-muted" id="eval-summary">{{ translate('Cargando...') }}</small>
            </div>
            <div class="eval-filter">
                <label for="evalFichaFilter">{{ translate('Caso') }}:</label>
                <select id="evalFichaFilter" class="form-control form-control-sm">
                    <option value="all">{{ translate('Todas las evaluaciones') }}</option>
                </select>
            </div>
        </div>

        <div id="eval-sections">
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                {{ translate('Cargando evaluaciones...') }}
            </div>
        </div>

        <div class="text-muted mt-3" style="font-size:.78rem;">
            <i class="fas fa-info-circle"></i>
            {{ translate('Las evaluaciones se cargan desde la bitácora clínica. Al crear una nueva desde el botón "Agregar", quedará vinculada al caso seleccionado.') }}
        </div>

    </div> {{-- /tab-evaluacion --}}

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

{{-- ============== Fase 3b — Modal genérico de evaluación inline ============== --}}
<div class="modal fade inline-eval-modal" id="modalEvalInline" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formEvalInline" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEvalInlineTitle">{{ translate('Nueva evaluación') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" style="max-height:75vh; overflow-y:auto;">
                    <input type="hidden" name="patient_id" id="evalInline_patient_id" value="{{ $patient->id }}">

                    <div class="ficha-context" id="evalInlineFichaContext">
                        <i class="fas fa-folder-open mr-1"></i>
                        <strong>{{ translate('Caso clínico') }}:</strong>
                        <span id="evalInlineFichaLabel">—</span>
                        <select id="evalInline_ficha_id" name="ficha_id" class="form-control form-control-sm ml-2" style="display:inline-block; width:auto; vertical-align:middle;"></select>
                    </div>

                    <div class="form-row-grid" id="evalInlineFields">
                        {{-- Inyectado por JS --}}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{ translate('Cerrar') }}</button>
                    <button type="submit" class="btn btn-success btn-sm" id="btnEvalInlineSave">
                        <i class="fas fa-save mr-1"></i> {{ translate('Guardar evaluación') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============== Fase 4c — Modal comparativo de evaluaciones ============== --}}
<div class="modal fade comparison-modal" id="modalEvalCompare" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEvalCompareTitle">{{ translate('Comparación temporal') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" style="max-height:78vh; overflow-y:auto;">
                <div class="cmp-legend">
                    <i class="fas fa-info-circle mr-1"></i>
                    <span>Las flechas indican el cambio respecto a la evaluación anterior.</span>
                    <span class="cmp-legend-badges">
                        <span class="cmp-delta cmp-delta-up">↑ mejora</span>
                        <span class="cmp-delta cmp-delta-down">↓ retroceso</span>
                        <span class="cmp-delta cmp-delta-same">= sin cambio</span>
                    </span>
                </div>
                <div id="modalEvalCompareBody"><!-- inyectado por JS --></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{ translate('Cerrar') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection
