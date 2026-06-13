{{--
    Fase 6a — PDF de evaluación individual
    Hoja membretada Healing Hands + datos paciente + datos ficha + campos del registro.
    Renderizado por mPDF (CSS3 con limitaciones — evitar flex avanzado).
--}}
@php
    use App\Support\EvaluationMeta;

    // IMPORTANTE: mPDF se ejecuta dentro del MISMO request PHP que generó esta vista,
    // así que NO puede cargar imágenes vía HTTP (php artisan serve es single-threaded,
    // se queda colgado hasta timeout). Pasamos siempre el path absoluto del filesystem.
    // Preferir el logo optimizado para PDF (700px). El logo-full.png original es
    // de 6879x4500px (~31MP con alfa) y mPDF lo omite cuando GD se queda sin
    // memoria. Fallback al full si el optimizado no existe.
    $logoPdf  = public_path('img/brand/logo-pdf.png');
    $logoFull = public_path('img/brand/logo-full.png');
    $logoSrc  = file_exists($logoPdf) ? $logoPdf : (file_exists($logoFull) ? $logoFull : null);

    // Helper local: formato seguro para valor
    $formatValue = function ($v) {
        if ($v === null || $v === '' ) return '<span style="color:#adb5bd;">—</span>';
        // booleano numérico (0/1) → Sí / No
        if (is_numeric($v) && in_array((int)$v, [0,1], true) && strlen((string)$v) === 1) {
            // Solo si NO parece un valor de escala (heurística simple)
            // No transformamos automáticamente para preservar significado.
        }
        return e((string)$v);
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $patient->full_name }}</title>
    <style>
        @page {
            margin-header: 8mm;
            margin-footer: 8mm;
        }

        body {
            font-family: dejavusans, sans-serif;
            font-size: 10pt;
            color: #2F4157;
            line-height: 1.4;
        }

        /* Header — hoja membretada */
        .pdf-header {
            width: 100%;
            margin-bottom: 14pt;
            padding-bottom: 8pt;
            border-bottom: 2pt solid #9F93E7;
        }
        .pdf-header table { width: 100%; border-collapse: collapse; }
        .pdf-header .logo-cell { width: 130pt; vertical-align: middle; }
        .pdf-header .logo-cell img { width: 110pt; height: auto; }
        .pdf-header .title-cell { vertical-align: middle; text-align: right; }
        .pdf-header .title { font-size: 16pt; font-weight: bold; color: #2F4157; margin: 0; }
        .pdf-header .subtitle { font-size: 9pt; color: #5e4fbf; margin-top: 2pt; }

        /* Patient block */
        .patient-block {
            background: #F4F1FB;
            padding: 8pt 12pt;
            border-left: 4pt solid #9F93E7;
            margin-bottom: 12pt;
        }
        .patient-block table { width: 100%; border-collapse: collapse; }
        .patient-block td {
            padding: 2pt 8pt 2pt 0;
            font-size: 9pt;
            vertical-align: top;
        }
        .patient-block .lbl {
            font-weight: bold;
            color: #5e4fbf;
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: .5pt;
            width: 70pt;
        }
        .patient-block .val { color: #2F4157; }

        /* Ficha clínica banner */
        .ficha-banner {
            background: #C7D9E5;
            padding: 6pt 10pt;
            border-radius: 3pt;
            margin-bottom: 10pt;
            font-size: 9pt;
            color: #1d5e6b;
        }
        .ficha-banner .ficha-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: .5pt;
            color: #0d5cbf;
        }

        /* Sections */
        .section {
            margin-bottom: 12pt;
            page-break-inside: avoid;
        }
        .section-title {
            background: #9F93E7;
            color: #fff;
            padding: 5pt 10pt;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .5pt;
            margin: 0 0 0 0;
        }
        .section-body { padding: 0; }

        /* Field grid — usamos tabla 2 columnas (label + value) */
        table.fields-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        table.fields-table tr { page-break-inside: avoid; }
        table.fields-table td {
            padding: 4pt 8pt;
            border-bottom: 1pt solid #efeaf9;
            vertical-align: top;
            font-size: 9pt;
        }
        table.fields-table tr:nth-child(even) td { background: #faf8ff; }
        table.fields-table .field-label {
            font-weight: bold;
            color: #5a6c80;
            width: 45%;
        }
        table.fields-table .field-value { color: #2F4157; }

        /* Para campos largos (textarea) usar row completa */
        .long-field {
            margin: 4pt 0;
            padding: 6pt 10pt;
            background: #faf8ff;
            border-left: 3pt solid #DFBEF4;
        }
        .long-field .lbl {
            font-weight: bold;
            color: #5e4fbf;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: .5pt;
            display: block;
            margin-bottom: 2pt;
        }
        .long-field .val {
            color: #2F4157;
            font-size: 9pt;
            white-space: pre-wrap;
        }

        /* Footer — firma + datos */
        .pdf-footer {
            margin-top: 18pt;
            padding-top: 10pt;
            border-top: 1pt solid #DFBEF4;
            font-size: 8pt;
            color: #5a6c80;
        }
        .signature-block {
            margin-top: 30pt;
            text-align: center;
        }
        .signature-line {
            border-top: 1pt solid #2F4157;
            width: 200pt;
            display: inline-block;
            padding-top: 4pt;
            font-size: 9pt;
            font-weight: bold;
            color: #2F4157;
        }
        .signature-meta {
            font-size: 8pt;
            color: #5a6c80;
            margin-top: 2pt;
        }

        .text-muted { color: #adb5bd; }
        .badge-primary {
            background: #9F93E7;
            color: #fff;
            padding: 1pt 5pt;
            border-radius: 2pt;
            font-size: 8pt;
            font-weight: bold;
        }
    </style>
</head>
<body>

{{-- ===================== HEADER MEMBRETADO ===================== --}}
<div class="pdf-header">
    <table>
        <tr>
            <td class="logo-cell">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Healing Hands">
                @else
                    <div style="font-family: serif; font-size: 14pt; color: #9F93E7; font-weight: bold;">
                        Healing Hands
                    </div>
                @endif
            </td>
            <td class="title-cell">
                <div class="title">{{ $title }}</div>
                <div class="subtitle">
                    Reporte clínico · {{ \Carbon\Carbon::parse($record->fecha ?? now())->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ===================== DATOS DEL PACIENTE ===================== --}}
<div class="patient-block">
    <table>
        <tr>
            <td class="lbl">Paciente</td>
            <td class="val"><strong>{{ $patient->full_name ?? '—' }}</strong></td>
            <td class="lbl">ID</td>
            <td class="val">#{{ $patient->id }}</td>
        </tr>
        <tr>
            @php
                $age = null;
                if (!empty($patient->dob)) {
                    try { $age = \Carbon\Carbon::parse($patient->dob)->age; } catch (\Throwable $e) {}
                }
            @endphp
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

{{-- ===================== FICHA CLÍNICA ASOCIADA ===================== --}}
@if($ficha)
    <div class="ficha-banner">
        <span class="ficha-label">Caso clínico:</span>
        <strong>{{ $ficha->diagnostico ?? ('Ficha #' . $ficha->id) }}</strong>
        @if(!empty($ficha->fecha))
            · iniciada el {{ \Carbon\Carbon::parse($ficha->fecha)->format('d/m/Y') }}
        @endif
        @if(!empty($ficha->motivo_consulta))
            <br><span style="font-size:8pt; color:#0d5cbf;">Motivo de consulta:</span> {{ Str::limit($ficha->motivo_consulta, 200) }}
        @endif
    </div>
@endif

{{-- ===================== SECCIONES DE CAMPOS ===================== --}}
@php
    // Decodificar entidades HTML del registro (xssProtection las codificó al guardar)
    $decode = function ($v) {
        if (!is_string($v)) return $v;
        return html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    };
@endphp

@foreach($sections as $section)
    @php
        // Filtrar campos que tengan algún valor en el registro
        $visibleFields = collect($section['fields'])->filter(function ($col) use ($record) {
            $v = $record->{$col} ?? null;
            return $v !== null && $v !== '';
        })->values();

        // Distinguir campos cortos (datos puntuales) de campos largos (textareas)
        // Heurística: si el texto es >80 chars o el nombre sugiere texto largo, va aparte
        $longFieldKeys = ['observaciones','observaciones2','observaciones_res','diagnostico','Diagnostico','Observaciones',
                          'pain_location','pain_start_when','pain_quality','pain_aggravating_factors',
                          'pain_relieving_factors','pain_associated_symptoms','pain_history',
                          'pain_impact_daily_activities','pain_psychosocial_factors','pain_emotional_response',
                          'pain_reduction_method','pain_environmental_triggers',
                          'pain_pharmacological_treatment','pain_non_pharmacological_treatment',
                          'estado_piel_izquierdo_anterior','estado_piel_derecho_anterior',
                          'estado_piel_izquierdo_posterior','estado_piel_derecho_posterior',
                          'motivo_consulta'];
    @endphp

    @if($visibleFields->count() === 0)
        @continue
    @endif

    <div class="section">
        <div class="section-title">{{ $section['title'] }}</div>
        <div class="section-body">
            @php
                $shortFields = $visibleFields->reject(fn($f) => in_array($f, $longFieldKeys));
                $longFields  = $visibleFields->filter(fn($f) => in_array($f, $longFieldKeys));
            @endphp

            @if($shortFields->count())
                <table class="fields-table">
                    @foreach($shortFields as $col)
                        @php $rawVal = $decode($record->{$col} ?? null); @endphp
                        <tr>
                            <td class="field-label">{{ EvaluationMeta::fieldLabel($tabla, $col) }}</td>
                            <td class="field-value">{!! $formatValue($rawVal) !!}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @foreach($longFields as $col)
                @php $rawVal = $decode($record->{$col} ?? null); @endphp
                <div class="long-field">
                    <span class="lbl">{{ EvaluationMeta::fieldLabel($tabla, $col) }}</span>
                    <span class="val">{!! $formatValue($rawVal) !!}</span>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

{{-- ===================== SECCIONES DINÁMICAS POR TIPO ===================== --}}

@if($tabla === 'fis_sensitivitys')
    @php
        // Dermatomas: cada uno tiene 3 columnas booleanas (_zn / _zs / _za)
        $regions = [
            'Cervical (C1–C8)'   => ['c1','c2','c3','c4','c5','c6','c7','c8'],
            'Torácico (T1–T12)'  => ['t1','t2','t3','t4','t5','t6','t7','t8','t9','t10','t11','t12'],
            'Lumbar (L1–L4)'     => ['l1','l2','l3','l4'],
            'Sacro (S1–S5)'      => ['s1','s2','s3','s4','s5'],
        ];
        $stateLabel = function ($rec, $code) {
            if ((int)($rec->{$code.'_zn'} ?? 0) === 1) return 'Normal';
            if ((int)($rec->{$code.'_zs'} ?? 0) === 1) return 'Sensible';
            if ((int)($rec->{$code.'_za'} ?? 0) === 1) return 'Alterada';
            return null;
        };
    @endphp
    @foreach($regions as $regionLabel => $codes)
        @php $rows = collect($codes)->map(fn($c) => ['code' => strtoupper($c), 'state' => $stateLabel($record, $c)])
                                     ->filter(fn($r) => $r['state'] !== null); @endphp
        @if($rows->count())
            <div class="section">
                <div class="section-title">{{ $regionLabel }}</div>
                <table class="fields-table">
                    @foreach($rows as $r)
                        <tr>
                            <td class="field-label">{{ $r['code'] }}</td>
                            <td class="field-value">{{ $r['state'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    @endforeach
@endif

@if($tabla === 'fis_evalineps')
    @php
        $views = ['ld' => 'Lateral derecho', 'po' => 'Posterior', 'an' => 'Anterior', 'li' => 'Lateral izquierdo'];
        $parts = ['cabeza' => 'Cabeza', 'hombros' => 'Hombros', 'codos' => 'Codos', 'torax' => 'Tórax',
                  'omoplatos' => 'Omóplatos', 'columna' => 'Columna', 'abdomen' => 'Abdomen',
                  'pelvis' => 'Pelvis', 'muslos' => 'Muslos', 'rodillas' => 'Rodillas',
                  'piernas' => 'Piernas', 'pies' => 'Pies'];
    @endphp
    <div class="section">
        <div class="section-title">Evaluación postural por vista</div>
        <table class="fields-table">
            <tr>
                <td class="field-label" style="background:#9F93E7;color:#fff;font-weight:bold;text-align:center;">Parte</td>
                @foreach($views as $vk => $vlbl)
                    <td class="field-label" style="background:#9F93E7;color:#fff;font-weight:bold;text-align:center;">{{ $vlbl }}</td>
                @endforeach
            </tr>
            @foreach($parts as $pk => $plbl)
                @php
                    $hasAny = false;
                    foreach($views as $vk => $vlbl) {
                        if (!empty($record->{$vk.'_'.$pk})) { $hasAny = true; break; }
                    }
                @endphp
                @if($hasAny)
                    <tr>
                        <td class="field-label" style="font-weight:600;">{{ $plbl }}</td>
                        @foreach($views as $vk => $vlbl)
                            @php $val = $decode($record->{$vk.'_'.$pk} ?? null); @endphp
                            <td class="field-value" style="text-align:center;">{!! $formatValue($val) !!}</td>
                        @endforeach
                    </tr>
                @endif
            @endforeach
        </table>
    </div>

    {{-- Fotos posturales si existen --}}
    @php
        $hasPhotos = false;
        foreach(['foto1','foto2','foto3','foto4'] as $fk) {
            if (!empty($record->{$fk})) { $hasPhotos = true; break; }
        }
    @endphp
    @if($hasPhotos)
        <div class="section">
            <div class="section-title">Fotografías posturales</div>
            <table class="fields-table">
                <tr>
                    @foreach(['foto1' => 'Lateral derecho', 'foto2' => 'Posterior', 'foto3' => 'Anterior', 'foto4' => 'Lateral izquierdo'] as $fk => $flbl)
                        @php $url = $record->{$fk} ?? null; @endphp
                        @if($url)
                            <td style="width:25%; text-align:center; padding:6pt;">
                                @php
                                    // Las fotos pueden vivir en dos lugares según cómo se subieron:
                                    //   - public/uploadfiles/...   (UtilityRepository::saveFile)
                                    //   - storage/app/public/...   (disco "public" de Laravel)
                                    // Probamos ambas ubicaciones del filesystem (mPDF lee local).
                                    $clean = ltrim($url, '/');
                                    $candidates = [
                                        public_path($clean),                              // public/uploadfiles/xxx.jpg
                                        storage_path('app/public/' . $clean),             // storage/app/public/evalineps/xxx.jpg
                                        public_path('storage/' . $clean),                 // via symlink (mismo destino)
                                    ];
                                    $imgPath = null;
                                    foreach ($candidates as $c) {
                                        if (file_exists($c)) { $imgPath = $c; break; }
                                    }
                                @endphp
                                @if($imgPath)
                                    <img src="{{ $imgPath }}" style="max-width:110pt; max-height:140pt;">
                                @else
                                    <span class="text-muted">[imagen no disponible]</span>
                                @endif
                                <div style="font-size:7.5pt; color:#5e4fbf; font-weight:bold; margin-top:3pt;">{{ $flbl }}</div>
                            </td>
                        @endif
                    @endforeach
                </tr>
            </table>
        </div>
    @endif
@endif

{{-- ===================== FOOTER ===================== --}}
<div class="signature-block">
    <div class="signature-line">
        {{ $user->name ?? '—' }}
    </div>
    <div class="signature-meta">
        Fisioterapeuta responsable<br>
        Evaluación realizada el {{ \Carbon\Carbon::parse($record->fecha ?? now())->format('d/m/Y') }}
    </div>
</div>

<div class="pdf-footer">
    <table style="width:100%;">
        <tr>
            <td style="width:50%;">
                <strong>Healing Hands</strong> — Spa terapéutico profesional<br>
                <span style="color:#9F93E7;">Un lugar para sanar, relajarse, rejuvenecer y revitalizarse</span>
            </td>
            <td style="width:50%; text-align:right;">
                @if($company && !empty($company->phone))
                    Tel: {{ $company->phone }}<br>
                @endif
                @if($company && !empty($company->email))
                    {{ $company->email }}<br>
                @endif
                Documento generado el {{ now()->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>
</div>

</body>
</html>
