<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFisios\FisEvPiels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class FisEvPielsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Muestra vista del formulario */
    public function formEvpiels(Request $request)
    {
        return view('forms_fisios.evpiels');
    }

    /** Listar todos los registros activos */
    public function getAllformEvpiels()
    {
        try {
            $data = FisEvPiels::join('cmn_patients', 'fis_evpiels.patient_id', '=', 'cmn_patients.id')
                ->join('users', 'fis_evpiels.user_id', '=', 'users.id')
                ->select(
                    'fis_evpiels.*',
                    'cmn_patients.full_name as customer_name',
                    'cmn_patients.full_name as customer_name2',
                    'cmn_patients.dob as birth_date',
                    'users.name as name_user',
                    'users.name as encargado'
                )
                ->where('fis_evpiels.status', '=', '1')
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
    public function createformEvpiels(Request $request)
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

            FisEvPiels::create($cleanData);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro guardado exitosamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Actualizar un registro */
    public function updateformEvpiels(Request $request)
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
                'id' => 'required|integer|exists:fis_evpiels,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $evpiels = FisEvPiels::find($request->id);
            if (!$evpiels) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $fillableData = $this->cleanRequestData($request);
            $evpiels->update($fillableData);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro actualizado correctamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Desactivar un registro */
    public function deleteformEvpiels(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:fis_evpiels,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $evpiels = FisEvPiels::find($request->id);
            if (!$evpiels) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $evpiels->status = 0;
            $evpiels->updated_by = Auth::id();
            $evpiels->save();

            return $this->apiResponse(['status' => '1', 'data' => 'Registro desactivado exitosamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Limpia y normaliza los datos antes de guardar */
    private function cleanRequestData(Request $request): array
    {
        return collect($request->only((new FisEvPiels())->getFillable()))
            ->map(function ($value) {
                if (is_string($value)) {
                    $value = trim($value);
                }
                return $value === '' ? null : $value;
            })
            ->toArray();
    }
}