<?php

namespace App\Models\Messaging;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Fase 9a — Registro de mensaje enviado.
 */
class MsgLog extends Model
{
    use HasFactory;

    protected $table = 'msg_logs';

    protected $fillable = [
        'patient_id',
        'to_phone',
        'to_name',
        'channel',
        'template_key',
        'body',
        'status',
        'provider',
        'provider_message_id',
        'provider_response',
        'error',
        'scheduled_for',
        'sent_at',
        'delivered_at',
        'created_by',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at'       => 'datetime',
        'delivered_at'  => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(\App\Models\Patient\CmnPatient::class, 'patient_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
