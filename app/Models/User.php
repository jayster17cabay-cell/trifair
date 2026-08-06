<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = $value === null ? null : strtolower(trim($value));
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value === null ? null : trim($value);
    }

    public function operator()
    {
        return $this->hasOne(Operator::class);
    }

    public function isSuperadmin()
    {
        return $this->role === 'superadmin';
    }

    public function isTfrbOfficer()
    {
        return $this->role === 'tfrb_officer';
    }

    public function isOperator()
    {
        return $this->role === 'operator';
    }

    public function isTfrbOfficerOrSuperadmin()
    {
        return in_array($this->role, ['superadmin', 'tfrb_officer']);
    }
}
