<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
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

    /**
     * Memoized collection of TODAs this user presides over (see
     * presidentTodas()); resolved once per request.
     */
    protected $presidentTodasCache = null;

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

    public function isPassenger()
    {
        return $this->role === 'passenger';
    }

    /**
     * Every TODA this user presides over. Presidency now lives on the
     * operator_toda membership (so one account can preside over several TODAs
     * while merely being a member of others). Legacy presidents that predate
     * the flag — role operator_president with only users.toda_id set — still
     * fall back to their home TODA so nothing regresses.
     *
     * @return Collection<int, \App\Models\Toda>
     */
    public function presidentTodas(): Collection
    {
        if ($this->presidentTodasCache !== null) {
            return $this->presidentTodasCache;
        }

        $todas = collect();

        if ($this->isOperatorPresident()) {
            $operator = $this->operator;
            if ($operator) {
                $todas = $operator->presidentTodas()->orderBy('todas.name')->get();
            }

            if ($todas->isEmpty() && $this->toda_id) {
                $toda = Toda::find($this->toda_id);
                if ($toda) {
                    $todas = collect([$toda]);
                }
            }
        }

        return $this->presidentTodasCache = $todas;
    }

    /**
     * The TODA this president is currently acting on: the session's current
     * TODA when it is one they preside over, otherwise their first presidency.
     * Returns null when the user presides over nothing.
     */
    public function presidentToda()
    {
        $todas = $this->presidentTodas();

        if ($todas->isEmpty()) {
            return null;
        }

        $currentId = (int) session('current_toda_id');

        return $todas->firstWhere('id', $currentId) ?? $todas->first();
    }

    /**
     * True when the user is the acting president of the given TODA right now:
     * they hold the presidency there and are not suspended for that TODA (a
     * TODA-less suspension holds every presidency at once).
     */
    public function isPresidentOf($todaId): bool
    {
        if (!$todaId || !$this->isOperatorPresident()) {
            return false;
        }

        if (!$this->presidentTodas()->contains('id', (int) $todaId)) {
            return false;
        }

        $operator = $this->operator;
        if (!$operator) {
            return true;
        }

        return !$operator->isSuspendedForToda(null)
            && !$operator->isSuspendedForToda((int) $todaId);
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
