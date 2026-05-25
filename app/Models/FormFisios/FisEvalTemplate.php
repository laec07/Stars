<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Fase 10 — Plantilla reutilizable de evaluación.
 */
class FisEvalTemplate extends Model
{
    use HasFactory;

    protected $table = 'fis_eval_templates';

    protected $fillable = [
        'tabla_form',
        'name',
        'description',
        'scope',
        'payload',
        'created_by',
        'updated_by',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->created_by) $model->created_by = Auth::id();
            if (!$model->updated_by) $model->updated_by = Auth::id();
            if (!isset($model->status)) $model->status = 1;
        });
        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
