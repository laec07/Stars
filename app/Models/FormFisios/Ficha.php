<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Ficha extends Model
{
    use HasFactory;

    protected $table = 'fis_fichas';

    protected $primaryKey = 'id';

    /**
     * ¿La ficha (caso clínico) está cerrada? Una ficha cerrada tiene fecha_alta.
     * Devuelve false si el id es vacío/null (vista global) o la ficha no existe.
     */
    public static function estaCerrada($fichaId): bool
    {
        if (empty($fichaId)) return false;
        return self::where('id', (int) $fichaId)
            ->where('status', 1)
            ->whereNotNull('fecha_alta')
            ->exists();
    }

    protected $fillable = [
        'id',
        'patient_id',
        'user_id',
        'fecha',
        'diagnostico',
        'motivo_consulta',
        'historial_medico',
        'enfermedades_cronicas',
        'cirugias_previas',
        'medicamentos_actuales',
        'alergias',
        'fecha_inicio',
        'mecanismo_lesion_origen',
        'evolucion_sintomas',
        'tratamientos_previos',
        'observacion_marcha',
        'observacion_otros',
        'diagnostico_fisioterapeutico',
        'corto_plazo',
        'mediano_plazo',
        'largo_plazo',
        'modalidades_ejercicio_terapeutico',
        'modalidades_electroterapia',
        'modalidades_masoterapia',
        'modalidades_estiramientos',
        'modalidades_tecaterapia',
        'modalidades_puncion_seca',
        'modalidades_electropuncion',
        'modalidades_otros',
        'frecuencia_semana',
        'duracion_semanas',
        'fecha_tratamiento',
        'tratamiento_realizado',
        'observaciones',
        'firma_profesional',
        'fecha_alta',
        'observaciones_cierre',
        'recomendaciones_finales',
        'firma',
        'nota_detallada',
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
            if (empty($model->fecha)) { $model->fecha = Carbon::now()->format('Y-m-d'); }
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });

        
    }
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer\CmnCustomer::class, 'csm_id');
    }

         // Relación con seguimientos
    public function seguimientos()
    {
        return $this->hasMany(FisSeguimientos::class, 'ficha_id');
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
