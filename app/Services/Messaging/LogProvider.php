<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Log;

/**
 * Provider de DESARROLLO: no envía nada — solo escribe al log.
 *
 * Útil para:
 *   - Antes de tener credenciales de Meta/Twilio
 *   - Tests automáticos
 *   - QA donde no quieres molestar a pacientes reales
 *
 * Marca el envío como "sent" para que la UI muestre el flujo completo.
 */
class LogProvider implements MessagingProvider
{
    public function send(?string $toPhone, string $body, array $context = []): array
    {
        $channel = $context['channel'] ?? 'log';
        $tpl     = $context['template_key'] ?? '-';
        $patient = $context['patient'] ?? null;

        $line = sprintf(
            "[MSG-LOG] to=%s name=%s channel=%s template=%s | %s",
            $toPhone ?: '(sin teléfono)',
            $patient->full_name ?? '?',
            $channel,
            $tpl,
            str_replace(["\r", "\n"], ' / ', $body)
        );
        Log::info($line);

        return [
            'status'              => 'sent',
            'sent_at'             => now(),
            'provider_message_id' => 'log-' . uniqid(),
            'provider_response'   => '[LOG PROVIDER] mensaje registrado, no enviado a red real.',
        ];
    }
}
