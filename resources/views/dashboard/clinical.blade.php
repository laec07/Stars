@extends('layouts.app')
@section('content')

@push("adminCss")
<style>
    /* ============= Fase 7 — Dashboard clínico ============= */

    .clinical-dashboard {
        padding: 1rem .5rem;
    }

    /* Header con saludo */
    .clinical-greeting {
        margin-bottom: 1.5rem;
    }
    .clinical-greeting h2 {
        font-family: var(--brand-font-display, Georgia, serif);
        color: var(--brand-neutral, #2F4157);
        font-size: 1.85rem;
        margin: 0 0 .25rem 0;
    }
    .clinical-greeting .greeting-sub {
        color: var(--brand-text-muted, #5a6c80);
        font-size: .92rem;
    }

    /* KPI grid */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .kpi-card {
        background: #fff;
        border-radius: .5rem;
        padding: 1.1rem 1.2rem;
        box-shadow: var(--brand-shadow-sm, 0 1px 3px rgba(47,65,87,.08));
        border-left: 4px solid var(--brand-primary, #9F93E7);
        display: flex;
        align-items: center;
        gap: .9rem;
        transition: transform .12s ease, box-shadow .12s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--brand-shadow-md, 0 4px 12px rgba(47,65,87,.10));
    }
    .kpi-icon {
        width: 48px; height: 48px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
        background: rgba(159, 147, 231, .15);
        color: var(--brand-primary-darker, #5e4fbf);
    }
    .kpi-icon.icon-success { background: rgba(49, 206, 54, .15); color: #1d7d2c; }
    .kpi-icon.icon-info    { background: rgba(199, 217, 229, .35); color: #1d5e6b; }
    .kpi-icon.icon-warning { background: rgba(255, 173, 70, .15); color: #996800; }

    .kpi-body { flex: 1; min-width: 0; }
    .kpi-value {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1;
        color: var(--brand-text, #2F4157);
        font-family: var(--brand-font-body);
    }
    .kpi-label {
        font-size: .78rem;
        color: var(--brand-text-muted, #5a6c80);
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-top: .2rem;
    }
    .kpi-sub {
        font-size: .72rem;
        color: var(--brand-text-muted, #5a6c80);
        margin-top: .15rem;
        opacity: .85;
    }

    /* Quick actions con toggle colapsable
       IMPORTANTE: prefijo cd- (clinical-dashboard) para no colisionar con
       Atlantis que usa .quick-actions para el dropdown de su topbar. */
    .cd-quick-actions-wrap {
        margin-bottom: 1.5rem;
    }
    .cd-quick-actions-toggle {
        background: transparent;
        border: none;
        color: var(--brand-text-muted, #5a6c80);
        font-size: .82rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: .3rem 0;
        margin-bottom: .55rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        user-select: none;
    }
    .cd-quick-actions-toggle:hover { color: var(--brand-primary-darker, #5e4fbf); }
    .cd-quick-actions-toggle .cd-quick-toggle-chevron {
        transition: transform .2s ease;
        font-size: .7rem;
    }
    .cd-quick-actions-toggle[aria-expanded="false"] .cd-quick-toggle-chevron {
        transform: rotate(180deg);
    }

    .cd-quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        overflow: hidden;
        transition: max-height .25s ease, opacity .2s ease, margin .25s ease;
        max-height: 200px;
        opacity: 1;
    }
    .cd-quick-actions.collapsed {
        max-height: 0;
        opacity: 0;
        margin: 0;
        gap: 0;
    }
    .cd-quick-action-btn {
        background: #fff;
        border: 1px solid rgba(159, 147, 231, .35);
        border-radius: .4rem;
        padding: .65rem 1rem;
        color: var(--brand-primary-darker, #5e4fbf);
        font-weight: 600;
        font-size: .88rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        transition: all .12s ease;
        cursor: pointer;
    }
    .cd-quick-action-btn:hover {
        background: var(--brand-primary, #9F93E7);
        color: #fff;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: var(--brand-shadow-sm);
    }

    /* Layout 2 columns */
    .clinical-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .clinical-card {
        background: #fff;
        border-radius: .5rem;
        padding: 1.2rem;
        box-shadow: var(--brand-shadow-sm);
        border: 1px solid #e9ecef;
    }
    .clinical-card-title {
        font-family: var(--brand-font-body);
        font-weight: 700;
        color: var(--brand-text, #2F4157);
        font-size: 1rem;
        margin: 0 0 .85rem 0;
        padding-bottom: .55rem;
        border-bottom: 2px solid rgba(159, 147, 231, .25);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .clinical-card-title i { color: var(--brand-primary, #9F93E7); margin-right: .35rem; }

    /* Chart container */
    .chart-container { position: relative; height: 240px; }

    /* Lists */
    .recent-list .recent-item {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .65rem 0;
        border-bottom: 1px solid #f1f3f5;
        font-size: .88rem;
    }
    .recent-list .recent-item:last-child { border-bottom: none; }
    .recent-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .78rem; flex-shrink: 0;
    }
    .recent-info { flex: 1; min-width: 0; }
    .recent-name {
        font-weight: 600;
        color: var(--brand-text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .recent-meta {
        font-size: .72rem;
        color: var(--brand-text-muted);
    }
    .recent-date {
        font-size: .76rem;
        color: var(--brand-primary-darker);
        font-weight: 600;
        flex-shrink: 0;
    }

    /* Alerts */
    .alerts-list { display: flex; flex-direction: column; gap: .55rem; }
    .alert-row {
        display: flex; align-items: center;
        gap: .7rem;
        padding: .65rem .8rem;
        border-radius: .4rem;
        font-size: .85rem;
    }
    .alert-row.alert-warning   { background: rgba(255, 173, 70, .12); border-left: 3px solid #ffad46; }
    .alert-row.alert-info      { background: rgba(199, 217, 229, .25); border-left: 3px solid #5bbfd6; }
    .alert-row.alert-secondary { background: rgba(108, 117, 125, .10); border-left: 3px solid #6c757d; }
    .alert-row .alert-icon { font-size: 1.05rem; }
    .alert-row .alert-content { flex: 1; }
    .alert-row .alert-count { font-weight: 700; font-size: .9rem; }
    .alert-row.alert-warning   .alert-icon { color: #996800; }
    .alert-row.alert-info      .alert-icon { color: #1d5e6b; }
    .alert-row.alert-secondary .alert-icon { color: #6c757d; }

    /* Top therapists */
    .top-therapist {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .5rem 0;
        border-bottom: 1px dashed #f1f3f5;
        font-size: .88rem;
    }
    .top-therapist:last-child { border-bottom: none; }
    .top-therapist-name { color: var(--brand-text); font-weight: 500; }
    .top-therapist-count {
        background: var(--brand-primary);
        color: #fff;
        font-weight: 700;
        padding: .15rem .65rem;
        border-radius: 1rem;
        font-size: .78rem;
    }

    /* Loading state */
    .dashboard-loading { text-align: center; padding: 3rem 1rem; color: #adb5bd; }
    .dashboard-loading i { font-size: 2rem; margin-bottom: .5rem; display: block; }

    /* Empty state */
    .empty-list { color: #adb5bd; font-style: italic; padding: 1rem 0; font-size: .85rem; text-align: center; }

    /* Responsive */
    @media (max-width: 992px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .clinical-row { grid-template-columns: 1fr; }
    }
    @media (max-width: 576px) {
        .kpi-grid { grid-template-columns: 1fr; }
        .kpi-card { padding: .9rem 1rem; }
        .kpi-value { font-size: 1.4rem; }
        .cd-quick-actions { flex-direction: column; }
        .cd-quick-action-btn { width: 100%; justify-content: center; }
    }
</style>
@endpush

<div class="page-inner clinical-dashboard">

    {{-- Saludo --}}
    <div class="clinical-greeting">
        <h2>
            @php
                $hora = (int) \Carbon\Carbon::now()->format('H');
                $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
            @endphp
            {{ $saludo }}, {{ auth()->user()->name ?? 'usuario' }}.
        </h2>
        <div class="greeting-sub">
            Resumen clínico — {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
        </div>
    </div>

    {{-- Quick actions con toggle para colapsar/expandir
         (clases cd-* para evitar colisión con .quick-actions de Atlantis) --}}
    <div class="cd-quick-actions-wrap">
        <button type="button" class="cd-quick-actions-toggle" id="quickActionsToggle" aria-expanded="true">
            <i class="fas fa-bolt"></i>
            <span>Accesos rápidos</span>
            <i class="fas fa-chevron-up cd-quick-toggle-chevron"></i>
        </button>
        <div class="cd-quick-actions" id="quickActionsPanel">
            <a href="{{ url('patient') }}" class="cd-quick-action-btn">
                <i class="fas fa-users"></i> Pacientes
            </a>
            <a href="{{ url('fis-ficha') }}" class="cd-quick-action-btn">
                <i class="fas fa-folder-open"></i> Fichas clínicas
            </a>
            <a href="{{ route('home') }}" class="cd-quick-action-btn">
                <i class="fas fa-calendar-alt"></i> Citas
            </a>
            <a href="{{ url('fis-evdolors') }}" class="cd-quick-action-btn">
                <i class="fas fa-stethoscope"></i> Evaluaciones
            </a>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="kpi-grid" id="kpiGrid">
        <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-calendar-day"></i></div><div class="kpi-body"><div class="kpi-value" data-kpi="sesiones_hoy">—</div><div class="kpi-label">Sesiones hoy</div></div></div>
        <div class="kpi-card"><div class="kpi-icon icon-info"><i class="fas fa-calendar-week"></i></div><div class="kpi-body"><div class="kpi-value" data-kpi="sesiones_semana">—</div><div class="kpi-label">Sesiones esta semana</div></div></div>
        <div class="kpi-card"><div class="kpi-icon icon-success"><i class="fas fa-users"></i></div><div class="kpi-body"><div class="kpi-value" data-kpi="pacientes_activos">—</div><div class="kpi-label">Pacientes activos</div><div class="kpi-sub" data-kpi-sub="pacientes_activos">últimos 30 días</div></div></div>
        <div class="kpi-card"><div class="kpi-icon icon-warning"><i class="fas fa-user-plus"></i></div><div class="kpi-body"><div class="kpi-value" data-kpi="pacientes_nuevos">—</div><div class="kpi-label">Nuevos este mes</div></div></div>
        <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-folder-open"></i></div><div class="kpi-body"><div class="kpi-value" data-kpi="fichas_abiertas">—</div><div class="kpi-label">Fichas abiertas</div></div></div>
        <div class="kpi-card"><div class="kpi-icon icon-info"><i class="fas fa-stethoscope"></i></div><div class="kpi-body"><div class="kpi-value" data-kpi="evaluaciones_mes">—</div><div class="kpi-label">Evaluaciones del mes</div></div></div>
        <div class="kpi-card"><div class="kpi-icon icon-success"><i class="fas fa-chart-line"></i></div><div class="kpi-body"><div class="kpi-value" data-kpi="sesiones_mes">—</div><div class="kpi-label">Sesiones del mes</div></div></div>
        <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-id-card"></i></div><div class="kpi-body"><div class="kpi-value" data-kpi="pacientes_totales">—</div><div class="kpi-label">Pacientes en BD</div></div></div>
    </div>

    {{-- Row 1: Sesiones por semana (chart) + Alertas --}}
    <div class="clinical-row">
        <div class="clinical-card">
            <div class="clinical-card-title">
                <span><i class="fas fa-chart-area"></i> Sesiones por semana</span>
                <span class="text-muted" style="font-size:.75rem; font-weight:400;">últimas 8 semanas</span>
            </div>
            <div class="chart-container">
                <canvas id="chartSessionsByWeek"></canvas>
            </div>
        </div>
        <div class="clinical-card">
            <div class="clinical-card-title"><span><i class="fas fa-bell"></i> Alertas</span></div>
            <div class="alerts-list" id="alertsList">
                <div class="empty-list">Cargando…</div>
            </div>
        </div>
    </div>

    {{-- Row 2: Sesiones recientes + Top fisios + Distribución evaluaciones --}}
    <div class="clinical-row" style="grid-template-columns: 1.4fr 1fr;">
        <div class="clinical-card">
            <div class="clinical-card-title"><span><i class="fas fa-history"></i> Sesiones recientes</span></div>
            <div class="recent-list" id="recentSessionsList">
                <div class="empty-list">Cargando…</div>
            </div>
        </div>
        <div class="clinical-card">
            <div class="clinical-card-title"><span><i class="fas fa-trophy"></i> Top fisioterapeutas del mes</span></div>
            <div id="topTherapistsList">
                <div class="empty-list">Cargando…</div>
            </div>
        </div>
    </div>

    {{-- Row 3: Distribución de evaluaciones --}}
    <div class="clinical-card">
        <div class="clinical-card-title">
            <span><i class="fas fa-tags"></i> Distribución de evaluaciones</span>
            <span class="text-muted" style="font-size:.75rem; font-weight:400;">últimos 90 días</span>
        </div>
        <div class="chart-container" style="height: 280px;">
            <canvas id="chartEvalTypes"></canvas>
        </div>
    </div>

</div>

@push("adminScripts")
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function ($) {
    'use strict';

    var sessionsChart = null;
    var evalTypesChart = null;

    function fmtNumber(n) {
        if (n == null) return '0';
        return new Intl.NumberFormat('es-GT').format(n);
    }

    function renderKpis(kpis) {
        if (!kpis) return;
        $('[data-kpi]').each(function () {
            var key = $(this).data('kpi');
            $(this).text(fmtNumber(kpis[key]));
        });
        // Sub-text dinámico
        if (kpis.pacientes_totales) {
            var pct = kpis.pacientes_totales > 0
                ? Math.round((kpis.pacientes_activos / kpis.pacientes_totales) * 100)
                : 0;
            $('[data-kpi-sub="pacientes_activos"]').text(
                'últimos 30 días · ' + pct + '% del total'
            );
        }
    }

    function renderSessionsByWeek(weeks) {
        if (!weeks || !weeks.length) return;
        var ctx = document.getElementById('chartSessionsByWeek');
        if (!ctx) return;
        if (sessionsChart) sessionsChart.destroy();

        sessionsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: weeks.map(function (w) { return w.label; }),
                datasets: [{
                    label: 'Sesiones',
                    data: weeks.map(function (w) { return w.count; }),
                    borderColor: '#9F93E7',
                    backgroundColor: 'rgba(159, 147, 231, .18)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#7d6fd6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#2F4157',
                        padding: 10,
                        titleFont: { weight: '600' },
                        callbacks: {
                            label: function (ctx) { return ctx.parsed.y + ' sesiones'; }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#5a6c80' } },
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 }, color: '#5a6c80' }, grid: { color: '#f1f3f5' } }
                }
            }
        });
    }

    function renderEvalTypes(types) {
        var ctx = document.getElementById('chartEvalTypes');
        if (!ctx) return;
        if (evalTypesChart) evalTypesChart.destroy();

        if (!types || !types.length) {
            $(ctx).replaceWith('<div class="empty-list">No hay evaluaciones registradas en los últimos 90 días.</div>');
            return;
        }

        var palette = ['#9F93E7', '#DFBEF4', '#C7D9E5', '#7d6fd6', '#5bbfd6', '#f29ab3',
                       '#f0c33c', '#7cbf3a', '#48abf7', '#ffad46', '#5a6c80'];

        evalTypesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: types.map(function (t) { return t.label; }),
                datasets: [{
                    data: types.map(function (t) { return t.count; }),
                    backgroundColor: types.map(function (_, i) { return palette[i % palette.length]; }),
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 11 }, color: '#2F4157', padding: 10, boxWidth: 14 }
                    },
                    tooltip: {
                        backgroundColor: '#2F4157',
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                                return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    function renderRecentSessions(sessions) {
        var $list = $('#recentSessionsList');
        if (!sessions || !sessions.length) {
            $list.html('<div class="empty-list">Sin sesiones registradas todavía.</div>');
            return;
        }
        var html = sessions.map(function (s) {
            var initials = (s.patient_name || '?')
                .split(/\s+/).filter(Boolean).slice(0, 2)
                .map(function (p) { return p.charAt(0); }).join('').toUpperCase();
            var meta = (s.user_name ? s.user_name : '') +
                       (s.tratamiento ? ' · ' + s.tratamiento : '');
            return '<a class="recent-item" href="' + JsManager.BaseUrl() + '/patient-summary/' + s.patient_id + '" style="text-decoration:none; color:inherit;">' +
                       '<div class="recent-avatar">' + initials + '</div>' +
                       '<div class="recent-info">' +
                           '<div class="recent-name">' + (s.patient_name || '—') + '</div>' +
                           '<div class="recent-meta">' + meta + '</div>' +
                       '</div>' +
                       '<div class="recent-date">' + s.fecha_label + '</div>' +
                   '</a>';
        }).join('');
        $list.html(html);
    }

    function renderAlerts(alerts) {
        var $list = $('#alertsList');
        if (!alerts || !alerts.length) {
            $list.html('<div class="empty-list"><i class="fas fa-check-circle" style="color:#31ce36; font-size:1.5rem;"></i><br>Todo en orden — sin alertas.</div>');
            return;
        }
        var html = alerts.map(function (a) {
            return '<div class="alert-row alert-' + (a.type || 'info') + '">' +
                       '<div class="alert-icon"><i class="fas ' + a.icon + '"></i></div>' +
                       '<div class="alert-content">' +
                           '<span class="alert-count">' + a.count + '</span> ' + a.msg +
                       '</div>' +
                   '</div>';
        }).join('');
        $list.html(html);
    }

    function renderTopTherapists(list) {
        var $list = $('#topTherapistsList');
        if (!list || !list.length) {
            $list.html('<div class="empty-list">Sin actividad este mes.</div>');
            return;
        }
        var html = list.map(function (t) {
            return '<div class="top-therapist">' +
                       '<span class="top-therapist-name">' + (t.name || '—') + '</span>' +
                       '<span class="top-therapist-count">' + t.count + '</span>' +
                   '</div>';
        }).join('');
        $list.html(html);
    }

    function loadDashboard() {
        JsManager.SendJson('GET', 'panel-clinico-data', '', function (json) {
            if (!json || json.status != '1' || !json.data) {
                console.error('Dashboard load failed', json);
                return;
            }
            var d = json.data;
            renderKpis(d.kpis);
            renderSessionsByWeek(d.sessionsByWeek);
            renderEvalTypes(d.evaluationTypes);
            renderRecentSessions(d.recentSessions);
            renderAlerts(d.alerts);
            renderTopTherapists(d.topTherapists);
        }, function (xhr) {
            console.error('Dashboard request error', xhr);
            $('#kpiGrid [data-kpi]').text('!');
        });
    }

    // Toggle de Quick Actions con persistencia en localStorage
    function setupQuickActionsToggle() {
        var $btn   = $('#quickActionsToggle');
        var $panel = $('#quickActionsPanel');
        if (!$btn.length || !$panel.length) return;

        var STORAGE_KEY = 'clinicalDash.quickActionsCollapsed';
        var initiallyCollapsed = false;
        try { initiallyCollapsed = window.localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) {}

        if (initiallyCollapsed) {
            $panel.addClass('collapsed');
            $btn.attr('aria-expanded', 'false');
        }

        $btn.on('click', function () {
            var nowCollapsed = !$panel.hasClass('collapsed');
            $panel.toggleClass('collapsed', nowCollapsed);
            $btn.attr('aria-expanded', nowCollapsed ? 'false' : 'true');
            try { window.localStorage.setItem(STORAGE_KEY, nowCollapsed ? '1' : '0'); } catch (e) {}
        });
    }

    $(document).ready(function () {
        setupQuickActionsToggle();
        loadDashboard();
    });
})(jQuery);
</script>
@endpush

@endsection
