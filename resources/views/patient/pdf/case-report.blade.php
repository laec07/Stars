{{--
    Reorg-A.2 — PDF de reporte focalizado por caso clínico.
    Incluye la ficha clínica COMPLETA + evaluaciones del caso + sesiones del caso.
--}}
@php
    use App\Support\EvaluationMeta;

    // Preferir el logo optimizado para PDF (700px). El logo-full.png original es
    // de 6879x4500px (~31MP con alfa) y mPDF lo omite cuando GD se queda sin
    // memoria. Fallback al full si el optimizado no existe.
    $logoPdf  = public_path('img/brand/logo-pdf.png');
    $logoFull = public_path('img/brand/logo-full.png');
    $logoSrc  = file_exists($logoPdf) ? $logoPdf : (file_exists($logoFull) ? $logoFull : null);

    $decode = function ($v) {
        if (!is_string($v)) return $v;
        return html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    };
    $formatValue = function ($v) {
        if ($v === null || $v === '') return '<span style="color:#adb5bd;">—</span>';
        return e((string)$v);
    };

    $modalidades = collect([
        'modalidades_ejercicio_terapeutico' => 'Ejercicio terapéutico',
        'modalidades_electroterapia'        => 'Electroterapia',
        'modalidades_masoterapia'           => 'Masoterapia',
        'modalidades_estiramientos'         => 'Estiramientos',
        'modalidades_tecaterapia'           => 'Tecarterapia',
        'modalidades_puncion_seca'          => 'Punción seca',
        'modalidades_electropuncion'        => 'Electropunción',
    ])->filter(fn($lbl, $key) => (int) ($ficha->{$key} ?? 0) === 1)->values()->all();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte clínico — {{ $caseTitle }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 9.5pt; color: #2F4157; line-height: 1.4; }

        .pdf-header { width:100%; margin-bottom:14pt; padding-bottom:8pt; border-bottom:2pt solid #9F93E7; }
        .pdf-header table { width:100%; border-collapse:collapse; }
        .pdf-header .logo-cell { width:130pt; vertical-align:middle; }
        .pdf-header .logo-cell img { width:110pt; height:auto; }
        .pdf-header .title-cell { vertical-align:middle; text-align:right; }
        .pdf-header .title { font-size:17pt; font-weight:bold; color:#2F4157; margin:0; }
        .pdf-header .subtitle { font-size:9pt; color:#5e4fbf; margin-top:2pt; }

        .patient-block { background:#F4F1FB; padding:8pt 12pt; border-left:4pt solid #9F93E7; margin-bottom:12pt; }
        .patient-block table { width:100%; border-collapse:collapse; }
        .patient-block td { padding:2pt 8pt 2pt 0; font-size:9pt; vertical-align:top; }
        .patient-block .lbl { font-weight:bold; color:#5e4fbf; text-transform:uppercase; font-size:7.5pt; letter-spacing:.5pt; width:70pt; }

        .case-banner {
            background:#9F93E7; color:#fff;
            padding:10pt 14pt;
            margin-bottom:12pt;
            border-radius:3pt;
        }
        .case-banner .case-name { font-size:13pt; font-weight:bold; margin-bottom:2pt; }
        .case-banner .case-meta { font-size:9pt; opacity:.92; }

        h2.major-section {
            background:#9F93E7; color:#fff;
            padding:7pt 12pt;
            font-size:12pt; font-weight:bold;
            text-transform:uppercase; letter-spacing:1pt;
            margin:18pt 0 10pt 0;
            page-break-after: avoid;
        }
        h3.minor-section {
            color:#5e4fbf;
            font-size:10.5pt; font-weight:bold;
            border-bottom:1pt solid #DFBEF4;
            padding-bottom:3pt;
            margin:12pt 0 6pt 0;
            page-break-after: avoid;
        }

        .ficha-section {
            margin-bottom:10pt; page-break-inside:avoid;
        }
        .ficha-section .sec-title {
            background:#DFBEF4; color:#2F4157;
            padding:4pt 8pt;
            font-size:9pt; font-weight:bold;
            text-transform:uppercase; letter-spacing:.5pt;
            margin-bottom:4pt;
        }
        .ficha-table { width:100%; border-collapse:collapse; }
        .ficha-table tr { page-break-inside:avoid; }
        .ficha-table td { padding:4pt 8pt; border-bottom:1pt solid #efeaf9; font-size:9pt; vertical-align:top; }
        .ficha-table tr:nth-child(even) td { background:#faf8ff; }
        .ficha-table .lbl { font-weight:bold; color:#5a6c80; width:35%; }

        .long-text {
            padding:7pt 10pt;
            background:#faf8ff;
            border-left:3pt solid #DFBEF4;
            font-size:9pt;
            white-space:pre-wrap;
            margin-bottom:6pt;
        }
        .long-text .lbl {
            font-weight:bold; color:#5e4fbf;
            text-transform:uppercase; font-size:7.5pt; letter-spacing:.5pt;
            display:block; margin-bottom:2pt;
        }

        .modality-tags { display:block; }
        .modality-tag {
            display:inline-block;
            background:#DFBEF4; color:#2F4157;
            padding:2pt 8pt;
            border-radius:8pt;
            margin:0 3pt 3pt 0;
            font-size:8.5pt;
            font-weight:600;
        }

        .eval-block, .sesion-block {
            border:1pt solid #efeaf9;
            border-radius:3pt;
            padding:7pt 11pt;
            margin-bottom:8pt;
            page-break-inside:avoid;
        }
        .eval-block .eval-title {
            font-weight:bold; color:#5e4fbf;
            font-size:9.5pt;
            border-bottom:1pt dashed #DFBEF4;
            padding-bottom:3pt; margin-bottom:5pt;
        }
        .eval-fields-table { width:100%; border-collapse:collapse; font-size:8.5pt; }
        .eval-fields-table td {
            padding:3pt 7pt;
            border-bottom:1pt solid #f1f3f5;
            vertical-align:top;
        }
        .eval-fields-table .lbl { font-weight:600; color:#5a6c80; width:45%; }

        .sesion-block .ses-date {
            font-weight:bold; color:#5e4fbf;
            font-size:9.5pt;
            margin-bottom:3pt;
        }
        .sesion-block .ses-content { color:#2F4157; font-size:9pt; margin-top:3pt; }
        .sesion-block .ses-content strong { color:#5a6c80; }

        .empty-msg { color:#adb5bd; font-style:italic; padding:10pt; text-align:center; font-size:9pt; }

        .pdf-footer { margin-top:18pt; padding-top:10pt; border-top:1pt solid #DFBEF4; font-size:8pt; color:#5a6c80; }
        .signature-block { margin-top:30pt; text-align:center; }
        .signature-line {
            border-top:1pt solid #2F4157; width:230pt;
            display:inline-block; padding-top:4pt;
            font-size:9pt; font-weight:bold; color:#2F4157;
        }
    </style>
</head>
<body>

{{-- ===== HEADER ===== --}}
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
                <div class="title">Reporte Clínico</div>
                <div class="subtitle">Caso clínico · {{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== PACIENTE ===== --}}
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
            <td><strong>{{ $patient->full_name ?? '—' }}</strong></td>
            <td class="lbl">ID</td>
            <td>#{{ $patient->id }}</td>
        </tr>
        <tr>
            <td class="lbl">Edad</td>
            <td>{{ $age !== null ? $age . ' años' : '—' }}</td>
            <td class="lbl">Teléfono</td>
            <td>{{ $patient->phone_no ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- ===== CASE BANNER ===== --}}
<div class="case-banner">
    <div class="case-name">{{ $caseTitle }}</div>
    <div class="case-meta">
        @if($ficha->fecha)
            Caso iniciado el {{ \Carbon\Carbon::parse($ficha->fecha)->format('d/m/Y') }} ·
        @endif
        Ficha #{{ $ficha->id }}
    </div>
</div>

{{-- ===== FICHA CLÍNICA COMPLETA ===== --}}
<h2 class="major-section">Ficha Clínica</h2>

@if(!empty($ficha->motivo_consulta))
    <div class="long-text">
        <span class="lbl">Motivo de consulta</span>
        {{ $decode($ficha->motivo_consulta) }}
    </div>
@endif

@php
    $antecedentes = collect([
        'historial_medico'     => 'Historial médico',
        'enfermedades_cronicas'=> 'Enfermedades crónicas',
        'cirugias_previas'     => 'Cirugías previas',
        'medicamentos_actuales'=> 'Medicamentos actuales',
        'alergias'             => 'Alergias',
    ])->filter(fn($lbl, $key) => !empty($ficha->{$key}));
@endphp
@if($antecedentes->isNotEmpty())
    <div class="ficha-section">
        <div class="sec-title">Antecedentes médicos relevantes</div>
        <table class="ficha-table">
            @foreach($antecedentes as $key => $lbl)
                <tr>
                    <td class="lbl">{{ $lbl }}</td>
                    <td>{{ $decode($ficha->{$key}) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

@php
    $lesionFields = collect([
        'fecha_inicio'            => 'Fecha de inicio',
        'mecanismo_lesion_origen' => 'Mecanismo / origen',
        'evolucion_sintomas'      => 'Evolución de los síntomas',
        'tratamientos_previos'    => 'Tratamientos previos',
    ])->filter(fn($lbl, $key) => !empty($ficha->{$key}));
@endphp
@if($lesionFields->isNotEmpty())
    <div class="ficha-section">
        <div class="sec-title">Historia de la lesión o condición</div>
        <table class="ficha-table">
            @foreach($lesionFields as $key => $lbl)
                <tr>
                    <td class="lbl">{{ $lbl }}</td>
                    <td>
                        @if($key === 'fecha_inicio')
                            {{ \Carbon\Carbon::parse($ficha->{$key})->format('d/m/Y') }}
                        @else
                            {{ $decode($ficha->{$key}) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

@php
    $evalFields = collect([
        'observacion_marcha'           => 'Observación de la marcha',
        'observacion_otros'            => 'Otras observaciones',
        'diagnostico_fisioterapeutico' => 'Diagnóstico fisioterapéutico',
    ])->filter(fn($lbl, $key) => !empty($ficha->{$key}));
@endphp
@if($evalFields->isNotEmpty())
    <div class="ficha-section">
        <div class="sec-title">Evaluación fisioterapéutica inicial</div>
        @foreach($evalFields as $key => $lbl)
            <div class="long-text">
                <span class="lbl">{{ $lbl }}</span>
                {{ $decode($ficha->{$key}) }}
            </div>
        @endforeach
    </div>
@endif

@php
    $objFields = collect([
        'corto_plazo'   => 'Corto plazo',
        'mediano_plazo' => 'Mediano plazo',
        'largo_plazo'   => 'Largo plazo',
    ])->filter(fn($lbl, $key) => !empty($ficha->{$key}));
@endphp
@if($objFields->isNotEmpty())
    <div class="ficha-section">
        <div class="sec-title">Objetivos del tratamiento</div>
        <table class="ficha-table">
            @foreach($objFields as $key => $lbl)
                <tr>
                    <td class="lbl">{{ $lbl }}</td>
                    <td>{{ $decode($ficha->{$key}) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

@if(!empty($modalidades) || !empty($ficha->modalidades_otros) || !empty($ficha->frecuencia_semana))
    <div class="ficha-section">
        <div class="sec-title">Plan de tratamiento</div>
        @if(!empty($modalidades))
            <div style="margin-bottom:5pt;">
                <strong style="font-size:9pt; color:#5e4fbf;">Modalidades:</strong>
                <div class="modality-tags" style="margin-top:3pt;">
                    @foreach($modalidades as $m)
                        <span class="modality-tag">{{ $m }}</span>
                    @endforeach
                </div>
            </div>
        @endif
        @if(!empty($ficha->modalidades_otros))
            <div class="long-text">
                <span class="lbl">Otros tratamientos</span>
                {{ $decode($ficha->modalidades_otros) }}
            </div>
        @endif
        @if(!empty($ficha->frecuencia_semana) || !empty($ficha->duracion_semanas))
            <table class="ficha-table">
                <tr>
                    <td class="lbl">Frecuencia</td>
                    <td>{{ $ficha->frecuencia_semana ?? 1 }} vez/sem</td>
                </tr>
                <tr>
                    <td class="lbl">Duración estimada</td>
                    <td>{{ $ficha->duracion_semanas ?? '?' }} semanas</td>
                </tr>
            </table>
        @endif
    </div>
@endif

{{-- ===== EVALUACIONES DEL CASO ===== --}}
<h2 class="major-section">Evaluaciones clínicas de este caso</h2>
@if(empty($evaluations))
    <div class="empty-msg">Este caso aún no tiene evaluaciones registradas.</div>
@else
    @foreach($evaluations as $tabla => $records)
        @php
            $typeName = EvaluationMeta::displayName($tabla);
            $sections = EvaluationMeta::sections($tabla);
        @endphp
        <h3 class="minor-section">{{ $typeName }} ({{ $records->count() }})</h3>

        @foreach($records as $record)
            <div class="eval-block">
                <div class="eval-title">
                    {{ $record->fecha ? \Carbon\Carbon::parse($record->fecha)->format('d/m/Y') : 'Sin fecha' }}
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
                        <div style="font-weight:600; font-size:8.5pt; color:#5e4fbf; margin-top:4pt; margin-bottom:2pt; text-transform:uppercase; letter-spacing:.04em;">{{ $section['title'] }}</div>
                    @endif
                    <table class="eval-fields-table">
                        @foreach($visibleFields as $col)
                            @php $rawVal = $decode($record->{$col} ?? null); @endphp
                            <tr>
                                <td class="lbl">{{ EvaluationMeta::fieldLabel($tabla, $col) }}</td>
                                <td>{!! $formatValue($rawVal) !!}</td>
                            </tr>
                        @endforeach
                    </table>
                @endforeach
            </div>
        @endforeach
    @endforeach
@endif

{{-- ===== SESIONES DEL CASO ===== --}}
<h2 class="major-section">Sesiones del caso ({{ $sesiones->count() }})</h2>
@if($sesiones->count() === 0)
    <div class="empty-msg">Este caso aún no tiene sesiones registradas.</div>
@else
    @foreach($sesiones as $ses)
        <div class="sesion-block">
            <div class="ses-date">
                {{ $ses->fecha ? \Carbon\Carbon::parse($ses->fecha)->format('d/m/Y') : 'Sin fecha' }}
                @if(!empty($ses->tratamiento_realizado))
                    <span style="font-weight:normal; color:#5a6c80; font-size:8.5pt;">
                        — {{ Str::limit($decode($ses->tratamiento_realizado), 120) }}
                    </span>
                @endif
            </div>
            @if(!empty($ses->evolucion))
                <div class="ses-content"><strong>Evolución:</strong> {{ $decode($ses->evolucion) }}</div>
            @endif
            @if(!empty($ses->observaciones))
                <div class="ses-content"><strong>Observaciones:</strong> {{ $decode($ses->observaciones) }}</div>
            @endif
        </div>
    @endforeach
@endif

{{-- ===== FIRMA ===== --}}
<div class="signature-block">
    <div class="signature-line">Fisioterapeuta responsable</div>
    <div style="font-size:8pt; color:#5a6c80; margin-top:2pt;">
        Documento emitido el {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- ===== FOOTER ===== --}}
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
            </td>
        </tr>
    </table>
    <div style="text-align:center; margin-top:8pt; font-size:7.5pt; color:#adb5bd;">
        Información clínica confidencial. Su uso queda restringido al paciente, sus representantes y profesionales autorizados.
    </div>
</div>

</body>
</html>
