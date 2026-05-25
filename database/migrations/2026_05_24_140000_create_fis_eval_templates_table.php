<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 10 — Plantillas reutilizables de evaluación.
 *
 * Cada plantilla guarda los valores de un formulario inline para que
 * el fisio pueda aplicarlos a un paciente nuevo y acelerar el llenado.
 *
 *   tabla_form    → tipo (fis_goniometrias, fis_cheqmus, etc.)
 *   scope         → 'personal' (solo creador) o 'global' (todo el equipo)
 *   payload       → JSON con los valores del formulario
 *
 * Idempotente.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('fis_eval_templates')) return;

        Schema::create('fis_eval_templates', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('tabla_form', 64)->index();    // ej. 'fis_goniometrias'
            $t->string('name', 191);                  // "Hombro doloroso fase 1"
            $t->text('description')->nullable();
            $t->enum('scope', ['personal', 'global'])->default('personal');
            $t->longText('payload');                  // JSON
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->tinyInteger('status')->default(1);
            $t->timestamps();

            $t->index(['tabla_form', 'scope', 'status']);
            $t->index(['tabla_form', 'created_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fis_eval_templates');
    }
};
