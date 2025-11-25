<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisAntropoms extends Model
{
    use HasFactory;

    protected $table = 'fis_antropoms';

    protected $primaryKey = 'id';

    protected $fillable = [
        // 'id',
        'patient_id',
        'user_id',
        'fecha',
        'peso',
        'talla',

        // Perímetros
        'brazo_flex_der', 'brazo_flex_izq',
        'brazo_rela_der', 'brazo_rela_izq',
        'anteb_der', 'anteb_izq',
        'mu_der', 'mu_izq',
        'mus_der', 'mus_izq',
        'pant_der', 'pant_izq',
        'tob_der', 'tob_izq',
        'cabeza_der', 'cabeza_izq',
        'cue_der', 'cue_izq',
        'tor_der', 'tor_izq',
        'cint_der', 'cint_izq',
        'cade_der', 'cade_izq',
        
        // Edema / inflamación
        'lug',
        'diam',

        // Tono muscular (checkboxs)
        'tono_muscular',
        'hipo',
        'hipe',
        'fluc',
        'tm_n',

        'observaciones','observaciones2','observaciones_res',
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
        return $this->belongsTo(\App\Models\Customer\CmnCustomer::class, 'customer_id');
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