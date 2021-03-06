<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

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
        'created_at' => 'datetime:Y-m-d'
    ];

    public function setNameAttribute($name)
    {
        return $this->attributes['name'] = ucwords($name);
    }


    public function roleName()
    {
        $roles = ['User', 'Admin', 'Staff', 'Vendor'];
        return $roles[$this->role];
    }


    public function isAdmin()
    {
        return $this->role == 1;
    }

    public function isUser()
    {
        return $this->role == 0;
    }

    public function isStaff()
    {
        return $this->role == 2;
    }

    public function isVendor()
    {
        return $this->role == 3;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function coupon()
    {
        return $this->hasOne(UserCoupon::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class)->with('orders');
    }
}
