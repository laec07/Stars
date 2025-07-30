<?php

namespace App\Models\FormFisios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FisCheqmus extends Model
{
    use HasFactory;

    protected $table = 'fis_cheqmus';

    protected $fillable = [
        'csm_id',
        'user_id',
        'Fecha',
        'cuello_iz', 'tronco_iz', 'cadera_iz', 'rodilla_iz', 'tobillo_iz',
        'escapula_iz', 'hombro_iz', 'codo_iz', 'antebrazo_iz', 'muneca_iz',
        'cuello_de', 'tronco_de', 'cadera_de', 'rodilla_de', 'tobillo_de',
        'escapula_de', 'hombro_de', 'codo_de', 'antebrazo_de', 'muneca_de',
        'Observaciones', 'updated_by', 'status','diagnostico','personalizado1',
        'personalizado2','personalizado3',
    ];

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
