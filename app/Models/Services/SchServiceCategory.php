<?php
namespace App\Models\Services;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class SchServiceCategory extends Model
{
 protected $fillable = [
        'id',
        'name',
        'created_by',
        'modified_by',
    ];
}
 