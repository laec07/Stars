<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nivel 2.1 — Opt-in de WhatsApp por paciente.
     * NULL  = aún no preguntado (estado inicial).
     * 1     = paciente acepta recibir mensajes por WhatsApp.
     * 0     = paciente rechaza explícitamente.
     */
    public function up()
    {
        if (! Schema::hasTable('cmn_patients')) {
            return;
        }
        if (! Schema::hasColumn('cmn_patients', 'wa_optin')) {
            Schema::table('cmn_patients', function (Blueprint $table) {
                $table->tinyInteger('wa_optin')->nullable()->after('phone_no');
                $table->index('wa_optin', 'idx_cmn_patients_wa_optin');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('cmn_patients') && Schema::hasColumn('cmn_patients', 'wa_optin')) {
            Schema::table('cmn_patients', function (Blueprint $table) {
                $table->dropIndex('idx_cmn_patients_wa_optin');
                $table->dropColumn('wa_optin');
            });
        }
    }
};
