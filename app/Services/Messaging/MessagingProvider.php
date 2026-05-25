<?php

namespace App\Services\Messaging;

/**
 * Contrato para todos los providers de mensajería.
 *
 * @return array {
 *     status: 'sent'|'failed',
 *     sent_at?: \Carbon\Carbon,
 *     provider_message_id?: string,
 *     provider_response?: string,
 * }
 */
interface MessagingProvider
{
    /**
     * Envía un mensaje al destinatario.
     *
     * @param string|null $toPhone   Teléfono en formato E.164 idealmente (+50212345678)
     * @param string      $body      Texto del mensaje
     * @param array       $context   Contexto extra: channel, template_key, patient, etc.
     * @return array
     * @throws \RuntimeException si falla el envío
     */
    public function send(?string $toPhone, string $body, array $context = []): array;
}
