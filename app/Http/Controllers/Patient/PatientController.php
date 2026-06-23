<?php

namespace App\Http\Controllers\Patient;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Repository\UtilityRepository;
use App\Models\Patient\CmnPatient;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function patient()
    {   
        return view('patient.patient');
    }

    public function patientStore(Request $data)
{
    try {
        $data->merge([
            'email' => UtilityRepository::emptyToNull($data->email),
            // Acepta dd/mm/aaaa (máscara del front) o ISO, normaliza a Y-m-d.
            'dob'   => UtilityRepository::normalizeDate($data->dob),
        ]);

        $validator = Validator::make($data->all(), [
            'full_name' => 'required|string',
            'email'     => ['nullable', 'email', 'unique:cmn_patients,email'],
            'phone_no'  => ['required', 'string', 'max:20', 'unique:cmn_patients,phone_no'],
            'dob'       => ['nullable', 'date', 'before_or_equal:today'],
        ]);


        $rutaArchivo = $data->image_url; 
                if ($rutaArchivo != null) {
                    $rutaArchivo = UtilityRepository::saveFile($rutaArchivo, ['image/png', 'pdf/pdf', 'image/jpg', 'image/jpeg']);
                }

        if (!$validator->fails()) {
            $creatorId =  auth()->id();
            $data['user_id'] =  $data['user_id']=UtilityRepository::emptyToNull($data->user_id);               
            //create new user

                $userId =   CmnPatient::create(
                    [
                'full_name' => $data->full_name,
                'phone_no' => $data->phone_no,
                // Nivel 2.6 — opt-in WhatsApp ('1' acepta / '0' rechaza / null no preguntado)
                'wa_optin' => $data->filled('wa_optin') ? ($data->wa_optin == '1' || $data->wa_optin === 1 || $data->wa_optin === true ? 1 : 0) : null,
                'email' => $data->email,
                'dob' => $data->dob,
                'treated' => $data->treated,
                'has_study' => $data->has_study,
                'archivo' => $rutaArchivo ?? null,
                'tax_number' => $data->tax_number,
                'state' => $data->state,
                'email_verified_at' => Carbon::now(),
                'is_sys_adm' => 0,
                'status' => 1,
                "created_by" => $creatorId
            ]
        );

        $data['user_id'] = $userId->id;

        
        return $this->apiResponse(['status' => '1', 'data' => ['cmn_patient_id' => $userId->id]], 200);
        }
         return $this->apiResponse(['status' => '500', 'data' => $validator->errors()], 400);
    } catch (Exception $ex) {
        return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
    }
}

    public function patientUpdate(Request $data)
    {

        try {
            $data->merge([
                'email' => UtilityRepository::emptyToNull($data->email),
                // Acepta dd/mm/aaaa (máscara del front) o ISO, normaliza a Y-m-d.
                'dob'   => UtilityRepository::normalizeDate($data->dob),
            ]);

            $validator = Validator::make($data->all(), [
                'full_name' => ['required', 'string'],
                'email'     => ['nullable', 'email', 'unique:cmn_patients,email,' . $data->user_id . ',id'],
                'phone_no'  => ['required', 'string', 'max:20', 'unique:cmn_patients,phone_no,' . $data->user_id . ',id'],
                'dob'       => ['nullable', 'date', 'before_or_equal:today'],
            ]);
    
            $rutaArchivo = $data->image_url; 
                if ($rutaArchivo != null) {
                    $rutaArchivo = UtilityRepository::saveFile($rutaArchivo, ['image/png', 'pdf/pdf', 'image/jpg', 'image/jpeg']);
                }
                
             if (!$validator->fails()) {
                $creatorId =  auth()->id();
                $data['user_id']=UtilityRepository::emptyToNull($data->user_id);
               $patient = CmnPatient::find($data->user_id);

                if ($patient) {
                    $patient->update([
                        'full_name' => $data->full_name,
                        'phone_no' => $data->phone_no,
                        // Nivel 2.6 — opt-in WhatsApp
                        'wa_optin' => $data->filled('wa_optin') ? ($data->wa_optin == '1' || $data->wa_optin === 1 || $data->wa_optin === true ? 1 : 0) : $patient->wa_optin,
                        'email' => $data->email,
                        'dob' => $data->dob,
                        'treated' => $data->treated,
                        'has_study' => $data->has_study,
                        'archivo' => $rutaArchivo ?? $patient->archivo, // mantiene el anterior si no se envía uno nuevo
                        'tax_number' => $data->tax_number,
                        'state' => $data->state,
                        'is_sys_adm' => 0,
                        'status' => 1,
                        'updated_by' => $creatorId // o 'updated_by' si usas ese campo
                    ]);
                
                 $data['user_id'] = $patient->id;

                    return $this->apiResponse(['status' => '1', 'data' => ''], 200);
                }else {
                    return $this->apiResponse(['status' => '0', 'data' => 'No encontrado'], 400);
                } 
               
                
            }
            return $this->apiResponse(['status' => '500', 'data' => $validator->errors()], 400);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }


    public function patientDelete(Request $data)
    {
        try {
            $rtr = CmnPatient::where('id', $data->id)->delete();
            return $this->apiResponse(['status' => '1', 'data' => $rtr], 200);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }

    public function getAllPatient()
    {
        try {
            // Nivel 1.1 — Enriquecer cada paciente con datos clínicos derivados
            // (edad, último evento, casos, evaluaciones, estado, iniciales, cumpleaños).
            // Hecho en SQL con subqueries para evitar N+1.
            $today = Carbon::now()->toDateString();
            $monthStart = Carbon::now()->startOfMonth()->toDateString();
            $monthEnd   = Carbon::now()->endOfMonth()->toDateString();
            $weekStart  = Carbon::now()->startOfWeek()->toDateString();
            $threshold90 = Carbon::now()->subDays(90)->toDateString();

            $hasFichaIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('fis_historys', 'ficha_id');

            $patients = CmnPatient::where('cmn_patients.status', 1)
                ->select('cmn_patients.*')
                ->selectSub(
                    \Illuminate\Support\Facades\DB::table('fis_fichas')
                        ->whereColumn('fis_fichas.patient_id', 'cmn_patients.id')
                        ->where('fis_fichas.status', 1)
                        ->selectRaw('COUNT(*)'),
                    'case_count'
                )
                ->selectSub(
                    \Illuminate\Support\Facades\DB::table('fis_fichas')
                        ->whereColumn('fis_fichas.patient_id', 'cmn_patients.id')
                        ->where('fis_fichas.status', 1)
                        ->whereNull('fis_fichas.fecha_alta')
                        ->selectRaw('COUNT(*)'),
                    'open_case_count'
                )
                ->selectSub(
                    \Illuminate\Support\Facades\DB::table('fis_historys')
                        ->whereColumn('fis_historys.patient_id', 'cmn_patients.id')
                        ->where('fis_historys.status', 1)
                        ->selectRaw('COUNT(*)'),
                    'event_count'
                )
                ->selectSub(
                    \Illuminate\Support\Facades\DB::table('fis_historys')
                        ->whereColumn('fis_historys.patient_id', 'cmn_patients.id')
                        ->where('fis_historys.status', 1)
                        // Excluir fichas y adjuntos: solo cuenta evaluaciones reales.
                        ->whereNotIn('fis_historys.tabla_form', ['fis_fichas', 'fis_adjuntos'])
                        ->selectRaw('COUNT(*)'),
                    'evaluation_count'
                )
                ->selectSub(
                    \Illuminate\Support\Facades\DB::table('fis_historys')
                        ->whereColumn('fis_historys.patient_id', 'cmn_patients.id')
                        ->where('fis_historys.status', 1)
                        ->selectRaw('MAX(fecha)'),
                    'last_visit'
                )
                ->get();

            // Hidratar wa_optin como booleano explícito (1 = acepta WhatsApp)
            $patients->transform(function ($p) {
                $p->wa_optin_yes = ((int) $p->wa_optin) === 1;
                return $p;
            });

            $data = $patients->map(function ($p) use ($today, $monthStart, $monthEnd, $weekStart, $threshold90) {
                $arr = $p->toArray();

                // Edad calculada
                $arr['age'] = $p->dob ? Carbon::parse($p->dob)->age : null;

                // Iniciales (máx 2) — para el avatar
                $name = trim($p->full_name ?? '');
                $parts = preg_split('/\s+/', $name);
                $initials = '';
                if (count($parts) >= 1 && $parts[0] !== '') $initials .= mb_substr($parts[0], 0, 1);
                if (count($parts) >= 2 && $parts[1] !== '') $initials .= mb_substr($parts[1], 0, 1);
                $arr['initials'] = mb_strtoupper($initials ?: '?');

                // Color del avatar — hash determinístico del nombre, devuelve clase 1..8
                $arr['avatar_color'] = (crc32($name) % 8) + 1;

                // Días desde la última visita
                $arr['days_since_visit'] = null;
                if (!empty($arr['last_visit'])) {
                    $arr['days_since_visit'] = Carbon::parse($arr['last_visit'])->diffInDays(Carbon::now());
                }

                // Estado clínico computado
                $arr['has_open_case'] = ((int) ($arr['open_case_count'] ?? 0)) > 0;
                $arr['seen_this_week'] = !empty($arr['last_visit']) && $arr['last_visit'] >= $weekStart;
                $arr['inactive_long'] = !empty($arr['last_visit']) && $arr['last_visit'] < $threshold90;
                $arr['no_visits'] = empty($arr['last_visit']);

                // Cumpleaños este mes
                $arr['birthday_this_month'] = false;
                $arr['birthday_md'] = null;
                if ($p->dob) {
                    $dob = Carbon::parse($p->dob);
                    $thisYearBday = Carbon::createFromDate(Carbon::now()->year, $dob->month, $dob->day)->toDateString();
                    $arr['birthday_this_month'] = ($thisYearBday >= $monthStart && $thisYearBday <= $monthEnd);
                    $arr['birthday_md'] = $dob->format('m-d');
                }

                // Etiqueta principal de estado (en orden de prioridad)
                if ($arr['has_open_case']) {
                    $arr['status_label'] = 'caso_abierto';
                } elseif ($arr['no_visits']) {
                    $arr['status_label'] = 'sin_visitas';
                } elseif ($arr['inactive_long']) {
                    $arr['status_label'] = 'inactivo';
                } else {
                    $arr['status_label'] = 'activo';
                }

                return $arr;
            });

            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx->getMessage()], 400);
        }
    }

    /**
     * Nivel 2.2 — Buscar pacientes similares para detectar duplicados al alta.
     * Recibe ?name=... y/o ?phone=... y devuelve hasta 5 matches con un score.
     *  - Nombre: LIKE sobre cada palabra (case-insensitive, normalizado).
     *  - Teléfono: match exacto en los últimos 8 dígitos (ignora prefijos
     *    internacionales y separadores). Suficiente para Guatemala (+502 ####-####).
     * No incluye al paciente que se está editando (?exclude_id=).
     */
    public function findDuplicates(Request $request)
    {
        try {
            $name  = trim((string) $request->input('name', ''));
            $phone = trim((string) $request->input('phone', ''));
            $excludeId = (int) $request->input('exclude_id', 0);

            // Necesitamos al menos 3 caracteres de nombre o 4 dígitos de teléfono
            $phoneDigits = preg_replace('/\D+/', '', $phone);
            $phoneTail = strlen($phoneDigits) >= 8 ? substr($phoneDigits, -8) : $phoneDigits;
            $hasName  = mb_strlen($name) >= 3;
            $hasPhone = strlen($phoneTail) >= 4;

            if (! $hasName && ! $hasPhone) {
                return $this->apiResponse(['status' => '1', 'data' => []], 200);
            }

            $q = CmnPatient::where('status', 1)
                ->select('id', 'full_name', 'phone_no', 'email', 'dob');

            if ($excludeId > 0) {
                $q->where('id', '!=', $excludeId);
            }

            $q->where(function ($outer) use ($name, $phoneTail, $hasName, $hasPhone) {
                if ($hasName) {
                    // Cada palabra >2 chars como AND interno → cualquier campo
                    $words = array_filter(preg_split('/\s+/', $name), fn($w) => mb_strlen($w) >= 3);
                    foreach ($words as $w) {
                        $outer->orWhere('full_name', 'LIKE', '%' . $w . '%');
                    }
                }
                if ($hasPhone) {
                    $outer->orWhereRaw("REPLACE(REPLACE(REPLACE(phone_no,' ',''),'-',''),'+','') LIKE ?", ['%' . $phoneTail]);
                }
            });

            $matches = $q->limit(5)->get();

            // Enriquecer con iniciales y color (mismo algoritmo que getAllPatient)
            $data = $matches->map(function ($p) use ($name, $phoneTail) {
                $fname = trim($p->full_name ?? '');
                $parts = preg_split('/\s+/', $fname);
                $ini = '';
                if (!empty($parts[0])) $ini .= mb_substr($parts[0], 0, 1);
                if (!empty($parts[1])) $ini .= mb_substr($parts[1], 0, 1);

                // Score simple para ordenar visualmente
                $score = 0;
                if ($phoneTail) {
                    $pdig = preg_replace('/\D+/', '', $p->phone_no ?? '');
                    if (strlen($pdig) >= strlen($phoneTail) && substr($pdig, -strlen($phoneTail)) === $phoneTail) {
                        $score += 100; // teléfono exacto = casi seguro duplicado
                    }
                }
                if ($name) {
                    similar_text(mb_strtolower($name), mb_strtolower($fname), $pct);
                    $score += (int) $pct;
                }

                return [
                    'id' => $p->id,
                    'full_name' => $fname,
                    'phone_no' => $p->phone_no,
                    'email' => $p->email,
                    'dob' => $p->dob,
                    'initials' => mb_strtoupper($ini ?: '?'),
                    'avatar_color' => (crc32($fname) % 8) + 1,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->all();

            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx->getMessage()], 400);
        }
    }

    /**
     * Fase 1 - Expediente del paciente (solo lectura).
     * Consume cmn_patients y la bitácora fis_historys que ya alimentan los
     * controladores de FormFisios. No escribe en ninguna tabla.
     */
    public function patientSummary($id)
    {
        $patient = CmnPatient::where('id', $id)->where('status', 1)->first();

        if (! $patient) {
            return redirect()->route('patient')->with('error', 'Paciente no encontrado');
        }

        $age = $patient->dob ? Carbon::parse($patient->dob)->age : null;

        // Fase Reorg-A — filtro por caso clínico vía query param ?caso=X|all
        $casoParam = request()->input('caso', 'all');
        $hasFichaIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('fis_historys', 'ficha_id');

        $timelineQuery = \Illuminate\Support\Facades\DB::table('fis_historys')
            ->leftJoin('users', 'fis_historys.user_id', '=', 'users.id')
            ->where('fis_historys.patient_id', $id)
            ->where('fis_historys.status', 1)
            ->orderBy('fis_historys.fecha', 'desc')
            ->orderBy('fis_historys.id', 'desc');

        if ($hasFichaIdColumn && $casoParam !== 'all' && $casoParam !== '') {
            if ($casoParam === 'unassigned') {
                $timelineQuery->whereNull('fis_historys.ficha_id');
            } elseif (is_numeric($casoParam)) {
                $timelineQuery->where('fis_historys.ficha_id', (int) $casoParam);
            }
        }

        $timelineSelect = [
            'fis_historys.id',
            'fis_historys.fecha',
            'fis_historys.tabla_form',
            'fis_historys.id_formulario',
            'fis_historys.created_at',
            'users.name as user_name',
        ];
        if ($hasFichaIdColumn) $timelineSelect[] = 'fis_historys.ficha_id';

        $timeline = $timelineQuery->select($timelineSelect)->get();

        // Etiqueta legible + icono + color + ruta destino para cada tabla_form
        $formMeta = [
            'fis_fichas'         => ['label' => 'Ficha clínica',         'icon' => 'fa-file-medical', 'color' => 'primary',   'route' => 'ficha.info'],
            'fis_cheqmus'        => ['label' => 'Chequeo muscular',      'icon' => 'fa-dumbbell',     'color' => 'success',   'route' => 'cheqmus.info'],
            'fis_cheqs'          => ['label' => 'Chequeo muscular (escala)', 'icon' => 'fa-dumbbell', 'color' => 'success',   'route' => 'cheqs.info'],
            'fis_evdolors'       => ['label' => 'Evaluación de dolor',   'icon' => 'fa-heart-broken', 'color' => 'danger',    'route' => 'evdolors.info'],
            'fis_sensitivitys'   => ['label' => 'Sensibilidad',          'icon' => 'fa-hand-paper',   'color' => 'warning',   'route' => 'sensitivitys.info'],
            'fis_antropometrias' => ['label' => 'Antropometría T.F',     'icon' => 'fa-balance-scale','color' => 'info',      'route' => 'antropometrias.info'],
            'fis_antropoms'      => ['label' => 'Antropometría',         'icon' => 'fa-ruler',        'color' => 'info',      'route' => 'antropoms.info'],
            'fis_goniometrias'   => ['label' => 'Goniometría',           'icon' => 'fa-compass',      'color' => 'secondary', 'route' => 'goniometrias.info'],
            'fis_evpiels'        => ['label' => 'Evaluación de piel',    'icon' => 'fa-hand-paper',   'color' => 'warning',   'route' => 'evpiels.info'],
            'fis_evalineps'      => ['label' => 'Alineación postural',   'icon' => 'fa-walking',      'color' => 'secondary', 'route' => 'evalineps.info'],
            'fis_electros'       => ['label' => 'Electroterapia',        'icon' => 'fa-bolt',         'color' => 'primary',   'route' => 'electros.info'],
            'fis_ultras'         => ['label' => 'Ultrasonido',           'icon' => 'fa-broadcast-tower','color' => 'primary', 'route' => 'ultras.info'],
            // Fase 15 — Adjuntos. Como no hay una "vista listado" externa, dejamos route=null;
            // el blade omite el link cuando route es null.
            'fis_adjuntos'       => ['label' => 'Adjunto agregado',      'icon' => 'fa-paperclip',    'color' => 'secondary', 'route' => null],
        ];

        $counts = $timeline->groupBy('tabla_form')->map->count();
        $totalEvents = $timeline->count();
        $lastEvent = $timeline->first();

        // Adjuntos no son "evaluaciones": se cuentan aparte (para el badge de la
        // ficha) y se excluyen del "Resumen por tipo de evaluación" y del stat
        // "Tipos de evaluación". El timeline ($timeline) sí los conserva como eventos.
        $adjuntosCount = (int) ($counts['fis_adjuntos'] ?? 0);
        $counts = $counts->except(['fis_adjuntos']);

        // Fase Reorg-A — Fichas del paciente para el case selector
        // + estadísticas por caso (n eval, n sesiones, última actividad)
        $fichas = \Illuminate\Support\Facades\DB::table('fis_fichas')
            ->where('patient_id', $id)
            ->where('status', 1)
            ->orderBy('fecha', 'desc')
            ->select('id', 'fecha', 'diagnostico', 'motivo_consulta')
            ->get();

        if ($fichas->count() > 0 && $hasFichaIdColumn) {
            // Contar evaluaciones por ficha desde fis_historys (sin fichas ni adjuntos)
            $evalCounts = \Illuminate\Support\Facades\DB::table('fis_historys')
                ->where('patient_id', $id)
                ->where('status', 1)
                ->whereNotIn('tabla_form', ['fis_fichas', 'fis_adjuntos'])
                ->whereNotNull('ficha_id')
                ->select('ficha_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as c'))
                ->groupBy('ficha_id')
                ->pluck('c', 'ficha_id')
                ->toArray();

            // Contar sesiones por ficha
            $sesCounts = \Illuminate\Support\Facades\DB::table('fis_seguimientos')
                ->where('patient_id', $id)
                ->where(function ($q) {
                    $q->where('status', 1)->orWhereNull('status');
                })
                ->whereNotNull('ficha_id')
                ->select('ficha_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as c'))
                ->groupBy('ficha_id')
                ->pluck('c', 'ficha_id')
                ->toArray();

            $fichas = $fichas->map(function ($f) use ($evalCounts, $sesCounts) {
                $f->eval_count = (int) ($evalCounts[$f->id] ?? 0);
                $f->ses_count  = (int) ($sesCounts[$f->id] ?? 0);
                return $f;
            });
        }

        $casoActivo = $casoParam;

        // Fase Reorg-A.2 — Si hay caso seleccionado, cargar la ficha COMPLETA
        // para mostrarla expandida en el tab Resumen.
        $fichaCompleta = null;
        // Conteos del caso para el modal "Eliminar caso" (impacto del borrado)
        $casoEvalCount = 0;
        $casoSesCount  = 0;
        if (is_numeric($casoActivo)) {
            $fichaCompleta = \App\Models\FormFisios\Ficha::where('id', (int) $casoActivo)
                ->where('patient_id', $id)
                ->where('status', 1)
                ->first();

            if ($fichaCompleta) {
                $casoEvalCount = \Illuminate\Support\Facades\DB::table('fis_historys')
                    ->where('ficha_id', $fichaCompleta->id)
                    ->where('status', 1)
                    ->whereNotIn('tabla_form', ['fis_fichas', 'fis_adjuntos'])
                    ->count();
                $casoSesCount = \Illuminate\Support\Facades\DB::table('fis_seguimientos')
                    ->where('ficha_id', $fichaCompleta->id)
                    ->where('status', 1)
                    ->count();
            }
        }

        return view('patient.summary', compact(
            'patient', 'age', 'timeline', 'formMeta', 'counts', 'totalEvents', 'lastEvent',
            'fichas', 'casoActivo', 'fichaCompleta', 'adjuntosCount',
            'casoEvalCount', 'casoSesCount'
        ));
    }

    /**
     * Elimina (borrado lógico) una ficha clínica y todo lo asociado: sesiones,
     * evaluaciones (vía bitácora fis_historys) y adjuntos. Todo en una
     * transacción; status=0 para poder revertir desde BD si fue un error.
     */
    public function eliminarFicha(Request $request)
    {
        try {
            $id = (int) $request->input('id');
            $ficha = \App\Models\FormFisios\Ficha::where('id', $id)->where('status', 1)->first();
            if (! $ficha) {
                return $this->apiResponse(['status' => '404', 'data' => 'Ficha no encontrada o ya eliminada.'], 404);
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                // 1) La ficha
                \Illuminate\Support\Facades\DB::table('fis_fichas')
                    ->where('id', $id)->update(['status' => 0]);
                // 2) Sesiones del caso
                \Illuminate\Support\Facades\DB::table('fis_seguimientos')
                    ->where('ficha_id', $id)->update(['status' => 0]);
                // 3) Evaluaciones + métricas (la bitácora es la fuente de verdad)
                \Illuminate\Support\Facades\DB::table('fis_historys')
                    ->where('ficha_id', $id)->update(['status' => 0]);
                // 4) Adjuntos del caso
                \Illuminate\Support\Facades\DB::table('fis_adjuntos')
                    ->where('ficha_id', $id)->update(['status' => 0]);
            });

            return $this->apiResponse(['status' => '1', 'data' => ['id' => $id]], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('eliminarFicha: ' . $e->getMessage());
            return $this->apiResponse(['status' => '500', 'data' => 'Error al eliminar el caso.'], 500);
        }
    }

    /**
     * Cierra (da de alta) un caso clínico: registra la fecha de alta y las
     * observaciones de cierre. No borra nada — el historial queda intacto y el
     * caso pasa a "cerrado" en la vista de Casos Clínicos.
     */
    public function cerrarFicha(Request $request)
    {
        try {
            $id = (int) $request->input('id');
            $ficha = \App\Models\FormFisios\Ficha::where('id', $id)->where('status', 1)->first();
            if (! $ficha) {
                return $this->apiResponse(['status' => '404', 'data' => 'Ficha no encontrada.'], 404);
            }

            // Fecha de alta: la provista (dd/mm/aaaa o ISO) o, por defecto, hoy.
            $fechaAlta = UtilityRepository::normalizeDate($request->input('fecha_alta'))
                ?: now()->format('Y-m-d');

            $ficha->fecha_alta = $fechaAlta;
            $ficha->observaciones_cierre = $request->input('observaciones_cierre');
            $ficha->save();

            return $this->apiResponse(['status' => '1', 'data' => ['id' => $id, 'fecha_alta' => $fechaAlta]], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('cerrarFicha: ' . $e->getMessage());
            return $this->apiResponse(['status' => '500', 'data' => 'Error al cerrar el caso.'], 500);
        }
    }

    /**
     * Reabre un caso cerrado: limpia la fecha de alta (vuelve a "abierto").
     * Las observaciones de cierre se conservan por trazabilidad.
     */
    public function reabrirFicha(Request $request)
    {
        try {
            $id = (int) $request->input('id');
            $ficha = \App\Models\FormFisios\Ficha::where('id', $id)->where('status', 1)->first();
            if (! $ficha) {
                return $this->apiResponse(['status' => '404', 'data' => 'Ficha no encontrada.'], 404);
            }

            $ficha->fecha_alta = null;
            $ficha->save();

            return $this->apiResponse(['status' => '1', 'data' => ['id' => $id]], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('reabrirFicha: ' . $e->getMessage());
            return $this->apiResponse(['status' => '500', 'data' => 'Error al reabrir el caso.'], 500);
        }
    }

    /**
     * Vista de Casos Clínicos — índice navegable de todas las fichas.
     * No permite crear fichas aisladas (eso se hace desde el expediente);
     * cada caso enlaza al expediente del paciente con ese caso activo.
     */
    public function casosClinicos()
    {
        $casos = \Illuminate\Support\Facades\DB::table('fis_fichas as f')
            ->join('cmn_patients as p', 'f.patient_id', '=', 'p.id')
            ->leftJoin('users as u', 'f.user_id', '=', 'u.id')
            ->where('f.status', 1)
            ->where('p.status', 1)
            ->select(
                'f.id', 'f.patient_id', 'f.diagnostico', 'f.motivo_consulta',
                'f.fecha', 'f.fecha_alta',
                'p.full_name as patient_name',
                'u.name as fisio_name'
            )
            ->selectSub(
                \Illuminate\Support\Facades\DB::table('fis_historys')
                    ->whereColumn('fis_historys.ficha_id', 'f.id')
                    ->where('fis_historys.status', 1)
                    ->whereNotIn('fis_historys.tabla_form', ['fis_fichas', 'fis_adjuntos'])
                    ->selectRaw('COUNT(*)'),
                'eval_count'
            )
            ->selectSub(
                \Illuminate\Support\Facades\DB::table('fis_seguimientos')
                    ->whereColumn('fis_seguimientos.ficha_id', 'f.id')
                    ->where('fis_seguimientos.status', 1)
                    ->selectRaw('COUNT(*)'),
                'ses_count'
            )
            // Abiertos primero (fecha_alta NULL), luego por fecha desc
            ->orderByRaw('f.fecha_alta IS NOT NULL ASC')
            ->orderByDesc('f.fecha')
            ->get();

        $decoder = fn($v) => is_string($v) ? html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $v;

        $casos = $casos->map(function ($c) use ($decoder) {
            $c->diagnostico     = $decoder($c->diagnostico);
            $c->motivo_consulta = $decoder($c->motivo_consulta);
            $c->patient_name    = $decoder($c->patient_name);
            $c->fisio_name      = $decoder($c->fisio_name);
            $c->estado          = empty($c->fecha_alta) ? 'abierto' : 'cerrado';
            return $c;
        });

        // Fisios distintos para el filtro (ya decodificados en el map anterior)
        $fisios = $casos->pluck('fisio_name')->filter()->unique()->sort()->values();

        $totalAbiertos = $casos->where('estado', 'abierto')->count();
        $totalCerrados = $casos->where('estado', 'cerrado')->count();

        return view('patient.casos', compact('casos', 'fisios', 'totalAbiertos', 'totalCerrados'));
    }

    /**
     * Fase 2 - Devuelve sesiones (fis_seguimientos) y fichas activas del paciente.
     * Reutiliza endpoints existentes para crear/editar; aquí solo se lee.
     */
    public function patientSesionesData($id)
    {
        try {
            $patient = CmnPatient::where('id', $id)->where('status', 1)->first();
            if (! $patient) {
                return $this->apiResponse(['status' => '404', 'data' => 'Paciente no encontrado'], 404);
            }

            // Columnas opcionales según el esquema real de fis_seguimientos
            $hasStatus = \Illuminate\Support\Facades\Schema::hasColumn('fis_seguimientos', 'status');
            $hasUserId = \Illuminate\Support\Facades\Schema::hasColumn('fis_seguimientos', 'user_id');
            $hasCreatedAt = \Illuminate\Support\Facades\Schema::hasColumn('fis_seguimientos', 'created_at');

            $select = [
                's.id',
                's.ficha_id',
                's.fecha',
                's.tratamiento_realizado',
                's.observaciones',
                's.evolucion',
                's.nota_detallada',
                'f.diagnostico as ficha_diagnostico',
                'f.motivo_consulta as ficha_motivo',
                'f.fecha as ficha_fecha',
            ];
            if ($hasCreatedAt) $select[] = 's.created_at';
            if ($hasUserId)    $select[] = 'u.name as user_name';

            $query = \Illuminate\Support\Facades\DB::table('fis_seguimientos as s')
                ->leftJoin('fis_fichas as f', 's.ficha_id', '=', 'f.id')
                ->where('s.patient_id', $id);

            if ($hasUserId) {
                $query->leftJoin('users as u', 's.user_id', '=', 'u.id');
            }
            if ($hasStatus) {
                $query->where(function ($q) {
                    $q->where('s.status', 1)->orWhereNull('s.status');
                });
            }

            // Filtro por caso clínico (ficha) — Fase Reorg-A
            $fichaFilter = request()->input('ficha_id', null);
            if ($fichaFilter !== null && $fichaFilter !== '' && $fichaFilter !== 'all') {
                if ($fichaFilter === 'unassigned') {
                    $query->whereNull('s.ficha_id');
                } else {
                    $query->where('s.ficha_id', (int) $fichaFilter);
                }
            }

            $sesiones = $query->orderBy('s.fecha', 'desc')
                ->orderBy('s.id', 'desc')
                ->select($select)
                ->get();

            $fichas = \Illuminate\Support\Facades\DB::table('fis_fichas')
                ->where('patient_id', $id)
                ->where('status', 1)
                ->orderBy('fecha', 'desc')
                ->select('id', 'fecha', 'diagnostico', 'motivo_consulta')
                ->get();

            return $this->apiResponse([
                'status' => '1',
                'data' => [
                    'sesiones' => $sesiones,
                    'fichas'   => $fichas,
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('patientSesionesData: ' . $e->getMessage());
            return $this->apiResponse([
                'status'  => '500',
                'message' => 'Error cargando sesiones',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fase 3 — Devuelve las evaluaciones del paciente agrupadas por tipo,
     * filtradas opcionalmente por ficha clínica.
     *
     * Fuente: bitácora fis_historys, excluyendo entradas que son la propia
     * ficha (tabla_form = fis_fichas) ya que se muestra aparte.
     *
     * Query params:
     *   ?ficha_id=N          → sólo evaluaciones vinculadas a esa ficha
     *   ?ficha_id=unassigned → sólo evaluaciones sin ficha asignada
     *   (sin param)          → todas
     */
    public function patientEvaluacionesData($id)
    {
        try {
            $patient = CmnPatient::where('id', $id)->where('status', 1)->first();
            if (! $patient) {
                return $this->apiResponse(['status' => '404', 'data' => 'Paciente no encontrado'], 404);
            }

            $fichaFilter = request()->input('ficha_id', null);

            $hasFichaIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('fis_historys', 'ficha_id');

            $base = \Illuminate\Support\Facades\DB::table('fis_historys as h')
                ->leftJoin('users as u', 'h.user_id', '=', 'u.id')
                ->where('h.patient_id', $id)
                ->where('h.status', 1)
                // Excluir fichas (se muestran aparte) y adjuntos (tienen su propio tab):
                // ninguno es una "evaluación".
                ->whereNotIn('h.tabla_form', ['fis_fichas', 'fis_adjuntos']);

            if ($hasFichaIdColumn && $fichaFilter !== null && $fichaFilter !== '' && $fichaFilter !== 'all') {
                if ($fichaFilter === 'unassigned') {
                    $base->whereNull('h.ficha_id');
                } else {
                    $base->where('h.ficha_id', (int) $fichaFilter);
                }
            }

            $selectColumns = [
                'h.id as history_id',
                'h.tabla_form',
                'h.id_formulario',
                'h.fecha',
                'h.created_at',
                'u.name as user_name',
            ];
            if ($hasFichaIdColumn) $selectColumns[] = 'h.ficha_id';

            $eventos = $base->orderBy('h.fecha', 'desc')
                ->orderBy('h.id', 'desc')
                ->select($selectColumns)
                ->get();

            // Agrupar por tabla_form
            $grouped = [];
            foreach ($eventos as $ev) {
                $key = $ev->tabla_form;
                if (! isset($grouped[$key])) $grouped[$key] = [];
                $grouped[$key][] = $ev;
            }

            // Fichas del paciente para el dropdown
            $fichas = \Illuminate\Support\Facades\DB::table('fis_fichas')
                ->where('patient_id', $id)
                ->where('status', 1)
                ->orderBy('fecha', 'desc')
                ->select('id', 'fecha', 'diagnostico', 'motivo_consulta')
                ->get();

            return $this->apiResponse([
                'status' => '1',
                'data' => [
                    'evaluaciones'        => $grouped,
                    'fichas'              => $fichas,
                    'has_ficha_id_column' => $hasFichaIdColumn,
                    'filter_applied'      => $fichaFilter,
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('patientEvaluacionesData: ' . $e->getMessage());
            return $this->apiResponse([
                'status'  => '500',
                'message' => 'Error cargando evaluaciones',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fase 4a — Lectura de un registro único de cualquier formulario FormFisios
     * para poder editarlo inline desde el expediente.
     *
     * @param string $tabla   ej. 'fis_evdolors', 'fis_cheqmus', etc.
     * @param int    $id      id del registro en la tabla correspondiente
     */
    public function getEvaluationRecord($tabla, $id)
    {
        try {
            $modelClass = $this->resolveEvaluationModel($tabla);
            if (! $modelClass) {
                return $this->apiResponse([
                    'status'  => '404',
                    'message' => 'Tabla de evaluación no reconocida: ' . $tabla,
                ], 404);
            }

            $record = $modelClass::find($id);
            if (! $record) {
                return $this->apiResponse([
                    'status'  => '404',
                    'message' => 'Registro no encontrado',
                ], 404);
            }

            // Si el registro está marcado inactivo (status=0), no lo entregamos.
            if (isset($record->status) && (int) $record->status === 0) {
                return $this->apiResponse([
                    'status'  => '404',
                    'message' => 'Registro inactivo',
                ], 404);
            }

            // Buscar el ficha_id correspondiente desde la bitácora
            // (las tablas fis_* no tienen ficha_id; vive en fis_historys).
            $fichaId = null;
            $hasFichaIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('fis_historys', 'ficha_id');
            if ($hasFichaIdColumn) {
                $historyRow = \Illuminate\Support\Facades\DB::table('fis_historys')
                    ->where('tabla_form', $tabla)
                    ->where('id_formulario', $id)
                    ->where('status', 1)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($historyRow) {
                    $fichaId = $historyRow->ficha_id;
                }
            }

            // Normalizar para el cliente: enviar como array con keys lowercase + alias 'id'.
            $data = $record->toArray();
            $pk   = $record->getKeyName();        // 'id' o 'Id'
            $data['id'] = $record->getKey();      // alias siempre lowercase
            $data['_primary_key'] = $pk;          // para que el cliente sepa qué nombre usar al actualizar
            $data['ficha_id'] = $fichaId;
            $data['_table_form'] = $tabla;

            return $this->apiResponse([
                'status' => '1',
                'data'   => $data,
            ], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('getEvaluationRecord: ' . $e->getMessage());
            return $this->apiResponse([
                'status'  => '500',
                'message' => 'Error leyendo evaluación',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fase 4c — Historial completo de un tipo de evaluación para un paciente,
     * para visualizar la evolución en el tiempo.
     *
     * @param string $tabla       ej. 'fis_goniometrias'
     * @param int    $patientId   id del paciente
     */
    public function getEvaluationHistory($tabla, $patientId)
    {
        try {
            $modelClass = $this->resolveEvaluationModel($tabla);
            if (! $modelClass) {
                return $this->apiResponse([
                    'status'  => '404',
                    'message' => 'Tabla de evaluación no reconocida: ' . $tabla,
                ], 404);
            }

            // Cargar todos los registros activos del paciente, ordenados cronológicamente
            $records = $modelClass::where('patient_id', $patientId)
                ->where('status', 1)
                ->orderBy('fecha', 'asc')
                ->orderBy(($modelClass::make())->getKeyName(), 'asc')
                ->get();

            if ($records->isEmpty()) {
                return $this->apiResponse([
                    'status' => '1',
                    'data'   => [ 'records' => [], 'count' => 0 ],
                ], 200);
            }

            // Buscar ficha_id por cada registro en fis_historys (para mostrar contexto)
            $hasFichaIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('fis_historys', 'ficha_id');
            $idsArr = $records->pluck($records->first()->getKeyName())->all();
            $fichaMap = [];
            if ($hasFichaIdColumn && count($idsArr)) {
                $histRows = \Illuminate\Support\Facades\DB::table('fis_historys')
                    ->where('tabla_form', $tabla)
                    ->whereIn('id_formulario', $idsArr)
                    ->where('status', 1)
                    ->select('id_formulario', 'ficha_id')
                    ->get();
                foreach ($histRows as $h) {
                    $fichaMap[$h->id_formulario] = $h->ficha_id;
                }
            }

            // Normalizar la respuesta: array de registros con campos planos + ficha_id
            $out = [];
            foreach ($records as $rec) {
                $arr = $rec->toArray();
                $arr['id']       = $rec->getKey();   // alias lowercase consistente
                $arr['ficha_id'] = $fichaMap[$rec->getKey()] ?? null;
                $out[] = $arr;
            }

            return $this->apiResponse([
                'status' => '1',
                'data'   => [
                    'records' => $out,
                    'count'   => count($out),
                    'tabla'   => $tabla,
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('getEvaluationHistory: ' . $e->getMessage());
            return $this->apiResponse([
                'status'  => '500',
                'message' => 'Error leyendo historial',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fase 6a — Genera y descarga PDF de una evaluación individual.
     * El PDF tiene hoja membretada con logo Healing Hands, datos del paciente,
     * datos de la ficha clínica asociada y los campos del registro.
     *
     * @param string $tabla   ej. 'fis_goniometrias'
     * @param int    $id      id del registro
     */
    public function downloadEvaluationPdf($tabla, $id)
    {
        try {
            $modelClass = $this->resolveEvaluationModel($tabla);
            if (! $modelClass) {
                abort(404, 'Tabla de evaluación no reconocida');
            }

            $record = $modelClass::find($id);
            if (! $record) {
                abort(404, 'Registro no encontrado');
            }
            if (isset($record->status) && (int) $record->status === 0) {
                abort(404, 'Registro inactivo');
            }

            // Paciente
            $patient = \App\Models\Patient\CmnPatient::find($record->patient_id);
            if (! $patient) {
                abort(404, 'Paciente no encontrado');
            }

            // Ficha clínica asociada (si existe)
            $ficha = null;
            if (\Illuminate\Support\Facades\Schema::hasColumn('fis_historys', 'ficha_id')) {
                $historyRow = \Illuminate\Support\Facades\DB::table('fis_historys')
                    ->where('tabla_form', $tabla)
                    ->where('id_formulario', $id)
                    ->where('status', 1)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($historyRow && $historyRow->ficha_id) {
                    $ficha = \Illuminate\Support\Facades\DB::table('fis_fichas')
                        ->where('id', $historyRow->ficha_id)
                        ->first();
                }
            }

            // Usuario que realizó la evaluación
            $user = \App\Models\User::find($record->user_id);

            // Información de la empresa para el header/footer (si existe)
            $company = class_exists(\App\Models\Settings\CmnCompany::class)
                ? \App\Models\Settings\CmnCompany::first()
                : null;

            // mPDF setup
            $mpdf = new \Mpdf\Mpdf([
                'mode'                 => 'utf-8',
                'format'               => 'A4',
                'orientation'          => 'P',
                'default_font'         => 'dejavusans',
                'margin_left'          => 12,
                'margin_right'         => 12,
                'margin_top'           => 30,
                'margin_bottom'        => 22,
                'margin_header'        => 8,
                'margin_footer'        => 8,
            ]);

            $title = \App\Support\EvaluationMeta::displayName($tabla);
            $mpdf->SetTitle($title . ' - ' . $patient->full_name);
            $mpdf->SetAuthor('Healing Hands');
            $mpdf->SetCreator('Healing Hands - Expediente Clínico');

            $html = view('patient.pdf.evaluation', [
                'tabla'   => $tabla,
                'record'  => $record,
                'patient' => $patient,
                'ficha'   => $ficha,
                'user'    => $user,
                'company' => $company,
                'sections'=> \App\Support\EvaluationMeta::sections($tabla),
                'title'   => $title,
            ])->render();

            $mpdf->WriteHTML($html);

            $filename = $tabla . '_' . $id . '_' . now()->format('Ymd_His') . '.pdf';
            // 'I' = inline (abre en navegador), 'D' = forzar descarga
            $mpdf->Output($filename, 'I');
            exit;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('downloadEvaluationPdf: ' . $e->getMessage());
            abort(500, 'Error generando PDF: ' . $e->getMessage());
        }
    }

    /**
     * Fase 6b — Genera PDF del expediente clínico completo de un paciente.
     * Incluye: datos del paciente + fichas clínicas + todas las evaluaciones activas.
     *
     * Reorg-A.2 — Si ?caso=X, renderiza el reporte focalizado de ese caso
     * con la ficha clínica COMPLETA + sus evaluaciones + sus sesiones.
     */
    public function downloadPatientExpedientePdf($patientId)
    {
        // Si hay caso seleccionado, delegar al reporte focalizado
        $caso = request('caso', 'all');
        if ($caso !== 'all' && $caso !== '' && is_numeric($caso)) {
            return $this->downloadCaseReportPdf($patientId, (int) $caso);
        }

        try {
            $patient = \App\Models\Patient\CmnPatient::find($patientId);
            if (! $patient) {
                abort(404, 'Paciente no encontrado');
            }

            // Fichas clínicas del paciente
            $fichas = \Illuminate\Support\Facades\DB::table('fis_fichas')
                ->where('patient_id', $patientId)
                ->where('status', 1)
                ->orderBy('fecha', 'desc')
                ->get();

            // Mapa de ficha_id por historial (para asociar evaluaciones a sus fichas)
            $hasFichaIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('fis_historys', 'ficha_id');
            $fichaMap = [];
            if ($hasFichaIdColumn) {
                $hist = \Illuminate\Support\Facades\DB::table('fis_historys')
                    ->where('patient_id', $patientId)
                    ->where('status', 1)
                    ->select('tabla_form', 'id_formulario', 'ficha_id')
                    ->get();
                foreach ($hist as $h) {
                    $fichaMap[$h->tabla_form . ':' . $h->id_formulario] = $h->ficha_id;
                }
            }

            // Cargar evaluaciones de cada tabla (las 11)
            $allTables = [
                'fis_evdolors', 'fis_cheqs', 'fis_evpiels', 'fis_antropometrias',
                'fis_antropoms', 'fis_goniometrias', 'fis_cheqmus', 'fis_sensitivitys',
                'fis_evalineps', 'fis_electros', 'fis_ultras',
            ];
            $evaluations = [];
            foreach ($allTables as $tabla) {
                $modelClass = $this->resolveEvaluationModel($tabla);
                if (! $modelClass) continue;
                $records = $modelClass::where('patient_id', $patientId)
                    ->where('status', 1)
                    ->orderBy('fecha', 'desc')
                    ->get();
                if ($records->count() > 0) {
                    $evaluations[$tabla] = $records;
                }
            }

            // Sesiones (fis_seguimientos)
            $sesiones = collect();
            if (\Illuminate\Support\Facades\Schema::hasTable('fis_seguimientos')) {
                $sesiones = \Illuminate\Support\Facades\DB::table('fis_seguimientos')
                    ->where('patient_id', $patientId)
                    ->where('status', 1)
                    ->orderBy('fecha', 'desc')
                    ->limit(20)        // últimas 20 para que el PDF no sea gigante
                    ->get();
            }

            // Empresa
            $company = class_exists(\App\Models\Settings\CmnCompany::class)
                ? \App\Models\Settings\CmnCompany::first()
                : null;

            $mpdf = new \Mpdf\Mpdf([
                'mode'                 => 'utf-8',
                'format'               => 'A4',
                'orientation'          => 'P',
                'default_font'         => 'dejavusans',
                'margin_left'          => 12,
                'margin_right'         => 12,
                'margin_top'           => 30,
                'margin_bottom'        => 22,
                'margin_header'        => 8,
                'margin_footer'        => 8,
            ]);

            $mpdf->SetTitle('Expediente clínico — ' . $patient->full_name);
            $mpdf->SetAuthor('Healing Hands');
            $mpdf->SetCreator('Healing Hands - Expediente Clínico');

            $html = view('patient.pdf.expediente', [
                'patient'     => $patient,
                'fichas'      => $fichas,
                'evaluations' => $evaluations,
                'fichaMap'    => $fichaMap,
                'sesiones'    => $sesiones,
                'company'     => $company,
            ])->render();

            $mpdf->WriteHTML($html);

            $filename = 'expediente_' . $patient->id . '_' . now()->format('Ymd_His') . '.pdf';
            $mpdf->Output($filename, 'I');
            exit;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('downloadPatientExpedientePdf: ' . $e->getMessage());
            abort(500, 'Error generando expediente PDF: ' . $e->getMessage());
        }
    }

    /**
     * Fase 11 — Datos consolidados para gráficos de evolución del paciente.
     * Devuelve solo las evaluaciones donde hay ≥2 registros (para poder graficar).
     * En UNA sola request — evita N+1 al front.
     */
    public function getPatientEvolution($patientId)
    {
        try {
            $patient = \App\Models\Patient\CmnPatient::find($patientId);
            if (!$patient) {
                return $this->apiResponse(['status' => '404', 'message' => 'Paciente no encontrado'], 404);
            }

            $defs = \App\Support\EvolutionCharts::definitionsFor((int) $patientId);
            $charts = [];

            // Filtro por caso clínico (ficha) — Fase Reorg-A
            $fichaFilter = request()->input('ficha_id', null);
            $hasFichaIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('fis_historys', 'ficha_id');
            $allowedIdsByTable = null;
            if ($hasFichaIdColumn && $fichaFilter !== null && $fichaFilter !== '' && $fichaFilter !== 'all') {
                // Para filtrar por ficha en evaluaciones, hay que cruzar con fis_historys
                // (la ficha_id vive ahí, no en las tablas fis_*).
                $allowedIdsByTable = \Illuminate\Support\Facades\DB::table('fis_historys')
                    ->where('patient_id', $patientId)
                    ->where('status', 1)
                    ->when($fichaFilter === 'unassigned',
                        fn($q) => $q->whereNull('ficha_id'),
                        fn($q) => $q->where('ficha_id', (int) $fichaFilter)
                    )
                    ->select('tabla_form', 'id_formulario')
                    ->get()
                    ->groupBy('tabla_form')
                    ->map(fn($g) => $g->pluck('id_formulario')->all())
                    ->toArray();
            }

            foreach ($defs as $tabla => $def) {
                $modelClass = $this->resolveEvaluationModel($tabla);
                if (!$modelClass) continue;

                // Columnas a recolectar: fecha + cada serie
                $columns = array_merge(
                    ['fecha'],
                    array_map(fn($s) => $s['name'], $def['series'])
                );

                $q = $modelClass::where('patient_id', $patientId)
                    ->where('status', 1);

                if ($allowedIdsByTable !== null) {
                    // Si no hay IDs para esta tabla en este filtro, saltar
                    $ids = $allowedIdsByTable[$tabla] ?? [];
                    if (empty($ids)) continue;
                    $q->whereIn($modelClass::make()->getKeyName(), $ids);
                }

                $records = $q->orderBy('fecha', 'asc')->get($columns);

                if ($records->count() < 2) continue;

                // X axis: fechas (formato dd/mm)
                $labels = $records->map(function ($r) {
                    return $r->fecha
                        ? \Carbon\Carbon::parse($r->fecha)->format('d/m/Y')
                        : '—';
                })->all();

                // Una serie por cada definición — solo incluir si tiene al menos 1 valor no-null
                $series = [];
                foreach ($def['series'] as $s) {
                    $values = $records->map(function ($r) use ($s) {
                        $v = $r->{$s['name']} ?? null;
                        if ($v === null || $v === '') return null;
                        return is_numeric($v) ? (float) $v : null;
                    })->all();

                    // Calcular dirección de cambio entre primer y último valor no-null
                    $firstNonNull = collect($values)->first(fn($v) => $v !== null);
                    $lastNonNull  = collect($values)->reverse()->first(fn($v) => $v !== null);
                    $delta = null;
                    $isImprovement = null;
                    if ($firstNonNull !== null && $lastNonNull !== null) {
                        $delta = round($lastNonNull - $firstNonNull, 2);
                        if ($delta != 0 && $s['direction'] !== 'none') {
                            $isImprovement = $s['direction'] === 'higher'
                                ? ($delta > 0)
                                : ($delta < 0);
                        }
                    }

                    // Solo agregar la serie si tiene al menos un dato
                    $hasData = collect($values)->filter(fn($v) => $v !== null)->count() > 0;
                    if (!$hasData) continue;

                    $series[] = [
                        'name'          => $s['name'],
                        'label'         => $s['label'],
                        'color'         => $s['color'],
                        'direction'     => $s['direction'],
                        'data'          => $values,
                        'first'         => $firstNonNull,
                        'last'          => $lastNonNull,
                        'delta'         => $delta,
                        'is_improvement'=> $isImprovement,
                    ];
                }

                if (empty($series)) continue;

                $charts[] = [
                    'tabla'   => $tabla,
                    'title'   => $def['title'],
                    'icon'    => $def['icon'] ?? 'fa-chart-line',
                    'y_label' => $def['y_label'] ?? '',
                    'y_min'   => $def['y_min']  ?? null,
                    'y_max'   => $def['y_max']  ?? null,
                    'labels'  => $labels,
                    'series'  => $series,
                    'count'   => count($labels),
                ];
            }

            return $this->apiResponse([
                'status' => '1',
                'data'   => [
                    'patient_id'   => $patient->id,
                    'patient_name' => $patient->full_name,
                    'charts'       => $charts,
                ],
            ], 200);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('getPatientEvolution: ' . $e->getMessage());
            return $this->apiResponse([
                'status'  => '500',
                'message' => 'Error cargando evolución',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorg-A.2 — Reporte focalizado de UN caso clínico:
     * ficha completa + sus evaluaciones + sus sesiones.
     */
    public function downloadCaseReportPdf($patientId, $fichaId)
    {
        try {
            $patient = \App\Models\Patient\CmnPatient::find($patientId);
            if (! $patient) abort(404, 'Paciente no encontrado');

            $ficha = \App\Models\FormFisios\Ficha::where('id', $fichaId)
                ->where('patient_id', $patientId)
                ->where('status', 1)
                ->first();
            if (! $ficha) abort(404, 'Caso clínico no encontrado');

            // Evaluaciones vinculadas a esta ficha (vía fis_historys)
            $evaluations = [];
            $hasFichaIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('fis_historys', 'ficha_id');
            if ($hasFichaIdColumn) {
                $historyByTable = \Illuminate\Support\Facades\DB::table('fis_historys')
                    ->where('patient_id', $patientId)
                    ->where('ficha_id', $fichaId)
                    ->where('status', 1)
                    ->whereNotIn('tabla_form', ['fis_fichas'])
                    ->select('tabla_form', 'id_formulario')
                    ->get()
                    ->groupBy('tabla_form');

                foreach ($historyByTable as $tabla => $rows) {
                    $modelClass = $this->resolveEvaluationModel($tabla);
                    if (!$modelClass) continue;
                    $ids = $rows->pluck('id_formulario')->all();
                    if (empty($ids)) continue;
                    $records = $modelClass::whereIn($modelClass::make()->getKeyName(), $ids)
                        ->where('status', 1)
                        ->orderBy('fecha', 'asc')
                        ->get();
                    if ($records->count() > 0) {
                        $evaluations[$tabla] = $records;
                    }
                }
            }

            // Sesiones de este caso
            $sesiones = \Illuminate\Support\Facades\DB::table('fis_seguimientos')
                ->where('patient_id', $patientId)
                ->where('ficha_id', $fichaId)
                ->where(function ($q) { $q->where('status', 1)->orWhereNull('status'); })
                ->orderBy('fecha', 'asc')
                ->get();

            $company = class_exists(\App\Models\Settings\CmnCompany::class)
                ? \App\Models\Settings\CmnCompany::first()
                : null;

            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'orientation'   => 'P',
                'default_font'  => 'dejavusans',
                'margin_left'   => 12,
                'margin_right'  => 12,
                'margin_top'    => 30,
                'margin_bottom' => 22,
                'margin_header' => 8,
                'margin_footer' => 8,
            ]);

            $caseTitle = trim((string) $ficha->diagnostico) ?: ('Caso #' . $ficha->id);
            $mpdf->SetTitle('Reporte clínico — ' . $caseTitle . ' — ' . $patient->full_name);
            $mpdf->SetAuthor('Healing Hands');

            $html = view('patient.pdf.case-report', [
                'patient'     => $patient,
                'ficha'       => $ficha,
                'evaluations' => $evaluations,
                'sesiones'    => $sesiones,
                'company'     => $company,
                'caseTitle'   => $caseTitle,
            ])->render();

            $mpdf->WriteHTML($html);
            $filename = 'caso_' . $ficha->id . '_' . now()->format('Ymd_His') . '.pdf';
            $mpdf->Output($filename, 'I');
            exit;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('downloadCaseReportPdf: ' . $e->getMessage());
            abort(500, 'Error generando reporte: ' . $e->getMessage());
        }
    }

    /**
     * Whitelist tabla_form -> clase de modelo.
     * Sólo las 11 evaluaciones que tienen config inline son editables.
     */
    private function resolveEvaluationModel(string $tabla): ?string
    {
        $map = [
            'fis_evdolors'        => \App\Models\FormFisios\FisEvDolors::class,
            'fis_cheqs'           => \App\Models\FormFisios\FisCheqs::class,
            'fis_evpiels'         => \App\Models\FormFisios\FisEvPiels::class,
            'fis_antropometrias'  => \App\Models\FormFisios\FisAntropometrias::class,
            'fis_antropoms'       => \App\Models\FormFisios\FisAntropoms::class,
            'fis_goniometrias'    => \App\Models\FormFisios\FisGoniometrias::class,
            'fis_cheqmus'         => \App\Models\FormFisios\FisCheqmus::class,
            'fis_sensitivitys'    => \App\Models\FormFisios\FisSensitivitys::class,
            'fis_evalineps'       => \App\Models\FormFisios\FisEvAlineps::class,
            'fis_electros'        => \App\Models\FormFisios\FisElectros::class,
            'fis_ultras'          => \App\Models\FormFisios\FisUltras::class,
        ];
        return $map[$tabla] ?? null;
    }
}
