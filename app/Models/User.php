<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable, CanResetPassword;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'provider',
        'provider_id',
        'toda_id',
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

    public function toda()
    {
        return $this->belongsTo(Toda::class);
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

    public function isOperatorPresident()
    {
        return $this->role === 'operator_president';
    }

    /**
     * The TODA this president governs. Uses the dedicated users.toda_id when
     * set, otherwise falls back to the president's own operator record's TODA
     * (a president is also an operator and may carry a personal rating).
     */
    public function presidentToda()
    {
        if ($this->toda_id) {
            return Toda::find($this->toda_id);
        }
        return $this->operator ? $this->operator->toda : null;
    }

    public function isTfrbOfficerOrSuperadmin()
    {
        return in_array($this->role, ['superadmin', 'tfrb_officer']);
    }

    public function isGoogleLinked()
    {
        return $this->provider === 'google' && $this->provider_id !== null;
    }
}
