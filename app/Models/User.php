<?php

namespace App\Models;

use App\Models\Customer\CmnUserBalance;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\UserManagement\SecUserRole;

class User extends Authenticatable implements MustVerifyEmail{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'photo',
        'status',
        'sec_role_id',
        'is_sys_adm',
        'user_type',//1 for system user,2 web site user
        'email_verified_at',
        'sch_employee_id'
    ];

    public function secUserRole(){
        return $this->hasMany(SecUserRole::class,'sec_user_id');
    }

    public function balances(){
        return $this->hasMany('App\Models\Customer\CmnUserBalance');
    }

    public function balance(){
        return $this->balances->where('status',1)->sum('amount');
    }

    public function userBalance()
    {
        return $this->morphMany(CmnUserBalance::class, "balanceable");
    }
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

}
