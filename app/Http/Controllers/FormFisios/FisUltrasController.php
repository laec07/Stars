<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFisios\FisUltras ;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class FisUltrasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Muestra vista del formulario */
    public function formUltras(Request $request)
    {
        return view('forms_fisios.ultras');
    }

    /** Listar todos los registros activos */
    public function getAllformUltras()
    {
        try {
            $data = FisUltras ::join('cmn_patients', 'fis_ultras.patient_id', '=', 'cmn_patients.id')
                ->join('users', 'fis_ultras.user_id', '=', 'users.id')
                ->select(
                    'fis_ultras.*',
                    'cmn_patients.full_name as customer_name',
                    'cmn_patients.full_name as customer_name2',
                    'cmn_patients.dob as birth_date',
                    'users.name as name_user',
                    'users.name as encargado'
                )
                ->where('fis_ultras.status', '=', '1')
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
    public function createformUltras(Request $request)
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

            FisUltras ::create($cleanData);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro guardado exitosamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Actualizar un registro */
    public function updateformUltras(Request $request)
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
                'id' => 'required|integer|exists:fis_ultras,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $ultras = FisUltras ::find($request->id);
            if (!$ultras) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $fillableData = $this->cleanRequestData($request);
            $ultras->update($fillableData);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro actualizado correctamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Desactivar un registro */
    public function deleteformUltras(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:fis_ultras,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $ultras = FisUltras ::find($request->id);
            if (!$ultras) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $ultras->status = 0;
            $ultras->updated_by = Auth::id();
            $ultras->save();

            return $this->apiResponse(['status' => '1', 'data' => 'Registro desactivado exitosamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Limpia y normaliza los datos antes de guardar */
    private function cleanRequestData(Request $request): array
    {
        return collect($request->only((new FisUltras ())->getFillable()))
            ->map(function ($value) {
                if (is_string($value)) {
                    $value = trim($value);
                }
                return $value === '' ? null : $value;
            })
            ->toArray();
    }
}