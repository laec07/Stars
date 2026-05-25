<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;

/**
 * Provider de Twilio (WhatsApp Business o SMS).
 *
 * Requiere en .env:
 *   TWILIO_SID=ACxxxx
 *   TWILIO_TOKEN=xxxxx
 *   TWILIO_FROM_WHATSAPP=whatsapp:+14155238886
 *   TWILIO_FROM_SMS=+1234567890
 *
 * Documentación: https://www.twilio.com/docs/whatsapp/api
 */
class TwilioProvider implements MessagingProvider
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(?string $toPhone, string $body, array $context = []): array
    {
        $sid   = $this->config['sid']   ?? null;
        $token = $this->config['token'] ?? null;
        if (!$sid || !$token) {
            throw new \RuntimeException('Twilio no configurado. Define TWILIO_SID y TWILIO_TOKEN en .env.');
        }

        $channel = $context['channel'] ?? 'whatsapp';
        $to      = $this->normalizePhone($toPhone, $channel);
        if (!$to) {
            throw new \RuntimeException('Teléfono inválido para envío via Twilio.');
        }

        $from = $channel === 'sms'
            ? ($this->config['from_sms'] ?? null)
            : ($this->config['from_whatsapp'] ?? null);
        if (!$from) {
            throw new \RuntimeException("Twilio: falta 'from' para canal {$channel}.");
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, $token)
            ->timeout(15)
            ->asForm()
            ->post($url, [
                'From' => $from,
                'To'   => $to,
                'Body' => $body,
            ]);

        if (!$response->successful()) {
            $err = $response->json('message') ?: $response->body();
            throw new \RuntimeException("Twilio error: {$err}");
        }

        return [
            'status'              => 'sent',
            'sent_at'             => now(),
            'provider_message_id' => $response->json('sid'),
            'provider_response'   => mb_substr($response->body(), 0, 2000),
        ];
    }

    private function normalizePhone(?string $phone, string $channel): ?string
    {
        if (!$phone) return null;
        $digits = preg_replace('/\D+/', '', $phone);
        if (!$digits) return null;
        if (strlen($digits) === 8) $digits = '502' . $digits;  // GT por defecto
        $e164 = '+' . $digits;
        return $channel === 'whatsapp' ? 'whatsapp:' . $e164 : $e164;
    }
}
