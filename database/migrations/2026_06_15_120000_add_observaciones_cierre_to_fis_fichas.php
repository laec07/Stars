<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cierre de caso clínico (alta del paciente).
 *
 * La fecha de alta ya existe (fis_fichas.fecha_alta). Aquí agregamos el campo
 * de observaciones de cierre/finalización que el fisio captura al dar de alta.
 *
 * Idempotente.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('fis_fichas')) return;
        if (! Schema::hasColumn('fis_fichas', 'observaciones_cierre')) {
            Schema::table('fis_fichas', function (Blueprint $t) {
                $t->text('observaciones_cierre')->nullable()->after('fecha_alta');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fis_fichas') && Schema::hasColumn('fis_fichas', 'observaciones_cierre')) {
            Schema::table('fis_fichas', function (Blueprint $t) {
                $t->dropColumn('observaciones_cierre');
            });
        }
    }
};
