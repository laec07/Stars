<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFisios\Ficha;
use App\Models\FormFisios\FisSeguimientos;
use App\Http\Controllers\FormFisios\UtilityFisioController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class FichaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Muestra vista del formulario */
    public function formFicha(Request $request)
    {
        return view('forms_fisios.ficha');
    }
        public function formFicha_form(Request $request)
    {
       
        return view('forms_fisios.createficha');
    }


    /** Listar todos los registros activos */
    public function getAllformFicha()
    {
        try {
           $data = Ficha::with('seguimientos') 
                ->join('cmn_patients', 'fis_fichas.patient_id', '=', 'cmn_patients.id')
                ->join('users', 'fis_fichas.user_id', '=', 'users.id')
                ->select(           
                    'fis_fichas.*',
                    'fis_fichas.id as ficha_id',
                    'fis_fichas.patient_id',
                    'cmn_patients.full_name as customer_name',
                    'cmn_patients.full_name as customer_name2',
                    'cmn_patients.dob as birth_date',
                    'users.name as name_user',
                    'users.name as encargado'
                )
                ->where('fis_fichas.status', 1)
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
                         $item->seguimientos = $item->seguimientos ?? [];
                    }return $item;
                });
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Crear un nuevo registro */
    public function createformFicha(Request $request)
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

            $ficha = Ficha::create($cleanData);

            // Crear entrada en la bitácora laestradas
            $tabla='fis_fichas';
            $patientId = $request->input('patient_id');
           UtilityFisioController::logEntry($patientId, $tabla, $ficha->id,1);
           
           return response()->json([
                'status' => '1',
                'redirect' => route('ficha.info'), 
                'message' => 'Registro guardado exitosamente.'
            ]);


        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
        $seguimiento = new Ficha();
        $seguimiento->nota_detallada = $request->nota_detallada;
        $seguimiento->ficha_id = $request->ficha_id;
        $seguimiento->save();
    }

    /** Actualizar un registro */
    public function updateformFicha(Request $request)
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
                'id' => 'required|integer|exists:fis_fichas,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $ficha = Ficha::find($request->id);
            if (!$ficha) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $fillableData = $this->cleanRequestData($request);
            $ficha->update($fillableData);

            return $this->apiResponse(['status' => '1', 'data' => 'Registro actualizado correctamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

    /** Desactivar un registro */
    public function deleteformFicha(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:fis_fichas,id',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $ficha = Ficha::find($request->id);
            if (!$ficha) {
                return $this->apiResponse(['status' => '404', 'data' => 'Registro no encontrado'], 404);
            }

            $ficha->status = 0;
            $ficha->updated_by = Auth::id();
            $ficha->save();
            // Borrar entrada en la bitácora
            $tabla='fis_fichas';
            UtilityFisioController::logDeleteByFields($ficha->patient_id,$tabla,$request->id);
            return $this->apiResponse(['status' => '1', 'data' => 'Registro desactivado exitosamente.'], 200);

        } catch (Exception $e) {
            return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
        }
    }

        /** Crear un nuevo seguimiento asociado a una ficha */
    public function createSeguimiento(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ficha_id' => 'required|integer|exists:fis_fichas,id',
                'patient_id' => 'required|integer|exists:cmn_patients,id',
                'fecha' => 'required|date',
                'tratamiento_realizado' => 'nullable|string|max:1000',
                'observaciones' => 'nullable|string|max:1000',
                'evolucion' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => '422',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Crear registro
            $seguimiento = FisSeguimientos::create([
                'ficha_id' => $request->ficha_id,
                'patient_id' => $request->patient_id,
                'fecha' => $request->fecha,
                'tratamiento_realizado' => $request->tratamiento_realizado,
                'observaciones' => $request->observaciones,
                'evolucion' => $request->evolucion,
                
            ]);

            return response()->json([
                'status' => '1',
                'message' => 'Seguimiento guardado correctamente.',
                'data' => $seguimiento
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => '403',
                'message' => 'Error al guardar el seguimiento: ' . $e->getMessage(),
            ], 403);
        }
    }


        /** Actualizar un seguimiento */
    public function updateSeguimiento(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ficha_id' => 'required|integer|exists:fis_fichas,id',
                'patient_id' => 'required|integer|exists:cmn_patients,id',
                'fecha' => 'required|date',
                'tratamiento_realizado' => 'nullable|string|max:1000',
                'observaciones' => 'nullable|string|max:1000',
                'evolucion' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => '422',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $seguimiento = FisSeguimientos::findOrFail($id);

            $seguimiento->update([
                'ficha_id' => $request->ficha_id,
                'patient_id' => $request->patient_id,
                'fecha' => $request->fecha,
                'tratamiento_realizado' => $request->tratamiento_realizado,
                'observaciones' => $request->observaciones,
                'evolucion' => $request->evolucion,
            ]);

            return response()->json([
                'status' => '1',
                'message' => 'Seguimiento actualizado correctamente.',
                'data' => $seguimiento
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => '403',
                'message' => 'Error al actualizar el seguimiento: ' . $e->getMessage(),
            ], 403);
        }
    }

    /** Eliminar un seguimiento */
    public function deleteSeguimiento($id)
    {
        try {
            $seguimiento = FisSeguimientos::findOrFail($id);
            $seguimiento->delete();

            return response()->json([
                'status' => '1',
                'message' => 'Seguimiento eliminado correctamente.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => '403',
                'message' => 'Error al eliminar el seguimiento: ' . $e->getMessage(),
            ], 403);
        }
    }


    /** Obtener seguimientos de una ficha (para usar en DataTable) */
public function getSeguimientosByFicha($fichaId)
{
    try {
        // Validar que exista la ficha
        $ficha = Ficha::findOrFail($fichaId);

        // Obtener todos los seguimientos asociados
        $seguimientos = FisSeguimientos::where('ficha_id', $fichaId)
            ->orderBy('fecha', 'desc')
            ->get();

        return $this->apiResponse(['status' => '1', 'data' => $seguimientos], 200);
    } catch (Exception $e) {
        return $this->apiResponse(['status' => '403', 'data' => $e->getMessage()], 400);
    }
}
    
public function uploadImage(Request $request)
{
    if ($request->hasFile('upload')) {

        $file = $request->file('upload');

        // Opcional: límite 20MB
        if ($file->getSize() > 20 * 1024 * 1024) {
            return response()->json([
                'error' => ['message' => 'El archivo es demasiado grande']
            ], 400);
        }

        $filename = time().'_'.$file->getClientOriginalName();

        $path = $file->storeAs('seguimientos', $filename, 'public');

        $url = asset('storage/'.$path);

        // 👇 Si es imagen
        if (str_contains($file->getMimeType(), 'image')) {
            return response()->json([
                'url' => $url
            ]);
        }

        // 👇 Si NO es imagen → insertar como link
        return response()->json([
            'url' => $url,
            'default' => $url
        ]);
    }

    return response()->json([
        'error' => ['message' => 'No se pudo subir el archivo']
    ], 400);
}


    /** Limpia y normaliza los datos antes de guardar */
    private function cleanRequestData(Request $request): array
    {
        return collect($request->only((new Ficha())->getFillable()))
            ->map(function ($value) {
                if (is_string($value)) {
                    $value = trim($value);
                }
                return $value === '' ? null : $value;
            })
            ->toArray();
    }
}