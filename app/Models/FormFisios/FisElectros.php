<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisElectros extends Model
{
    use HasFactory;

    protected $table = 'fis_electros';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'patient_id',
        'user_id',
        'fecha',
        'current_type',
        'waveform',
        'display',
        'cc_cv',
        'method',
        'carrier_frequency',
        'channel_mode',
        'frequency_mhz',
        'burst_frequency',
        'vector_scan',
        'duty_cycle',
        'treatment_time',
        'anti_fatigue',
        'cycle_time',
        'frequency_modulation',
        'polarity',
        'amplitude_modulation',
        'ramp',
        'phase_duration',
        'observaciones', 'diagnostico',
        'Campopersonalizado1', 'Campopersonalizado2', 'Campopersonalizado3',
        'status', 'created_by', 'updated_by'
    ];
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->user_id = Auth::id();
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
            $model->status = 1;
            $model->fecha = Carbon::now()->format('Y-m-d');
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer\CmnCustomer::class, 'csm_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
