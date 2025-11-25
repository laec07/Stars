<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use App\Models\FormFisios\FisCheqmus;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FormFisios\UtilityFisioController;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FisCheqmusController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Summary of muscle check 
     * Author: laestrada
     * Date: 09-mar-2025
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\FormFisios\View\View
     */
    public function formCheqMusc(Request $request){
        return view('forms_fisios.cheqmus');

    }

    /* --- Obtiene datos a mostrar en pantalla principal index
    ------ Colocar los datos que se mostrara en el div al actualizar
    */
    public function getAllformCheqMusc()
    { 
        try {
            $data = FisCheqmus::join('cmn_patients', 'fis_cheqmus.patient_id', '=', 'cmn_patients.id')
            ->join('users', 'fis_cheqmus.user_id','=','users.id')
            ->select(
                'fis_cheqmus.*',
                'cmn_patients.full_name as customer_name',
                'cmn_patients.full_name as customer_name2',
                'cmn_patients.dob as birth_date',
                'users.name as name_user',
                'users.name as encargado'
            )
            ->where('fis_cheqmus.status','=','1')
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
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

    //Guardar Información
    public function createformCheqMusc(Request $data)
    {

        try {

        $validator = Validator::make($data->all(), [ 
            'patient_id' => 'required|string',

        ]);
            if (!$validator->fails()) {
              $form = FisCheqmus::create($data->only(
                    (new FisCheqmus())->getFillable()
                ));

            // Crear entrada en la bitácora laestrada
            $tabla='fis_cheqmus';
            $patientId = $data->input('patient_id');
           UtilityFisioController::logEntry($patientId, $tabla, $form->Id,1);

                return $this->apiResponse(['status' => '1', 'data' => ''], 200);
            }

            
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx->getMessage()], 400);
        }
    }

    //Actualiza Información
    public function updateformCheqMusc(Request $data)
    {
        try {
           // Validar que venga un ID válido
            $validator = Validator::make($data->all(), [
                'id' => 'required|integer|exists:fis_cheqmus,id',
            ]);

            if ($validator->fails()) {
            return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            // Buscar el registro a actualizar
            $cheqMusc = FisCheqmus::find($data->id);
            if (!$cheqMusc) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }
            
            // Obtener los campos que se pueden actualizar
            $fillableData = $data->only((new FisCheqmus())->getFillable());

            // Actualizar
            $cheqMusc->update($fillableData);
            
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

    //Desactiva Información
    public function deleteformCheqMusc(Request $data)
    {
        try {
            $validator = Validator::make($data->all(), [
                'Id' => 'required|integer|exists:fis_cheqmus,Id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $cheqMusc = FisCheqmus::find($data->Id); // Usa el primaryKey correcto
            if (!$cheqMusc) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $cheqMusc->status = 0;
            $cheqMusc->updated_by = Auth::id();
            $cheqMusc->save();

            // Borrar entrada en la bitácora
            $tabla='fis_cheqmus';
            UtilityFisioController::logDeleteByFields($cheqMusc->patient_id,$tabla,$data->Id);

            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

}
