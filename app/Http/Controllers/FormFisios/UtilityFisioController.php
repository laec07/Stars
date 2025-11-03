<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use App\Models\FormFisios\UtilityFisio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UtilityFisioController extends Controller
{
    /**
     * Crea un registro en la tabla de utilidades/bitácora.
     * @param array $data Keys: patient_id, tabla_form, status (opcional), fecha (opcional), user_id (opcional), cualquier otro campo fillable
     * @return UtilityFisio
     */
    public function createLog(array $data): UtilityFisio
    {
        $payload = array_merge([
            'patient_id' => $data['patient_id'] ?? null,
            'id_formulario' => $data['id_formulario'] ?? null,
            'tabla_form' => $data['tabla_form'] ?? null,
            'status'     => $data['status'] ?? 1,
            'fecha'      => $data['fecha'] ?? now()->format('Y-m-d'),
            'user_id'    => $data['user_id'] ?? Auth::id(),
        ], $data);

        return UtilityFisio::create($payload);
    }

    /**
     * Actualiza un registro de la bitácora.
     * @param int $id
     * @param array $data
     * @return UtilityFisio
     */
    public function updateLog(int $id, array $data): UtilityFisio
    {
        $model = UtilityFisio::findOrFail($id);

        // Evitar sobrescribir created_by o user_id si no corresponde
        unset($data['created_by']);
        unset($data['user_id']);

        $model->fill($data);
        $model->updated_by = Auth::id();
        $model->save();

        return $model;
    }

    /**
     * Borrado lógico (status = 0). Retorna el modelo modificado.
     * @param int $id
     * @return UtilityFisio
     */
    public function deleteLog(int $id): UtilityFisio
    {
        $model = UtilityFisio::findOrFail($id);
        $model->status = 0;
        $model->updated_by = Auth::id();
        $model->save();

        return $model;
    }

    /**
     * Restaurar registro (status = 1).
     * @param int $id
     * @return UtilityFisio
     */
    public function restoreLog(int $id): UtilityFisio
    {
        $model = UtilityFisio::findOrFail($id);
        $model->status = 1;
        $model->updated_by = Auth::id();
        $model->save();

        return $model;
    }

    /**
     * Método estático de conveniencia para crear desde cualquier lugar.
     * UtilityFisioController::logEntry($patientId, 'form_name', 1, ['campo_adicional'=>'valor']);
     */
    public static function logEntry(int $patientId, string $tabla_form, int $id_formulario, int $status = 1, array $extra = []): UtilityFisio
    {
        $controller = new self();
        $data = array_merge($extra, [
            'patient_id' => $patientId,
            'tabla_form' => $tabla_form,
            'id_formulario' => $id_formulario,
            'status'     => $status,
        ]);
        return $controller->createLog($data);
    }

    /**
     * Método estático de conveniencia para actualizar desde cualquier lugar.
     * UtilityFisioController::logUpdate($id, ['nota'=>'nuevo']);
     */
    public static function logUpdate(int $id, array $data): UtilityFisio
    {
        $controller = new self();
        return $controller->updateLog($id, $data);
    }

    /**
     * Método estático para borrar lógicamente desde cualquier lugar.
     * UtilityFisioController::logDelete($id);
     */
    public static function logDelete(int $id): UtilityFisio
    {
        $controller = new self();
        return $controller->deleteLog($id);
    }

    /**
     * Método estático para restaurar desde cualquier lugar.
     * UtilityFisioController::logRestore($id);
     */
    public static function logRestore(int $id): UtilityFisio
    {
        $controller = new self();
        return $controller->restoreLog($id);
    }

    /**
     * Atajo para usar desde un Request de un formulario.
     */
    public function logFromRequest(Request $request, string $tabla_form): UtilityFisio
    {
        $data = $request->only(['patient_id', 'id_formulario']) + ['tabla_form' => $tabla_form];
        return $this->createLog($data);
    }

    /**
     * Busca y borra lógicamente un registro por sus campos clave
     * @param int $patientId ID del paciente
     * @param string $tablaForm Nombre de la tabla del formulario
     * @param int $idFormulario ID del formulario específico
     * @return UtilityFisio|null
     */
    public function deleteLogByFields(int $patientId, string $tablaForm, int $idFormulario): ?UtilityFisio
    {
        $record = UtilityFisio::where('patient_id', $patientId)
            ->where('tabla_form', $tablaForm)
            ->where('id_formulario', $idFormulario)
            ->where('status', 1)
            ->first();

        if ($record) {
            $record->status = 0;
            $record->updated_by = Auth::id();
            $record->save();
            return $record;
        }

        return null;
    }

    /**
     * Método estático para borrar lógicamente usando campos clave
     * Ejemplo: UtilityFisioController::logDeleteByFields($patientId, 'form_name', $idFormulario);
     */
    public static function logDeleteByFields(int $patientId, string $tablaForm, int $idFormulario): ?UtilityFisio
    {
        $controller = new self();
        return $controller->deleteLogByFields($patientId, $tablaForm, $idFormulario);
    }
}
