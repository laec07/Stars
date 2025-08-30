<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisEvAlineps extends Model
{
    use HasFactory;

    protected $table = 'fis_evalineps';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'patient_id',
        'user_id',
        'fecha',
        // LATERAL DERECHO
        'ld_cabeza','ld_hombros','ld_codos','ld_torax','ld_omoplatos',
        'ld_columna','ld_abdomen','ld_pelvis','ld_muslos','ld_rodillas',
        'ld_piernas','ld_pies',

        // POSTERIOR
        'po_cabeza','po_hombros','po_codos','po_torax','po_omoplatos',
        'po_columna','po_abdomen','po_pelvis','po_muslos','po_rodillas',
        'po_piernas','po_pies',

        // ANTERIOR
        'an_cabeza','an_hombros','an_codos','an_torax','an_omoplatos',
        'an_columna','an_abdomen','an_pelvis','an_muslos','an_rodillas',
        'an_piernas','an_pies',

        // LADO IZQUIERDO
        'li_cabeza','li_hombros','li_codos','li_torax','li_omoplatos',
        'li_columna','li_abdomen','li_pelvis','li_muslos','li_rodillas',
        'li_piernas','li_pies',

        // FOTOS
        'foto1','foto2','foto3','foto4',
        
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
