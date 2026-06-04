<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 15 — Adjuntos de la ficha clínica.
 *
 * Cada paciente puede tener archivos (exámenes, fotos clínicas, documentos,
 * recetas) que se vinculan opcionalmente a una ficha. Si ficha_id es NULL,
 * el adjunto es "general del paciente" (ej. INE, pasaporte).
 *
 *   patient_id  → siempre obligatorio
 *   ficha_id    → opcional; permite scope por caso clínico
 *   categoria   → enum semántico para filtrar/agrupar en UI
 *   file_path   → path relativo bajo public/uploadfiles/
 *   size_bytes  → para cuotas y métricas
 *
 * Idempotente.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('fis_adjuntos')) return;

        Schema::create('fis_adjuntos', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('patient_id');
            $t->unsignedBigInteger('ficha_id')->nullable();
            $t->enum('categoria', [
                'examenes',          // RX, RMN, eco, laboratorios
                'fotos_clinicas',    // postura, lesión, edema, evolución
                'documentos',        // referencias, altas, informes externos
                'recetas',           // tratamiento farmacológico
                'otros',             // catch-all
            ])->default('otros');
            $t->string('file_name', 255);         // nombre original (para mostrar/descargar)
            $t->string('file_path', 500);         // ej. 'uploadfiles/abc123.pdf'
            $t->string('mime', 128)->nullable();
            $t->unsignedBigInteger('size_bytes')->default(0);
            $t->text('descripcion')->nullable();  // nota libre del fisio
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->tinyInteger('status')->default(1); // 1=activo, 0=eliminado lógico
            $t->timestamps();

            $t->index(['patient_id', 'status']);
            $t->index(['patient_id', 'ficha_id', 'status']);
            $t->index(['categoria', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fis_adjuntos');
    }
};
