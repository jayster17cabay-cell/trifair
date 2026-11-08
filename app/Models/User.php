<?php

namespace App\Models;

use App\Mail\VerifyEmail;
use App\Support\TransactionalMail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable, CanResetPassword;

    /**
     * Password given to operator accounts created by a TODA president, TFRB
     * officer or superadmin. The account is forced to choose a new password
     * right after its first sign-in.
     */
    public const DEFAULT_OPERATOR_PASSWORD = 'operator123';

    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
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
        'must_change_password' => 'boolean',
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

    /**
     * Send the branded verification email through the application's
     * transactional mail pipeline.
     *
     * Overriding the framework notification keeps the verification message on
     * the same Brevo transport, From/Reply-To identity and HTML + plain-text
     * multipart format as every other TriFair email. That consistency is what
     * keeps the verification link out of spam folders.
     */
    public function sendEmailVerificationNotification()
    {
        if ($this->hasVerifiedEmail()) {
            return;
        }

        try {
            TransactionalMail::send(
                $this->getEmailForVerification(),
                new VerifyEmail($this)
            );
        } catch (\Throwable $e) {
            Log::error('Email verification send failed: ' . get_class($e) . ': ' . $e->getMessage(), [
                'user_id' => $this->getKey(),
                'email' => $this->getEmailForVerification(),
            ]);

            throw $e;
        }
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value === null ? null : trim($value);
    }

    /**
     * Compose the stored display name. Operator names are shown last-name
     * first, e.g. "Dela Cruz Juan Santos".
     */
    public static function composeName(?string $first, ?string $middle, ?string $last): string
    {
        $parts = array_filter([
            trim((string) $last),
            trim((string) $first),
            trim((string) $middle),
        ], fn ($part) => $part !== '');

        return implode(' ', $parts);
    }

    /**
     * Split a legacy combined name that follows the old First Middle Last
     * convention. Accounts created after the split columns were added use
     * nameParts() directly instead.
     *
     * @return array{first: string, middle: string, last: string}
     */
    public static function splitLegacyName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) <= 1) {
            return ['first' => $parts[0] ?? '', 'middle' => '', 'last' => ''];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);

        return ['first' => $first, 'middle' => implode(' ', $parts), 'last' => $last];
    }

    /**
     * The three name parts used by the account forms. Older accounts only have
     * the combined `name` (First Middle Last), so fall back to splitting that
     * value when the dedicated columns are empty.
     *
     * @return array{first: string, middle: string, last: string}
     */
    public function nameParts(): array
    {
        $first = trim((string) $this->first_name);
        $middle = trim((string) $this->middle_name);
        $last = trim((string) $this->last_name);

        if ($first !== '' || $last !== '') {
            return ['first' => $first, 'middle' => $middle, 'last' => $last];
        }

        return self::splitLegacyName($this->attributes['name'] ?? '');
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

    /**
     * True while the account still has to replace the password it was given
     * when an administrator or TODA president created it.
     */
    public function requiresPasswordChange(): bool
    {
        return (bool) $this->must_change_password;
    }
}
