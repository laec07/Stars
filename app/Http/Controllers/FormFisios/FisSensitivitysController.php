<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFisios\FisSensitivitys;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class FisSensitivitysController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function formSensitivitys(Request $request)
    {
        return view('forms_fisios.sensitivitys');
    }

    public function getAllformSensitivitys()
    {
        try {
            $data = FisSensitivitys::join('cmn_patients', 'fis_sensitivitys.patient_id', '=', 'cmn_patients.id')
                ->join('users', 'fis_sensitivitys.user_id', '=', 'users.id')
                ->select(
                    'fis_sensitivitys.*',
                    'cmn_patients.full_name as customer_name',
                    'users.name as name_user'
                )
                ->where('fis_sensitivitys.status', '=', '1')
                ->get();

            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

   public function createformSensitivitys(Request $data)
{

    try {
        $validator = Validator::make($data->all(), [ 
            'patient_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
        }

        // Limpia los datos: convierte '' a null
        $cleanData = collect($data->only((new FisSensitivitys())->getFillable()))
            ->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

        FisSensitivitys::create($cleanData);

        return $this->apiResponse(['status' => '1', 'data' => 'Registro guardado exitosamente.'], 200);

    } catch (Exception $qx) {
        return $this->apiResponse(['status' => '403', 'data' => $qx->getMessage()], 400);
    }
}


    //Actualiza Información
   public function updateformSensitivitys(Request $data)
{
     DB::listen(function ($query) {
        logger()->info("SQL: " . $query->sql);
        logger()->info("Bindings: " . json_encode($query->bindings));
        logger()->info("Time: " . $query->time);
    });
    try {
        // Validar que venga un ID válido
        $validator = Validator::make($data->all(), [
            'id' => 'required|integer|exists:fis_sensitivitys,id',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
        }

        // Buscar el registro a actualizar
        $sensitivitys = FisSensitivitys::find($data->id);
        if (!$sensitivitys) {
            return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
        }

        // Limpiar los datos: convertir '' a null
        $fillableData = collect($data->only((new FisSensitivitys())->getFillable()))
            ->map(function ($value) {
                return $value === '' ? null : $value;
            })->toArray();

        // Actualizar
        $sensitivitys->update($fillableData);

        return $this->apiResponse(['status' => '1', 'data' => $fillableData], 200);

    } catch (Exception $qx) {
        return $this->apiResponse(['status' => '403', 'data' => $qx->getMessage()], 400);
    }
}


    //Desactiva Información
    public function deleteformSensitivitys(Request $data)
    {
        try {
            $validator = Validator::make($data->all(), [
                'id' => 'required|integer|exists:fis_sensitivitys,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $sensitivitys = FisSensitivitys::find($data->id); // Usa el primaryKey correcto
            if (!$sensitivitys) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $sensitivitys->status = 0;
            $sensitivitys->updated_by = Auth::id();
            $sensitivitys->save();

            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }
}
