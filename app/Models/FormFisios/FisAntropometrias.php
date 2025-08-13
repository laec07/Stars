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
        'id',
        'patient_id',
        'user_id',
        'fecha',
        //Campos Antropometrias
        //EQUILIBRIO SENTADO
        'equi_s','equi_f',
        //LEVANTARSE
        'lev_i','lev_c','lev_ca',
        //INTENTO DE LEVANTARSE
        'int_i','int_c','int_ca', 
        //EQUILIBRIO INMEDIATO AL LEVANTARSE
        'equil_i','equil_e','equil_es',
        //EQUILIBRIO EN BIPEDESTACIÒN
        'equib_i','equib_e','equib_b',
        //EMPUJON
        'em_t','em_s','em_f',
        //OJOS CERRADOS
        'oj_i','oj_e',
        //GIRO DE 360°
        'gir_p','gir_pa',
        //SENTARSE
        'se_i','se_u','se_s',
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
