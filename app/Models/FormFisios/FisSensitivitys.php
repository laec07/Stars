<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisSensitivitys extends Model
{
    use HasFactory;

    protected $table = 'fis_sensitivitys';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'patient_id',
        'user_id',
        'fecha',
        // Campos C
        'c2_zn', 'c2_zs', 'c2_za',
        'c1_zn', 'c1_zs', 'c1_za',
        'c3_zn', 'c3_zs', 'c3_za',
        'c4_zn', 'c4_zs', 'c4_za',
        'c5_zn', 'c5_zs', 'c5_za',
        'c6_zn', 'c6_zs', 'c6_za',
        'c7_zn', 'c7_zs', 'c7_za',
        'c8_zn', 'c8_zs', 'c8_za',
        // Campos T
        't1_zn', 't1_zs', 't1_za',
        't2_zn', 't2_zs', 't2_za',
        't3_zn', 't3_zs', 't3_za',
        't4_zn', 't4_zs', 't4_za',
        't5_zn', 't5_zs', 't5_za',
        't6_zn', 't6_zs', 't6_za',
        't7_zn', 't7_zs', 't7_za',
        't8_zn', 't8_zs', 't8_za',
        't9_zn', 't9_zs', 't9_za',
        't10_zn', 't10_zs', 't10_za',
        't11_zn', 't11_zs', 't11_za',
        't12_zn', 't12_zs', 't12_za',
        // Campos L
        'l1_zn', 'l1_zs', 'l1_za',
        'l2_zn', 'l2_zs', 'l2_za',
        'l3_zn', 'l3_zs', 'l3_za',
        'l4_zn', 'l4_zs', 'l4_za',
        // Campos S
        's1_zn', 's1_zs', 's1_za',
        's2_zn', 's2_zs', 's2_za',
        's3_zn', 's3_zs', 's3_za',
        's4_zn', 's4_zs', 's4_za',
        's5_zn', 's5_zs', 's5_za',
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
