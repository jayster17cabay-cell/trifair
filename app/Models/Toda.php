<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Toda extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'area',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function operators()
    {
        return $this->hasMany(Operator::class);
    }

    public function activeOperators()
    {
        return $this->hasMany(Operator::class)->where('status', 'active');
    }

    /**
     * Add operators_count / active_operators_count to every TODA in the
     * query, for the TODA Activity cards. A member sits on their home TODA or
     * on any TODA holding an active operator_toda row, and because withCount
     * resolves a relation on a keyless model that union cannot be expressed
     * as a relation — it is written as a correlated subquery instead,
     * mirroring Operator::scopeBelongsToToda.
     */
    public function scopeWithMemberCounts($query)
    {
        $memberCount = function (bool $activeOnly) {
            return Operator::query()
                ->whereHas('user', fn ($user) => $user->where('role', 'operator'))
                ->notArchived()
                ->when($activeOnly, fn ($query) => $query->where('operators.status', 'active'))
                ->where(function ($query) {
                    $query->whereColumn('operators.toda_id', 'todas.id')
                        ->orWhereHas('todaMemberships', function ($membership) {
                            $membership->whereColumn('operator_toda.toda_id', 'todas.id')
                                ->where('operator_toda.status', OperatorToda::STATUS_ACTIVE);
                        });
                })
                ->selectRaw('count(*)');
        };

        return $query
            ->select('todas.*')
            ->selectSub($memberCount(false), 'operators_count')
            ->selectSub($memberCount(true), 'active_operators_count');
    }

    /**
     * The TODA's presidency: the active operator_toda membership flagged as
     * president. Presidency lives on the membership (not the account) so that
     * one user can preside over several TODAs. The `presidentUser()` helper
     * resolves the underlying account.
     */
    public function president()
    {
        return $this->hasOne(OperatorToda::class, 'toda_id')
            ->where('is_president', true)
            ->where('status', OperatorToda::STATUS_ACTIVE);
    }

    /**
     * The User account of the TODA's president, if any. Kept for callers that
     * need the person (name, etc.) rather than the membership row.
     */
    public function presidentUser(): ?User
    {
        return optional(optional($this->president)->operator)->user;
    }

    /**
     * The Operator row of the TODA's president, if any.
     */
    public function presidentOperator(): ?Operator
    {
        return optional($this->president)->operator;
    }

    public function totalRatings()
    {
        return Rating::whereIn('operator_id', $this->operators()->pluck('id'));
    }
}
