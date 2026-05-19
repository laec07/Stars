@extends('layouts.app')
@section('content')

@push("adminCss")
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

    @media (max-width: 768px) {
        .patient-header { padding:1rem; }
        .patient-avatar { width:52px; height:52px; font-size:1.25rem; }
        .stat-card .stat-value { font-size:1.4rem; }
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
                <h4 class="mb-1">{{ $patient->full_name }}</h4>
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

</div>

@endsection
