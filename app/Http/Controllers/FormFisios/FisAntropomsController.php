<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFisios\FisAntropoms;
use App\Http\Controllers\FormFisios\UtilityFisioController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class FisAntropomsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function formAntropoms(Request $request)
    {
        return view('forms_fisios.antropoms');
    }

    public function getAllformAntropoms()
    {
        try {
            $data = FisAntropoms::join('cmn_patients', 'fis_antropoms.patient_id', '=', 'cmn_patients.id')
                ->join('users', 'fis_antropoms.user_id', '=', 'users.id')
                ->select(
                    'fis_antropoms.*',
                    'cmn_patients.full_name as customer_name',
                    'users.name as name_user'
                )
                ->where('fis_antropoms.status', '=', '1')
                ->get();

            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

   public function createformAntropoms(Request $data)
{

    try {
        $validator = Validator::make($data->all(), [ 
            'patient_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
        }

        // Limpia los datos: convierte '' a null
        $cleanData = collect($data->only((new FisAntropoms())->getFillable()))
            ->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

        $form = FisAntropoms::create($cleanData);
        // Crear entrada en la bitácora laestradas
            $tabla='fis_antropoms';
            $patientId = $data->input('patient_id');
           UtilityFisioController::logEntry($patientId, $tabla, $form->id,1);

        return $this->apiResponse(['status' => '1', 'data' => 'Registro guardado exitosamente.'], 200);

    } catch (Exception $qx) {
        return $this->apiResponse(['status' => '403', 'data' => $qx->getMessage()], 400);
    }
}


    //Actualiza Información
   public function updateformAntropoms(Request $data)
{
     DB::listen(function ($query) {
        logger()->info("SQL: " . $query->sql);
        logger()->info("Bindings: " . json_encode($query->bindings));
        logger()->info("Time: " . $query->time);
    });
    try {
        // Validar que venga un ID válido
        $validator = Validator::make($data->all(), [
            'id' => 'required|integer|exists:fis_antropoms,id',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
        }

        // Buscar el registro a actualizar
        $antropoms = FisAntropoms::find($data->id);
        if (!$antropoms) {
            return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
        }

        // Limpiar los datos: convertir '' a null
        $fillableData = collect($data->only((new FisAntropoms())->getFillable()))
            ->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

        // Actualizar
        $antropoms->update($fillableData);

        return $this->apiResponse(['status' => '1', 'data' => $fillableData], 200);

    } catch (Exception $qx) {
        return $this->apiResponse(['status' => '403', 'data' => $qx->getMessage()], 400);
    }
}


    //Desactiva Información
    public function deleteformAntropoms(Request $data)
    {
        try {
            $validator = Validator::make($data->all(), [
                'id' => 'required|integer|exists:fis_antropoms,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $antropoms = FisAntropoms::find($data->id); // Usa el primaryKey correcto
            if (!$antropoms) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $antropoms->status = 0;
            $antropoms->updated_by = Auth::id();
            $antropoms->save();

            // Borrar entrada en la bitácora
            $tabla='fis_antropoms';
            UtilityFisioController::logDeleteByFields($antropoms->patient_id,$tabla,$data->id);

            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }
}