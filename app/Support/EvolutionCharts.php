<?php

namespace App\Support;

/**
 * Fase 11 — Metadatos de gráficos de evolución temporal.
 *
 * Para cada tipo de evaluación, declara qué series temporales mostrar:
 *   - name:      columna en BD
 *   - label:     leyenda del gráfico
 *   - color:     hex (paleta de marca)
 *   - direction: 'higher' (mejora subir), 'lower' (mejora bajar), 'none'
 *
 * El endpoint /patient-evolution lee este registro para extraer y devolver
 * solo las columnas relevantes — evita serializar el record completo.
 */
class EvolutionCharts
{
    /**
     * @return array {tabla => {chart_def}}
     */
    public static function definitions(): array
    {
        // Paleta marca para colores consistentes
        $purple = '#9F93E7';
        $mauve  = '#DFBEF4';
        $blue   = '#5bbfd6';
        $charcoal = '#2F4157';
        $green  = '#31ce36';
        $orange = '#ffad46';
        $red    = '#dc3545';
        $pink   = '#f29ab3';

        return [

            'fis_evdolors' => [
                'title'   => 'Evolución del dolor (EVA)',
                'icon'    => 'fa-heart-broken',
                'y_min'   => 0,
                'y_max'   => 10,
                'y_label' => 'EVA (0-10)',
                'series'  => [
                    [ 'name' => 'pain_severity',                'label' => 'EVA actual',          'color' => $red,    'direction' => 'lower' ],
                    [ 'name' => 'pain_usual_intensity',         'label' => 'EVA habitual',        'color' => $orange, 'direction' => 'lower' ],
                    [ 'name' => 'pain_reduction_effectiveness', 'label' => 'Efectividad alivio', 'color' => $green,  'direction' => 'higher' ],
                ],
            ],

            'fis_antropometrias' => [
                'title'   => 'Equilibrio (Tinetti)',
                'icon'    => 'fa-balance-scale',
                'y_min'   => 0,
                'y_max'   => 15,
                'y_label' => 'Puntaje (0-15)',
                'series'  => [
                    [ 'name' => 'total_puntaje', 'label' => 'Puntaje total', 'color' => $purple, 'direction' => 'higher' ],
                ],
            ],

            'fis_antropoms' => [
                'title'   => 'Peso y talla',
                'icon'    => 'fa-ruler',
                'y_label' => 'Medida',
                'series'  => [
                    [ 'name' => 'peso',  'label' => 'Peso (kg)',  'color' => $blue,   'direction' => 'none' ],
                    [ 'name' => 'talla', 'label' => 'Talla (cm)', 'color' => $purple, 'direction' => 'none' ],
                ],
            ],

            'fis_goniometrias' => [
                'title'   => 'Goniometría — rangos clave',
                'icon'    => 'fa-compass',
                'y_label' => 'Grados (°)',
                'y_min'   => 0,
                'series'  => [
                    [ 'name' => 'hombro_flex_izq',  'label' => 'Hombro flex IZQ', 'color' => $purple, 'direction' => 'higher' ],
                    [ 'name' => 'hombro_flex_der',  'label' => 'Hombro flex DER', 'color' => $mauve,  'direction' => 'higher' ],
                    [ 'name' => 'rodilla_flex_izq', 'label' => 'Rodilla flex IZQ','color' => $blue,   'direction' => 'higher' ],
                    [ 'name' => 'rodilla_flex_der', 'label' => 'Rodilla flex DER','color' => $green,  'direction' => 'higher' ],
                ],
            ],

            'fis_cheqmus' => [
                'title'   => 'Fuerza muscular (Daniels)',
                'icon'    => 'fa-dumbbell',
                'y_min'   => 0,
                'y_max'   => 5,
                'y_label' => 'Grado (0-5)',
                'series'  => [
                    [ 'name' => 'fcm_ho_if', 'label' => 'Hombro flex IZQ', 'color' => $purple, 'direction' => 'higher' ],
                    [ 'name' => 'fcm_ho_df', 'label' => 'Hombro flex DER', 'color' => $mauve,  'direction' => 'higher' ],
                    [ 'name' => 'fcm_co_if', 'label' => 'Codo flex IZQ',   'color' => $blue,   'direction' => 'higher' ],
                    [ 'name' => 'fcm_co_df', 'label' => 'Codo flex DER',   'color' => $green,  'direction' => 'higher' ],
                ],
            ],
        ];
    }

    /**
     * Devuelve solo las definiciones cuya tabla existe y tiene ≥2 records
     * activos para este paciente.
     */
    public static function definitionsFor(int $patientId): array
    {
        $defs = self::definitions();
        $out  = [];
        foreach ($defs as $tabla => $def) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($tabla)) continue;
            $count = \Illuminate\Support\Facades\DB::table($tabla)
                ->where('patient_id', $patientId)
                ->where('status', 1)
                ->count();
            if ($count >= 2) {
                $out[$tabla] = $def;
            }
        }
        return $out;
    }
}
