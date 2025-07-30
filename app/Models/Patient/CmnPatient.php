<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmnPatient extends Model
{
    use     HasFactory;

    protected $table = 'cmn_patients';

    protected $fillable = [
        'id',
        'created_by',
        'user_id',
        'full_name',
        'phone_no',
        'email',
        'dob',
        'treated',
        'has_study',
        'archivo',
        'state',
        
    ];
}
