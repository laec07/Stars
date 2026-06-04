<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Fase 15 — Adjunto de ficha clínica.
 *
 * Vincula archivos físicos (en public/uploadfiles/) a un paciente y, de forma
 * opcional, a una ficha clínica específica. Pensado para exámenes médicos,
 * fotos clínicas, documentos y recetas.
 */
class FisAdjunto extends Model
{
    use HasFactory;

    protected $table = 'fis_adjuntos';

    public const CATEGORIAS = [
        'examenes'       => 'Exámenes',
        'fotos_clinicas' => 'Fotos clínicas',
        'documentos'     => 'Documentos',
        'recetas'        => 'Recetas',
        'otros'          => 'Otros',
    ];

    protected $fillable = [
        'patient_id',
        'ficha_id',
        'categoria',
        'file_name',
        'file_path',
        'mime',
        'size_bytes',
        'descripcion',
        'uploaded_by',
        'status',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'status'     => 'integer',
        'patient_id' => 'integer',
        'ficha_id'   => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->uploaded_by) $model->uploaded_by = Auth::id();
            if (!isset($model->status)) $model->status = 1;
            if (!$model->categoria) $model->categoria = 'otros';
        });
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    /**
     * URL pública del archivo. El path en BD es 'uploadfiles/xxx.ext'
     * (servido directo desde public/), así que un slash inicial basta.
     */
    public function getPublicUrlAttribute(): string
    {
        return '/' . ltrim($this->file_path, '/');
    }

    /** ¿Es un archivo que se puede previsualizar como imagen? */
    public function isImage(): bool
    {
        return $this->mime && str_starts_with($this->mime, 'image/');
    }

    /** ¿PDF? */
    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf';
    }
}
