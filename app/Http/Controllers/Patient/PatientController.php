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
            'dob'   => UtilityRepository::emptyToNull($data->dob),
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
                'dob'   => UtilityRepository::emptyToNull($data->dob),
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
            $data = CmnPatient::select('*')
             ->where('status', 1)
            ->get();
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
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

        $timeline = \Illuminate\Support\Facades\DB::table('fis_historys')
            ->leftJoin('users', 'fis_historys.user_id', '=', 'users.id')
            ->where('fis_historys.patient_id', $id)
            ->where('fis_historys.status', 1)
            ->orderBy('fis_historys.fecha', 'desc')
            ->orderBy('fis_historys.id', 'desc')
            ->select(
                'fis_historys.id',
                'fis_historys.fecha',
                'fis_historys.tabla_form',
                'fis_historys.id_formulario',
                'fis_historys.created_at',
                'users.name as user_name'
            )
            ->get();

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
        ];

        $counts = $timeline->groupBy('tabla_form')->map->count();
        $totalEvents = $timeline->count();
        $lastEvent = $timeline->first();

        return view('patient.summary', compact(
            'patient', 'age', 'timeline', 'formMeta', 'counts', 'totalEvents', 'lastEvent'
        ));
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
}
