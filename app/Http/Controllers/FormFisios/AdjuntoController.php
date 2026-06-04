<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use App\Http\Repository\UtilityRepository;
use App\Models\FormFisios\FisAdjunto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

/**
 * Fase 15 — Adjuntos de la ficha clínica.
 *
 * Endpoints:
 *   GET  adjuntos/{patientId}?ficha_id=&categoria=  → listado filtrable
 *   POST adjuntos                                    → upload multi-file
 *   POST adjuntos/{id}/delete                        → borrado lógico + unlink
 *   GET  adjuntos/{id}/download                      → forzar descarga con nombre original
 *
 * Cada upload/delete se registra en fis_historys con tabla_form='fis_adjuntos'
 * para que aparezca en el timeline del expediente.
 */
class AdjuntoController extends Controller
{
    /** Mime types permitidos. Imágenes, PDFs, Office. */
    private const ALLOWED_MIMES = [
        // Imágenes
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif',
        // PDFs
        'application/pdf',
        // Office
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        // Texto plano
        'text/plain',
    ];

    /** Tamaño máximo por archivo en bytes (20 MB). */
    private const MAX_FILE_BYTES = 20 * 1024 * 1024;

    /** Cuota soft por paciente: alerta visual, no bloqueo. (500 MB) */
    public const QUOTA_SOFT_BYTES = 500 * 1024 * 1024;

    public function __construct()
    {
        $this->middleware('auth');
    }

    // -------------------------------------------------------- LIST

    /**
     * Lista de adjuntos de un paciente, filtrable por ficha y categoría.
     * Devuelve también un resumen agregado (totales, tamaño usado).
     */
    public function index(Request $request, $patientId)
    {
        try {
            $q = FisAdjunto::where('patient_id', (int) $patientId)
                ->where('status', 1);

            $fichaId = $request->input('ficha_id');
            if ($fichaId === 'unassigned') {
                $q->whereNull('ficha_id');
            } elseif (is_numeric($fichaId)) {
                $q->where('ficha_id', (int) $fichaId);
            }
            // si $fichaId es null o 'all' → no filtra, trae todo

            $categoria = $request->input('categoria');
            if ($categoria && array_key_exists($categoria, FisAdjunto::CATEGORIAS)) {
                $q->where('categoria', $categoria);
            }

            $rows = $q->orderByDesc('created_at')->get();

            // Resolver nombres de uploaders
            $userIds = $rows->pluck('uploaded_by')->filter()->unique()->all();
            $userMap = !empty($userIds)
                ? \App\Models\User::whereIn('id', $userIds)->pluck('name', 'id')->toArray()
                : [];

            $items = $rows->map(function ($r) use ($userMap) {
                return [
                    'id'           => $r->id,
                    'patient_id'   => $r->patient_id,
                    'ficha_id'     => $r->ficha_id,
                    'categoria'    => $r->categoria,
                    'categoria_label' => FisAdjunto::CATEGORIAS[$r->categoria] ?? $r->categoria,
                    'file_name'    => $r->file_name,
                    'file_url'     => $r->public_url,
                    'mime'         => $r->mime,
                    'size_bytes'   => (int) $r->size_bytes,
                    'descripcion'  => $r->descripcion,
                    'is_image'     => $r->isImage(),
                    'is_pdf'       => $r->isPdf(),
                    'uploaded_by'  => $r->uploaded_by,
                    'uploader_name'=> $userMap[$r->uploaded_by] ?? null,
                    'created_at'   => $r->created_at ? $r->created_at->toIso8601String() : null,
                ];
            });

            // Resumen agregado (siempre sobre TODO el paciente, no filtrado)
            $totalsByCategory = FisAdjunto::where('patient_id', (int) $patientId)
                ->where('status', 1)
                ->select('categoria', DB::raw('COUNT(*) as cnt'), DB::raw('COALESCE(SUM(size_bytes),0) as bytes'))
                ->groupBy('categoria')
                ->get()
                ->keyBy('categoria');

            $summary = [];
            foreach (FisAdjunto::CATEGORIAS as $key => $label) {
                $row = $totalsByCategory->get($key);
                $summary[$key] = [
                    'label' => $label,
                    'count' => $row ? (int) $row->cnt : 0,
                    'bytes' => $row ? (int) $row->bytes : 0,
                ];
            }

            $totalBytes = array_sum(array_column($summary, 'bytes'));
            $totalCount = array_sum(array_column($summary, 'count'));

            return $this->apiResponse([
                'status' => '1',
                'data' => [
                    'items'   => $items,
                    'summary' => [
                        'by_category' => $summary,
                        'total_count' => $totalCount,
                        'total_bytes' => $totalBytes,
                        'quota_bytes' => self::QUOTA_SOFT_BYTES,
                    ],
                ],
            ], 200);
        } catch (\Throwable $e) {
            return $this->apiResponse([
                'status' => '500',
                'message' => 'Error listando adjuntos.',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------- UPLOAD

    /**
     * Sube uno o varios archivos. Form-data:
     *   files[]      → file (multi)
     *   patient_id   → int (requerido)
     *   ficha_id     → int (opcional, null = adjunto general del paciente)
     *   categoria    → enum (default 'otros')
     *   descripcion  → text (opcional, aplicado a TODOS los archivos del batch)
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'patient_id' => 'required|integer',
            'ficha_id'   => 'nullable|integer',
            'categoria'  => 'nullable|string|in:' . implode(',', array_keys(FisAdjunto::CATEGORIAS)),
            'descripcion'=> 'nullable|string|max:1000',
            'files'      => 'required|array|min:1|max:20',
            'files.*'    => 'required|file',
        ]);
        if ($v->fails()) {
            return $this->apiResponse(['status' => '422', 'data' => $v->errors()], 422);
        }

        $patientId = (int) $request->input('patient_id');
        $fichaId   = $request->input('ficha_id') ?: null;
        $categoria = $request->input('categoria', 'otros');
        $descripcion = $request->input('descripcion');

        // Carpeta destino por paciente: uploadfiles/adjuntos/{Nombre_Apellido}_{id}
        // Fallback a uploadfiles/adjuntos si no se resuelve el paciente.
        $uploadFolder = UtilityRepository::patientFolder($patientId) ?: 'uploadfiles/adjuntos';

        $saved = [];
        $errors = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $mime = $file->getClientMimeType();
                if (!in_array($mime, self::ALLOWED_MIMES, true)) {
                    $errors[] = [
                        'file_name' => $file->getClientOriginalName(),
                        'reason'    => 'Tipo de archivo no permitido (' . $mime . ').',
                    ];
                    continue;
                }
                if ($file->getSize() > self::MAX_FILE_BYTES) {
                    $errors[] = [
                        'file_name' => $file->getClientOriginalName(),
                        'reason'    => 'Excede el tamaño máximo (20 MB).',
                    ];
                    continue;
                }

                // Guardar en public/uploadfiles/adjuntos/{paciente}/ via repository
                // (constraint del proyecto: siempre UtilityRepository::saveFile)
                $path = UtilityRepository::saveFile($file, self::ALLOWED_MIMES, $uploadFolder);

                $row = FisAdjunto::create([
                    'patient_id'  => $patientId,
                    'ficha_id'    => $fichaId,
                    'categoria'   => $categoria,
                    'file_name'   => $file->getClientOriginalName(),
                    'file_path'   => $path,
                    'mime'        => $mime,
                    'size_bytes'  => $file->getSize(),
                    'descripcion' => $descripcion,
                ]);

                $this->logHistory($patientId, $fichaId, $row->id);

                $saved[] = [
                    'id'         => $row->id,
                    'file_name'  => $row->file_name,
                    'file_url'   => $row->public_url,
                    'size_bytes' => (int) $row->size_bytes,
                    'is_image'   => $row->isImage(),
                    'is_pdf'     => $row->isPdf(),
                    'categoria'  => $row->categoria,
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'file_name' => $file->getClientOriginalName(),
                    'reason'    => 'Error interno: ' . $e->getMessage(),
                ];
            }
        }

        return $this->apiResponse([
            'status' => count($saved) > 0 ? '1' : '500',
            'data'   => [
                'saved'  => $saved,
                'errors' => $errors,
            ],
        ], count($saved) > 0 ? 200 : 422);
    }

    // -------------------------------------------------------- DELETE

    public function destroy($id)
    {
        try {
            $row = FisAdjunto::where('id', (int) $id)->where('status', 1)->first();
            if (!$row) {
                return $this->apiResponse(['status' => '404', 'message' => 'Adjunto no encontrado.'], 404);
            }

            // Borrado lógico — mantenemos el archivo físico por trazabilidad
            // y para no romper enlaces ya distribuidos. Si se requiere unlink
            // físico, descomenta el bloque siguiente.
            // $abs = public_path($row->file_path);
            // if (File::exists($abs)) File::delete($abs);

            $row->status = 0;
            $row->save();

            // Desactivar también el registro espejo en fis_historys (el que se
            // creó en el upload vía logHistory). Si no, el adjunto eliminado
            // seguiría apareciendo en el timeline y en los counts del expediente,
            // que leen de fis_historys con status=1.
            DB::table('fis_historys')
                ->where('tabla_form', 'fis_adjuntos')
                ->where('id_formulario', $row->id)
                ->where('patient_id', $row->patient_id)
                ->update(['status' => 0, 'updated_at' => now()]);

            return $this->apiResponse(['status' => '1', 'data' => ['id' => $row->id]], 200);
        } catch (\Throwable $e) {
            return $this->apiResponse([
                'status' => '500',
                'message' => 'Error eliminando adjunto.',
                'debug'   => $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------- DOWNLOAD

    /**
     * Descarga el archivo con su nombre original (force-download).
     */
    public function download($id)
    {
        $row = FisAdjunto::where('id', (int) $id)->where('status', 1)->first();
        if (!$row) abort(404);

        $abs = public_path($row->file_path);
        if (!File::exists($abs)) abort(404);

        return response()->download($abs, $row->file_name);
    }

    // -------------------------------------------------------- INTERNAL

    /**
     * Registra el upload en fis_historys para que aparezca en el timeline del expediente.
     */
    private function logHistory(int $patientId, ?int $fichaId, int $adjuntoId): void
    {
        try {
            DB::table('fis_historys')->insert([
                'patient_id'    => $patientId,
                'ficha_id'      => $fichaId,
                'user_id'       => Auth::id() ?? 0,
                'id_formulario' => $adjuntoId,
                'fecha'         => now()->toDateString(),
                'tabla_form'    => 'fis_adjuntos',
                'status'        => 1,
                'created_by'    => Auth::id(),
                'updated_by'    => Auth::id(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            // No queremos que el log rompa el flujo principal
            \Log::warning('AdjuntoController logHistory failed: ' . $e->getMessage());
        }
    }
}
