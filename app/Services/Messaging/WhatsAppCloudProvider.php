<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;

/**
 * Provider de WhatsApp Cloud API (Meta).
 *
 * Requiere en .env:
 *   WHATSAPP_PHONE_ID=...
 *   WHATSAPP_TOKEN=...
 *   WHATSAPP_API_VERSION=v18.0
 *
 * Documentación: https://developers.facebook.com/docs/whatsapp/cloud-api
 *
 * IMPORTANTE — Ventana de 24 horas:
 *   Meta solo permite mensajes "free-form" (texto libre) si el paciente
 *   te escribió o respondió a una plantilla en las últimas 24 horas.
 *
 *   Fuera de esa ventana SOLO puedes enviar mensajes desde plantillas
 *   pre-aprobadas (template messages). Si no tienes plantillas
 *   configuradas en Meta Business Manager, los envíos fallarán.
 *
 *   Para evitar esto en arranque:
 *   1. Crea las plantillas en Meta Business Manager
 *   2. Anota sus nombres exactos
 *   3. Configúralos en .env: WHATSAPP_TPL_REMINDER=mi_plantilla_recordatorio
 *
 *   Si template_key existe en config('messaging.whatsapp_cloud.templates'),
 *   este provider intenta enviar como template. Si no, envía como texto libre.
 */
class WhatsAppCloudProvider implements MessagingProvider
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(?string $toPhone, string $body, array $context = []): array
    {
        $phoneId = $this->config['phone_number_id'] ?? null;
        $token   = $this->config['access_token']    ?? null;
        $apiVer  = $this->config['api_version']     ?? 'v18.0';

        if (!$phoneId || !$token) {
            throw new \RuntimeException(
                'WhatsApp Cloud no configurado. Define WHATSAPP_PHONE_ID y WHATSAPP_TOKEN en .env.'
            );
        }

        $to = $this->normalizePhone($toPhone);
        if (!$to) {
            throw new \RuntimeException('Teléfono inválido o ausente para envío de WhatsApp.');
        }

        $url = "https://graph.facebook.com/{$apiVer}/{$phoneId}/messages";

        // Determinar si tenemos un template aprobado para este envío
        $templateKey      = $context['template_key'] ?? null;
        $templateName     = $templateKey
            ? ($this->config['templates'][$templateKey] ?? null)
            : null;

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
        ];

        if ($templateName) {
            // ENVÍO POR TEMPLATE (funciona fuera de ventana de 24h)
            $payload['type']     = 'template';
            $payload['template'] = [
                'name'     => $templateName,
                'language' => ['code' => 'es'],
                // Si tu plantilla tiene parámetros {{1}}, {{2}}, etc., los pasas en components.
                // Aquí pasamos el body completo como un solo parámetro de body — ajusta según tu plantilla real.
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => [['type' => 'text', 'text' => $body]],
                    ],
                ],
            ];
        } else {
            // ENVÍO FREE-FORM TEXT (solo funciona dentro de ventana de 24h)
            $payload['type'] = 'text';
            $payload['text'] = ['preview_url' => false, 'body' => $body];
        }

        $response = Http::withToken($token)
            ->timeout(15)
            ->acceptJson()
            ->post($url, $payload);

        if (!$response->successful()) {
            $err = $response->json('error.message') ?: $response->body();
            throw new \RuntimeException("WhatsApp Cloud error: {$err}");
        }

        $messageId = $response->json('messages.0.id');

        return [
            'status'              => 'sent',
            'sent_at'             => now(),
            'provider_message_id' => $messageId,
            'provider_response'   => mb_substr($response->body(), 0, 2000),
        ];
    }

    /**
     * Normaliza el número a E.164 sin signos.
     * Si no detecta código de país, asume +502 (Guatemala) por defecto.
     */
    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;
        // Quita todo lo que no sea dígito
        $digits = preg_replace('/\D+/', '', $phone);
        if (!$digits) return null;
        // Si ya empieza con 502 y tiene >=11 dígitos, ok
        if (strlen($digits) >= 11 && substr($digits, 0, 3) === '502') {
            return $digits;
        }
        // Si tiene 8 dígitos típicos de Guatemala, prepende 502
        if (strlen($digits) === 8) {
            return '502' . $digits;
        }
        return $digits;
    }
}
