<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FisSeguimientos extends Model
{
    use HasFactory;

    protected $table = 'fis_seguimientos';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'patient_id',
        'user_id',
        'ficha_id',
        'fecha',
        'tratamiento_realizado',
        'observaciones',
        'evolucion',
        'status',
        'created_by',
        'updated_by'
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

    // Relación con la ficha clínica (padre)
    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'ficha_id');
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
