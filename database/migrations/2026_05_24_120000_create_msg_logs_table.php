<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 9a — Tabla de log de mensajes enviados (WhatsApp / SMS / log).
 *
 * Toda comunicación con pacientes pasa por aquí: el envío inmediato manual
 * y los futuros recordatorios automatizados. Mantiene trazabilidad legal
 * (qué se envió, cuándo, a quién, con qué resultado).
 *
 * Idempotente: usa Schema::hasTable.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('msg_logs')) {
            return;
        }
        Schema::create('msg_logs', function (Blueprint $t) {
            $t->bigIncrements('id');

            // Destinatario
            $t->unsignedBigInteger('patient_id')->nullable()->index();
            $t->string('to_phone', 32)->nullable();        // E.164 idealmente: +50212345678
            $t->string('to_name', 191)->nullable();        // snapshot del nombre al momento del envío

            // Canal y plantilla
            $t->enum('channel', ['whatsapp', 'sms', 'log'])->default('log');
            $t->string('template_key', 64)->nullable();    // 'reminder', 'evaluation_ready', 'free_text'
            $t->text('body');                              // texto final renderizado

            // Estado del envío
            $t->enum('status', ['queued', 'sent', 'failed', 'delivered', 'read', 'cancelled'])
              ->default('queued')->index();
            $t->string('provider', 32)->nullable();        // 'whatsapp_cloud', 'twilio', 'log'
            $t->string('provider_message_id', 191)->nullable();
            $t->text('provider_response')->nullable();     // JSON dump de la respuesta del API
            $t->text('error')->nullable();

            // Fechas
            $t->timestamp('scheduled_for')->nullable()->index();  // para futuros recordatorios programados
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('delivered_at')->nullable();

            // Auditoría
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();

            $t->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('msg_logs');
    }
};
