<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 3 — Asociar cada evaluación con una ficha clínica.
     * Sólo se agrega la columna a la bitácora (fis_historys) — no se tocan
     * las 11 tablas fis_* individuales. Las relaciones se mantienen 1:1
     * entre (tabla_form, id_formulario) ↔ ficha_id en esta misma fila.
     */
    public function up()
    {
        if (! Schema::hasTable('fis_historys')) {
            return;
        }
        if (! Schema::hasColumn('fis_historys', 'ficha_id')) {
            Schema::table('fis_historys', function (Blueprint $table) {
                $table->unsignedBigInteger('ficha_id')->nullable()->after('patient_id');
                $table->index('ficha_id', 'idx_fis_historys_ficha_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('fis_historys') && Schema::hasColumn('fis_historys', 'ficha_id')) {
            Schema::table('fis_historys', function (Blueprint $table) {
                $table->dropIndex('idx_fis_historys_ficha_id');
                $table->dropColumn('ficha_id');
            });
        }
    }
};
