<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisEvPiels extends Model
{
    use HasFactory;

    protected $table = 'fis_evpiels';

    protected $primaryKey = 'id';

    protected $fillable = [
        'patient_id',
        'user_id',
        'fecha', "zonas",
        'estado_piel_izquierdo_anterior',
        'estado_piel_izquierdo_posterior',
        'estado_piel_derecho_anterior',
        'estado_piel_derecho_posterior',
        'diagnostico','observaciones', 
        'Campopersonalizado1','Campopersonalizado2', 
        'Campopersonalizado3', 'Campopersonalizado4',
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
