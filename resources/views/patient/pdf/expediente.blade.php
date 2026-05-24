{{--
    Fase 6b — PDF del expediente clínico completo
    Hoja membretada Healing Hands + datos paciente + fichas + evaluaciones + sesiones recientes.
--}}
@php
    use App\Support\EvaluationMeta;

    // mPDF carga imágenes desde filesystem, NUNCA vía HTTP (cuelga artisan serve).
    $logoPath = public_path('img/brand/logo-full.png');
    $logoSrc  = file_exists($logoPath) ? $logoPath : null;

    $decode = function ($v) {
        if (!is_string($v)) return $v;
        return html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    };
    $formatValue = function ($v) {
        if ($v === null || $v === '') return '<span style="color:#adb5bd;">—</span>';
        return e((string)$v);
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expediente clínico — {{ $patient->full_name }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 9.5pt; color: #2F4157; line-height: 1.4; }
        .pdf-header { width:100%; margin-bottom:14pt; padding-bottom:8pt; border-bottom:2pt solid #9F93E7; }
        .pdf-header table { width:100%; border-collapse:collapse; }
        .pdf-header .logo-cell { width:130pt; vertical-align:middle; }
        .pdf-header .logo-cell img { width:110pt; height:auto; }
        .pdf-header .title-cell { vertical-align:middle; text-align:right; }
        .pdf-header .title { font-size:18pt; font-weight:bold; color:#2F4157; margin:0; }
        .pdf-header .subtitle { font-size:9pt; color:#5e4fbf; margin-top:2pt; }

        .patient-block { background:#F4F1FB; padding:8pt 12pt; border-left:4pt solid #9F93E7; margin-bottom:12pt; }
        .patient-block table { width:100%; border-collapse:collapse; }
        .patient-block td { padding:2pt 8pt 2pt 0; font-size:9pt; vertical-align:top; }
        .patient-block .lbl { font-weight:bold; color:#5e4fbf; text-transform:uppercase; font-size:7.5pt; letter-spacing:.5pt; width:70pt; }
        .patient-block .val { color:#2F4157; }

        h2.major-section {
            background: linear-gradient(135deg, #9F93E7, #7d6fd6);
            background: #9F93E7;
            color: #fff;
            padding: 8pt 12pt;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1pt;
            margin: 18pt 0 10pt 0;
            page-break-after: avoid;
        }
        h3.eval-type-title {
            color: #5e4fbf;
            font-size: 11pt;
            font-weight: bold;
            border-bottom: 1pt solid #DFBEF4;
            padding-bottom: 3pt;
            margin: 14pt 0 8pt 0;
            page-break-after: avoid;
        }
        .section-title {
            background: #DFBEF4;
            color: #2F4157;
            padding: 4pt 8pt;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .5pt;
            margin-top: 6pt;
        }

        table.fields-table { width:100%; border-collapse:collapse; }
        table.fields-table tr { page-break-inside:avoid; }
        table.fields-table td { padding:3pt 8pt; border-bottom:1pt solid #efeaf9; font-size:8.5pt; vertical-align:top; }
        table.fields-table tr:nth-child(even) td { background:#faf8ff; }
        table.fields-table .field-label { font-weight:bold; color:#5a6c80; width:45%; }
        table.fields-table .field-value { color:#2F4157; }

        .eval-block {
            border: 1pt solid #efeaf9;
            border-radius: 3pt;
            padding: 6pt 10pt;
            margin-bottom: 8pt;
            page-break-inside: avoid;
        }
        .eval-block .eval-header {
            font-size: 9pt;
            color: #5e4fbf;
            border-bottom: 1pt dashed #DFBEF4;
            padding-bottom: 3pt;
            margin-bottom: 5pt;
            font-weight: bold;
        }
        .eval-block .eval-meta {
            font-size: 8pt;
            color: #6c757d;
        }

        .ficha-card {
            border-left: 4pt solid #C7D9E5;
            background: #f7fafc;
            padding: 6pt 12pt;
            margin-bottom: 8pt;
            font-size: 9pt;
        }
        .ficha-card .ficha-title { font-weight: bold; color: #1d5e6b; font-size: 10pt; }
        .ficha-card .ficha-meta { color: #5a6c80; font-size: 8pt; }
        .ficha-card .ficha-motivo { color: #2F4157; margin-top: 3pt; }

        .sesion-card {
            border-left: 3pt solid #9F93E7;
            background: #faf8ff;
            padding: 6pt 12pt;
            margin-bottom: 6pt;
            font-size: 8.5pt;
            page-break-inside: avoid;
        }
        .sesion-card .sesion-date { font-weight: bold; color: #5e4fbf; font-size: 9pt; }
        .sesion-card .sesion-content { color: #2F4157; margin-top: 3pt; }

        .empty-state { color: #adb5bd; font-style: italic; padding: 8pt; text-align: center; font-size: 9pt; }

        .toc { margin-bottom: 16pt; padding: 8pt 12pt; background:#faf8ff; border-radius:3pt; border:1pt solid #DFBEF4; font-size: 9pt; }
        .toc-title { font-weight:bold; color:#5e4fbf; font-size:10pt; margin-bottom:4pt; text-transform:uppercase; letter-spacing:.5pt; }
        .toc-item { padding: 2pt 0; }
        .toc-count { display:inline-block; min-width:20pt; padding:1pt 5pt; background:#9F93E7; color:#fff; border-radius:8pt; font-size:7.5pt; font-weight:bold; text-align:center; }

        .pdf-footer { margin-top:18pt; padding-top:10pt; border-top:1pt solid #DFBEF4; font-size:8pt; color:#5a6c80; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

{{-- ========== PORTADA / HEADER ========== --}}
<div class="pdf-header">
    <table>
        <tr>
            <td class="logo-cell">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Healing Hands">
                @else
                    <div style="font-family:serif; font-size:14pt; color:#9F93E7; font-weight:bold;">Healing Hands</div>
                @endif
            </td>
            <td class="title-cell">
                <div class="title">Expediente Clínico</div>
                <div class="subtitle">Reporte completo · {{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- ========== DATOS DEL PACIENTE ========== --}}
@php
    $age = null;
    if (!empty($patient->dob)) {
        try { $age = \Carbon\Carbon::parse($patient->dob)->age; } catch (\Throwable $e) {}
    }
@endphp
<div class="patient-block">
    <table>
        <tr>
            <td class="lbl">Paciente</td>
            <td class="val"><strong>{{ $patient->full_name ?? '—' }}</strong></td>
            <td class="lbl">ID</td>
            <td class="val">#{{ $patient->id }}</td>
        </tr>
        <tr>
            <td class="lbl">Edad</td>
            <td class="val">{{ $age !== null ? $age . ' años' : '—' }}</td>
            <td class="lbl">Fecha nac.</td>
            <td class="val">{{ $patient->dob ? \Carbon\Carbon::parse($patient->dob)->format('d/m/Y') : '—' }}</td>
        </tr>
        @if(!empty($patient->phone_no) || !empty($patient->email))
        <tr>
            <td class="lbl">Teléfono</td>
            <td class="val">{{ $patient->phone_no ?? '—' }}</td>
            <td class="lbl">Email</td>
            <td class="val">{{ $patient->email ?? '—' }}</td>
        </tr>
        @endif
    </table>
</div>

{{-- ========== TABLA DE CONTENIDO ========== --}}
<div class="toc">
    <div class="toc-title">Resumen del expediente</div>
    <div class="toc-item"><span class="toc-count">{{ $fichas->count() }}</span> Casos clínicos (fichas)</div>
    <div class="toc-item"><span class="toc-count">{{ collect($evaluations)->sum(fn($r) => $r->count()) }}</span> Evaluaciones registradas</div>
    <div class="toc-item"><span class="toc-count">{{ $sesiones->count() }}</span> Sesiones recientes</div>
</div>

{{-- ========== FICHAS CLÍNICAS ========== --}}
<h2 class="major-section">Casos clínicos</h2>
@forelse($fichas as $ficha)
    <div class="ficha-card">
        <div class="ficha-title">{{ $ficha->diagnostico ?: 'Ficha #' . $ficha->id }}</div>
        <div class="ficha-meta">
            Iniciada: {{ $ficha->fecha ? \Carbon\Carbon::parse($ficha->fecha)->format('d/m/Y') : 'sin fecha' }}
            · Ficha #{{ $ficha->id }}
        </div>
        @if(!empty($ficha->motivo_consulta))
            <div class="ficha-motivo">
                <strong>Motivo:</strong> {{ Str::limit($decode($ficha->motivo_consulta), 400) }}
            </div>
        @endif
    </div>
@empty
    <div class="empty-state">El paciente no tiene fichas clínicas registradas.</div>
@endforelse

{{-- ========== EVALUACIONES POR TIPO ========== --}}
<h2 class="major-section">Evaluaciones clínicas</h2>
@forelse($evaluations as $tabla => $records)
    @php
        $typeName = EvaluationMeta::displayName($tabla);
        $sections = EvaluationMeta::sections($tabla);
    @endphp

    <h3 class="eval-type-title">{{ $typeName }} ({{ $records->count() }})</h3>

    @foreach($records as $record)
        <div class="eval-block">
            <div class="eval-header">
                {{ $record->fecha ? \Carbon\Carbon::parse($record->fecha)->format('d/m/Y') : 'Sin fecha' }}
                @php
                    $key = $tabla . ':' . $record->getKey();
                    $fichaId = $fichaMap[$key] ?? null;
                    $linkedFicha = $fichaId ? $fichas->firstWhere('id', $fichaId) : null;
                @endphp
                @if($linkedFicha)
                    <span class="eval-meta">· {{ Str::limit($linkedFicha->diagnostico ?: ('Ficha #' . $linkedFicha->id), 60) }}</span>
                @endif
            </div>

            @foreach($sections as $section)
                @php
                    $visibleFields = collect($section['fields'])->filter(function ($col) use ($record) {
                        $v = $record->{$col} ?? null;
                        return $v !== null && $v !== '';
                    })->values();
                @endphp
                @if($visibleFields->count() === 0) @continue @endif

                @if(count($sections) > 1)
                    <div class="section-title">{{ $section['title'] }}</div>
                @endif

                <table class="fields-table">
                    @foreach($visibleFields as $col)
                        @php $rawVal = $decode($record->{$col} ?? null); @endphp
                        <tr>
                            <td class="field-label">{{ EvaluationMeta::fieldLabel($tabla, $col) }}</td>
                            <td class="field-value">{!! $formatValue($rawVal) !!}</td>
                        </tr>
                    @endforeach
                </table>
            @endforeach
        </div>
    @endforeach
@empty
    <div class="empty-state">El paciente no tiene evaluaciones registradas.</div>
@endforelse

{{-- ========== SESIONES RECIENTES ========== --}}
@if($sesiones->count() > 0)
    <h2 class="major-section">Sesiones recientes ({{ $sesiones->count() }})</h2>
    @foreach($sesiones as $ses)
        <div class="sesion-card">
            <div class="sesion-date">
                {{ $ses->fecha ? \Carbon\Carbon::parse($ses->fecha)->format('d/m/Y') : 'Sin fecha' }}
                @if(!empty($ses->tratamiento))
                    <span style="font-weight:normal; color:#5a6c80; font-size:8.5pt;">
                        — {{ Str::limit($decode($ses->tratamiento), 100) }}
                    </span>
                @endif
            </div>
            @if(!empty($ses->evolucion))
                <div class="sesion-content"><strong>Evolución:</strong> {{ Str::limit(strip_tags($decode($ses->evolucion)), 350) }}</div>
            @endif
            @if(!empty($ses->observaciones))
                <div class="sesion-content"><strong>Observaciones:</strong> {{ Str::limit(strip_tags($decode($ses->observaciones)), 350) }}</div>
            @endif
        </div>
    @endforeach
@endif

{{-- ========== FOOTER ========== --}}
<div class="pdf-footer">
    <table style="width:100%;">
        <tr>
            <td style="width:50%;">
                <strong>Healing Hands</strong> — Spa terapéutico profesional<br>
                <span style="color:#9F93E7;">Un lugar para sanar, relajarse, rejuvenecer y revitalizarse</span>
            </td>
            <td style="width:50%; text-align:right;">
                @if($company && !empty($company->phone))Tel: {{ $company->phone }}<br>@endif
                @if($company && !empty($company->email)){{ $company->email }}<br>@endif
                Documento generado el {{ now()->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>
    <div style="text-align:center; margin-top:8pt; font-size:7.5pt; color:#adb5bd;">
        Este documento contiene información clínica confidencial. Su uso queda restringido al paciente,
        sus representantes y profesionales autorizados.
    </div>
</div>

</body>
</html>
