@extends('layouts.app')
@section('content')

@push("adminScripts")
@php
    // Decode helper local — entidades HTML del middleware xssProtection.
    // Replicado aquí porque @push("adminScripts") corre antes del cuerpo.
    $ctxDecoder = fn($v) => is_string($v) ? html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $v;
    // Normalizar fichas para el JS: campos de texto decodificados (ñ, á, ó, etc.).
    // $fichas puede venir como Collection de stdClass (query builder) o de modelos
    // Eloquent, así que normalizamos a array genéricamente.
    $ctxFichas = isset($fichas)
        ? collect($fichas)->map(function ($f) use ($ctxDecoder) {
            if (is_array($f)) {
                $a = $f;
            } elseif (is_object($f) && method_exists($f, 'toArray')) {
                $a = $f->toArray();
            } else {
                $a = (array) $f; // stdClass u otros
            }
            foreach (['diagnostico', 'motivo_consulta'] as $col) {
                if (isset($a[$col])) $a[$col] = $ctxDecoder($a[$col]);
            }
            return $a;
        })->all()
        : [];
@endphp
<script>
    window.PATIENT_CONTEXT = {
        id: {{ (int) $patient->id }},
        name: @json($ctxDecoder($patient->full_name)),
        uploadUrl: @json(url('seguimiento/upload-image')),
        // Fase Reorg-A — caso activo (vino por query param ?caso=X o default 'all')
        activeCase: @json($casoActivo ?? 'all'),
        fichas: @json($ctxFichas)
    };

    // Nivel 1.4 — Registrar este paciente en "Recientes" al abrir el expediente.
    (function () {
        try {
            var KEY = 'hh.patientList.recents';
            var MAX = 6;
            var p = window.PATIENT_CONTEXT;
            if (!p || !p.id) return;
            // Iniciales y color (mismo algoritmo que el backend)
            var name = String(p.name || '').trim();
            var parts = name.split(/\s+/);
            var ini = ((parts[0] || '')[0] || '') + ((parts[1] || '')[0] || '');
            ini = (ini || '?').toUpperCase();
            // hash determinístico simple para color 1..8
            var h = 0;
            for (var i = 0; i < name.length; i++) { h = ((h << 5) - h + name.charCodeAt(i)) | 0; }
            var color = (Math.abs(h) % 8) + 1;

            var raw = localStorage.getItem(KEY);
            var list = raw ? JSON.parse(raw) : [];
            list = list.filter(function (x) { return x.id !== p.id; });
            list.unshift({ id: p.id, full_name: p.name, initials: ini, avatar_color: color });
            list = list.slice(0, MAX);
            localStorage.setItem(KEY, JSON.stringify(list));
        } catch (e) { /* silencioso */ }
    })();
</script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
{{-- Fase 11 — Chart.js para los gráficos de evolución --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
    /* ====== Reorg-A.2 — Ficha clínica completa (inline en tab Resumen) ====== */
    .ficha-completa-card {
        background:#fff;
        border:1px solid rgba(159, 147, 231, .35);
        border-left:5px solid var(--brand-primary, #9F93E7);
        border-radius:.5rem;
        margin-bottom:1.2rem;
        box-shadow: var(--brand-shadow-sm);
        overflow:hidden;
    }
    .ficha-completa-header {
        display:flex; align-items:center; gap:.85rem;
        padding:.85rem 1.1rem;
        cursor:pointer;
        background: linear-gradient(135deg, rgba(159, 147, 231, .06), rgba(199, 217, 229, .12));
        transition: background .15s ease;
        user-select: none;
    }
    .ficha-completa-header:hover { background: linear-gradient(135deg, rgba(159, 147, 231, .12), rgba(199, 217, 229, .2)); }
    .ficha-completa-icon {
        width:42px; height:42px; border-radius:.4rem;
        background: var(--brand-primary, #9F93E7);
        color:#fff;
        display:flex; align-items:center; justify-content:center;
        font-size:1.1rem;
        flex-shrink:0;
    }
    .ficha-completa-title-block { flex:1; min-width:0; }
    .ficha-completa-title {
        font-weight:700; font-size:1rem;
        color: var(--brand-text, #2F4157);
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .ficha-completa-meta {
        font-size:.75rem; color:var(--brand-text-muted, #5a6c80);
        margin-top:.1rem;
    }
    .caso-estado-badge {
        display:inline-block; font-size:.68rem; font-weight:700;
        padding:.1rem .5rem; border-radius:1rem; margin-left:.5rem;
        vertical-align:middle; text-transform:uppercase; letter-spacing:.03em;
    }
    .caso-estado-badge.cerrado { background:#e3f6e6; color:#1d7d2c; }
    .ficha-cierre-block { background:#f4fbf5; border-radius:.4rem; padding:.5rem .7rem; }

    /* Banner de caso cerrado (solo lectura) */
    .caso-cerrado-banner {
        display:flex; align-items:flex-start; gap:.6rem;
        background:#fff4d6; border:1px solid #f0d68a; color:#7a5b00;
        border-radius:.5rem; padding:.7rem 1rem; margin-bottom:.8rem;
        font-size:.85rem;
    }
    .caso-cerrado-banner i { font-size:1rem; margin-top:.1rem; flex-shrink:0; }
    .caso-cerrado-banner .ccb-text strong { color:#6b4f00; }
    .ficha-completa-toggle-hint {
        font-size:.78rem; font-weight:600;
        color:var(--brand-primary-darker, #5e4fbf);
        background:rgba(159, 147, 231, .12);
        padding:.3rem .7rem;
        border-radius:1rem;
        white-space:nowrap;
        flex-shrink:0;
    }
    .ficha-completa-header[aria-expanded="true"] .ficha-completa-toggle-hint i,
    .ficha-completa-header:not(.collapsed) .ficha-completa-toggle-hint i {
        transform: rotate(180deg);
    }
    .ficha-completa-toggle-hint i { transition: transform .2s ease; }

    .ficha-completa-body {
        padding: 0 1.1rem 1rem 1.1rem;
        border-top:1px solid rgba(159, 147, 231, .15);
    }
    .ficha-block {
        padding:.85rem 0;
        border-bottom:1px dashed #f1f3f5;
    }
    .ficha-block:last-child { border-bottom:none; }
    .ficha-block-title {
        font-weight:700; font-size:.78rem;
        color:var(--brand-primary-darker, #5e4fbf);
        text-transform:uppercase; letter-spacing:.04em;
        margin-bottom:.5rem;
    }
    .ficha-block-title i { color:var(--brand-primary, #9F93E7); }
    .ficha-block-text {
        color:var(--brand-text, #2F4157);
        font-size:.92rem;
        line-height:1.45;
        white-space:pre-wrap;
    }
    .ficha-field {
        display:flex; flex-wrap:wrap; gap:.35rem .65rem;
        padding:.3rem 0;
        font-size:.88rem;
    }
    .ficha-field-label {
        font-weight:600;
        color:var(--brand-text-muted, #5a6c80);
        min-width:140px;
        flex-shrink:0;
    }
    .ficha-field-value {
        color:var(--brand-text, #2F4157);
        flex:1;
        white-space:pre-wrap;
    }
    .ficha-modalities-list { display:inline-flex; flex-wrap:wrap; gap:.3rem; }
    .ficha-modality-tag {
        background:rgba(159, 147, 231, .15);
        color:var(--brand-primary-darker, #5e4fbf);
        padding:.15rem .55rem;
        border-radius:1rem;
        font-size:.78rem;
        font-weight:600;
    }
    .ficha-completa-actions {
        margin-top:.85rem;
        padding-top:.65rem;
        border-top:1px solid #f1f3f5;
        display:flex; gap:.45rem;
        flex-wrap:wrap;
    }

    @media (max-width: 768px) {
        .ficha-completa-header { flex-wrap:wrap; }
        .ficha-completa-toggle-hint { font-size:.7rem; padding:.2rem .5rem; }
        .ficha-field { flex-direction:column; gap:.15rem; }
        .ficha-field-label { min-width:auto; }
    }

    /* ====== Fase Reorg-A — Case selector global ====== */
    .case-selector {
        background: linear-gradient(135deg, rgba(159, 147, 231, .06) 0%, rgba(199, 217, 229, .15) 100%);
        border: 1px solid rgba(159, 147, 231, .25);
        border-radius: .5rem;
        padding: .65rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .case-selector-left { display:flex; align-items:center; gap:.75rem; flex:1; min-width:240px; }
    .case-selector-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--brand-primary-darker, #5e4fbf);
        white-space: nowrap;
    }
    .case-selector-label i { color: var(--brand-primary, #9F93E7); }
    .case-selector-input {
        flex: 1; min-width: 200px;
        background: #fff;
        border: 1px solid rgba(159, 147, 231, .35);
        border-radius: .35rem;
        padding: .45rem .65rem;
        font-size: .9rem;
        color: var(--brand-text, #2F4157);
        font-family: var(--brand-font-body);
        font-weight: 500;
        cursor: pointer;
        appearance: menulist;
    }
    .case-selector-input:focus {
        outline: none;
        border-color: var(--brand-primary, #9F93E7);
        box-shadow: 0 0 0 3px rgba(159, 147, 231, .25);
    }
    .case-selector-stats {
        font-size: .78rem;
        color: var(--brand-text-muted, #5a6c80);
        background: #fff;
        padding: .4rem .7rem;
        border-radius: 1rem;
        border: 1px solid #e9ecef;
        white-space: nowrap;
    }
    .case-selector-empty {
        font-size: .85rem; color: var(--brand-text-muted, #5a6c80);
        font-style: italic;
    }

    /* Botón Nueva Ficha */
    .btn-new-case {
        background: var(--brand-primary, #9F93E7);
        color: #fff;
        border: none;
        border-radius: .4rem;
        padding: .55rem 1rem;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        white-space: nowrap;
        transition: background .15s ease, transform .12s ease, box-shadow .15s ease;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(159, 147, 231, .25);
    }
    .btn-new-case:hover {
        background: var(--brand-primary-dark, #7d6fd6);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(159, 147, 231, .4);
    }
    .btn-new-case i { font-size: .85rem; }

    @media (max-width: 768px) {
        .case-selector { flex-direction: column; align-items: stretch; }
        .case-selector-left { flex-direction: column; align-items: stretch; }
        .case-selector-label { width: 100%; }
        .case-selector-stats { text-align: center; }
        .btn-new-case { width: 100%; justify-content: center; }
    }

    /* ============= Modal Nueva Ficha Clínica ============= */
    .new-case-modal .modal-content { border-radius: .5rem; }
    .new-case-modal .new-case-header {
        background: linear-gradient(135deg, rgba(159, 147, 231, .12), rgba(199, 217, 229, .25));
        border-bottom: 1px solid rgba(159, 147, 231, .25);
    }
    .new-case-modal .modal-title {
        font-family: var(--brand-font-body);
        color: var(--brand-text);
        font-weight: 600;
    }
    .new-case-modal .new-case-body {
        max-height: 75vh;
        overflow-y: auto;
        padding: 1rem 1.25rem;
    }
    .new-case-modal .new-case-intro {
        background: rgba(255, 173, 70, .12);
        border-left: 3px solid #ffad46;
        padding: .55rem .8rem;
        border-radius: .3rem;
        font-size: .82rem;
        color: #6c4500;
        margin-bottom: 1rem;
    }

    /* Sección principal (recomendada) */
    .new-case-modal .nc-section-main {
        background: #faf8ff;
        border: 1px solid rgba(159, 147, 231, .3);
        border-left: 4px solid var(--brand-primary, #9F93E7);
        border-radius: .4rem;
        padding: .85rem 1rem;
        margin-bottom: 1rem;
    }
    .new-case-modal .nc-section-title {
        font-weight: 700;
        font-size: .85rem;
        color: var(--brand-primary-darker, #5e4fbf);
        margin-bottom: .65rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .new-case-modal .form-group label {
        font-size: .8rem;
        font-weight: 600;
        color: var(--brand-text, #2F4157);
        margin-bottom: .25rem;
    }
    .new-case-modal .form-group { margin-bottom: .75rem; }
    .new-case-modal textarea.form-control { font-family: var(--brand-font-body); font-size: .88rem; }

    /* Acordeón */
    .new-case-modal .nc-accordion { display: flex; flex-direction: column; gap: .4rem; }
    .new-case-modal .nc-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: .4rem;
        overflow: hidden;
    }
    .new-case-modal .nc-card-header {
        padding: .65rem .85rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        font-weight: 600;
        font-size: .88rem;
        color: var(--brand-text);
        background: #fafbfc;
        transition: background .12s ease;
        user-select: none;
    }
    .new-case-modal .nc-card-header:hover { background: rgba(159, 147, 231, .08); }
    .new-case-modal .nc-card-header i:first-child { color: var(--brand-primary-darker); }
    .new-case-modal .nc-card-header span { flex: 1; }
    .new-case-modal .nc-card-chevron {
        font-size: .7rem; color: #adb5bd;
        transition: transform .2s ease;
    }
    .new-case-modal .nc-card-header[aria-expanded="true"] .nc-card-chevron,
    .new-case-modal .nc-card-header:not(.collapsed) .nc-card-chevron {
        transform: rotate(180deg);
    }
    .new-case-modal .nc-card-body {
        padding: .85rem 1rem;
        border-top: 1px solid #f1f3f5;
    }

    /* Modalidades como chips */
    .new-case-modal .nc-modalities {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: .5rem;
        margin-bottom: .85rem;
    }
    .new-case-modal .nc-modality-chip {
        cursor: pointer; margin: 0;
    }
    .new-case-modal .nc-modality-chip input[type="checkbox"] { display: none; }
    .new-case-modal .nc-chip-content {
        display: flex; align-items: center; gap: .45rem;
        padding: .5rem .65rem;
        background: #fff;
        border: 1.5px solid #e9ecef;
        border-radius: .35rem;
        font-size: .82rem;
        color: var(--brand-text);
        transition: all .12s ease;
    }
    .new-case-modal .nc-chip-content i { color: var(--brand-primary-darker); font-size: .9rem; }
    .new-case-modal .nc-modality-chip:hover .nc-chip-content {
        border-color: var(--brand-primary);
        background: rgba(159, 147, 231, .05);
    }
    .new-case-modal .nc-modality-chip input[type="checkbox"]:checked + .nc-chip-content {
        background: var(--brand-primary);
        color: #fff;
        border-color: var(--brand-primary);
    }
    .new-case-modal .nc-modality-chip input[type="checkbox"]:checked + .nc-chip-content i { color: #fff; }

    .new-case-modal .new-case-footer {
        background: #fafbfc;
        flex-wrap: wrap;
        gap: .35rem;
    }
    .new-case-modal .new-case-hint { flex: 1; min-width: 200px; }

    @media (max-width: 768px) {
        .new-case-modal .new-case-body { max-height: 80vh; padding: .85rem .75rem; }
        .new-case-modal .nc-modalities { grid-template-columns: 1fr 1fr; }
    }

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
        margin-right:.35rem;
        min-width:36px; min-height:32px;
        display:inline-flex; align-items:center; justify-content:center;
        transition:background .15s ease, color .15s ease;
    }
    .eval-section-body .eval-row-delete:hover {
        background:#dc3545; color:#fff;
    }
    .eval-section-body .eval-row-delete i { font-size:.85rem; }

    /* Fase 6a — botón Descargar PDF (link estilizado como botón) */
    .eval-section-body .eval-row-pdf {
        background:transparent; border:1px solid #6c757d;
        color:#495057;
        border-radius:.3rem; padding:.2rem .5rem;
        font-size:.75rem; line-height:1;
        margin-right:.5rem;
        min-width:36px; min-height:32px;
        display:inline-flex; align-items:center; justify-content:center;
        transition:background .15s ease, color .15s ease;
        text-decoration:none !important;
    }
    .eval-section-body .eval-row-pdf:hover {
        background:#495057; color:#fff;
        text-decoration:none !important;
    }
    .eval-section-body .eval-row-pdf i { font-size:.85rem; }

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

    /* Launcher de nueva evaluación — grid de chips por tipo */
    .eval-launcher {
        background:#fff; border:1px solid #e9ecef; border-radius:.5rem;
        padding:1rem 1.1rem; margin-bottom:1rem;
    }
    .eval-launcher.is-empty { background:linear-gradient(135deg, rgba(159,147,231,.06) 0%, rgba(159,147,231,.02) 100%); border-color:rgba(159,147,231,.35); }
    .eval-launcher-head {
        display:flex; align-items:center; flex-wrap:wrap; gap:.4rem .8rem; margin-bottom:.7rem;
    }
    .eval-launcher-head .eval-launcher-title {
        margin:0; font-size:.95rem; font-weight:600; color:#212529;
        display:flex; align-items:center; gap:.45rem;
    }
    .eval-launcher-head .eval-launcher-title i { color:var(--brand-primary-darker); }
    .eval-launcher-head .eval-launcher-sub { font-size:.78rem; color:#6c757d; margin-left:.2rem; }
    .eval-launcher-toggle {
        margin-left:auto; background:none; border:none; cursor:pointer;
        color:#6c757d; font-size:.78rem; padding:.2rem .5rem; border-radius:.3rem;
        display:inline-flex; align-items:center; gap:.3rem;
    }
    .eval-launcher-toggle:hover { background:#f1f3f5; color:#212529; }
    .eval-launcher-toggle i { transition:transform .15s; }
    .eval-launcher.collapsed .eval-launcher-toggle i { transform:rotate(-90deg); }
    .eval-launcher.collapsed .eval-launcher-grid { display:none; }
    .eval-launcher.collapsed .eval-launcher-warn { display:none; }

    .eval-launcher-warn {
        background:#fff4d6; color:#996800; border:1px solid #f0d68a;
        padding:.45rem .7rem; border-radius:.35rem; font-size:.78rem;
        margin-bottom:.7rem;
        display:flex; align-items:center; gap:.45rem;
    }
    .eval-launcher-warn a { color:#7a4f00; text-decoration:underline; font-weight:500; }

    .eval-launcher-grid {
        display:grid; gap:.5rem;
        grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));
    }
    .eval-launcher-chip {
        display:flex; align-items:center; gap:.55rem;
        background:#fff; border:1px solid #e9ecef; border-radius:.45rem;
        padding:.55rem .7rem; cursor:pointer; text-align:left;
        font-size:.83rem; color:#212529;
        transition:border-color .15s, box-shadow .15s, transform .05s;
        min-height:48px; width:100%;
    }
    .eval-launcher-chip:hover:not(:disabled) {
        border-color:var(--brand-primary-darker);
        box-shadow:0 2px 8px rgba(159,147,231,.18);
    }
    .eval-launcher-chip:active:not(:disabled) { transform:translateY(1px); }
    .eval-launcher-chip:disabled { opacity:.5; cursor:not-allowed; }
    .eval-launcher-chip .lc-icon {
        width:30px; height:30px; border-radius:.35rem;
        display:flex; align-items:center; justify-content:center;
        color:#fff; font-size:.82rem; flex-shrink:0;
    }
    .eval-launcher-chip .lc-label { flex:1; line-height:1.15; }
    .eval-launcher-chip .lc-plus { color:#adb5bd; font-size:.85rem; }
    .eval-launcher-chip:hover:not(:disabled) .lc-plus { color:var(--brand-primary-darker); }

    .eval-launcher-empty-cta {
        text-align:center; padding:.6rem 0 .9rem 0;
    }
    .eval-launcher-empty-cta h6 {
        margin:0 0 .25rem 0; color:var(--brand-primary-darker); font-weight:600;
    }
    .eval-launcher-empty-cta p {
        margin:0; color:#6c757d; font-size:.83rem;
    }

    @media (max-width: 768px) {
        .eval-section-body { padding:.4rem .8rem .8rem 1rem; }
        .eval-section-body .eval-row { flex-wrap:wrap; }
        .eval-section-body .eval-row-date { width:auto; margin-right:.5rem; }
        .eval-filter { width:100%; margin-left:0; }
        .eval-filter select { flex:1; }
        .eval-launcher-grid { grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); }
    }

    /* ====== Fase 15 — Adjuntos (tablet-first) ====== */
    .adj-header {
        display:flex; flex-wrap:wrap; align-items:center; gap:.6rem 1rem;
        margin-bottom:1rem;
    }
    .adj-header h5 { margin:0; }
    .adj-summary-pill {
        background:rgba(159,147,231,.10); color:var(--brand-primary-darker);
        border:1px solid rgba(159,147,231,.35); padding:.3rem .75rem;
        border-radius:1rem; font-size:.78rem; font-weight:500;
    }

    /* Zona drop + acciones principales */
    .adj-dropzone {
        position:relative;
        border:2px dashed rgba(159,147,231,.45);
        background:linear-gradient(135deg, rgba(159,147,231,.04) 0%, rgba(159,147,231,.01) 100%);
        border-radius:.6rem;
        padding:1.4rem 1rem;
        text-align:center;
        margin-bottom:1rem;
        transition:background .15s, border-color .15s;
    }
    .adj-dropzone.is-dragover {
        background:rgba(159,147,231,.10);
        border-color:var(--brand-primary-darker);
    }
    .adj-dropzone-title {
        font-size:.92rem; font-weight:600; color:#495057; margin-bottom:.2rem;
    }
    .adj-dropzone-hint {
        font-size:.78rem; color:#6c757d; margin-bottom:.9rem;
    }
    .adj-actions {
        display:flex; flex-wrap:wrap; gap:.6rem; justify-content:center;
    }
    .adj-btn-action {
        display:inline-flex; align-items:center; gap:.45rem;
        background:#fff; border:1px solid #e9ecef; border-radius:.45rem;
        padding:.7rem 1.1rem; font-size:.88rem; color:#212529; cursor:pointer;
        min-height:52px;  /* touch target tablet */
        transition:border-color .15s, box-shadow .15s, background .15s;
    }
    .adj-btn-action:hover {
        border-color:var(--brand-primary-darker);
        box-shadow:0 2px 8px rgba(159,147,231,.18);
        background:#fff;
    }
    .adj-btn-action.is-primary {
        background:var(--brand-primary-darker); color:#fff; border-color:var(--brand-primary-darker);
    }
    .adj-btn-action.is-primary:hover { background:#7c6dc7; }
    .adj-btn-action i { font-size:1rem; }

    /* Categoría chips de filtro */
    .adj-filters {
        display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:.9rem;
    }
    .adj-filter-chip {
        background:#fff; border:1px solid #e9ecef; border-radius:1rem;
        padding:.32rem .8rem; font-size:.8rem; color:#495057; cursor:pointer;
        display:inline-flex; align-items:center; gap:.35rem;
        min-height:34px;
        transition:background .15s, border-color .15s;
    }
    .adj-filter-chip:hover { border-color:var(--brand-primary-darker); }
    .adj-filter-chip.is-active {
        background:var(--brand-primary-darker); color:#fff; border-color:var(--brand-primary-darker);
    }
    .adj-filter-chip .chip-count {
        background:rgba(0,0,0,.08); padding:.05rem .45rem; border-radius:1rem;
        font-size:.72rem; font-weight:600;
    }
    .adj-filter-chip.is-active .chip-count { background:rgba(255,255,255,.25); }

    /* Cuota */
    .adj-quota {
        background:#f8f9fa; border:1px solid #e9ecef; border-radius:.4rem;
        padding:.55rem .8rem; margin-bottom:.9rem; font-size:.78rem; color:#495057;
        display:flex; align-items:center; gap:.6rem;
    }
    .adj-quota.is-warn { background:#fff4d6; border-color:#f0d68a; color:#7a4f00; }
    .adj-quota-bar { flex:1; height:6px; background:#e9ecef; border-radius:3px; overflow:hidden; }
    .adj-quota-bar-fill { height:100%; background:var(--brand-primary-darker); transition:width .25s; }
    .adj-quota.is-warn .adj-quota-bar-fill { background:#d9a236; }

    /* Grid de tarjetas */
    .adj-grid {
        display:grid; gap:.85rem;
        grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));
    }
    .adj-card {
        background:#fff; border:1px solid #e9ecef; border-radius:.5rem;
        overflow:hidden;
        display:flex; flex-direction:column;
        transition:border-color .15s, box-shadow .15s, transform .05s;
        cursor:pointer;
    }
    .adj-card:hover {
        border-color:var(--brand-primary-darker);
        box-shadow:0 4px 14px rgba(159,147,231,.18);
        transform:translateY(-1px);
    }
    .adj-card-thumb {
        position:relative;
        width:100%; aspect-ratio:4/3;
        background:#f1f3f5;
        display:flex; align-items:center; justify-content:center;
        overflow:hidden;
    }
    .adj-card-thumb img {
        width:100%; height:100%; object-fit:cover;
        display:block;
    }
    .adj-card-thumb .adj-thumb-icon {
        font-size:2.4rem; color:#adb5bd;
    }
    .adj-card-thumb .adj-thumb-icon.is-pdf { color:#dc3545; }
    .adj-card-thumb .adj-thumb-icon.is-doc { color:#0d6efd; }
    .adj-card-thumb .adj-thumb-icon.is-img { color:var(--brand-primary-darker); }
    .adj-card-body {
        padding:.55rem .65rem;
        display:flex; flex-direction:column; gap:.15rem;
        flex:1;
    }
    .adj-card-name {
        font-size:.82rem; color:#212529; font-weight:500;
        line-height:1.2;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
        overflow:hidden;
        word-break:break-word;
    }
    .adj-card-meta {
        font-size:.7rem; color:#6c757d;
        display:flex; justify-content:space-between; align-items:center;
        margin-top:.25rem;
    }
    .adj-card-cat {
        display:inline-block; font-size:.65rem;
        padding:.05rem .45rem; border-radius:.7rem;
        background:rgba(159,147,231,.12); color:var(--brand-primary-darker);
        font-weight:600; text-transform:uppercase; letter-spacing:.03em;
    }
    .adj-card-actions {
        position:absolute; top:.4rem; right:.4rem;
        display:flex; gap:.25rem;
        opacity:0; transition:opacity .15s;
    }
    .adj-card:hover .adj-card-actions { opacity:1; }
    .adj-card-actions button {
        background:rgba(255,255,255,.92); border:1px solid rgba(0,0,0,.08);
        border-radius:.3rem; width:32px; height:32px;
        display:flex; align-items:center; justify-content:center;
        color:#495057; cursor:pointer; font-size:.8rem;
    }
    .adj-card-actions button:hover { background:#fff; color:#212529; }
    .adj-card-actions button.adj-btn-danger { color:#dc3545; }
    .adj-card-actions button.adj-btn-danger:hover { background:#dc3545; color:#fff; }
    /* En tablet/táctil mostramos siempre las acciones porque no hay hover */
    @media (hover: none) {
        .adj-card-actions { opacity:1; }
        .adj-card-actions button { background:#fff; }
    }

    /* Modal preview */
    .adj-preview-modal .modal-dialog { max-width: 92vw; }
    .adj-preview-modal .modal-content { background:#212529; color:#fff; border-radius:.4rem; }
    .adj-preview-modal .modal-header { border-bottom:1px solid rgba(255,255,255,.1); padding:.7rem 1rem; }
    .adj-preview-modal .modal-title { color:#fff; font-size:.95rem; }
    .adj-preview-modal .close { color:#fff; opacity:.8; text-shadow:none; font-size:1.6rem; }
    .adj-preview-modal .modal-body {
        padding:0; min-height:60vh;
        display:flex; align-items:center; justify-content:center;
        background:#000;
    }
    .adj-preview-modal .adj-preview-img {
        max-width:100%; max-height:78vh; object-fit:contain;
    }
    .adj-preview-modal .adj-preview-pdf {
        width:100%; height:78vh; border:none; background:#fff;
    }
    .adj-preview-modal .modal-footer {
        background:#1a1d20; border-top:1px solid rgba(255,255,255,.1);
        padding:.6rem 1rem; gap:.4rem;
    }

    /* Modal upload (confirmación de categoría/descripción) */
    .adj-upload-modal .upload-queue {
        max-height:240px; overflow-y:auto;
        border:1px solid #e9ecef; border-radius:.4rem;
        margin-bottom:.8rem;
    }
    .adj-upload-modal .upload-queue-item {
        display:flex; align-items:center; gap:.6rem;
        padding:.5rem .7rem; border-bottom:1px solid #f1f3f5;
        font-size:.85rem;
    }
    .adj-upload-modal .upload-queue-item:last-child { border-bottom:none; }
    .adj-upload-modal .upload-queue-item .uq-icon {
        width:36px; height:36px; border-radius:.3rem; background:#f1f3f5;
        display:flex; align-items:center; justify-content:center;
        flex-shrink:0; color:var(--brand-primary-darker);
    }
    .adj-upload-modal .upload-queue-item .uq-info { flex:1; min-width:0; }
    .adj-upload-modal .upload-queue-item .uq-name {
        font-weight:500; color:#212529; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .adj-upload-modal .upload-queue-item .uq-size {
        font-size:.72rem; color:#6c757d;
    }
    .adj-upload-modal .upload-queue-item .uq-remove {
        background:none; border:none; color:#6c757d; cursor:pointer;
        padding:.3rem .5rem; border-radius:.3rem;
    }
    .adj-upload-modal .upload-queue-item .uq-remove:hover { background:#fee; color:#dc3545; }
    .adj-upload-modal .progress { height:6px; }
    .adj-upload-modal .progress-bar { background:var(--brand-primary-darker); }

    .adj-empty {
        text-align:center; padding:2rem 1rem; color:#adb5bd;
    }
    .adj-empty i { font-size:2.4rem; display:block; margin-bottom:.6rem; }

    @media (max-width: 768px) {
        .adj-dropzone { padding:1rem .7rem; }
        .adj-actions { flex-direction:column; }
        .adj-btn-action { width:100%; justify-content:center; }
        .adj-grid { grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:.6rem; }
        .adj-card-thumb { aspect-ratio:1/1; }
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
    .inline-eval-modal .field-file-uploads .fu-placeholder {
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        gap:.15rem;
    }
    .inline-eval-modal .field-file-uploads .fu-placeholder i { font-size:1.1rem; }
    .inline-eval-modal .field-file-uploads .fu-placeholder i + i { margin-left:.3rem; }
    .inline-eval-modal .field-file-uploads .fu-placeholder-hint {
        font-size:.6rem; color:#adb5bd; text-transform:none;
        letter-spacing:0; font-weight:500; margin-top:.15rem;
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

    /* ============== Fase 10 — Plantillas de evaluación ============== */
    .inline-eval-header { display:flex; align-items:center; }
    .inline-eval-header .modal-title { flex:1; }

    .eval-tpl-dropdown .eval-tpl-toggle {
        font-size:.78rem; font-weight:600;
        padding:.25rem .65rem;
        border-radius:.3rem;
    }
    .eval-tpl-menu {
        min-width:280px; max-width:340px;
        max-height:380px; overflow-y:auto;
        padding:.35rem 0;
        font-family:var(--brand-font-body);
    }
    .eval-tpl-menu .dropdown-header {
        font-size:.7rem; font-weight:700;
        color:var(--brand-text-muted);
        text-transform:uppercase; letter-spacing:.04em;
        padding:.4rem 1rem .2rem;
    }
    .eval-tpl-empty {
        font-size:.82rem; color:#adb5bd; font-style:italic;
        padding:.55rem 1rem;
    }
    .eval-tpl-item {
        display:flex; align-items:flex-start;
        gap:.5rem;
        padding:.45rem 1rem;
        cursor:pointer;
        border-left:3px solid transparent;
        transition:background .12s ease, border-color .12s ease;
    }
    .eval-tpl-item:hover {
        background:rgba(159, 147, 231, .10);
        border-left-color:var(--brand-primary, #9F93E7);
    }
    .eval-tpl-item .eval-tpl-info { flex:1; min-width:0; }
    .eval-tpl-item .eval-tpl-name {
        font-weight:600; font-size:.88rem;
        color:var(--brand-text);
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .eval-tpl-item .eval-tpl-meta {
        font-size:.7rem; color:var(--brand-text-muted); margin-top:.1rem;
    }
    .eval-tpl-scope-badge {
        font-size:.62rem; font-weight:700;
        padding:.1rem .4rem; border-radius:.6rem;
        text-transform:uppercase; letter-spacing:.04em;
        flex-shrink:0; margin-top:.2rem;
    }
    .eval-tpl-scope-badge.personal { background:rgba(159, 147, 231, .15); color:var(--brand-primary-darker); }
    .eval-tpl-scope-badge.global   { background:rgba(199, 217, 229, .35); color:#1d5e6b; }
    .eval-tpl-item-delete {
        background:transparent; border:none; cursor:pointer;
        color:#cfd5dd; padding:.15rem .3rem;
        font-size:.85rem; line-height:1;
        flex-shrink:0;
    }
    .eval-tpl-item-delete:hover { color:#dc3545; }

    /* Modal de guardar plantilla */
    .eval-tpl-save-modal .modal-title { color:var(--brand-text); }
    .tpl-scope-options { display:grid; grid-template-columns:1fr 1fr; gap:.6rem; margin-top:.2rem; }
    .tpl-scope-option { cursor:pointer; margin:0; }
    .tpl-scope-option input[type="radio"] { display:none; }
    .tpl-scope-card {
        border:2px solid #e9ecef;
        border-radius:.4rem;
        padding:.7rem .9rem;
        transition:all .15s ease;
        font-size:.82rem;
        color:var(--brand-text);
        background:#fff;
    }
    .tpl-scope-card i { color:var(--brand-primary-darker); }
    .tpl-scope-option input[type="radio"]:checked + .tpl-scope-card {
        border-color:var(--brand-primary);
        background:rgba(159, 147, 231, .08);
    }
    .tpl-scope-desc { font-size:.7rem; color:var(--brand-text-muted); margin-top:.15rem; }

    @media (max-width: 576px) {
        .tpl-scope-options { grid-template-columns:1fr; }
        .eval-tpl-menu { min-width:240px; }
    }

    /* ============== Fase 11 — Tab Evolución (gráficos) ============== */
    .evol-header {
        display:flex; align-items:flex-start; gap:1rem;
        margin-bottom:1.2rem; padding:.85rem 1rem;
        background:#fff;
        border:1px solid #e9ecef;
        border-left:4px solid var(--brand-primary, #9F93E7);
        border-radius:.4rem;
    }
    .evol-header-text { flex:1; min-width:0; }
    .evol-header h4 {
        margin:0 0 .15rem 0;
        font-size:1.05rem;
        color:var(--brand-text);
        font-family:var(--brand-font-body);
    }
    .evol-header h4 i { color:var(--brand-primary, #9F93E7); }
    .evol-subtitle { font-size:.82rem; color:var(--brand-text-muted); }

    .evol-legend {
        display:flex; flex-direction:column;
        gap:.25rem;
        font-size:.72rem;
        color:var(--brand-text-muted);
        flex-shrink:0;
    }
    .evol-legend-item { display:inline-flex; align-items:center; gap:.4rem; }
    .evol-legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .evol-legend-dot.up   { background:#31ce36; }
    .evol-legend-dot.down { background:#dc3545; }
    .evol-legend-dot.none { background:#cfd5dd; }

    .evol-grid {
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap:1rem;
    }
    .evol-card {
        background:#fff;
        border:1px solid #e9ecef;
        border-radius:.5rem;
        box-shadow:var(--brand-shadow-sm);
        padding:1rem;
        display:flex;
        flex-direction:column;
        min-width:0;
    }
    .evol-card-header {
        display:flex; align-items:center; gap:.5rem;
        padding-bottom:.5rem;
        margin-bottom:.65rem;
        border-bottom:1px solid #f1f3f5;
    }
    .evol-card-icon {
        width:34px; height:34px; border-radius:.4rem;
        background:rgba(159, 147, 231, .15);
        color:var(--brand-primary-darker);
        display:flex; align-items:center; justify-content:center;
        font-size:.95rem;
        flex-shrink:0;
    }
    .evol-card-title {
        font-weight:700;
        font-size:.95rem;
        color:var(--brand-text);
        flex:1; min-width:0;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .evol-card-meta {
        font-size:.7rem;
        color:var(--brand-text-muted);
        flex-shrink:0;
    }
    .evol-chart-wrap {
        position:relative;
        height:220px;
        margin-bottom:.6rem;
    }
    .evol-series-summary {
        display:flex; flex-wrap:wrap;
        gap:.4rem .55rem;
        padding-top:.5rem;
        border-top:1px dashed #f1f3f5;
        font-size:.72rem;
    }
    .evol-summary-item {
        display:inline-flex; align-items:center; gap:.3rem;
        padding:.2rem .5rem;
        border-radius:1rem;
        background:#f8f9fc;
        color:var(--brand-text);
    }
    .evol-summary-item .evol-summary-dot {
        width:8px; height:8px; border-radius:50%; flex-shrink:0;
    }
    .evol-summary-delta {
        font-weight:700;
        font-size:.72rem;
        padding:.05rem .35rem;
        border-radius:.6rem;
        margin-left:.15rem;
    }
    .evol-summary-delta.up   { background:#d1e7dd; color:#0a3622; }
    .evol-summary-delta.down { background:#f8d7da; color:#842029; }
    .evol-summary-delta.none { background:#e9ecef; color:#495057; }

    .evol-empty-card {
        grid-column: 1 / -1;
        text-align:center;
        color:#adb5bd;
        padding:2.5rem 1rem;
        background:#fafbfc;
        border-radius:.4rem;
        border:1px dashed #dee2e6;
    }
    .evol-empty-card i { font-size:2.2rem; color:#dee2e6; margin-bottom:.5rem; display:block; }

    @media (max-width: 992px) {
        .evol-grid { grid-template-columns: 1fr; }
        .evol-header { flex-direction:column; }
        .evol-legend { flex-direction:row; flex-wrap:wrap; gap:.65rem; }
    }
    @media (max-width: 576px) {
        .evol-chart-wrap { height:200px; }
    }

    /* ============== Fase 9a — Tab Mensajes + Modal envío ============== */
    .msg-tab-header {
        display:flex; align-items:flex-start; gap:1rem;
        margin-bottom:1rem; padding:.75rem 1rem;
        background:#fff;
        border:1px solid #e9ecef;
        border-left:4px solid #25D366;
        border-radius:.4rem;
    }
    .msg-tab-title-block { flex:1; min-width:0; }
    .msg-tab-title-block h4 {
        margin:0 0 .15rem 0;
        font-size:1.05rem;
        color:var(--brand-text);
        font-family:var(--brand-font-body);
    }
    .msg-tab-title-block h4 .fab { color:#25D366; }
    .msg-tab-subtitle { font-size:.85rem; color:var(--brand-text-muted); }

    .msg-provider-hint {
        background:rgba(199, 217, 229, .25);
        color:#1d5e6b;
        padding:.5rem .8rem;
        border-radius:.3rem;
        font-size:.78rem;
        margin-bottom:.85rem;
        display:none;
    }
    .msg-provider-hint.visible { display:block; }

    .msg-list .msg-card {
        background:#fff;
        border:1px solid #e9ecef;
        border-radius:.4rem;
        padding:.85rem 1rem;
        margin-bottom:.75rem;
        box-shadow:var(--brand-shadow-sm);
        position:relative;
        border-left:4px solid #25D366;
    }
    .msg-list .msg-card.failed { border-left-color:#dc3545; }
    .msg-list .msg-card.queued { border-left-color:#ffad46; }
    .msg-card-header {
        display:flex; align-items:center; gap:.65rem;
        margin-bottom:.4rem;
        font-size:.82rem;
        color:var(--brand-text-muted);
    }
    .msg-card-channel {
        background:#e8f8ed; color:#1a7a36;
        padding:.1rem .5rem; border-radius:1rem;
        font-size:.7rem; font-weight:600;
        text-transform:uppercase;
    }
    .msg-card-channel.sms { background:rgba(199, 217, 229, .35); color:#1d5e6b; }
    .msg-card-channel.log { background:#f1f3f5; color:#6c757d; }
    .msg-card-status {
        font-size:.7rem; font-weight:600;
        padding:.1rem .5rem; border-radius:1rem;
    }
    .msg-card-status.sent      { background:#e8f8ed; color:#1a7a36; }
    .msg-card-status.queued    { background:#fff4d6; color:#996800; }
    .msg-card-status.failed    { background:#fde2e1; color:#a8201a; }
    .msg-card-status.delivered { background:rgba(159, 147, 231, .15); color:var(--brand-primary-darker); }

    .msg-card-template {
        font-size:.7rem; color:var(--brand-primary-darker);
        background:rgba(159, 147, 231, .12);
        padding:.1rem .5rem; border-radius:1rem;
        font-weight:600;
    }
    .msg-card-meta { margin-left:auto; font-size:.72rem; color:var(--brand-text-muted); }
    .msg-card-body {
        white-space:pre-wrap;
        color:var(--brand-text);
        font-size:.92rem;
        line-height:1.45;
        background:#faf8ff;
        border-radius:.3rem;
        padding:.6rem .75rem;
    }
    .msg-card-error {
        background:#fde2e1; color:#a8201a;
        padding:.4rem .7rem; border-radius:.25rem;
        font-size:.78rem; margin-top:.4rem;
        font-family:monospace;
    }

    /* Modal envío */
    .msg-send-modal .modal-title { color:var(--brand-text); }
    .msg-template-grid {
        display:grid;
        grid-template-columns:repeat(3, 1fr);
        gap:.5rem;
        margin-bottom:1rem;
    }
    .msg-template-btn {
        background:#fff;
        border:1px solid #e9ecef;
        border-radius:.4rem;
        padding:.6rem .5rem;
        text-align:center;
        cursor:pointer;
        transition:all .15s ease;
        font-size:.8rem;
        color:var(--brand-text);
        font-weight:600;
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:.3rem;
    }
    .msg-template-btn:hover {
        background:rgba(159, 147, 231, .1);
        border-color:var(--brand-primary);
    }
    .msg-template-btn.active {
        background:var(--brand-primary);
        color:#fff;
        border-color:var(--brand-primary);
    }
    .msg-template-btn i { font-size:1.15rem; color:var(--brand-primary-darker); }
    .msg-template-btn.active i { color:#fff; }

    .msg-vars-row { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:.75rem; }
    .msg-vars-row .form-group { margin-bottom:0; }

    .msg-preview-wrap label { font-size:.85rem; color:var(--brand-text); font-weight:600; }
    .msg-char-count { font-size:.72rem; color:var(--brand-text-muted); font-weight:400; }
    .msg-preview-textarea {
        font-family:var(--brand-font-body);
        font-size:.92rem;
        white-space:pre-wrap;
        min-height:160px;
        background:#faf8ff;
    }

    .msg-helpers {
        font-size:.72rem; color:var(--brand-text-muted);
        margin-top:.5rem; padding:.4rem .65rem;
        background:rgba(255, 173, 70, .08);
        border-radius:.25rem;
    }
    .msg-helpers code {
        background:rgba(159, 147, 231, .15);
        color:var(--brand-primary-darker);
        padding:1px 5px; border-radius:.2rem;
        font-size:.7rem;
    }

    .msg-provider-badge {
        font-size:.72rem;
        background:#f1f3f5;
        color:#5a6c80;
        padding:.2rem .6rem;
        border-radius:1rem;
        margin-right:auto;
    }
    .msg-provider-badge.provider-log {
        background:rgba(255, 173, 70, .15); color:#996800;
    }

    @media (max-width: 768px) {
        .msg-template-grid { grid-template-columns:repeat(2, 1fr); }
        .msg-vars-row { grid-template-columns:1fr; }
        .msg-tab-header { flex-direction:column; align-items:stretch; }
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

    @php
        // Helper global de decode de entidades HTML.
        // El middleware xssProtection codifica al guardar (ñ → &ntilde;);
        // aquí decodificamos para mostrar texto limpio en pantalla.
        // Idempotente: aplicado dos veces produce el mismo resultado.
        if (!isset($decoder)) {
            $decoder = fn($v) => is_string($v) ? html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $v;
        }
    @endphp

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
            {{-- Fase 6b + Reorg-A.2 — Botón descargar PDF context-aware:
                 si hay caso seleccionado, descarga reporte del caso (con ficha completa);
                 si no, descarga el expediente global del paciente. --}}
            <div class="ml-auto" style="flex-shrink:0;">
                @php
                    $isCaseFiltered = isset($casoActivo) && is_numeric($casoActivo);
                    $pdfUrl = url('expediente-pdf/' . $patient->id) . ($isCaseFiltered ? ('?caso=' . $casoActivo) : '');
                    $pdfLabel = $isCaseFiltered ? translate('Descargar reporte del caso') : translate('Descargar expediente');
                    $pdfTitle = $isCaseFiltered
                        ? translate('Reporte clínico del caso seleccionado: ficha completa + sus evaluaciones + sus sesiones')
                        : translate('Expediente completo del paciente: resumen de todos los casos, evaluaciones y sesiones');
                @endphp
                <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-primary btn-sm" title="{{ $pdfTitle }}">
                    <i class="fas fa-file-pdf mr-1"></i> {{ $pdfLabel }}
                </a>
            </div>
        </div>
    </div>

    {{-- ============== Fase Reorg-A — Selector global de caso clínico ============== --}}
    <div class="case-selector" id="caseSelectorBar">
        @if(isset($fichas) && $fichas->count() > 0)
            <div class="case-selector-left">
                <div class="case-selector-label">
                    <i class="fas fa-folder-open mr-1"></i> {{ translate('Caso clínico activo') }}
                </div>
                <select id="caseSelector" class="case-selector-input">
                    <option value="all" {{ $casoActivo === 'all' ? 'selected' : '' }}>
                        {{ translate('Todos los casos — vista global del paciente') }}
                    </option>
                    @foreach($fichas as $f)
                        @php
                            $fDiag = trim($decoder($f->diagnostico ?? ''));
                            $fLabel = $fDiag !== '' ? $fDiag : ('Ficha #' . $f->id);
                            if (mb_strlen($fLabel) > 60) $fLabel = mb_substr($fLabel, 0, 60) . '…';
                            $fDate = $f->fecha ? \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') : '—';
                        @endphp
                        <option value="{{ $f->id }}" {{ (string)$casoActivo === (string)$f->id ? 'selected' : '' }}>
                            {{ $fLabel }} · {{ $fDate }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="case-selector-stats" id="caseSelectorStats">
                @php
                    if ($casoActivo === 'all') {
                        $statsText = $fichas->count() . ' ' . ($fichas->count() === 1 ? 'caso' : 'casos') . ' · vista completa';
                    } else {
                        $activeFicha = $fichas->firstWhere('id', (int) $casoActivo);
                        if ($activeFicha) {
                            $statsText = ($activeFicha->eval_count ?? 0) . ' evaluaciones · '
                                . ($activeFicha->ses_count ?? 0) . ' sesiones';
                            if (!empty($activeFicha->fecha)) {
                                $days = \Carbon\Carbon::parse($activeFicha->fecha)->diffInDays(now());
                                $statsText .= ' · iniciado hace ' . $days . ' día' . ($days === 1 ? '' : 's');
                            }
                        } else {
                            $statsText = 'caso no encontrado';
                        }
                    }
                @endphp
                {{ $statsText }}
            </div>
        @else
            <div class="case-selector-left">
                <div class="case-selector-label">
                    <i class="fas fa-folder-open mr-1"></i> {{ translate('Sin casos clínicos') }}
                </div>
                <span class="case-selector-empty">
                    {{ translate('Este paciente aún no tiene fichas clínicas. Crea la primera para iniciar el tratamiento.') }}
                </span>
            </div>
        @endif

        {{-- Botón "Nueva ficha clínica" — siempre disponible --}}
        <button type="button" class="btn-new-case" id="btnNewCase" title="Iniciar caso clínico nuevo para este paciente">
            <i class="fas fa-folder-plus mr-1"></i>
            <span>{{ translate('Nueva ficha clínica') }}</span>
        </button>
    </div>

    {{-- Banner de caso cerrado: el caso está dado de alta → solo lectura
         (no se pueden agregar/editar sesiones ni evaluaciones; sí adjuntos). --}}
    @if(isset($fichaCompleta) && $fichaCompleta && !empty($fichaCompleta->fecha_alta))
        <div class="caso-cerrado-banner">
            <i class="fas fa-lock"></i>
            <div class="ccb-text">
                <strong>{{ translate('Caso cerrado') }}</strong>
                ({{ translate('alta') }} {{ \Carbon\Carbon::parse($fichaCompleta->fecha_alta)->format('d/m/Y') }}).
                {{ translate('Para registrar sesiones o evaluaciones, reábrelo desde el resumen. Aún puedes consultar el historial y adjuntar documentos.') }}
            </div>
        </div>
    @endif

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
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-evolucion" role="tab" id="tab-evolucion-trigger">
                    <i class="fas fa-chart-line mr-1"></i> {{ translate('Evolución') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-adjuntos" role="tab" id="tab-adjuntos-trigger">
                    <i class="fas fa-paperclip mr-1"></i> {{ translate('Adjuntos') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-mensajes" role="tab" id="tab-mensajes-trigger">
                    <i class="fab fa-whatsapp mr-1"></i> {{ translate('Mensajes') }}
                </a>
            </li>
        </ul>
    </div>

    <div class="expediente-tab-content tab-content">

    {{-- =========================== TAB RESUMEN =========================== --}}
    <div class="tab-pane fade show active" id="tab-resumen" role="tabpanel">

    @if(isset($fichaCompleta) && $fichaCompleta)
        {{-- ===== Ficha clínica completa (visible solo cuando hay caso seleccionado) ===== --}}
        @php
            // Helpers para detectar si secciones tienen contenido
            $hasAntecedentes = !empty($fichaCompleta->historial_medico) || !empty($fichaCompleta->enfermedades_cronicas)
                || !empty($fichaCompleta->cirugias_previas) || !empty($fichaCompleta->medicamentos_actuales)
                || !empty($fichaCompleta->alergias);
            $hasLesion = !empty($fichaCompleta->fecha_inicio) || !empty($fichaCompleta->mecanismo_lesion_origen)
                || !empty($fichaCompleta->evolucion_sintomas) || !empty($fichaCompleta->tratamientos_previos);
            $hasEvalFisio = !empty($fichaCompleta->observacion_marcha) || !empty($fichaCompleta->observacion_otros)
                || !empty($fichaCompleta->diagnostico_fisioterapeutico);
            $hasObjetivos = !empty($fichaCompleta->corto_plazo) || !empty($fichaCompleta->mediano_plazo)
                || !empty($fichaCompleta->largo_plazo);
            $modalidadesSeleccionadas = collect([
                'modalidades_ejercicio_terapeutico' => 'Ejercicio terapéutico',
                'modalidades_electroterapia'        => 'Electroterapia',
                'modalidades_masoterapia'           => 'Masoterapia',
                'modalidades_estiramientos'         => 'Estiramientos',
                'modalidades_tecaterapia'           => 'Tecarterapia',
                'modalidades_puncion_seca'          => 'Punción seca',
                'modalidades_electropuncion'        => 'Electropunción',
            ])->filter(fn($lbl, $key) => (int) ($fichaCompleta->{$key} ?? 0) === 1)->values()->all();
            $hasPlan = !empty($modalidadesSeleccionadas) || !empty($fichaCompleta->modalidades_otros);
            $decoder = fn($v) => is_string($v) ? html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $v;
        @endphp

        <div class="ficha-completa-card">
            <div class="ficha-completa-header" id="fichaCompletaToggle" data-toggle="collapse" data-target="#fichaCompletaBody" aria-expanded="false">
                <div class="ficha-completa-icon"><i class="fas fa-folder-open"></i></div>
                <div class="ficha-completa-title-block">
                    <div class="ficha-completa-title">
                        {{ $decoder($fichaCompleta->diagnostico) ?: 'Ficha #' . $fichaCompleta->id }}
                        @if(!empty($fichaCompleta->fecha_alta))
                            <span class="caso-estado-badge cerrado" title="{{ translate('Caso dado de alta') }}">
                                <i class="fas fa-check-circle"></i> {{ translate('Cerrado') }}
                            </span>
                        @endif
                    </div>
                    <div class="ficha-completa-meta">
                        @if($fichaCompleta->fecha)
                            Iniciada {{ \Carbon\Carbon::parse($fichaCompleta->fecha)->format('d/m/Y') }} ·
                        @endif
                        Caso #{{ $fichaCompleta->id }}
                        @if(!empty($fichaCompleta->fecha_alta))
                            · <span title="{{ translate('Fecha de alta') }}">
                                <i class="fas fa-check-circle" style="color:#1d7d2c;"></i>
                                {{ translate('Alta') }}: {{ \Carbon\Carbon::parse($fichaCompleta->fecha_alta)->format('d/m/Y') }}
                            </span>
                        @endif
                        @php $adjCount = (int) ($adjuntosCount ?? 0); @endphp
                        @if($adjCount > 0)
                            · <span title="{{ translate('Archivos adjuntos en este caso') }}">
                                <i class="fas fa-paperclip"></i> {{ $adjCount }} {{ $adjCount === 1 ? translate('adjunto') : translate('adjuntos') }}
                            </span>
                        @endif
                    </div>
                </div>
                <span class="ficha-completa-toggle-hint">
                    <i class="fas fa-chevron-down"></i> {{ translate('Ver ficha completa') }}
                </span>
            </div>
            <div id="fichaCompletaBody" class="collapse ficha-completa-body">

                @if(!empty($fichaCompleta->motivo_consulta))
                    <div class="ficha-block">
                        <div class="ficha-block-title">{{ translate('Motivo de consulta') }}</div>
                        <div class="ficha-block-text">{{ $decoder($fichaCompleta->motivo_consulta) }}</div>
                    </div>
                @endif

                @if($hasAntecedentes)
                    <div class="ficha-block">
                        <div class="ficha-block-title"><i class="fas fa-notes-medical mr-1"></i>{{ translate('Antecedentes médicos') }}</div>
                        @foreach([
                            'historial_medico'     => 'Historial médico',
                            'enfermedades_cronicas'=> 'Enfermedades crónicas',
                            'cirugias_previas'     => 'Cirugías previas',
                            'medicamentos_actuales'=> 'Medicamentos actuales',
                            'alergias'             => 'Alergias',
                        ] as $field => $lbl)
                            @if(!empty($fichaCompleta->{$field}))
                                <div class="ficha-field">
                                    <span class="ficha-field-label">{{ $lbl }}:</span>
                                    <span class="ficha-field-value">{{ $decoder($fichaCompleta->{$field}) }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if($hasLesion)
                    <div class="ficha-block">
                        <div class="ficha-block-title"><i class="fas fa-heart-broken mr-1"></i>{{ translate('Historia de la lesión') }}</div>
                        @if(!empty($fichaCompleta->fecha_inicio))
                            <div class="ficha-field">
                                <span class="ficha-field-label">Fecha de inicio:</span>
                                <span class="ficha-field-value">{{ \Carbon\Carbon::parse($fichaCompleta->fecha_inicio)->format('d/m/Y') }}</span>
                            </div>
                        @endif
                        @foreach([
                            'mecanismo_lesion_origen' => 'Mecanismo / origen',
                            'evolucion_sintomas'      => 'Evolución de los síntomas',
                            'tratamientos_previos'    => 'Tratamientos previos',
                        ] as $field => $lbl)
                            @if(!empty($fichaCompleta->{$field}))
                                <div class="ficha-field">
                                    <span class="ficha-field-label">{{ $lbl }}:</span>
                                    <span class="ficha-field-value">{{ $decoder($fichaCompleta->{$field}) }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if($hasEvalFisio)
                    <div class="ficha-block">
                        <div class="ficha-block-title"><i class="fas fa-walking mr-1"></i>{{ translate('Evaluación fisioterapéutica inicial') }}</div>
                        @foreach([
                            'observacion_marcha'           => 'Marcha',
                            'observacion_otros'            => 'Otras observaciones',
                            'diagnostico_fisioterapeutico' => 'Diagnóstico fisioterapéutico',
                        ] as $field => $lbl)
                            @if(!empty($fichaCompleta->{$field}))
                                <div class="ficha-field">
                                    <span class="ficha-field-label">{{ $lbl }}:</span>
                                    <span class="ficha-field-value">{{ $decoder($fichaCompleta->{$field}) }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if($hasObjetivos)
                    <div class="ficha-block">
                        <div class="ficha-block-title"><i class="fas fa-bullseye mr-1"></i>{{ translate('Objetivos del tratamiento') }}</div>
                        @foreach([
                            'corto_plazo'   => 'Corto plazo',
                            'mediano_plazo' => 'Mediano plazo',
                            'largo_plazo'   => 'Largo plazo',
                        ] as $field => $lbl)
                            @if(!empty($fichaCompleta->{$field}))
                                <div class="ficha-field">
                                    <span class="ficha-field-label">{{ $lbl }}:</span>
                                    <span class="ficha-field-value">{{ $decoder($fichaCompleta->{$field}) }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if($hasPlan)
                    <div class="ficha-block">
                        <div class="ficha-block-title"><i class="fas fa-list-check mr-1"></i>{{ translate('Plan de tratamiento') }}</div>
                        @if(!empty($modalidadesSeleccionadas))
                            <div class="ficha-field">
                                <span class="ficha-field-label">Modalidades:</span>
                                <span class="ficha-modalities-list">
                                    @foreach($modalidadesSeleccionadas as $m)
                                        <span class="ficha-modality-tag">{{ $m }}</span>
                                    @endforeach
                                </span>
                            </div>
                        @endif
                        @if(!empty($fichaCompleta->modalidades_otros))
                            <div class="ficha-field">
                                <span class="ficha-field-label">Otros tratamientos:</span>
                                <span class="ficha-field-value">{{ $decoder($fichaCompleta->modalidades_otros) }}</span>
                            </div>
                        @endif
                        @if(!empty($fichaCompleta->frecuencia_semana) || !empty($fichaCompleta->duracion_semanas))
                            <div class="ficha-field">
                                <span class="ficha-field-label">Plan:</span>
                                <span class="ficha-field-value">
                                    {{ $fichaCompleta->frecuencia_semana ?? 1 }} vez/sem ·
                                    {{ $fichaCompleta->duracion_semanas ?? '?' }} semanas
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Cierre del caso: visible solo cuando el caso está cerrado (tiene fecha_alta) --}}
                @if(!empty($fichaCompleta->fecha_alta))
                    <div class="ficha-block ficha-cierre-block">
                        <div class="ficha-block-title"><i class="fas fa-check-circle mr-1" style="color:#1d7d2c;"></i>{{ translate('Cierre del caso') }}</div>
                        <div class="ficha-field">
                            <span class="ficha-field-label">{{ translate('Fecha de alta') }}:</span>
                            <span class="ficha-field-value">{{ \Carbon\Carbon::parse($fichaCompleta->fecha_alta)->format('d/m/Y') }}</span>
                        </div>
                        @if(!empty($fichaCompleta->observaciones_cierre))
                            <div class="ficha-field">
                                <span class="ficha-field-label">{{ translate('Observaciones de cierre') }}:</span>
                                <span class="ficha-field-value">{{ $decoder($fichaCompleta->observaciones_cierre) }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="ficha-completa-actions">
                    {{-- Abre el modal de ficha (NewCaseManager) en modo edición,
                         pre-cargado con los datos de esta ficha. Reemplaza el link
                         al formulario antiguo /fis-ficha. --}}
                    <button type="button" class="btn btn-outline-primary btn-sm" data-action="edit-ficha">
                        <i class="fas fa-edit mr-1"></i> {{ translate('Editar ficha clínica') }}
                    </button>

                    @if(empty($fichaCompleta->fecha_alta))
                        {{-- Caso abierto → cerrar / dar de alta --}}
                        <button type="button" class="btn btn-outline-success btn-sm ml-1" data-action="close-ficha">
                            <i class="fas fa-check-circle mr-1"></i> {{ translate('Cerrar caso') }}
                        </button>
                    @else
                        {{-- Caso cerrado → reabrir --}}
                        <button type="button" class="btn btn-outline-secondary btn-sm ml-1" data-action="reopen-ficha"
                                data-ficha-id="{{ $fichaCompleta->id }}">
                            <i class="fas fa-undo mr-1"></i> {{ translate('Reabrir caso') }}
                        </button>
                    @endif

                    {{-- Eliminar caso: borrado lógico en cascada (ficha + evaluaciones + sesiones + adjuntos) --}}
                    <button type="button" class="btn btn-outline-danger btn-sm ml-1" data-action="delete-ficha">
                        <i class="fas fa-trash-alt mr-1"></i> {{ translate('Eliminar caso') }}
                    </button>
                </div>
            </div>

            {{-- Datos completos de la ficha activa para popular el modal de edición.
                 Decodificados (entidades del middleware xssProtection). --}}
            <script>
                window.FICHA_ACTIVA = @json(
                    collect($fichaCompleta->toArray())->map(function ($v) {
                        return is_string($v)
                            ? html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                            : $v;
                    })
                );
            </script>
        </div>
    @endif

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
                                {{-- Deep link: cambia al tab Evaluación y expande la sección de este tipo --}}
                                <a href="#" data-action="goto-eval-section" data-key="{{ $key }}"
                                   title="{{ translate('Ver evaluaciones de este tipo') }}">
                                    {{ translate('Ver') }} <i class="fas fa-arrow-right"></i>
                                </a>
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
                                        {{-- Deep link al registro específico:
                                             - Si la evaluación tiene config inline (JS lo decide) → abre modal pre-cargado.
                                             - Si no, fallback al formulario externo.
                                             Aquí siempre emitimos el botón con la info del registro;
                                             el handler JS decide el destino real. --}}
                                        @if($event->id_formulario)
                                            · <a href="#" data-action="view-event"
                                                 data-key="{{ $event->tabla_form }}"
                                                 data-id="{{ $event->id_formulario }}"
                                                 @if($meta['route'] && \Illuminate\Support\Facades\Route::has($meta['route']))
                                                    data-fallback="{{ route($meta['route']) }}"
                                                 @endif
                                                 style="color:var(--brand-primary-darker);"
                                                 title="{{ translate('Ver esta evaluación') }}">
                                                {{ translate('Ver evaluación') }}
                                                <i class="fas fa-eye" style="font-size:.7rem;"></i>
                                            </a>
                                        @elseif($meta['route'] && \Illuminate\Support\Facades\Route::has($meta['route']))
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
                <div style="font-size:.72rem; color:#adb5bd; margin-top:.15rem;">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ translate('Filtra por caso clínico usando el selector arriba del expediente.') }}
                </div>
            </div>
        </div>

        {{-- Launcher: grid de tipos para iniciar una nueva evaluación vinculada al caso activo --}}
        <div id="eval-launcher" class="eval-launcher" style="display:none;"></div>

        <div id="eval-sections">
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                {{ translate('Cargando evaluaciones...') }}
            </div>
        </div>

    </div> {{-- /tab-evaluacion --}}

    {{-- =========================== TAB EVOLUCIÓN (Fase 11) =========================== --}}
    <div class="tab-pane fade" id="tab-evolucion" role="tabpanel">
        <div class="evol-header">
            <div class="evol-header-text">
                <h4><i class="fas fa-chart-line mr-1"></i> {{ translate('Evolución del paciente') }}</h4>
                <div class="evol-subtitle">
                    {{ translate('Visualización de progreso a través de las evaluaciones registradas. Solo se muestran los tipos con 2 o más evaluaciones.') }}
                </div>
            </div>
            <div class="evol-legend">
                <span class="evol-legend-item"><span class="evol-legend-dot up"></span> mejora</span>
                <span class="evol-legend-item"><span class="evol-legend-dot down"></span> retroceso</span>
                <span class="evol-legend-item"><span class="evol-legend-dot none"></span> sin cambio</span>
            </div>
        </div>

        <div id="evolGrid" class="evol-grid">
            <div class="empty-state">
                <i class="fas fa-chart-line"></i>
                <span>{{ translate('Cargando gráficos de evolución…') }}</span>
            </div>
        </div>
    </div> {{-- /tab-evolucion --}}

    {{-- =========================== TAB ADJUNTOS (Fase 15) =========================== --}}
    <div class="tab-pane fade" id="tab-adjuntos" role="tabpanel">

        <div class="adj-header">
            <div>
                <h5><i class="fas fa-paperclip mr-1"></i> {{ translate('Adjuntos') }}</h5>
                <small class="text-muted" id="adj-summary">{{ translate('Cargando...') }}</small>
                <div style="font-size:.72rem; color:#adb5bd; margin-top:.15rem;">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ translate('Los archivos se vinculan al caso clínico activo. Filtra desde el selector arriba.') }}
                </div>
            </div>
        </div>

        {{-- Zona drop + acciones --}}
        <div id="adj-dropzone" class="adj-dropzone">
            <div class="adj-dropzone-title">
                <i class="fas fa-cloud-upload-alt mr-1"></i> {{ translate('Sube exámenes, fotos clínicas, documentos o recetas') }}
            </div>
            <div class="adj-dropzone-hint">
                {{ translate('Arrastra archivos aquí o usa los botones de abajo. JPG, PNG, PDF, DOC, DOCX. Máx 20 MB por archivo.') }}
            </div>
            <div class="adj-actions">
                {{-- En tablet/móvil, capture="environment" abre directo la cámara trasera --}}
                <button type="button" class="adj-btn-action is-primary" data-action="adj-camera">
                    <i class="fas fa-camera"></i> {{ translate('Tomar foto') }}
                </button>
                <button type="button" class="adj-btn-action" data-action="adj-pick">
                    <i class="fas fa-folder-open"></i> {{ translate('Subir archivo') }}
                </button>
            </div>
            {{-- Inputs ocultos para el picker y la cámara --}}
            <input type="file" id="adjFileInput" multiple
                   accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt"
                   style="display:none;">
            <input type="file" id="adjCameraInput" accept="image/*" capture="environment"
                   style="display:none;">
        </div>

        {{-- Cuota --}}
        <div id="adj-quota" class="adj-quota" style="display:none;">
            <i class="fas fa-database"></i>
            <span class="adj-quota-text">—</span>
            <div class="adj-quota-bar"><div class="adj-quota-bar-fill" style="width:0%"></div></div>
        </div>

        {{-- Filtros por categoría --}}
        <div id="adj-filters" class="adj-filters"></div>

        {{-- Grid de adjuntos --}}
        <div id="adj-grid" class="adj-grid">
            <div class="adj-empty">
                <i class="fas fa-spinner fa-spin"></i>
                {{ translate('Cargando adjuntos…') }}
            </div>
        </div>

    </div> {{-- /tab-adjuntos --}}

    {{-- =========================== TAB MENSAJES (Fase 9a) =========================== --}}
    <div class="tab-pane fade" id="tab-mensajes" role="tabpanel">
        <div class="msg-tab-header">
            <div class="msg-tab-title-block">
                <h4><i class="fab fa-whatsapp mr-1"></i> Mensajes con {{ explode(' ', trim($patient->full_name))[0] ?? '' }}</h4>
                <div class="msg-tab-subtitle">
                    @if($patient->phone_no)
                        Teléfono: <strong>{{ $patient->phone_no }}</strong>
                    @else
                        <span class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Sin teléfono registrado — edita el perfil para agregarlo.</span>
                    @endif
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="btnAbrirEnvioMsg" {{ empty($patient->phone_no) ? 'disabled' : '' }}>
                <i class="fas fa-paper-plane mr-1"></i> {{ translate('Enviar mensaje') }}
            </button>
        </div>

        <div class="msg-provider-hint" id="msgProviderHint">
            {{-- inyectado por JS según el provider activo --}}
        </div>

        <div id="msgList" class="msg-list">
            <div class="empty-state">
                <i class="fas fa-comment-dots"></i>
                {{ translate('Cargando mensajes…') }}
            </div>
        </div>
    </div> {{-- /tab-mensajes --}}

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
                <div class="modal-header inline-eval-header">
                    <h5 class="modal-title" id="modalEvalInlineTitle">{{ translate('Nueva evaluación') }}</h5>
                    {{-- Fase 10 — Botón de plantillas (dropdown) --}}
                    <div class="eval-tpl-dropdown dropdown ml-auto mr-2">
                        <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle eval-tpl-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-clone mr-1"></i> {{ translate('Plantillas') }}
                        </button>
                        <div class="dropdown-menu dropdown-menu-right eval-tpl-menu" id="evalTplMenu">
                            <div class="eval-tpl-menu-section" id="evalTplList">
                                <div class="dropdown-header"><i class="fas fa-spinner fa-spin mr-1"></i> Cargando…</div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="btnSaveAsTemplate">
                                <i class="fas fa-bookmark mr-1"></i> {{ translate('Guardar como plantilla') }}
                            </a>
                        </div>
                    </div>
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

{{-- ============== Fase 9a — Modal de envío de mensaje ============== --}}
<div class="modal fade msg-send-modal" id="modalSendMsg" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formSendMsg" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fab fa-whatsapp mr-1" style="color:#25D366;"></i>
                        {{ translate('Enviar mensaje a') }} <span id="msgToName">—</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    {{-- Selección de plantilla --}}
                    <div class="msg-template-grid" id="msgTemplateGrid">
                        {{-- Inyectado por JS --}}
                    </div>

                    {{-- Variables editables --}}
                    <div class="msg-vars-row" id="msgVarsRow" style="display:none;">
                        <div class="form-group">
                            <label>{{ translate('Fecha (opcional)') }}</label>
                            <input type="text" id="msgVarFecha" class="form-control" placeholder="ej. 25 de mayo">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Hora (opcional)') }}</label>
                            <input type="text" id="msgVarHora" class="form-control" placeholder="ej. 10:00">
                        </div>
                    </div>

                    {{-- Vista previa / editor del mensaje --}}
                    <div class="msg-preview-wrap">
                        <label class="d-flex align-items-center justify-content-between mb-1">
                            <span><i class="fas fa-eye mr-1"></i> {{ translate('Vista previa (editable)') }}</span>
                            <span class="msg-char-count"><span id="msgCharCount">0</span> caracteres</span>
                        </label>
                        <textarea id="msgBody" class="form-control msg-preview-textarea" rows="9" placeholder="{{ translate('Selecciona una plantilla o escribe un mensaje libre…') }}"></textarea>
                    </div>

                    <div class="msg-helpers">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ translate('Variables disponibles:') }}
                        <code>{paciente}</code>, <code>{clinic_name}</code>, <code>{fecha}</code>, <code>{hora}</code>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="msg-provider-badge" id="msgProviderBadge"></span>
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{ translate('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnEnviarMsg">
                        <i class="fas fa-paper-plane mr-1"></i> {{ translate('Enviar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============== Quick-add — Modal de nueva ficha clínica ============== --}}
<div class="modal fade new-case-modal" id="modalNewCase" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formNewCase" autocomplete="off">
                @csrf
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                <div class="modal-header new-case-header">
                    <h5 class="modal-title">
                        <i class="fas fa-folder-plus mr-1" style="color:var(--brand-primary, #9F93E7);"></i>
                        {{ translate('Nueva ficha clínica') }} —
                        <span class="text-muted" style="font-weight:400; font-size:.92rem;">{{ $patient->full_name }}</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body new-case-body">
                    <div class="new-case-intro">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ translate('Solo el motivo y diagnóstico son recomendados al iniciar. El resto puedes completarlo después con más tiempo.') }}
                    </div>

                    {{-- ====== Sección 1: Identificación clínica (visible) ====== --}}
                    <div class="nc-section nc-section-main">
                        <div class="nc-section-title">
                            <i class="fas fa-clipboard-list mr-1"></i> {{ translate('Identificación del caso') }}
                            <span class="text-muted" style="font-size:.72rem; margin-left:.4rem;">{{ translate('(recomendado)') }}</span>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Motivo de consulta') }} <span class="text-danger">*</span></label>
                            <textarea name="motivo_consulta" class="form-control" rows="2" placeholder="¿Por qué viene? Síntomas principales, cuándo iniciaron, etc."></textarea>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Diagnóstico inicial') }}</label>
                            <input type="text" name="diagnostico" class="form-control" placeholder="Ej. Tendinopatía del manguito rotador">
                        </div>
                    </div>

                    {{-- ====== Acordeón de secciones opcionales ====== --}}
                    <div class="nc-accordion" id="newCaseAccordion">

                        {{-- Antecedentes médicos --}}
                        <div class="nc-card">
                            <div class="nc-card-header" data-toggle="collapse" data-target="#nc-antecedentes">
                                <i class="fas fa-notes-medical mr-2"></i>
                                <span>{{ translate('Antecedentes médicos relevantes') }}</span>
                                <i class="fas fa-chevron-down nc-card-chevron"></i>
                            </div>
                            <div id="nc-antecedentes" class="collapse">
                                <div class="nc-card-body">
                                    <div class="form-group">
                                        <label>{{ translate('Historial médico') }}</label>
                                        <textarea name="historial_medico" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Enfermedades crónicas') }}</label>
                                        <textarea name="enfermedades_cronicas" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Cirugías previas') }}</label>
                                        <textarea name="cirugias_previas" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Medicamentos actuales') }}</label>
                                        <textarea name="medicamentos_actuales" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Alergias') }}</label>
                                        <textarea name="alergias" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Historia de la lesión --}}
                        <div class="nc-card">
                            <div class="nc-card-header" data-toggle="collapse" data-target="#nc-lesion">
                                <i class="fas fa-heart-broken mr-2"></i>
                                <span>{{ translate('Historia de la lesión o condición') }}</span>
                                <i class="fas fa-chevron-down nc-card-chevron"></i>
                            </div>
                            <div id="nc-lesion" class="collapse">
                                <div class="nc-card-body">
                                    <div class="form-row">
                                        <div class="col-md-4 form-group">
                                            <label>{{ translate('Fecha de inicio') }}</label>
                                            <input type="date" name="fecha_inicio" class="form-control" max="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="col-md-8 form-group">
                                            <label>{{ translate('Mecanismo de lesión / origen') }}</label>
                                            <textarea name="mecanismo_lesion_origen" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Evolución de los síntomas') }}</label>
                                        <textarea name="evolucion_sintomas" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Tratamientos previos') }}</label>
                                        <textarea name="tratamientos_previos" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Evaluación fisioterapéutica --}}
                        <div class="nc-card">
                            <div class="nc-card-header" data-toggle="collapse" data-target="#nc-eval">
                                <i class="fas fa-walking mr-2"></i>
                                <span>{{ translate('Evaluación fisioterapéutica inicial') }}</span>
                                <i class="fas fa-chevron-down nc-card-chevron"></i>
                            </div>
                            <div id="nc-eval" class="collapse">
                                <div class="nc-card-body">
                                    <p class="text-muted" style="font-size:.78rem;">{{ translate('Observación') }}:</p>
                                    <div class="form-group">
                                        <label>{{ translate('Marcha') }}</label>
                                        <textarea name="observacion_marcha" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Otras observaciones') }}</label>
                                        <textarea name="observacion_otros" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Diagnóstico fisioterapéutico') }}</label>
                                        <textarea name="diagnostico_fisioterapeutico" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Objetivos --}}
                        <div class="nc-card">
                            <div class="nc-card-header" data-toggle="collapse" data-target="#nc-objetivos">
                                <i class="fas fa-bullseye mr-2"></i>
                                <span>{{ translate('Objetivos del tratamiento') }}</span>
                                <i class="fas fa-chevron-down nc-card-chevron"></i>
                            </div>
                            <div id="nc-objetivos" class="collapse">
                                <div class="nc-card-body">
                                    <div class="form-group">
                                        <label>{{ translate('Corto plazo') }}</label>
                                        <textarea name="corto_plazo" class="form-control" rows="2" placeholder="Primeras 2-3 semanas"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Mediano plazo') }}</label>
                                        <textarea name="mediano_plazo" class="form-control" rows="2" placeholder="1-2 meses"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Largo plazo') }}</label>
                                        <textarea name="largo_plazo" class="form-control" rows="2" placeholder="3+ meses"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Plan de tratamiento --}}
                        <div class="nc-card">
                            <div class="nc-card-header" data-toggle="collapse" data-target="#nc-plan">
                                <i class="fas fa-list-check mr-2"></i>
                                <span>{{ translate('Plan de tratamiento') }}</span>
                                <i class="fas fa-chevron-down nc-card-chevron"></i>
                            </div>
                            <div id="nc-plan" class="collapse">
                                <div class="nc-card-body">
                                    <p class="text-muted" style="font-size:.78rem;">{{ translate('Modalidades:') }}</p>
                                    <div class="nc-modalities">
                                        @php
                                            $mods = [
                                                'modalidades_ejercicio_terapeutico' => ['icon' => 'fa-dumbbell',          'label' => 'Ejercicio terapéutico'],
                                                'modalidades_electroterapia'        => ['icon' => 'fa-bolt',              'label' => 'Electroterapia'],
                                                'modalidades_masoterapia'           => ['icon' => 'fa-hands',             'label' => 'Masoterapia'],
                                                'modalidades_estiramientos'         => ['icon' => 'fa-stretching-figure', 'label' => 'Estiramientos'],
                                                'modalidades_tecaterapia'           => ['icon' => 'fa-wave-square',       'label' => 'Tecarterapia'],
                                                'modalidades_puncion_seca'          => ['icon' => 'fa-syringe',           'label' => 'Punción seca'],
                                                'modalidades_electropuncion'        => ['icon' => 'fa-charging-station',  'label' => 'Electropunción'],
                                            ];
                                        @endphp
                                        @foreach($mods as $name => $m)
                                            <label class="nc-modality-chip">
                                                <input type="hidden" name="{{ $name }}" value="0">
                                                <input type="checkbox" name="{{ $name }}" value="1">
                                                <span class="nc-chip-content">
                                                    <i class="fas {{ $m['icon'] }}"></i>
                                                    <span>{{ $m['label'] }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Otros tratamientos') }}</label>
                                        <textarea name="modalidades_otros" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-row">
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Frecuencia (veces/semana)') }}</label>
                                            <input type="number" name="frecuencia_semana" class="form-control" value="1" min="1" max="7">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Duración estimada (semanas)') }}</label>
                                            <input type="number" name="duracion_semanas" class="form-control" value="10" min="1" max="104">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer new-case-footer">
                    <span class="new-case-hint text-muted" style="font-size:.75rem; margin-right:auto;">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ translate('La ficha se creará y quedará activa automáticamente') }}
                    </span>
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{ translate('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSaveNewCase">
                        <i class="fas fa-save mr-1"></i> {{ translate('Crear ficha clínica') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============== Modal: Eliminar caso clínico (borrado lógico en cascada) ============== --}}
@if(isset($fichaCompleta) && $fichaCompleta)
<div class="modal fade" id="modalDeleteCaso" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#fdf2f2; border-bottom:1px solid #f5c6cb;">
                <h5 class="modal-title" style="color:#a32a37;">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ translate('Eliminar caso clínico') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom:.6rem;">
                    {{ translate('Vas a eliminar el caso') }}
                    <strong>"{{ $decoder(trim($fichaCompleta->diagnostico)) ?: ('Ficha #' . $fichaCompleta->id) }}"</strong>.
                </p>
                <div style="background:#fdf2f2; border:1px solid #f5c6cb; border-radius:.4rem; padding:.6rem .8rem; margin-bottom:.8rem; font-size:.86rem;">
                    <div style="font-weight:600; color:#a32a37; margin-bottom:.3rem;">{{ translate('Se desactivarán también:') }}</div>
                    <ul style="margin:0; padding-left:1.2rem; color:#6c4145;">
                        <li>{{ $casoEvalCount }} {{ $casoEvalCount === 1 ? translate('evaluación') : translate('evaluaciones') }}</li>
                        <li>{{ $casoSesCount }} {{ $casoSesCount === 1 ? translate('sesión') : translate('sesiones') }}</li>
                        <li>{{ $adjuntosCount }} {{ $adjuntosCount === 1 ? translate('adjunto') : translate('adjuntos') }}</li>
                    </ul>
                </div>
                <p style="font-size:.82rem; color:#6c757d; margin-bottom:.7rem;">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ translate('El caso dejará de aparecer en el sistema. Esta acción solo puede revertirse desde soporte técnico.') }}
                </p>
                <div class="form-group" style="margin-bottom:0;">
                    <label style="font-size:.82rem; font-weight:600;">
                        {{ translate('Para confirmar, escribe') }} <span style="color:#a32a37;">ELIMINAR</span>
                    </label>
                    <input type="text" id="deleteCasoConfirm" class="form-control" autocomplete="off"
                           placeholder="ELIMINAR" style="min-height:42px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{ translate('Cancelar') }}</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnConfirmDeleteCaso"
                        data-ficha-id="{{ $fichaCompleta->id }}" disabled>
                    <i class="fas fa-trash-alt mr-1"></i> {{ translate('Sí, eliminar caso') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============== Modal: Cerrar caso clínico (dar de alta) ============== --}}
<div class="modal fade" id="modalCloseCaso" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#eef9f0; border-bottom:1px solid #c6e9cb;">
                <h5 class="modal-title" style="color:#1d7d2c;">
                    <i class="fas fa-check-circle mr-1"></i> {{ translate('Cerrar caso clínico') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom:.8rem; font-size:.9rem;">
                    {{ translate('Vas a dar de alta el caso') }}
                    <strong>"{{ $decoder(trim($fichaCompleta->diagnostico)) ?: ('Ficha #' . $fichaCompleta->id) }}"</strong>.
                    {{ translate('El historial se conserva; el caso quedará marcado como cerrado y podrás reabrirlo si es necesario.') }}
                </p>
                <div class="form-group">
                    <label style="font-size:.82rem; font-weight:600;">{{ translate('Fecha de alta') }}</label>
                    <input type="text" id="closeCasoFecha" class="form-control"
                           inputmode="numeric" maxlength="10" placeholder="dd/mm/aaaa"
                           value="{{ now()->format('d/m/Y') }}" style="min-height:42px;">
                    <small class="text-muted" style="font-size:.72rem;">{{ translate('Formato') }}: dd/mm/aaaa</small>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label style="font-size:.82rem; font-weight:600;">{{ translate('Observaciones de cierre / finalización') }}</label>
                    <textarea id="closeCasoObs" class="form-control" rows="3"
                              placeholder="{{ translate('Resumen de evolución, resultado del tratamiento, recomendaciones al paciente, etc.') }}">{{ $decoder($fichaCompleta->observaciones_cierre) }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{ translate('Cancelar') }}</button>
                <button type="button" class="btn btn-success btn-sm" id="btnConfirmCloseCaso"
                        data-ficha-id="{{ $fichaCompleta->id }}">
                    <i class="fas fa-check-circle mr-1"></i> {{ translate('Cerrar caso') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ============== Fase 10 — Modal para guardar plantilla de evaluación ============== --}}
<div class="modal fade eval-tpl-save-modal" id="modalSaveEvalTpl" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formSaveEvalTpl" autocomplete="off">
                <input type="hidden" id="saveTplId" value="">
                <input type="hidden" id="saveTplTabla" value="">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bookmark mr-1" style="color:var(--brand-primary, #9F93E7);"></i>
                        <span id="saveTplModalTitle">{{ translate('Guardar como plantilla') }}</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ translate('Nombre') }}<span style="color:#dc3545;"> *</span></label>
                        <input type="text" id="saveTplName" class="form-control" required maxlength="191" placeholder="Ej. Hombro doloroso fase 1">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Descripción (opcional)') }}</label>
                        <textarea id="saveTplDescription" class="form-control" rows="2" maxlength="1000" placeholder="Para qué tipo de paciente, observaciones, etc."></textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Visibilidad') }}</label>
                        <div class="tpl-scope-options">
                            <label class="tpl-scope-option">
                                <input type="radio" name="saveTplScope" value="personal" checked>
                                <div class="tpl-scope-card">
                                    <i class="fas fa-user mr-1"></i>
                                    <strong>{{ translate('Personal') }}</strong>
                                    <div class="tpl-scope-desc">Solo tú la verás</div>
                                </div>
                            </label>
                            <label class="tpl-scope-option">
                                <input type="radio" name="saveTplScope" value="global">
                                <div class="tpl-scope-card">
                                    <i class="fas fa-users mr-1"></i>
                                    <strong>{{ translate('Global') }}</strong>
                                    <div class="tpl-scope-desc">Disponible para todo el equipo</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{ translate('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save mr-1"></i> {{ translate('Guardar plantilla') }}
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

{{-- =============== MODAL UPLOAD DE ADJUNTOS (Fase 15) =============== --}}
<div class="modal fade adj-upload-modal" id="modalAdjUpload" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formAdjUpload" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cloud-upload-alt mr-1"></i>
                        {{ translate('Subir adjuntos') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{-- Cola de archivos seleccionados --}}
                    <div class="upload-queue" id="adjUploadQueue"></div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label style="font-size:.78rem; color:#495057; font-weight:600; text-transform:uppercase;">
                                {{ translate('Categoría') }}
                            </label>
                            <select id="adjUploadCategoria" class="form-control form-control-sm">
                                <option value="examenes">🩻 Exámenes (RX, RMN, lab)</option>
                                <option value="fotos_clinicas">📸 Fotos clínicas</option>
                                <option value="documentos">📄 Documentos médicos</option>
                                <option value="recetas">💊 Recetas</option>
                                <option value="otros" selected>📎 Otros</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label style="font-size:.78rem; color:#495057; font-weight:600; text-transform:uppercase;">
                                {{ translate('Vincular a caso clínico') }}
                            </label>
                            <select id="adjUploadFichaId" class="form-control form-control-sm">
                                <option value="">— {{ translate('General del paciente (sin caso)') }} —</option>
                                {{-- Resto se llena dinámicamente en JS --}}
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="font-size:.78rem; color:#495057; font-weight:600; text-transform:uppercase;">
                            {{ translate('Descripción') }} <span class="text-muted" style="text-transform:none; font-weight:400;">({{ translate('opcional, se aplica a todos los archivos del lote') }})</span>
                        </label>
                        <textarea id="adjUploadDescripcion" class="form-control form-control-sm" rows="2"
                                  placeholder="{{ translate('Ej. Radiografía AP de columna lumbar — Dr. Pérez 25/05/2026') }}"></textarea>
                    </div>

                    <div class="progress" style="display:none;" id="adjUploadProgressWrap">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                             id="adjUploadProgress" style="width:0%"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
                        {{ translate('Cancelar') }}
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnAdjUploadConfirm">
                        <i class="fas fa-upload mr-1"></i> {{ translate('Subir') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =============== MODAL PREVIEW DE ADJUNTO (Fase 15) =============== --}}
<div class="modal fade adj-preview-modal" id="modalAdjPreview" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adjPreviewTitle">—</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="adjPreviewBody">
                {{-- Inyectado por JS: <img> o <iframe> según tipo --}}
            </div>
            <div class="modal-footer">
                <span class="text-muted mr-auto" id="adjPreviewMeta" style="font-size:.78rem;"></span>
                <a href="#" id="adjPreviewDownload" class="btn btn-default btn-sm" target="_blank">
                    <i class="fas fa-download mr-1"></i> {{ translate('Descargar') }}
                </a>
                <button type="button" class="btn btn-danger btn-sm" id="btnAdjPreviewDelete">
                    <i class="fas fa-trash mr-1"></i> {{ translate('Eliminar') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
