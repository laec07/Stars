<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFisios\FisGoniometrias;
use App\Http\Controllers\FormFisios\UtilityFisioController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

class FisGoniometriasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Muestra vista del formulario */
    public function formGoniometrias(Request $request)
    {
        return view('forms_fisios.goniometrias');
    }

    /** Listar todos los registros activos */
    public function getAllformGoniometrias()
    {
        try {
            $data = FisGoniometrias::join('cmn_patients', 'fis_goniometrias.patient_id', '=', 'cmn_patients.id')
                ->join('users', 'fis_goniometrias.user_id', '=', 'users.id')
                ->select(
                    'fis_goniometrias.*',
                    'cmn_patients.full_name as customer_name',
                    'users.name as name_user'
                )
                ->where('fis_goniometrias.status', 1)
                ->get();

            return $this->apiResponse(['status' => '1', 'data' => $data], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Crear un nuevo registro */
    public function createformGoniometrias(Request $request)
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

            $form = FisGoniometrias::create($cleanData);

            // Crear entrada en la bitácora laestradas
            $tabla='fis_goniometrias';
            $patientId = $request->input('patient_id');
           UtilityFisioController::logEntry($patientId, $tabla, $form->id,1);
            

            return $this->apiResponse(['status' => '1', 'data' => 'Registro guardado exitosamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Actualizar un registro */
    public function updateformGoniometrias(Request $request)
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
                'id' => 'required|integer|exists:fis_goniometrias,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $goniometrias = FisGoniometrias::find($request->id);
            if (!$goniometrias) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $fillableData = $this->cleanRequestData($request);
            $goniometrias->update($fillableData);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro actualizado correctamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Desactivar un registro */
    public function deleteformGoniometrias(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:fis_goniometrias,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $goniometrias = FisGoniometrias::find($request->id);
            if (!$goniometrias) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $goniometrias->status = 0;
            $goniometrias->updated_by = Auth::id();
            $goniometrias->save();

            // Borrar entrada en la bitácora
            $tabla='fis_goniometrias';
            UtilityFisioController::logDeleteByFields($goniometrias->patient_id,$tabla,$request->id);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro desactivado exitosamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Limpia y normaliza los datos antes de guardar */
    private function cleanRequestData(Request $request): array
    {
        return collect($request->only((new FisGoniometrias())->getFillable()))
            ->map(function ($value) {
                if (is_string($value)) {
                    $value = trim($value);
                }
                return $value === '' ? null : $value;
            })
            ->toArray();
    }
}