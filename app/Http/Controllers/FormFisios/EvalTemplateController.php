<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use App\Models\FormFisios\FisEvalTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Fase 10 — Plantillas de evaluación.
 *
 * Endpoints:
 *   GET    eval-templates?tabla=fis_xxx     → lista (personal + globales)
 *   POST   eval-templates                   → crea o actualiza
 *   GET    eval-templates/{id}              → detalle (con payload)
 *   POST   eval-templates/{id}/delete       → borrado lógico
 */
class EvalTemplateController extends Controller
{
    /** Solo las 11 evaluaciones con config inline aceptan templates */
    private const ALLOWED_TABLES = [
        'fis_evdolors', 'fis_cheqs', 'fis_evpiels', 'fis_antropometrias',
        'fis_antropoms', 'fis_goniometrias', 'fis_cheqmus', 'fis_sensitivitys',
        'fis_evalineps', 'fis_electros', 'fis_ultras',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Lista plantillas para un tipo de evaluación.
     * Visibles para el usuario actual: las suyas (personal) + todas las globales.
     */
    public function index(Request $request)
    {
        try {
            $tabla = $request->input('tabla', '');
            if (!in_array($tabla, self::ALLOWED_TABLES, true)) {
                return $this->apiResponse(['status' => '422', 'message' => 'Tipo de evaluación inválido'], 422);
            }

            $userId = Auth::id();
            $rows = FisEvalTemplate::where('tabla_form', $tabla)
                ->where('status', 1)
                ->where(function ($q) use ($userId) {
                    $q->where('scope', 'global')
                      ->orWhere(function ($qq) use ($userId) {
                          $qq->where('scope', 'personal')->where('created_by', $userId);
                      });
                })
                ->orderBy('scope', 'desc')   // personal primero (P > G alfa); luego ordenamos por nombre
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'scope', 'created_by', 'created_at']);

            // Resolver nombres de creadores
            $userIds = $rows->pluck('created_by')->filter()->unique()->all();
            $userMap = [];
            if (!empty($userIds)) {
                $userMap = \App\Models\User::whereIn('id', $userIds)->pluck('name', 'id')->toArray();
            }

            $payload = $rows->map(function ($r) use ($userMap, $userId) {
                return [
                    'id'           => $r->id,
                    'name'         => $r->name,
                    'description'  => $r->description,
                    'scope'        => $r->scope,
                    'created_by'   => $r->created_by,
                    'creator_name' => $userMap[$r->created_by] ?? null,
                    'is_owner'     => $r->created_by == $userId,
                    'created_at'   => $r->created_at ? $r->created_at->toIso8601String() : null,
                ];
            });

            return $this->apiResponse([
                'status' => '1',
                'data'   => [
                    'tabla'     => $tabla,
                    'templates' => $payload,
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('EvalTemplate.index: ' . $e->getMessage());
            return $this->apiResponse([
                'status' => '500', 'message' => 'Error listando plantillas',
                'debug'  => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detalle de una plantilla — incluye el payload completo.
     */
    public function show($id)
    {
        try {
            $tpl = FisEvalTemplate::where('status', 1)->find($id);
            if (!$tpl) {
                return $this->apiResponse(['status' => '404', 'message' => 'Plantilla no encontrada'], 404);
            }
            if (!$this->canRead($tpl)) {
                return $this->apiResponse(['status' => '403', 'message' => 'Sin acceso a esta plantilla'], 403);
            }

            $payload = json_decode($tpl->payload, true) ?: [];

            return $this->apiResponse([
                'status' => '1',
                'data'   => [
                    'id'          => $tpl->id,
                    'tabla_form'  => $tpl->tabla_form,
                    'name'        => $tpl->name,
                    'description' => $tpl->description,
                    'scope'       => $tpl->scope,
                    'payload'     => $payload,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return $this->apiResponse([
                'status' => '500', 'message' => 'Error', 'debug' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crea o actualiza una plantilla.
     * Si se manda 'id' y es válido, actualiza.
     * El payload viene como objeto JSON con los valores del formulario.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tabla_form'  => 'required|string|max:64',
                'name'        => 'required|string|max:191',
                'description' => 'nullable|string|max:1000',
                'scope'       => 'required|in:personal,global',
                'payload'     => 'required',
                'id'          => 'nullable|integer',
            ]);
            if ($validator->fails()) {
                return $this->apiResponse(['status' => '422', 'data' => $validator->errors()], 422);
            }

            $tabla = $request->input('tabla_form');
            if (!in_array($tabla, self::ALLOWED_TABLES, true)) {
                return $this->apiResponse(['status' => '422', 'message' => 'Tipo de evaluación inválido'], 422);
            }

            // Acepta payload como string JSON o como array
            $payload = $request->input('payload');
            if (is_array($payload)) {
                $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            }
            // Limpia datos per-registro que NO deben estar en plantilla
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                foreach (['patient_id', 'ficha_id', 'id', 'Id', '_token', 'status',
                          'created_by', 'updated_by', 'created_at', 'updated_at'] as $strip) {
                    unset($decoded[$strip]);
                }
                $payload = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }

            $id = $request->input('id');
            if ($id) {
                $tpl = FisEvalTemplate::where('status', 1)->find($id);
                if (!$tpl) {
                    return $this->apiResponse(['status' => '404', 'message' => 'Plantilla no encontrada'], 404);
                }
                if (!$this->canEdit($tpl)) {
                    return $this->apiResponse(['status' => '403', 'message' => 'Sin permiso para editar esta plantilla'], 403);
                }
                $tpl->update([
                    'name'        => $request->input('name'),
                    'description' => $request->input('description'),
                    'scope'       => $request->input('scope'),
                    'payload'     => $payload,
                ]);
                return $this->apiResponse([
                    'status' => '1',
                    'data'   => ['id' => $tpl->id, 'name' => $tpl->name, 'action' => 'updated'],
                ], 200);
            }

            $tpl = FisEvalTemplate::create([
                'tabla_form'  => $tabla,
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
                'scope'       => $request->input('scope'),
                'payload'     => $payload,
            ]);
            return $this->apiResponse([
                'status' => '1',
                'data'   => ['id' => $tpl->id, 'name' => $tpl->name, 'action' => 'created'],
            ], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('EvalTemplate.store: ' . $e->getMessage());
            return $this->apiResponse([
                'status' => '500', 'message' => 'Error guardando plantilla',
                'debug'  => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Borrado lógico (status=0).
     */
    public function destroy($id)
    {
        try {
            $tpl = FisEvalTemplate::find($id);
            if (!$tpl) {
                return $this->apiResponse(['status' => '404', 'message' => 'Plantilla no encontrada'], 404);
            }
            if (!$this->canEdit($tpl)) {
                return $this->apiResponse(['status' => '403', 'message' => 'Sin permiso para borrar esta plantilla'], 403);
            }
            $tpl->status = 0;
            $tpl->updated_by = Auth::id();
            $tpl->save();
            return $this->apiResponse(['status' => '1', 'data' => ['id' => $tpl->id]], 200);
        } catch (\Throwable $e) {
            return $this->apiResponse([
                'status' => '500', 'message' => 'Error', 'debug' => $e->getMessage(),
            ], 500);
        }
    }

    // ---- ACL helpers ----
    private function canRead(FisEvalTemplate $tpl): bool
    {
        if ($tpl->scope === 'global') return true;
        return $tpl->created_by == Auth::id();
    }

    private function canEdit(FisEvalTemplate $tpl): bool
    {
        // Globales: solo admin (user_type=1 típicamente) o el creador
        if ($tpl->scope === 'global') {
            $u = Auth::user();
            $isAdmin = $u && (
                (isset($u->user_type) && (int) $u->user_type === 1) ||
                $u->id == $tpl->created_by
            );
            return (bool) $isAdmin;
        }
        // Personales: solo su creador
        return $tpl->created_by == Auth::id();
    }
}
