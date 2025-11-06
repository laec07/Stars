<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisAntropometrias extends Model
{
    use HasFactory;

    protected $table = 'fis_antropometrias';

    protected $primaryKey = 'id';

    protected $fillable = [
     
        'patient_id',
        'user_id',
        'fecha',
        //Campos Antropometrias
        //EQUILIBRIO SENTADO
        'equi_s',
        //LEVANTARSE
        'lev_i',
        //INTENTO DE LEVANTARSE
        'int_i',
        //EQUILIBRIO INMEDIATO AL LEVANTARSE
        'equil_i',
        //EQUILIBRIO EN BIPEDESTACIÒN
        'equib_i',
        //EMPUJON
        'em_t',
        //OJOS CERRADOS
        'oj_i',
        //GIRO DE 360°
        'gir_p',
        //SENTARSE
        'se_i',
        "total_puntaje",
        'observaciones',
        'Campopersonalizado1', 'Campopersonalizado2',
        'Campopersonalizado3', 'Campopersonalizado4', 'Campopersonalizado5',
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
