<?php

namespace App\Support;

/**
 * Metadatos de presentación para las evaluaciones FormFisios.
 *
 * Mantiene el mismo significado clínico que EVAL_INLINE_CONFIGS en
 * public/js/custom/patient/expediente.js, pero solo lo necesario
 * para renderizar PDFs y otros reportes server-side:
 *
 *  - labels(tabla)   → ['col_name' => 'Etiqueta legible']
 *  - sections(tabla) → [['title' => '...', 'fields' => [...]]] orden y agrupación
 *  - displayName(tabla) → nombre humano de la evaluación
 *
 * Convención: solo aparecen aquí los campos que el fisio/paciente
 * verá en el PDF. Columnas internas (id, status, user_id, created_at,
 * created_by, updated_at, updated_by, Campopersonalizado*) se omiten.
 */
class EvaluationMeta
{
    /** Nombre humano por tabla — encabezado del PDF */
    public static function displayName(string $tabla): string
    {
        return self::DISPLAY_NAMES[$tabla] ?? ucfirst(str_replace('fis_', '', $tabla));
    }

    /** Devuelve las secciones (con sus campos en orden) para una tabla */
    public static function sections(string $tabla): array
    {
        return self::SECTIONS[$tabla] ?? [
            ['title' => 'Datos', 'fields' => []],
        ];
    }

    /** Devuelve el label legible de un campo */
    public static function fieldLabel(string $tabla, string $col): string
    {
        return self::LABELS[$tabla][$col] ?? self::humanize($col);
    }

    /** Fallback: convierte snake_case → "Snake case" si no hay label registrado */
    private static function humanize(string $col): string
    {
        return ucfirst(str_replace('_', ' ', $col));
    }

    private const DISPLAY_NAMES = [
        'fis_evdolors'        => 'Evaluación de dolor',
        'fis_cheqs'           => 'Chequeo general',
        'fis_evpiels'         => 'Evaluación de piel',
        'fis_antropometrias'  => 'Antropometría T.F (equilibrio Tinetti)',
        'fis_antropoms'       => 'Antropometría',
        'fis_goniometrias'    => 'Goniometría',
        'fis_cheqmus'         => 'Chequeo muscular completo',
        'fis_sensitivitys'    => 'Sensibilidad — dermatomas',
        'fis_evalineps'       => 'Alineación postural',
        'fis_electros'        => 'Electroterapia',
        'fis_ultras'          => 'Ultrasonido',
    ];

    /**
     * Labels por tabla y por columna. Solo campos VISIBLES en PDF.
     * El orden se controla desde SECTIONS, no desde aquí.
     */
    private const LABELS = [
        'fis_evdolors' => [
            'fecha'                            => 'Fecha',
            'pain_severity'                    => 'EVA actual (0-10)',
            'pain_usual_intensity'             => 'EVA habitual',
            'pain_reduction_effectiveness'     => 'Efectividad del reductor',
            'pain_location'                    => 'Ubicación del dolor',
            'pain_start_when'                  => '¿Cuándo comenzó?',
            'pain_quality'                     => 'Calidad del dolor',
            'pain_aggravating_factors'         => 'Factores agravantes',
            'pain_relieving_factors'           => 'Factores que alivian',
            'pain_associated_symptoms'         => 'Síntomas asociados',
            'pain_history'                     => 'Antecedentes',
            'pain_impact_daily_activities'     => 'Impacto en actividades diarias',
            'pain_psychosocial_factors'        => 'Factores psicosociales',
            'pain_emotional_response'          => 'Respuesta emocional',
            'pain_reduction_method'            => 'Método utilizado para reducir',
            'pain_environmental_triggers'      => 'Detonantes ambientales',
            'pain_pharmacological_treatment'   => 'Tratamiento farmacológico',
            'pain_non_pharmacological_treatment' => 'Tratamiento no farmacológico',
            'diagnostico'                      => 'Diagnóstico',
            'observaciones'                    => 'Observaciones',
        ],

        'fis_evpiels' => [
            'fecha'                            => 'Fecha',
            'zonas'                            => 'Zonas evaluadas',
            'estado_piel_izquierdo_anterior'   => 'Izquierdo · Anterior',
            'estado_piel_derecho_anterior'     => 'Derecho · Anterior',
            'estado_piel_izquierdo_posterior'  => 'Izquierdo · Posterior',
            'estado_piel_derecho_posterior'    => 'Derecho · Posterior',
            'diagnostico'                      => 'Diagnóstico',
            'observaciones'                    => 'Observaciones',
        ],

        'fis_antropometrias' => [
            'fecha'                            => 'Fecha',
            'total_puntaje'                    => 'Puntaje total (0-15)',
            'equi_s'                           => 'Equilibrio sentado',
            'lev_i'                            => 'Levantarse',
            'int_i'                            => 'Intento de levantarse',
            'equil_i'                          => 'Equilibrio inmediato al levantarse',
            'equib_i'                          => 'Equilibrio en bipedestación',
            'em_t'                             => 'Empujón',
            'oj_i'                             => 'Ojos cerrados',
            'gir_p'                            => 'Giro de 360°',
            'se_i'                             => 'Sentarse',
            'diagnostico'                      => 'Diagnóstico',
            'observaciones'                    => 'Observaciones',
        ],

        'fis_antropoms' => [
            'fecha'              => 'Fecha',
            'peso'               => 'Peso (kg)',
            'talla'              => 'Talla (cm)',
            'brazo_flex_izq'     => '1. Brazo flexionado IZQ',
            'brazo_flex_der'     => '1. Brazo flexionado DER',
            'brazo_rela_izq'     => '2. Brazo relajado IZQ',
            'brazo_rela_der'     => '2. Brazo relajado DER',
            'anteb_izq'          => '3. Antebrazo IZQ',
            'anteb_der'          => '3. Antebrazo DER',
            'mu_izq'             => '4. Muñeca IZQ',
            'mu_der'             => '4. Muñeca DER',
            'mus_izq'            => '5. Muslo IZQ',
            'mus_der'            => '5. Muslo DER',
            'pant_izq'           => '6. Pantorrilla IZQ',
            'pant_der'           => '6. Pantorrilla DER',
            'tob_izq'            => '7. Tobillo IZQ',
            'tob_der'            => '7. Tobillo DER',
            'cabeza_izq'         => '8. Cabeza IZQ',
            'cabeza_der'         => '8. Cabeza DER',
            'cue_izq'            => '9. Cuello IZQ',
            'cue_der'            => '9. Cuello DER',
            'tor_izq'            => '10. Tórax IZQ',
            'tor_der'            => '10. Tórax DER',
            'cint_izq'           => '11. Cintura IZQ',
            'cint_der'           => '11. Cintura DER',
            'cade_izq'           => '12. Cadera IZQ',
            'cade_der'           => '12. Cadera DER',
            'lug'                => 'Edema — Lugar',
            'diam'               => 'Edema — Diámetro (cm)',
            'observaciones2'     => 'Edema — Observaciones',
            'hipo'               => 'Tono: Hipotonía',
            'hipe'               => 'Tono: Hipertonía',
            'fluc'               => 'Tono: TM Fluctuante',
            'tm_n'               => 'Tono: TM Normal',
            'observaciones'      => 'Observaciones generales',
            'observaciones_res'  => 'Observaciones y resultados',
        ],

        'fis_goniometrias' => [
            'fecha'                       => 'Fecha',
            // HOMBRO
            'hombro_flex_izq'             => 'Hombro · Flexión IZQ',
            'hombro_flex_der'             => 'Hombro · Flexión DER',
            'hombro_ext_izq'              => 'Hombro · Extensión IZQ',
            'hombro_ext_der'              => 'Hombro · Extensión DER',
            'hombro_ad_izq'               => 'Hombro · Aducción IZQ',
            'hombro_ad_der'               => 'Hombro · Aducción DER',
            'hombro_abd_izq'              => 'Hombro · Abducción IZQ',
            'hombro_abd_der'              => 'Hombro · Abducción DER',
            'hombro_rot_int_izq'          => 'Hombro · Rot. interna IZQ',
            'hombro_rot_int_der'          => 'Hombro · Rot. interna DER',
            'hombro_rot_ext_izq'          => 'Hombro · Rot. externa IZQ',
            'hombro_rot_ext_der'          => 'Hombro · Rot. externa DER',
            // CODO
            'codo_flex_izq'               => 'Codo · Flexión IZQ',
            'codo_flex_der'               => 'Codo · Flexión DER',
            'codo_ext_izq'                => 'Codo · Extensión IZQ',
            'codo_ext_der'                => 'Codo · Extensión DER',
            'codo_pro_izq'                => 'Codo · Pronación IZQ',
            'codo_pro_der'                => 'Codo · Pronación DER',
            'codo_sup_izq'                => 'Codo · Supinación IZQ',
            'codo_sup_der'                => 'Codo · Supinación DER',
            // MUÑECA
            'muneca_flex_dorsal_izq'      => 'Muñeca · Flex dorsal IZQ',
            'muneca_flex_dorsal_der'      => 'Muñeca · Flex dorsal DER',
            'muneca_flex_palmar_izq'      => 'Muñeca · Flex palmar IZQ',
            'muneca_flex_palmar_der'      => 'Muñeca · Flex palmar DER',
            'muneca_desv_radial_izq'      => 'Muñeca · Desv. radial IZQ',
            'muneca_desv_radial_der'      => 'Muñeca · Desv. radial DER',
            'muneca_desv_cubital_izq'     => 'Muñeca · Desv. cubital IZQ',
            'muneca_desv_cubital_der'     => 'Muñeca · Desv. cubital DER',
            // CADERA
            'cadera_flex_recta_izq'       => 'Cadera · Flex recta IZQ',
            'cadera_flex_recta_der'       => 'Cadera · Flex recta DER',
            'cadera_ex_recta_izq'         => 'Cadera · Ext recta IZQ',
            'cadera_ex_recta_der'         => 'Cadera · Ext recta DER',
            'cadera_flex_flexionada_izq'  => 'Cadera · Flex flexionada IZQ',
            'cadera_flex_flexionada_der'  => 'Cadera · Flex flexionada DER',
            'cadera_ext_flexionada_izq'   => 'Cadera · Ext flexionada IZQ',
            'cadera_ext_flexionada_der'   => 'Cadera · Ext flexionada DER',
            'cadera_ext_izq'              => 'Cadera · Extensión IZQ',
            'cadera_ext_der'              => 'Cadera · Extensión DER',
            'cadera_ad_izq'               => 'Cadera · Aducción IZQ',
            'cadera_ad_der'               => 'Cadera · Aducción DER',
            'cadera_abd_izq'              => 'Cadera · Abducción IZQ',
            'cadera_abd_der'              => 'Cadera · Abducción DER',
            'cadera_rot_int_izq'          => 'Cadera · Rot. interna IZQ',
            'cadera_rot_int_der'          => 'Cadera · Rot. interna DER',
            'cadera_rot_ext_izq'          => 'Cadera · Rot. externa IZQ',
            'cadera_rot_ext_der'          => 'Cadera · Rot. externa DER',
            // RODILLA
            'rodilla_flex_izq'            => 'Rodilla · Flexión IZQ',
            'rodilla_flex_der'            => 'Rodilla · Flexión DER',
            'rodilla_ext_izq'             => 'Rodilla · Extensión IZQ',
            'rodilla_ext_der'             => 'Rodilla · Extensión DER',
            // TOBILLO
            'tobillo_flex_plantar_izq'    => 'Tobillo · Flex plantar IZQ',
            'tobillo_flex_plantar_der'    => 'Tobillo · Flex plantar DER',
            'tobillo_flex_dorsal_izq'     => 'Tobillo · Flex dorsal IZQ',
            'tobillo_flex_dorsal_der'     => 'Tobillo · Flex dorsal DER',
            'tobillo_inversion_izq'       => 'Tobillo · Inversión IZQ',
            'tobillo_inversion_der'       => 'Tobillo · Inversión DER',
            'tobillo_eversion_izq'        => 'Tobillo · Eversión IZQ',
            'tobillo_eversion_der'        => 'Tobillo · Eversión DER',
            // Cierre
            'diagnostico'                 => 'Diagnóstico',
            'observaciones'               => 'Observaciones',
        ],

        'fis_cheqmus' => [
            'fecha'              => 'Fecha',
            // Cuello
            'fcm_cu_if'          => 'Cuello · Flexión IZQ',
            'fcm_cu_df'          => 'Cuello · Flexión DER',
            'fcm_cu_ie'          => 'Cuello · Extensión IZQ',
            'fcm_cu_de'          => 'Cuello · Extensión DER',
            // Trapecio
            'fcm_tr_if'          => 'Trapecio · Flexión IZQ',
            'fcm_tr_df'          => 'Trapecio · Flexión DER',
            'fcm_tr_ie'          => 'Trapecio · Extensión IZQ',
            'fcm_tr_de'          => 'Trapecio · Extensión DER',
            'fcm_tr_ir'          => 'Trapecio · Rotación IZQ',
            'fcm_tr_dr'          => 'Trapecio · Rotación DER',
            // Hombro
            'fcm_ho_if'          => 'Hombro · Flexión IZQ',
            'fcm_ho_df'          => 'Hombro · Flexión DER',
            'fcm_ho_ie'          => 'Hombro · Extensión IZQ',
            'fcm_ho_de'          => 'Hombro · Extensión DER',
            'fcm_ho_ia'          => 'Hombro · Abducción IZQ',
            'fcm_ho_da'          => 'Hombro · Abducción DER',
            'fcm_ho_ic'          => 'Hombro · Aducción IZQ',
            'fcm_ho_dc'          => 'Hombro · Aducción DER',
            'fcm_ho_ir'          => 'Hombro · Rot. interna IZQ',
            'fcm_ho_dr'          => 'Hombro · Rot. interna DER',
            'fcm_ho_ix'          => 'Hombro · Rot. externa IZQ',
            'fcm_ho_dx'          => 'Hombro · Rot. externa DER',
            // Codo
            'fcm_co_if'          => 'Codo · Flexión IZQ',
            'fcm_co_df'          => 'Codo · Flexión DER',
            'fcm_co_ie'          => 'Codo · Extensión IZQ',
            'fcm_co_de'          => 'Codo · Extensión DER',
            // Antebrazo
            'fcm_an_ia'          => 'Antebrazo · Pronación IZQ',
            'fcm_an_da'          => 'Antebrazo · Pronación DER',
            'fcm_an_is'          => 'Antebrazo · Supinación IZQ',
            'fcm_an_ds'          => 'Antebrazo · Supinación DER',
            // Muñeca
            'fcm_mu_im'          => 'Muñeca · Flex/Ext (m) IZQ',
            'fcm_mu_dm'          => 'Muñeca · Flex/Ext (m) DER',
            'fcm_mu_ie'          => 'Muñeca · Flex/Ext (e) IZQ',
            'fcm_mu_de'          => 'Muñeca · Flex/Ext (e) DER',
            // Rodilla
            'fcm_ro_if'          => 'Rodilla · Flexión IZQ',
            'fcm_ro_df'          => 'Rodilla · Flexión DER',
            'fcm_ro_ix'          => 'Rodilla · Extensión IZQ',
            'fcm_ro_dx'          => 'Rodilla · Extensión DER',
            // Tronco
            'fcm_to_ii'          => 'Tronco i IZQ',
            'fcm_to_di'          => 'Tronco i DER',
            'fcm_to_ie'          => 'Tronco e IZQ',
            'fcm_to_de'          => 'Tronco e DER',
            'fcm_to_if'          => 'Tronco f IZQ',
            'fcm_to_df'          => 'Tronco f DER',
            'fcm_to_id'          => 'Tronco d IZQ',
            'fcm_to_dd'          => 'Tronco d DER',
            // Espalda
            'fcm_es_ie'          => 'Espalda e IZQ',
            'fcm_es_de'          => 'Espalda e DER',
            'fcm_es_id'          => 'Espalda d IZQ',
            'fcm_es_dd'          => 'Espalda d DER',
            'fcm_es_ia'          => 'Espalda a IZQ',
            'fcm_es_da'          => 'Espalda a DER',
            'fcm_es_ic'          => 'Espalda c IZQ',
            'fcm_es_dc'          => 'Espalda c DER',
            // Cabeza/cervical
            'fcm_ca_if'          => 'Cabeza · Flex/Ext (1) IZQ',
            'fcm_ca_ef'          => 'Cabeza · Flex/Ext (1) DER',
            'fcm_ca_ie'          => 'Cabeza · Flex/Ext (2) IZQ',
            'fcm_ca_de'          => 'Cabeza · Flex/Ext (2) DER',
            'fcm_ca_ia'          => 'Cabeza · Lat. (a) IZQ',
            'fcm_ca_da'          => 'Cabeza · Lat. (a) DER',
            'fcm_ca_in'          => 'Cabeza · Lat. (n) IZQ',
            'fcm_ca_dn'          => 'Cabeza · Lat. (n) DER',
            'fcm_ca_ir'          => 'Cabeza · Rotación IZQ',
            'fcm_ca_dr'          => 'Cabeza · Rotación DER',
            'fcm_ca_ix'          => 'Cabeza · Otro IZQ',
            'fcm_ca_dx'          => 'Cabeza · Otro DER',
            // Cierre
            'Diagnostico'        => 'Diagnóstico',
            'Observaciones'      => 'Observaciones',
        ],

        'fis_sensitivitys' => [
            'fecha'         => 'Fecha',
            'Diagnostico'   => 'Diagnóstico',
            'Observaciones' => 'Observaciones',
            // Los 29 dermatomas se etiquetan dinámicamente en el blade
        ],

        'fis_evalineps' => [
            'fecha'              => 'Fecha',
            'foto1'              => 'Foto 1 — Lateral derecho',
            'foto2'              => 'Foto 2 — Posterior',
            'foto3'              => 'Foto 3 — Anterior',
            'foto4'              => 'Foto 4 — Lateral izquierdo',
            'diagnostico'        => 'Diagnóstico',
            'observaciones'      => 'Observaciones',
            // Los 48 campos de la grilla postural (4 vistas × 12 partes) se etiquetan dinámicamente
        ],

        'fis_electros' => [
            'fecha'                  => 'Fecha',
            'seccion'                => 'Sección anatómica',
            'current_type'           => 'Tipo de corriente',
            'waveform'               => 'Waveform',
            'display'                => 'Display',
            'cc_cv'                  => 'CC / CV',
            'method'                 => 'Method',
            'carrier_frequency'      => 'Carrier Frecuencia',
            'channel_mode'           => 'Channel Mode',
            'frequency_mhz'          => 'Frecuencia (MHz)',
            'burst_frequency'        => 'Burst Freq.',
            'vector_scan'            => 'Vector Scan',
            'duty_cycle'             => 'Duty Cycle',
            'treatment_time'         => 'Treatment Time',
            'anti_fatigue'           => 'Anti-Fatigue',
            'cycle_time'             => 'Cycle Time',
            'frequency_modulation'   => 'Freq. Mod.',
            'polarity'               => 'Polarity',
            'amplitude_modulation'   => 'Amplish. Mod.',
            'ramp'                   => 'Ramp',
            'phase_duration'         => 'Phase Duration',
            'diagnostico'            => 'Diagnóstico',
            'observaciones'          => 'Observaciones',
        ],

        'fis_ultras' => [
            'fecha'                  => 'Fecha',
            'current_type'           => 'Tipo',
            'waveform'               => 'Waveform',
            'display'                => 'Display',
            'cc_cv'                  => 'CC / CV',
            'method'                 => 'Method',
            'diagnostico'            => 'Diagnóstico',
            'observaciones'          => 'Observaciones',
        ],
    ];

    /**
     * Secciones por tabla — agrupación visual en el PDF.
     * Cada sección tiene 'title' y 'fields' (lista de columnas en orden).
     */
    private const SECTIONS = [
        'fis_evdolors' => [
            ['title' => 'Datos generales', 'fields' => ['fecha']],
            ['title' => 'Escala Visual Analógica (EVA)', 'fields' => [
                'pain_severity', 'pain_usual_intensity', 'pain_reduction_effectiveness',
            ]],
            ['title' => 'Caracterización del dolor', 'fields' => [
                'pain_location', 'pain_start_when', 'pain_quality',
                'pain_aggravating_factors', 'pain_relieving_factors',
                'pain_associated_symptoms', 'pain_history',
            ]],
            ['title' => 'Impacto y contexto', 'fields' => [
                'pain_impact_daily_activities', 'pain_psychosocial_factors',
                'pain_emotional_response', 'pain_environmental_triggers',
            ]],
            ['title' => 'Tratamiento', 'fields' => [
                'pain_reduction_method', 'pain_pharmacological_treatment', 'pain_non_pharmacological_treatment',
            ]],
            ['title' => 'Cierre', 'fields' => ['diagnostico', 'observaciones']],
        ],

        'fis_evpiels' => [
            ['title' => 'Datos generales', 'fields' => ['fecha', 'zonas']],
            ['title' => 'Estado de la piel por hemicuerpo', 'fields' => [
                'estado_piel_izquierdo_anterior', 'estado_piel_derecho_anterior',
                'estado_piel_izquierdo_posterior', 'estado_piel_derecho_posterior',
            ]],
            ['title' => 'Cierre', 'fields' => ['diagnostico', 'observaciones']],
        ],

        'fis_antropometrias' => [
            ['title' => 'Datos generales', 'fields' => ['fecha', 'total_puntaje']],
            ['title' => 'Ítems del test de Tinetti', 'fields' => [
                'equi_s', 'lev_i', 'int_i', 'equil_i', 'equib_i',
                'em_t', 'oj_i', 'gir_p', 'se_i',
            ]],
            ['title' => 'Cierre', 'fields' => ['diagnostico', 'observaciones']],
        ],

        'fis_antropoms' => [
            ['title' => 'Datos generales', 'fields' => ['fecha', 'peso', 'talla']],
            ['title' => 'Perímetros (cm)', 'fields' => [
                'brazo_flex_izq', 'brazo_flex_der',
                'brazo_rela_izq', 'brazo_rela_der',
                'anteb_izq', 'anteb_der',
                'mu_izq', 'mu_der',
                'mus_izq', 'mus_der',
                'pant_izq', 'pant_der',
                'tob_izq', 'tob_der',
                'cabeza_izq', 'cabeza_der',
                'cue_izq', 'cue_der',
                'tor_izq', 'tor_der',
                'cint_izq', 'cint_der',
                'cade_izq', 'cade_der',
            ]],
            ['title' => 'Edema / inflamación', 'fields' => ['lug', 'diam', 'observaciones2']],
            ['title' => 'Tono muscular', 'fields' => ['hipo', 'hipe', 'fluc', 'tm_n']],
            ['title' => 'Cierre', 'fields' => ['observaciones', 'observaciones_res']],
        ],

        'fis_goniometrias' => [
            ['title' => 'Datos generales', 'fields' => ['fecha']],
            ['title' => 'Hombro (°)', 'fields' => [
                'hombro_flex_izq', 'hombro_flex_der',
                'hombro_ext_izq', 'hombro_ext_der',
                'hombro_ad_izq', 'hombro_ad_der',
                'hombro_abd_izq', 'hombro_abd_der',
                'hombro_rot_int_izq', 'hombro_rot_int_der',
                'hombro_rot_ext_izq', 'hombro_rot_ext_der',
            ]],
            ['title' => 'Codo (°)', 'fields' => [
                'codo_flex_izq', 'codo_flex_der',
                'codo_ext_izq', 'codo_ext_der',
                'codo_pro_izq', 'codo_pro_der',
                'codo_sup_izq', 'codo_sup_der',
            ]],
            ['title' => 'Muñeca (°)', 'fields' => [
                'muneca_flex_dorsal_izq', 'muneca_flex_dorsal_der',
                'muneca_flex_palmar_izq', 'muneca_flex_palmar_der',
                'muneca_desv_radial_izq', 'muneca_desv_radial_der',
                'muneca_desv_cubital_izq', 'muneca_desv_cubital_der',
            ]],
            ['title' => 'Cadera (°)', 'fields' => [
                'cadera_flex_recta_izq', 'cadera_flex_recta_der',
                'cadera_ex_recta_izq', 'cadera_ex_recta_der',
                'cadera_flex_flexionada_izq', 'cadera_flex_flexionada_der',
                'cadera_ext_flexionada_izq', 'cadera_ext_flexionada_der',
                'cadera_ext_izq', 'cadera_ext_der',
                'cadera_ad_izq', 'cadera_ad_der',
                'cadera_abd_izq', 'cadera_abd_der',
                'cadera_rot_int_izq', 'cadera_rot_int_der',
                'cadera_rot_ext_izq', 'cadera_rot_ext_der',
            ]],
            ['title' => 'Rodilla (°)', 'fields' => [
                'rodilla_flex_izq', 'rodilla_flex_der',
                'rodilla_ext_izq', 'rodilla_ext_der',
            ]],
            ['title' => 'Tobillo (°)', 'fields' => [
                'tobillo_flex_plantar_izq', 'tobillo_flex_plantar_der',
                'tobillo_flex_dorsal_izq', 'tobillo_flex_dorsal_der',
                'tobillo_inversion_izq', 'tobillo_inversion_der',
                'tobillo_eversion_izq', 'tobillo_eversion_der',
            ]],
            ['title' => 'Cierre', 'fields' => ['diagnostico', 'observaciones']],
        ],

        'fis_cheqmus' => [
            ['title' => 'Datos generales', 'fields' => ['fecha']],
            ['title' => 'Cuello (Daniels 0-5)', 'fields' => [
                'fcm_cu_if', 'fcm_cu_df', 'fcm_cu_ie', 'fcm_cu_de',
            ]],
            ['title' => 'Trapecio / hombro alto', 'fields' => [
                'fcm_tr_if', 'fcm_tr_df', 'fcm_tr_ie', 'fcm_tr_de', 'fcm_tr_ir', 'fcm_tr_dr',
            ]],
            ['title' => 'Cabeza / cervical', 'fields' => [
                'fcm_ca_if', 'fcm_ca_ef', 'fcm_ca_ie', 'fcm_ca_de',
                'fcm_ca_ia', 'fcm_ca_da', 'fcm_ca_in', 'fcm_ca_dn',
                'fcm_ca_ir', 'fcm_ca_dr', 'fcm_ca_ix', 'fcm_ca_dx',
            ]],
            ['title' => 'Hombro', 'fields' => [
                'fcm_ho_if', 'fcm_ho_df', 'fcm_ho_ie', 'fcm_ho_de',
                'fcm_ho_ia', 'fcm_ho_da', 'fcm_ho_ic', 'fcm_ho_dc',
                'fcm_ho_ir', 'fcm_ho_dr', 'fcm_ho_ix', 'fcm_ho_dx',
            ]],
            ['title' => 'Codo', 'fields' => [
                'fcm_co_if', 'fcm_co_df', 'fcm_co_ie', 'fcm_co_de',
            ]],
            ['title' => 'Antebrazo', 'fields' => [
                'fcm_an_ia', 'fcm_an_da', 'fcm_an_is', 'fcm_an_ds',
            ]],
            ['title' => 'Muñeca', 'fields' => [
                'fcm_mu_im', 'fcm_mu_dm', 'fcm_mu_ie', 'fcm_mu_de',
            ]],
            ['title' => 'Tronco / espalda', 'fields' => [
                'fcm_to_ii', 'fcm_to_di', 'fcm_to_ie', 'fcm_to_de',
                'fcm_to_if', 'fcm_to_df', 'fcm_to_id', 'fcm_to_dd',
                'fcm_es_ie', 'fcm_es_de', 'fcm_es_id', 'fcm_es_dd',
                'fcm_es_ia', 'fcm_es_da', 'fcm_es_ic', 'fcm_es_dc',
            ]],
            ['title' => 'Rodilla', 'fields' => [
                'fcm_ro_if', 'fcm_ro_df', 'fcm_ro_ix', 'fcm_ro_dx',
            ]],
            ['title' => 'Cierre', 'fields' => ['Diagnostico', 'Observaciones']],
        ],

        'fis_sensitivitys' => [
            ['title' => 'Datos generales', 'fields' => ['fecha']],
            // Las regiones cervical/torácico/lumbar/sacro se renderizan dinámicamente
            ['title' => 'Cierre', 'fields' => ['Diagnostico', 'Observaciones']],
        ],

        'fis_evalineps' => [
            ['title' => 'Datos generales', 'fields' => ['fecha']],
            // La grilla postural y las fotos se renderizan dinámicamente
            ['title' => 'Cierre', 'fields' => ['diagnostico', 'observaciones']],
        ],

        'fis_electros' => [
            ['title' => 'Datos generales', 'fields' => ['fecha', 'seccion']],
            ['title' => 'Parámetros de la corriente', 'fields' => [
                'current_type', 'waveform', 'display', 'cc_cv', 'method',
                'carrier_frequency', 'channel_mode', 'frequency_mhz',
                'burst_frequency', 'vector_scan', 'duty_cycle',
            ]],
            ['title' => 'Tiempo / modulación', 'fields' => [
                'treatment_time', 'anti_fatigue', 'cycle_time',
                'frequency_modulation', 'polarity', 'amplitude_modulation',
                'ramp', 'phase_duration',
            ]],
            ['title' => 'Cierre', 'fields' => ['diagnostico', 'observaciones']],
        ],

        'fis_ultras' => [
            ['title' => 'Datos generales', 'fields' => ['fecha']],
            ['title' => 'Parámetros', 'fields' => [
                'current_type', 'waveform', 'display', 'cc_cv', 'method',
            ]],
            ['title' => 'Cierre', 'fields' => ['diagnostico', 'observaciones']],
        ],
    ];

    /**
     * Para evaluaciones cuyos campos son demasiados y dinámicos
     * (postural grid de 48 inputs, dermatomas con 3 booleanos cada uno),
     * el blade del PDF se encarga de iterarlos a partir de patrones de columnas.
     * Aquí solo retornamos las secciones "estáticas".
     */
}
