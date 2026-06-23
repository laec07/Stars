@extends('layouts.app')
@section('content')

@push("adminCss")
<style>
    .cc-wrap { padding: 0 .25rem; }

    .cc-header { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem 1rem; margin-bottom:1rem; }
    .cc-header h4 { margin:0; display:flex; align-items:center; gap:.5rem; }
    .cc-header h4 i { color: var(--brand-primary, #9F93E7); }
    .cc-header .cc-sub { font-size:.82rem; color:#6c757d; }

    /* Resumen de conteos */
    .cc-stats { display:flex; gap:.5rem; margin-left:auto; flex-wrap:wrap; }
    .cc-pill {
        font-size:.78rem; font-weight:600; padding:.3rem .75rem; border-radius:1rem;
        border:1px solid #e9ecef; background:#fff; color:#495057;
    }
    .cc-pill.open   { background:#e3f6e6; color:#1d7d2c; border-color:#bfe9c6; }
    .cc-pill.closed { background:#f1f3f5; color:#6c757d; }

    /* Toolbar de filtros */
    .cc-toolbar {
        display:flex; flex-wrap:wrap; gap:.6rem; align-items:center;
        background:#fff; border:1px solid #e9ecef; border-radius:.5rem;
        padding:.7rem .85rem; margin-bottom:1rem;
    }
    .cc-search {
        flex:1; min-width:220px; position:relative;
    }
    .cc-search i {
        position:absolute; left:.7rem; top:50%; transform:translateY(-50%);
        color:#adb5bd; font-size:.85rem;
    }
    .cc-search input {
        width:100%; padding:.5rem .7rem .5rem 2rem;
        border:1px solid #e9ecef; border-radius:.4rem; font-size:.88rem;
        min-height:42px;
    }
    .cc-toolbar select {
        border:1px solid #e9ecef; border-radius:.4rem; font-size:.85rem;
        padding:.45rem .6rem; min-height:42px; background:#fff; min-width:140px;
    }

    /* Tabla */
    .cc-table-wrap { background:#fff; border:1px solid #e9ecef; border-radius:.5rem; overflow:hidden; }
    table.cc-table { width:100%; border-collapse:collapse; }
    table.cc-table thead th {
        text-align:left; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em;
        color:#6c757d; font-weight:600; padding:.7rem .9rem; border-bottom:2px solid #f1f3f5;
        background:#fafbfc; white-space:nowrap;
    }
    table.cc-table tbody td { padding:.7rem .9rem; border-bottom:1px solid #f8f9fa; font-size:.88rem; vertical-align:middle; }
    table.cc-table tbody tr { cursor:pointer; transition:background .12s; }
    table.cc-table tbody tr:hover { background:rgba(159,147,231,.07); }
    table.cc-table tbody tr:last-child td { border-bottom:none; }

    .cc-patient { font-weight:600; color:#212529; }
    .cc-diag { color:#495057; }
    .cc-diag .cc-motivo { display:block; font-size:.74rem; color:#adb5bd; margin-top:.1rem; }
    .cc-fisio { color:#6c757d; font-size:.83rem; }
    .cc-date { color:#6c757d; font-size:.83rem; white-space:nowrap; }

    .cc-badge {
        font-size:.72rem; font-weight:600; padding:.15rem .6rem; border-radius:1rem; white-space:nowrap;
    }
    .cc-badge.open   { background:#e3f6e6; color:#1d7d2c; }
    .cc-badge.closed { background:#f1f3f5; color:#868e96; }

    .cc-counts { font-size:.78rem; color:#6c757d; white-space:nowrap; }
    .cc-counts .cc-c { display:inline-flex; align-items:center; gap:.25rem; margin-right:.6rem; }
    .cc-counts i { color:#adb5bd; }

    .cc-go { color:var(--brand-primary-darker, #5e4fbf); text-align:right; }

    .cc-empty { text-align:center; padding:3rem 1rem; color:#adb5bd; }
    .cc-empty i { font-size:2rem; display:block; margin-bottom:.6rem; opacity:.5; }

    .cc-norows { display:none; text-align:center; padding:2rem 1rem; color:#adb5bd; font-style:italic; }

    @media (max-width: 768px) {
        .cc-hide-sm { display:none; }
        .cc-stats { width:100%; margin-left:0; }
    }
</style>
@endpush

<div class="page-inner">
    <div class="cc-wrap">

        <div class="cc-header">
            <div>
                <h4><i class="fas fa-folder-open"></i> {{ translate('Casos clínicos') }}</h4>
                <div class="cc-sub">{{ translate('Todas las fichas clínicas. Haz clic en un caso para abrir el expediente del paciente.') }}</div>
            </div>
            <div class="cc-stats">
                <span class="cc-pill open">{{ $totalAbiertos }} {{ translate('abiertos') }}</span>
                <span class="cc-pill closed">{{ $totalCerrados }} {{ translate('cerrados') }}</span>
                <span class="cc-pill">{{ $casos->count() }} {{ translate('total') }}</span>
            </div>
        </div>

        @if($casos->isEmpty())
            <div class="cc-table-wrap">
                <div class="cc-empty">
                    <i class="far fa-folder"></i>
                    {{ translate('Aún no hay fichas clínicas registradas.') }}
                    <div style="font-size:.8rem; margin-top:.5rem;">
                        {{ translate('Las fichas se crean desde el expediente de cada paciente.') }}
                    </div>
                </div>
            </div>
        @else
            <div class="cc-toolbar">
                <div class="cc-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="ccSearch" placeholder="{{ translate('Buscar por paciente o diagnóstico…') }}">
                </div>
                <select id="ccEstado">
                    <option value="">{{ translate('Todos los estados') }}</option>
                    <option value="abierto">{{ translate('Abiertos') }}</option>
                    <option value="cerrado">{{ translate('Cerrados') }}</option>
                </select>
                @if($fisios->count() > 1)
                    <select id="ccFisio">
                        <option value="">{{ translate('Todos los fisioterapeutas') }}</option>
                        @foreach($fisios as $f)
                            <option value="{{ $f }}">{{ $f }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="cc-table-wrap">
                <table class="cc-table" id="ccTable">
                    <thead>
                        <tr>
                            <th>{{ translate('Paciente') }}</th>
                            <th>{{ translate('Diagnóstico') }}</th>
                            <th class="cc-hide-sm">{{ translate('Fisioterapeuta') }}</th>
                            <th class="cc-hide-sm">{{ translate('Inicio') }}</th>
                            <th>{{ translate('Estado') }}</th>
                            <th class="cc-hide-sm">{{ translate('Actividad') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($casos as $c)
                            @php
                                $diag = trim($c->diagnostico) ?: '';
                                $motivo = trim($c->motivo_consulta) ?: '';
                                $titulo = $diag ?: ($motivo ?: 'Ficha #' . $c->id);
                                $fisio = $c->fisio_name ?: '—';
                                $haystack = mb_strtolower(trim($c->patient_name . ' ' . $diag . ' ' . $motivo));
                                $url = url('patient-summary/' . $c->patient_id) . '?caso=' . $c->id;
                            @endphp
                            <tr data-href="{{ $url }}"
                                data-estado="{{ $c->estado }}"
                                data-fisio="{{ $fisio }}"
                                data-search="{{ $haystack }}">
                                <td><span class="cc-patient">{{ $c->patient_name }}</span></td>
                                <td class="cc-diag">
                                    {{ $titulo }}
                                    @if($diag && $motivo)
                                        <span class="cc-motivo">{{ \Illuminate\Support\Str::limit($motivo, 70) }}</span>
                                    @endif
                                </td>
                                <td class="cc-hide-sm cc-fisio">{{ $fisio }}</td>
                                <td class="cc-hide-sm cc-date">
                                    {{ $c->fecha ? \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') : '—' }}
                                </td>
                                <td>
                                    @if($c->estado === 'abierto')
                                        <span class="cc-badge open">{{ translate('Abierto') }}</span>
                                    @else
                                        <span class="cc-badge closed">{{ translate('Cerrado') }}</span>
                                    @endif
                                </td>
                                <td class="cc-hide-sm cc-counts">
                                    <span class="cc-c" title="{{ translate('Evaluaciones') }}"><i class="fas fa-clipboard-list"></i> {{ (int) $c->eval_count }}</span>
                                    <span class="cc-c" title="{{ translate('Sesiones') }}"><i class="fas fa-notes-medical"></i> {{ (int) $c->ses_count }}</span>
                                </td>
                                <td class="cc-go"><i class="fas fa-arrow-right"></i></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="cc-norows" id="ccNoRows">
                    {{ translate('No hay casos que coincidan con los filtros.') }}
                </div>
            </div>
        @endif

    </div>
</div>

@push("adminScripts")
<script>
(function () {
    "use strict";
    var $search = document.getElementById('ccSearch');
    var $estado = document.getElementById('ccEstado');
    var $fisio  = document.getElementById('ccFisio');
    var table   = document.getElementById('ccTable');
    if (!table) return;
    var rows    = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
    var noRows  = document.getElementById('ccNoRows');

    // Navegar al expediente al hacer clic en una fila
    rows.forEach(function (tr) {
        tr.addEventListener('click', function () {
            var href = tr.getAttribute('data-href');
            if (href) window.location.href = href;
        });
    });

    function applyFilters() {
        var q   = ($search && $search.value || '').trim().toLowerCase();
        var est = ($estado && $estado.value) || '';
        var fis = ($fisio && $fisio.value) || '';
        var visible = 0;

        rows.forEach(function (tr) {
            var okSearch = !q   || (tr.getAttribute('data-search') || '').indexOf(q) !== -1;
            var okEstado = !est || tr.getAttribute('data-estado') === est;
            var okFisio  = !fis || tr.getAttribute('data-fisio') === fis;
            var show = okSearch && okEstado && okFisio;
            tr.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noRows) noRows.style.display = visible === 0 ? 'block' : 'none';
    }

    if ($search) $search.addEventListener('input', applyFilters);
    if ($estado) $estado.addEventListener('change', applyFilters);
    if ($fisio)  $fisio.addEventListener('change', applyFilters);
})();
</script>
@endpush

@endsection
