<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFisios\FisEvDolors;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FormFisios\UtilityFisioController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;  
use Exception;

class FisEvDolorsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Muestra vista del formulario */
    public function formEvdolors(Request $request)
    {
        return view('forms_fisios.evdolors');
    }

    /** Listar todos los registros activos */
    public function getAllformEvdolors()
    {
        try {
            $data = FisEvDolors::join('cmn_patients', 'fis_evdolors.patient_id', '=', 'cmn_patients.id')
                ->join('users', 'fis_evdolors.user_id', '=', 'users.id')
                ->select(
                    'fis_evdolors.*',
                    'cmn_patients.full_name as customer_name',
                    'cmn_patients.full_name as customer_name2',
                    'cmn_patients.dob as birth_date',
                    'users.name as name_user',  
                    'users.name as encargado'
                )
                ->where('fis_evdolors.status', '=', '1')
                ->get();
                $data->transform(function ($item) {
                    if ($item->birth_date) {
                        // Calcular edad
                        $item->age = Carbon::parse($item->birth_date)->age;

                        // Formatear fecha a otro formato, por ejemplo dd/mm/yyyy
                        $item->birth_date_formatted = Carbon::parse($item->birth_date)->format('d/m/Y');
                    } else {
                        $item->age = null;
                        $item->birth_date_formatted = null;
                    }return $item;
                });

            return $this->apiResponse(['status' => '1', 'data' => $data], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Crear un nuevo registro */
    public function createformEvdolors(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|integer|exists:cmn_patients,id',
                // Agregar otras validaciones necesarias
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $cleanData = $this->cleanRequestData($request);
            $cleanData['user_id'] = Auth::id();

            $form = FisEvDolors::create($cleanData);

            // Crear entrada en la bitácora laestradas
            $tabla='fis_evdolors';
            $patientId = $request->input('patient_id');
           UtilityFisioController::logEntry($patientId, $tabla, $form->id,1);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro guardado exitosamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Actualizar un registro */
    public function updateformEvdolors(Request $request)
    {
        if (app()->environment('local')) {
            DB::listen(function ($query) {
                logger()->info("SQL: " . $query->sql);
                logger()->info("Bindings: " . json_encode($query->bindings));
                logger()->info("Time: " . $query->time);
            });
        }

        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:fis_evdolors,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $evdolors = FisEvDolors::find($request->id);
            if (!$evdolors) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $fillableData = $this->cleanRequestData($request);
            $evdolors->update($fillableData);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro actualizado correctamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Desactivar un registro */
    public function deleteformEvdolors(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:fis_evdolors,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $evdolors = FisEvDolors::find($request->id);
            if (!$evdolors) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $evdolors->status = 0;
            $evdolors->updated_by = Auth::id();
            $evdolors->save();

            // Borrar entrada en la bitácora
            $tabla='fis_evdolors';
            UtilityFisioController::logDeleteByFields($evdolors->patient_id,$tabla,$request->id);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro desactivado exitosamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Limpia y normaliza los datos antes de guardar */
    private function cleanRequestData(Request $request): array
    {
        return collect($request->only((new FisEvDolors())->getFillable()))
            ->map(function ($value) {
                if (is_string($value)) {
                    $value = trim($value);
                }
                return $value === '' ? null : $value;
            })
            ->toArray();
    }
}