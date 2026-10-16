<?php

namespace App\Http\Controllers\Concerns;

use App\Helpers\ActivityLogger;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Shared operator suspension actions for the admin surfaces (superadmin and
 * TFRB officer). TODA presidents cannot suspend themselves, so an errant
 * president is handled from here instead.
 */
trait SuspendsOperator
{
    public function suspendOperator(Request $request, Operator $operator)
    {
        if ($operator->isSuspended()) {
            return redirect()->back()->with('error', 'This operator is already suspended.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'suspended_at' => ['required', 'date'],
            'suspended_until' => ['required', 'date', 'after_or_equal:suspended_at'],
        ]);

        $operator->suspend([
            'toda_id' => $operator->toda_id,
            'suspended_by' => Auth::id(),
            'reason' => $validated['reason'],
            'suspended_at' => $validated['suspended_at'],
            'suspended_until' => $validated['suspended_until'],
        ]);

        $name = $operator->user->name ?? 'Operator';

        ActivityLogger::log(
            'suspend_operator',
            "Suspended operator {$name} until {$validated['suspended_until']}",
            $operator,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return redirect()->back()->with('success', "{$name} has been suspended.");
    }

    public function endOperatorSuspension(Operator $operator)
    {
        $name = $operator->user->name ?? 'Operator';

        if (!$operator->endSuspension(Auth::user())) {
            return redirect()->back()->with('error', 'This operator has no active suspension.');
        }

        ActivityLogger::log(
            'end_operator_suspension',
            "Ended the suspension of operator {$name}",
            $operator,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return redirect()->back()->with('success', "{$name}'s suspension has been lifted.");
    }
}
