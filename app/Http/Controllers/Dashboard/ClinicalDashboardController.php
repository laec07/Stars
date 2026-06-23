<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 7 — Dashboard clínico de Healing Hands.
 *
 * Provee KPIs operativos para el equipo de fisioterapia:
 *   - Pacientes activos en últimos 30 días
 *   - Sesiones de hoy / semana / mes
 *   - Tendencia de sesiones (8 semanas)
 *   - Distribución de tipos de evaluación
 *   - Sesiones recientes y alertas
 *
 * Toda la data viene de fis_seguimientos, fis_fichas, fis_historys y cmn_patients.
 * NO toca el DashboardController existente (que es de booking).
 */
class ClinicalDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Página HTML del dashboard clínico */
    public function index()
    {
        return view('dashboard.clinical');
    }

    /**
     * Endpoint JSON con todos los datos agregados que consume el dashboard.
     * Una sola llamada → varios bloques. Reduce ida y vuelta.
     */
    public function data()
    {
        try {
            return $this->apiResponse([
                'status' => '1',
                'data'   => [
                    'kpis'             => $this->buildKpis(),
                    'sessionsByWeek'   => $this->buildSessionsByWeek(),
                    'evaluationTypes'  => $this->buildEvaluationTypesDistribution(),
                    'recentSessions'   => $this->buildRecentSessions(),
                    'alerts'           => $this->buildAlerts(),
                    'topTherapists'    => $this->buildTopTherapists(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ClinicalDashboard.data: ' . $e->getMessage());
            return $this->apiResponse([
                'status' => '500',
                'message' => 'Error cargando datos del dashboard',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    // ----------------------------------------------------------------
    // KPI cards
    // ----------------------------------------------------------------
    private function buildKpis(): array
    {
        $today        = Carbon::today();
        $startOfWeek  = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Sesiones hoy
        $sesionesHoy = DB::table('fis_seguimientos')
            ->where('status', 1)
            ->whereDate('fecha', $today)
            ->count();

        // Sesiones esta semana (lunes - hoy)
        $sesionesSemana = DB::table('fis_seguimientos')
            ->where('status', 1)
            ->whereBetween('fecha', [$startOfWeek->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
            ->count();

        // Sesiones este mes
        $sesionesMes = DB::table('fis_seguimientos')
            ->where('status', 1)
            ->whereBetween('fecha', [$startOfMonth->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
            ->count();

        // Pacientes activos (≥ 1 sesión en últimos 30 días)
        $pacientesActivos = DB::table('fis_seguimientos')
            ->where('status', 1)
            ->where('fecha', '>=', $thirtyDaysAgo->format('Y-m-d'))
            ->distinct()
            ->count('patient_id');

        // Pacientes totales activos en BD
        $pacientesTotales = DB::table('cmn_patients')
            ->where('status', 1)
            ->count();

        // Nuevos pacientes este mes
        $pacientesNuevos = DB::table('cmn_patients')
            ->where('status', 1)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        // Fichas clínicas abiertas
        $fichasAbiertas = DB::table('fis_fichas')
            ->where('status', 1)
            ->count();

        // Evaluaciones realizadas este mes
        $evaluacionesMes = 0;
        if (Schema::hasTable('fis_historys')) {
            $evaluacionesMes = DB::table('fis_historys')
                ->where('status', 1)
                ->whereNotIn('tabla_form', ['fis_fichas'])
                ->where('fecha', '>=', $startOfMonth->format('Y-m-d'))
                ->count();
        }

        return [
            'sesiones_hoy'        => $sesionesHoy,
            'sesiones_semana'     => $sesionesSemana,
            'sesiones_mes'        => $sesionesMes,
            'pacientes_activos'   => $pacientesActivos,
            'pacientes_totales'   => $pacientesTotales,
            'pacientes_nuevos'    => $pacientesNuevos,
            'fichas_abiertas'     => $fichasAbiertas,
            'evaluaciones_mes'    => $evaluacionesMes,
        ];
    }

    // ----------------------------------------------------------------
    // Sesiones por semana, últimas 8 semanas
    // Devuelve [{ label: '21 abr', count: 12 }, ...] en orden cronológico
    // ----------------------------------------------------------------
    private function buildSessionsByWeek(): array
    {
        $weeks = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = Carbon::now()->startOfWeek()->subWeeks($i);
            $end   = (clone $start)->endOfWeek();

            $count = DB::table('fis_seguimientos')
                ->where('status', 1)
                ->whereBetween('fecha', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->count();

            $weeks[] = [
                'label' => $start->locale('es')->isoFormat('D MMM'),
                'count' => $count,
            ];
        }
        return $weeks;
    }

    // ----------------------------------------------------------------
    // Distribución por tipo de evaluación (últimos 90 días)
    // ----------------------------------------------------------------
    private function buildEvaluationTypesDistribution(): array
    {
        if (! Schema::hasTable('fis_historys')) return [];

        $since = Carbon::now()->subDays(90)->format('Y-m-d');
        $rows = DB::table('fis_historys')
            ->where('status', 1)
            ->whereNotIn('tabla_form', ['fis_fichas'])
            ->where('fecha', '>=', $since)
            ->select('tabla_form', DB::raw('COUNT(*) as c'))
            ->groupBy('tabla_form')
            ->orderByDesc('c')
            ->get();

        // Mapeo legible
        $labels = [
            'fis_evdolors'        => 'Evaluación de dolor',
            'fis_cheqs'           => 'Chequeo general',
            'fis_evpiels'         => 'Evaluación de piel',
            'fis_antropometrias'  => 'Antropometría T.F',
            'fis_antropoms'       => 'Antropometría',
            'fis_goniometrias'    => 'Goniometría',
            'fis_cheqmus'         => 'Chequeo muscular',
            'fis_sensitivitys'    => 'Sensibilidad',
            'fis_evalineps'       => 'Alineación postural',
            'fis_electros'        => 'Electroterapia',
            'fis_ultras'          => 'Ultrasonido',
        ];

        return $rows->map(function ($r) use ($labels) {
            return [
                'tabla'  => $r->tabla_form,
                'label'  => $labels[$r->tabla_form] ?? $r->tabla_form,
                'count'  => (int) $r->c,
            ];
        })->values()->toArray();
    }

    // ----------------------------------------------------------------
    // Sesiones recientes (últimas 10)
    // ----------------------------------------------------------------
    private function buildRecentSessions(): array
    {
        $rows = DB::table('fis_seguimientos as s')
            ->leftJoin('cmn_patients as p', 's.patient_id', '=', 'p.id')
            ->leftJoin('users as u', 's.user_id', '=', 'u.id')
            ->where('s.status', 1)
            ->orderByDesc('s.fecha')
            ->orderByDesc('s.id')
            ->limit(10)
            ->select(
                's.id', 's.patient_id', 's.fecha', 's.tratamiento_realizado',
                'p.full_name as patient_name',
                'u.name as user_name'
            )
            ->get();

        return $rows->map(function ($r) {
            $tratamiento = (string) ($r->tratamiento_realizado ?? '');
            return [
                'id'             => $r->id,
                'patient_id'     => $r->patient_id,
                'patient_name'   => $r->patient_name,
                'user_name'      => $r->user_name,
                'fecha'          => $r->fecha,
                'fecha_label'    => $r->fecha ? Carbon::parse($r->fecha)->locale('es')->isoFormat('D MMM') : '—',
                'tratamiento'    => mb_strlen($tratamiento) > 80
                                    ? mb_substr($tratamiento, 0, 80) . '…'
                                    : $tratamiento,
            ];
        })->toArray();
    }

    // ----------------------------------------------------------------
    // Alertas operacionales
    // ----------------------------------------------------------------
    private function buildAlerts(): array
    {
        $alerts = [];

        // 1. Pacientes con tratamiento sin sesión en >45 días (posible abandono)
        $sixWeeksAgo = Carbon::now()->subDays(45)->format('Y-m-d');
        $abandonos = DB::table('fis_seguimientos')
            ->select('patient_id', DB::raw('MAX(fecha) as ultima'))
            ->where('status', 1)
            ->groupBy('patient_id')
            ->having('ultima', '<', $sixWeeksAgo)
            ->get();

        if ($abandonos->count() > 0) {
            // Filtrar solo pacientes con ficha abierta (no descartados)
            $patientIds = $abandonos->pluck('patient_id')->all();
            $conFichaAbierta = DB::table('fis_fichas')
                ->where('status', 1)
                ->whereIn('patient_id', $patientIds)
                ->distinct()
                ->pluck('patient_id');

            if ($conFichaAbierta->count() > 0) {
                $alerts[] = [
                    'type'  => 'warning',
                    'icon'  => 'fa-user-clock',
                    'label' => 'Posible abandono',
                    'count' => $conFichaAbierta->count(),
                    'msg'   => 'paciente(s) con ficha abierta sin sesiones en 45+ días',
                ];
            }
        }

        // 2. Fichas sin evaluación inicial (creadas hace >7 días sin evaluaciones)
        if (Schema::hasTable('fis_historys') && Schema::hasColumn('fis_historys', 'ficha_id')) {
            $weekAgo = Carbon::now()->subDays(7)->format('Y-m-d');
            $fichasConEval = DB::table('fis_historys')
                ->where('status', 1)
                ->whereNotIn('tabla_form', ['fis_fichas'])
                ->whereNotNull('ficha_id')
                ->distinct()
                ->pluck('ficha_id');

            $fichasSinEval = DB::table('fis_fichas')
                ->where('status', 1)
                ->where('fecha', '<=', $weekAgo)
                ->whereNotIn('id', $fichasConEval)
                ->count();

            if ($fichasSinEval > 0) {
                $alerts[] = [
                    'type'  => 'info',
                    'icon'  => 'fa-clipboard-list',
                    'label' => 'Sin evaluación',
                    'count' => $fichasSinEval,
                    'msg'   => 'ficha(s) clínica(s) sin evaluación tras 7 días de apertura',
                ];
            }
        }

        // 3. Datos faltantes — pacientes sin teléfono
        $sinTelefono = DB::table('cmn_patients')
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('phone_no')->orWhere('phone_no', '');
            })
            ->count();

        if ($sinTelefono > 0) {
            $alerts[] = [
                'type'  => 'secondary',
                'icon'  => 'fa-phone-slash',
                'label' => 'Sin teléfono',
                'count' => $sinTelefono,
                'msg'   => 'paciente(s) sin teléfono registrado',
            ];
        }

        return $alerts;
    }

    // ----------------------------------------------------------------
    // Top fisioterapeutas por actividad clínica del mes (sesiones + evaluaciones)
    // ----------------------------------------------------------------
    // Se rankea por la actividad total, pero se devuelve el desglose para que
    // el dashboard muestre "X sesiones · Y evaluaciones". Así no se invisibiliza
    // a los fisios que sobre todo realizan evaluaciones.
    private function buildTopTherapists(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');

        // Sesiones por fisio (fis_seguimientos)
        $sesiones = DB::table('fis_seguimientos as s')
            ->where('s.status', 1)
            ->where('s.fecha', '>=', $startOfMonth)
            ->whereNotNull('s.user_id')
            ->select('s.user_id', DB::raw('COUNT(*) as c'))
            ->groupBy('s.user_id')
            ->pluck('c', 's.user_id');

        // Evaluaciones por fisio (fis_historys, excluyendo fichas y adjuntos:
        // ninguno es una evaluación clínica).
        $evaluaciones = DB::table('fis_historys as h')
            ->where('h.status', 1)
            ->where('h.fecha', '>=', $startOfMonth)
            ->whereNotIn('h.tabla_form', ['fis_fichas', 'fis_adjuntos'])
            ->whereNotNull('h.user_id')
            ->select('h.user_id', DB::raw('COUNT(*) as c'))
            ->groupBy('h.user_id')
            ->pluck('c', 'h.user_id');

        // Combinar ambos conteos por fisio
        $byUser = [];
        foreach ($sesiones as $uid => $c) {
            $byUser[$uid] = ['ses' => (int) $c, 'eval' => 0];
        }
        foreach ($evaluaciones as $uid => $c) {
            if (!isset($byUser[$uid])) $byUser[$uid] = ['ses' => 0, 'eval' => 0];
            $byUser[$uid]['eval'] = (int) $c;
        }

        if (empty($byUser)) return [];

        // Nombres de los fisios involucrados
        $names = DB::table('users')->whereIn('id', array_keys($byUser))->pluck('name', 'id');

        $result = [];
        foreach ($byUser as $uid => $vals) {
            $result[] = [
                'name'         => $names[$uid] ?? '—',
                'count'        => $vals['ses'] + $vals['eval'], // total para el ranking
                'sesiones'     => $vals['ses'],
                'evaluaciones' => $vals['eval'],
            ];
        }

        // Ordenar por actividad total (desc) y limitar a 5
        usort($result, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($result, 0, 5);
    }
}
