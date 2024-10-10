<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\Controller;

class CmnBranch extends Model
{
    protected $fillable = [
        'id',
        'name',
        'phone',
        'email',
        'address',
        'order',
        'status',
        'created_by',
        'updated_by'
    ];
    public function scopeUserBranches($query)
    {
        $br=new Controller();
        return $query->whereIn('cmn_branches.id',$br->getUserBranch()->pluck('cmn_branch_id'));
    }
}
