<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisGoniometrias extends Model
{
    use HasFactory;

    protected $table = 'fis_goniometrias';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'patient_id',
        'user_id',
        'fecha',
        // HOMBRO
        'hombro_flex_izq',
        'hombro_ext_izq',
        'hombro_flex_der',
        'hombro_ext_der',
        'hombro_ad_izq',
        'hombro_abd_izq',
        'hombro_ad_der',
        'hombro_abd_der',
        'hombro_rot_int_izq',
        'hombro_rot_ext_izq',
        'hombro_rot_int_der',
        'hombro_rot_ext_der',

        // CODO
        'codo_flex_izq',
        'codo_ext_izq',
        'codo_flex_der',
        'codo_ext_der',
        'codo_pro_izq',
        'codo_sup_izq',
        'codo_pro_der',
        'codo_sup_der',

        // MUÑECA
        'muneca_flex_dorsal_izq',
        'muneca_flex_palmar_izq',
        'muneca_flex_dorsal_der',
        'muneca_flex_palmar_der',
        'muneca_desv_radial_izq',
        'muneca_desv_cubital_izq',
        'muneca_desv_radial_der',
        'muneca_desv_cubital_der',

        // CADERA
        'cadera_flex_recta_izq',
        'cadera_flex_recta_der',
        'cadera_ex_recta_izq',
        'cadera_ex_recta_der',
        'cadera_flex_flexionada_izq',
        'cadera_ext_flexionada_izq',
        'cadera_flex_flexionada_der',
        'cadera_ext_flexionada_der',
        'cadera_ext_izq',
        'cadera_ext_der',
        'cadera_ad_izq',
        'cadera_abd_izq',
        'cadera_ad_der',
        'cadera_abd_der',
        'cadera_rot_int_izq',
        'cadera_rot_ext_izq',
        'cadera_rot_int_der',
        'cadera_rot_ext_der',

        // RODILLA
        'rodilla_flex_izq',
        'rodilla_flex_der',
        'rodilla_ext_izq',
        'rodilla_ext_der',

        // TOBILLO
        'tobillo_flex_plantar_izq',
        'tobillo_flex_dorsal_izq',
        'tobillo_flex_plantar_der',
        'tobillo_flex_dorsal_der',
        'tobillo_inversion_izq',
        'tobillo_eversion_izq',
        'tobillo_inversion_der',
        'tobillo_eversion_der',
        
        //Extras
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
