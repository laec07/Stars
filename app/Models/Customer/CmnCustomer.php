<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmnCustomer extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'full_name',
        'phone_no',
        'email',
        'dob',
        'street_address',
        'Occupation',
        'exercie',
        'hobbies',
        'services',
        'ser',
        'ses',
        'medical',
        'traumatic',
        'ex',
        'mosly',
        'stre',
        'mos',
        'li'

    ];
}
