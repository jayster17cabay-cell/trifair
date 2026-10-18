<?php

namespace App\Support;

use App\Models\OperatorToda;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves the operator's "current TODA" — the single organisation their
 * dashboard is scoped to right now. It lives in the session so the choice
 * survives navigation and lets one account move between the TODAs it is
 * connected to (as president or as a plain member).
 *
 * Defaulting follows the product rule: a president lands on a TODA they
 * preside over, everyone else on their home TODA.
 */
class TodaContext
{
    public const SESSION_KEY = 'current_toda_id';

    /**
     * Every TODA the user is connected to: their home TODA plus every TODA they
     * hold an ACTIVE membership in. Ordered by name for a stable switcher.
     *
     * @return Collection<int, \App\Models\Toda>
     */
    public static function availableTodas(User $user): Collection
    {
        $ids = collect();

        if ($user->toda_id) {
            $ids->push((int) $user->toda_id);
        }

        $operator = $user->operator;
        if ($operator) {
            $ids = $ids->merge(
                $operator->todaMemberships()
                    ->where('status', OperatorToda::STATUS_ACTIVE)
                    ->whereNotNull('toda_id')
                    ->pluck('toda_id')
            );
        }

        $ids = $ids->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Toda::whereIn('id', $ids)->orderBy('name')->get();
    }

    /**
     * The TODA the account should land on when nothing is selected yet: a TODA
     * they preside over, otherwise their home TODA, otherwise their first
     * membership.
     */
    public static function defaultId(User $user): ?int
    {
        $presidencies = $user->presidentTodas();
        if ($presidencies->isNotEmpty()) {
            return (int) $presidencies->first()->id;
        }

        if ($user->toda_id) {
            return (int) $user->toda_id;
        }

        $first = self::availableTodas($user)->first();

        return $first ? (int) $first->id : null;
    }

    /**
     * The current TODA id: the session value when it is still one the user is
     * connected to, otherwise the default. Null when the user is connected to
     * no TODA at all.
     */
    public static function currentId(User $user): ?int
    {
        $available = self::availableTodas($user)->pluck('id')->map(fn ($id) => (int) $id);

        $sessionId = (int) session(self::SESSION_KEY);
        if ($sessionId && $available->contains($sessionId)) {
            return $sessionId;
        }

        return self::defaultId($user);
    }

    /**
     * True when the account is connected to more than one TODA and therefore
     * has a real choice to make. Single-TODA accounts are never asked.
     */
    public static function hasChoice(User $user): bool
    {
        return self::availableTodas($user)->count() > 1;
    }

    /**
     * True when a TODA was explicitly picked this session (not merely defaulted
     * into). Used to decide whether the selection screen still has to be shown.
     */
    public static function isChosen(User $user): bool
    {
        $sessionId = (int) session(self::SESSION_KEY);

        if (!$sessionId) {
            return false;
        }

        return self::availableTodas($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->contains($sessionId);
    }

    public static function current(User $user): ?Toda
    {
        $id = self::currentId($user);

        return $id ? Toda::find($id) : null;
    }

    public static function isConnected(User $user, int $todaId): bool
    {
        return self::availableTodas($user)->contains('id', $todaId);
    }

    public static function set(int $todaId): void
    {
        session()->put(self::SESSION_KEY, $todaId);
    }
}
