<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 8 — Búsqueda global cross-módulo.
 *
 * Endpoint único que devuelve resultados de varias entidades:
 *   - Pacientes (nombre, teléfono, email, DPI/tax_number)
 *   - Fichas clínicas (diagnóstico, motivo de consulta, con nombre paciente)
 *   - Acciones rápidas estáticas (Nueva ficha, Crear paciente, etc.)
 *
 * Diseñado para alimentar el modal "Ctrl+K" del topbar. Optimizado:
 *   - Limita resultados por categoría (≤5)
 *   - Solo se consulta si q tiene ≥2 caracteres
 *   - Una query por entidad — ningún N+1
 */
class GlobalSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function search(Request $request)
    {
        try {
            $q = trim((string) $request->input('q', ''));
            if (mb_strlen($q) < 2) {
                return $this->apiResponse([
                    'status' => '1',
                    'data'   => [
                        'query'    => $q,
                        'patients' => [],
                        'fichas'   => [],
                        'actions'  => $this->staticActions(),
                    ],
                ], 200);
            }

            // Escape % y _ para LIKE seguro
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';

            // ----- Pacientes -----
            $patients = DB::table('cmn_patients')
                ->where('status', 1)
                ->where(function ($w) use ($like) {
                    $w->where('full_name', 'like', $like)
                      ->orWhere('phone_no',  'like', $like)
                      ->orWhere('email',     'like', $like)
                      ->orWhere('tax_number','like', $like);
                })
                ->orderByRaw("CASE WHEN full_name LIKE ? THEN 0 ELSE 1 END", [str_replace(['%', '_'], ['\%', '\_'], $q) . '%']) // los que EMPIEZAN con la query primero
                ->orderBy('full_name')
                ->limit(8)
                ->select('id', 'full_name', 'phone_no', 'email', 'tax_number', 'dob')
                ->get();

            // ----- Fichas clínicas -----
            $fichas = [];
            if (Schema::hasTable('fis_fichas')) {
                $fichas = DB::table('fis_fichas as f')
                    ->leftJoin('cmn_patients as p', 'f.patient_id', '=', 'p.id')
                    ->where('f.status', 1)
                    ->where(function ($w) use ($like) {
                        $w->where('f.diagnostico',     'like', $like)
                          ->orWhere('f.motivo_consulta','like', $like)
                          ->orWhere('p.full_name',     'like', $like);
                    })
                    ->orderByDesc('f.fecha')
                    ->limit(5)
                    ->select(
                        'f.id', 'f.patient_id', 'f.fecha',
                        'f.diagnostico', 'f.motivo_consulta',
                        'p.full_name as patient_name'
                    )
                    ->get();
            }

            return $this->apiResponse([
                'status' => '1',
                'data'   => [
                    'query'    => $q,
                    'patients' => $patients->map(function ($p) {
                        return [
                            'id'         => $p->id,
                            'name'       => $p->full_name,
                            'phone'      => $p->phone_no,
                            'email'      => $p->email,
                            'tax_number' => $p->tax_number,
                            'dob'        => $p->dob,
                            'url'        => url('patient-summary/' . $p->id),
                        ];
                    })->values(),
                    'fichas'   => collect($fichas)->map(function ($f) {
                        return [
                            'id'              => $f->id,
                            'patient_id'      => $f->patient_id,
                            'patient_name'    => $f->patient_name,
                            'diagnostico'     => $f->diagnostico,
                            'motivo_consulta' => $f->motivo_consulta
                                ? \Illuminate\Support\Str::limit(
                                    html_entity_decode($f->motivo_consulta, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                                    80
                                )
                                : null,
                            'fecha'           => $f->fecha,
                            'url'             => url('patient-summary/' . $f->patient_id),
                        ];
                    })->values(),
                    'actions'  => $this->filterActions($q),
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('GlobalSearch: ' . $e->getMessage());
            return $this->apiResponse([
                'status' => '500',
                'message'=> 'Error en búsqueda',
                'debug'  => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Acciones rápidas estáticas — siempre disponibles.
     * Cada una tiene un label, icono, URL y keywords para hacer match con la query.
     */
    private function staticActions(): array
    {
        return [
            [
                'label'    => 'Ver lista de pacientes',
                'sub'      => 'Patient · directorio completo',
                'icon'     => 'fa-users',
                'url'      => url('patient'),
                'keywords' => 'pacientes lista directorio patients',
            ],
            [
                'label'    => 'Nueva ficha clínica',
                'sub'      => 'Abrir caso clínico para un paciente',
                'icon'     => 'fa-folder-plus',
                'url'      => url('fis-ficha'),
                'keywords' => 'ficha clinica nueva caso crear abrir',
            ],
            [
                'label'    => 'Panel clínico',
                'sub'      => 'Resumen operativo del día',
                'icon'     => 'fa-heartbeat',
                'url'      => url('panel-clinico'),
                'keywords' => 'panel clinico dashboard kpis resumen',
            ],
            [
                'label'    => 'Calendario de citas',
                'sub'      => 'Agenda y bookings',
                'icon'     => 'fa-calendar-alt',
                'url'      => url('home'),
                'keywords' => 'calendario citas agenda booking',
            ],
        ];
    }

    private function filterActions(string $q): array
    {
        $needle = mb_strtolower($q);
        return collect($this->staticActions())
            ->filter(function ($a) use ($needle) {
                $hay = mb_strtolower($a['label'] . ' ' . $a['sub'] . ' ' . ($a['keywords'] ?? ''));
                return strpos($hay, $needle) !== false;
            })
            ->values()
            ->all();
    }
}
