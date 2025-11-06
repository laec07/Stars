<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisEvDolors extends Model
{
    use HasFactory;

    protected $table = 'fis_evdolors';

    protected $primaryKey = 'id';

    protected $fillable = [
        // 'id',
        'patient_id',
        'user_id',
        'fecha',
        //Campos Ev-Dolor
        'pain_location',
        'pain_start_when',
        'pain_start_time',
        'pain_end_time',
        'pain_severity',
        'pain_place',
        'pain_activity',
        'pain_usual_intensity',
        'pain_reduction_method',
        'pain_reduction_effectiveness',
        'observaciones', 'Campopersonalizado1',
        'Campopersonalizado2', 'Campopersonalizado3', 'Campopersonalizado4',
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
