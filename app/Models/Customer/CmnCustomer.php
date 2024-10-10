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
        'country',
        'state',
        'postal_code',
        'city',
        'street_address',
        'street_number',
        'remarks'
    ];
}
