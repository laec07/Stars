<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Models\Messaging\MsgLog;
use App\Models\Patient\CmnPatient;
use App\Services\Messaging\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Fase 9a — Endpoints de mensajería relacionada al paciente.
 *
 *   GET  patient-messages/{id}        → log de mensajes del paciente
 *   GET  message-templates            → catálogo de plantillas
 *   POST send-patient-message         → envía mensaje (body o template)
 */
class PatientMessagingController extends Controller
{
    private MessagingService $svc;

    public function __construct(MessagingService $svc)
    {
        $this->middleware('auth');
        $this->svc = $svc;
    }

    /**
     * Listar mensajes enviados a un paciente, ordenados por fecha desc.
     */
    public function listForPatient($id)
    {
        try {
            $patient = CmnPatient::find($id);
            if (!$patient) {
                return $this->apiResponse(['status' => '404', 'message' => 'Paciente no encontrado'], 404);
            }

            $rows = MsgLog::where('patient_id', $id)
                ->orderByDesc('id')
                ->limit(100)
                ->get(['id', 'channel', 'template_key', 'body', 'status', 'provider',
                       'sent_at', 'delivered_at', 'error', 'created_at', 'created_by']);

            // Resolver nombre del fisio que envió
            $userIds = $rows->pluck('created_by')->filter()->unique()->all();
            $userMap = [];
            if (!empty($userIds)) {
                $userMap = \App\Models\User::whereIn('id', $userIds)
                    ->pluck('name', 'id')->toArray();
            }

            $payload = $rows->map(function ($r) use ($userMap) {
                return [
                    'id'           => $r->id,
                    'channel'      => $r->channel,
                    'template_key' => $r->template_key,
                    'body'         => $r->body,
                    'status'       => $r->status,
                    'provider'     => $r->provider,
                    'sent_at'      => optional($r->sent_at)->toIso8601String(),
                    'delivered_at' => optional($r->delivered_at)->toIso8601String(),
                    'error'        => $r->error,
                    'created_at'   => $r->created_at ? $r->created_at->toIso8601String() : null,
                    'created_by'   => $r->created_by,
                    'user_name'    => $userMap[$r->created_by] ?? null,
                ];
            });

            return $this->apiResponse([
                'status' => '1',
                'data'   => [
                    'patient_id'      => $patient->id,
                    'patient_name'    => $patient->full_name,
                    'patient_phone'   => $patient->phone_no,
                    'messages'        => $payload,
                    'current_provider'=> $this->svc->currentProvider(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('listForPatient: ' . $e->getMessage());
            return $this->apiResponse([
                'status' => '500', 'message' => 'Error cargando mensajes',
                'debug'  => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Devuelve las plantillas disponibles + las variables que aceptan.
     */
    public function templates()
    {
        return $this->apiResponse([
            'status' => '1',
            'data'   => [
                'templates' => $this->svc->listTemplates(),
                'vars'      => [
                    'paciente'      => 'Primer nombre del paciente',
                    'paciente_full' => 'Nombre completo',
                    'clinic_name'   => 'Nombre de la clínica',
                    'clinic_phone'  => 'Teléfono de la clínica',
                    'fecha'         => 'Fecha (la pones tú)',
                    'hora'          => 'Hora (la pones tú)',
                ],
                'provider'  => $this->svc->currentProvider(),
            ],
        ], 200);
    }

    /**
     * Pre-rendiza una plantilla con variables para mostrar preview antes de enviar.
     * GET render-template?template_key=xxx&vars[fecha]=24/05/2026&patient_id=44
     */
    public function renderTemplate(Request $request)
    {
        try {
            $patient = null;
            if ($request->filled('patient_id')) {
                $patient = CmnPatient::find($request->input('patient_id'));
            }
            $defaults = $patient ? $this->svc->defaultVars($patient) : [];
            $vars = array_merge($defaults, (array) $request->input('vars', []));

            $body = '';
            if ($request->filled('template_key')) {
                $body = $this->svc->renderTemplate($request->input('template_key'), $vars);
            } elseif ($request->filled('body')) {
                $body = $this->svc->interpolate((string) $request->input('body'), $vars);
            }

            return $this->apiResponse([
                'status' => '1',
                'data'   => ['body' => $body],
            ], 200);
        } catch (\Throwable $e) {
            return $this->apiResponse([
                'status' => '500',
                'message' => 'Error al renderizar plantilla',
                'debug'  => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envía un mensaje al paciente.
     * POST send-patient-message
     *   patient_id (req)
     *   channel        ('whatsapp' default)
     *   template_key   ('free' o cualquier plantilla)
     *   body           (texto final si no se usa template)
     *   vars[*]
     */
    public function sendToPatient(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id'   => 'required|integer|exists:cmn_patients,id',
                'channel'      => 'nullable|in:whatsapp,sms,log',
                'template_key' => 'nullable|string|max:64',
                'body'         => 'nullable|string|max:4000',
            ]);
            if ($validator->fails()) {
                return $this->apiResponse([
                    'status' => '422', 'data' => $validator->errors(),
                ], 422);
            }

            $patient = CmnPatient::find($request->input('patient_id'));
            if (!$patient) {
                return $this->apiResponse(['status' => '404', 'message' => 'Paciente no encontrado'], 404);
            }

            if (empty($patient->phone_no)) {
                return $this->apiResponse([
                    'status'  => '422',
                    'message' => 'El paciente no tiene teléfono registrado. Edita el perfil para agregarlo.',
                ], 422);
            }

            $opts = [
                'channel'      => $request->input('channel'),
                'template_key' => $request->input('template_key'),
                'vars'         => (array) $request->input('vars', []),
            ];
            if ($request->filled('body')) {
                $opts['body'] = $request->input('body');
            }

            $log = $this->svc->sendToPatient($patient, $opts);

            return $this->apiResponse([
                'status' => $log->status === 'failed' ? '500' : '1',
                'data'   => [
                    'id'        => $log->id,
                    'status'    => $log->status,
                    'body'      => $log->body,
                    'sent_at'   => optional($log->sent_at)->toIso8601String(),
                    'provider'  => $log->provider,
                    'error'     => $log->error,
                ],
            ], $log->status === 'failed' ? 500 : 200);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('sendToPatient endpoint: ' . $e->getMessage());
            return $this->apiResponse([
                'status' => '500',
                'message' => 'Error enviando mensaje',
                'debug'  => $e->getMessage(),
            ], 500);
        }
    }
}
