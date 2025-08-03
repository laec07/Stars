<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisCheqmus extends Model
{
    use HasFactory;

    protected $table = 'fis_cheqmus';

    protected $primaryKey = 'Id';

    protected $fillable = [
        'Id',
        'patient_id',
        'user_id',
        'fecha',
        'fcm_cu_df', 'fcm_cu_if', 'fcm_cu_de', 'fcm_cu_ie',
        'fcm_tr_df', 'fcm_tr_if', 'fcm_tr_de', 'fcm_tr_ie', 'fcm_tr_dr', 'fcm_tr_ir',
        'fcm_ca_ef', 'fcm_ca_if', 'fcm_ca_de', 'fcm_ca_ie', 'fcm_ca_da', 'fcm_ca_ia',
        'fcm_ca_dn', 'fcm_ca_in', 'fcm_ca_dr', 'fcm_ca_ir', 'fcm_ca_dx', 'fcm_ca_ix',
        'fcm_ro_df', 'fcm_ro_if', 'fcm_ro_dx', 'fcm_ro_ix',
        'fcm_to_di', 'fcm_to_ii', 'fcm_to_de', 'fcm_to_ie', 'fcm_to_df', 'fcm_to_if',
        'fcm_to_dd', 'fcm_to_id',
        'fcm_es_de', 'fcm_es_ie', 'fcm_es_dd', 'fcm_es_id', 'fcm_es_da', 'fcm_es_ia',
        'fcm_es_dc', 'fcm_es_ic',
        'fcm_ho_df', 'fcm_ho_if', 'fcm_ho_de', 'fcm_ho_ie', 'fcm_ho_da', 'fcm_ho_ia',
        'fcm_ho_dc', 'fcm_ho_ic', 'fcm_ho_dr', 'fcm_ho_ir', 'fcm_ho_dx', 'fcm_ho_ix',
        'fcm_co_df', 'fcm_co_if', 'fcm_co_de', 'fcm_co_ie',
        'fcm_an_da', 'fcm_an_ia', 'fcm_an_ds', 'fcm_an_is',
        'fcm_mu_dm', 'fcm_mu_im', 'fcm_mu_de', 'fcm_mu_ie',
        'Observaciones', 'Diagnostico',
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
