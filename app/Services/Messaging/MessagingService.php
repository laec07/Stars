<?php

namespace App\Services\Messaging;

use App\Models\Messaging\MsgLog;
use App\Models\Patient\CmnPatient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Fase 9a — Service principal de mensajería.
 *
 * Orquesta:
 *   - Render de plantilla con variables
 *   - Persistir en msg_logs (siempre, sin importar el provider)
 *   - Delegar el envío real al provider activo
 *   - Actualizar el log con el resultado
 *
 * Uso:
 *   app(MessagingService::class)->sendToPatient($patient, [
 *       'channel'       => 'whatsapp',
 *       'template_key'  => 'reminder',
 *       'vars'          => ['fecha' => '24/05/2026', 'hora' => '10:00'],
 *   ]);
 *
 *   o con texto libre:
 *
 *   ->sendToPatient($patient, ['body' => 'Hola María, ...'])
 */
class MessagingService
{
    private array $config;
    private MessagingProvider $provider;

    public function __construct()
    {
        $this->config = config('messaging', []);
        $this->provider = $this->resolveProvider($this->config['provider'] ?? 'log');
    }

    /**
     * Envía un mensaje a un paciente.
     *
     * @param CmnPatient $patient
     * @param array $opts {
     *     channel?: 'whatsapp'|'sms'|'log',
     *     template_key?: string,
     *     vars?: array<string,string>,
     *     body?: string,              // si se da, sobrescribe la plantilla
     *     scheduled_for?: string,     // ISO datetime, para envíos programados
     * }
     */
    public function sendToPatient(CmnPatient $patient, array $opts = []): MsgLog
    {
        $channel = $opts['channel'] ?? ($this->config['default_channel'] ?? 'whatsapp');
        $body    = $opts['body']    ?? $this->renderTemplate(
            $opts['template_key'] ?? 'free',
            array_merge($this->defaultVars($patient), $opts['vars'] ?? [])
        );

        if (trim($body) === '') {
            throw new \InvalidArgumentException('Cuerpo de mensaje vacío.');
        }

        // Persistir el log ANTES de enviar — captura intent aunque el provider falle
        $log = MsgLog::create([
            'patient_id'    => $patient->id,
            'to_phone'      => $patient->phone_no,
            'to_name'       => $patient->full_name,
            'channel'       => $channel,
            'template_key'  => $opts['template_key'] ?? null,
            'body'          => $body,
            'status'        => 'queued',
            'provider'      => $this->config['provider'] ?? 'log',
            'scheduled_for' => $opts['scheduled_for'] ?? null,
            'created_by'    => Auth::id(),
        ]);

        // Si está programado para el futuro, NO enviar ahora
        if (!empty($opts['scheduled_for'])) {
            return $log;
        }

        // Dry-run global (settings.dry_run=true) o provider 'log' → no envío real
        if (!empty($this->config['dry_run']) && !($this->provider instanceof LogProvider)) {
            $log->status = 'sent';
            $log->sent_at = now();
            $log->provider_response = '[DRY RUN] Provider habilitado pero dry_run activo';
            $log->save();
            return $log;
        }

        try {
            $result = $this->provider->send($log->to_phone, $body, [
                'channel'      => $channel,
                'template_key' => $opts['template_key'] ?? null,
                'patient'      => $patient,
            ]);
            $log->status              = $result['status']              ?? 'sent';
            $log->sent_at             = $result['sent_at']             ?? now();
            $log->provider_message_id = $result['provider_message_id'] ?? null;
            $log->provider_response   = $result['provider_response']   ?? null;
            $log->save();
        } catch (\Throwable $e) {
            Log::error('MessagingService.sendToPatient failed', [
                'patient_id' => $patient->id,
                'msg'        => $e->getMessage(),
            ]);
            $log->status = 'failed';
            $log->error  = $e->getMessage();
            $log->save();
        }

        return $log;
    }

    /**
     * Renderiza una plantilla del catálogo + sustituye variables.
     */
    public function renderTemplate(string $key, array $vars = []): string
    {
        $tpl = $this->config['templates'][$key]['body'] ?? '';
        return $this->interpolate($tpl, $vars);
    }

    /**
     * Sustituye {placeholders} por valores del array.
     */
    public function interpolate(string $template, array $vars): string
    {
        if (trim($template) === '') return '';
        $out = $template;
        foreach ($vars as $k => $v) {
            $out = str_replace('{' . $k . '}', (string) $v, $out);
        }
        // Limpia variables no rellenadas — evita que el mensaje quede con "{algo}" visible
        $out = preg_replace('/\{[a-z_]+\}/i', '', $out);
        return trim($out);
    }

    /**
     * Variables disponibles por defecto en cualquier plantilla.
     */
    public function defaultVars(CmnPatient $patient): array
    {
        $name = trim((string) ($patient->full_name ?? ''));
        // Tomar solo el primer nombre para saludo más natural
        $firstName = $name !== '' ? explode(' ', $name)[0] : '';

        return [
            'paciente'      => $firstName,
            'paciente_full' => $name,
            'clinic_name'   => $this->config['clinic_name']  ?? 'Healing Hands',
            'clinic_phone'  => $this->config['clinic_phone'] ?? '',
            'clinic_url'    => $this->config['clinic_url']   ?? '',
            'fecha'         => '',
            'hora'          => '',
        ];
    }

    /**
     * Lista de plantillas para mostrar en UI.
     */
    public function listTemplates(): array
    {
        $tpls = $this->config['templates'] ?? [];
        $out  = [];
        foreach ($tpls as $key => $t) {
            $out[] = [
                'key'    => $key,
                'label'  => $t['label'] ?? $key,
                'icon'   => $t['icon']  ?? 'fa-comment',
                'body'   => $t['body']  ?? '',
            ];
        }
        return $out;
    }

    public function currentProvider(): string
    {
        return $this->config['provider'] ?? 'log';
    }

    private function resolveProvider(string $name): MessagingProvider
    {
        switch ($name) {
            case 'whatsapp_cloud':
                return new WhatsAppCloudProvider($this->config['whatsapp_cloud'] ?? []);
            case 'twilio':
                return new TwilioProvider($this->config['twilio'] ?? []);
            case 'log':
            default:
                return new LogProvider();
        }
    }
}
