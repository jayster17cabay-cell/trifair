<?php

namespace App\Http\Controllers;

use App\Support\TodaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The "which TODA are you?" gate shown once per login to any operator or
 * president connected to two or more TODAs. Picking one stamps the session and
 * sends the account to its dashboard; it is also the place a signed-in account
 * returns to when it wants to change TODA without logging out.
 */
class TodaSelectionController extends Controller
{
    /**
     * Render the picker. A single-TODA account has no choice to make, so it is
     * bounced back to the dashboard rather than shown an empty decision.
     */
    public function create()
    {
        $user = Auth::user();
        $todas = TodaContext::availableTodas($user);

        if ($todas->count() <= 1) {
            return redirect()->route('dashboard');
        }

        $operator = $user->operator;
        if ($operator) {
            $operator->syncSuspensionStatus();
        }

        $presidencyIds = $user->presidentTodas()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('toda.select', compact('user', 'todas', 'operator', 'presidencyIds'));
    }

    /**
     * Record the choice. Only a TODA this account is connected to (home TODA or
     * an active membership) can be selected.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'toda_id' => ['required', 'integer'],
        ]);

        $todaId = (int) $data['toda_id'];

        if (!TodaContext::isConnected($user, $todaId)) {
            return back()->withInput()->with('error', 'You are not a member of that TODA.');
        }

        TodaContext::set($todaId);

        return redirect()->route('dashboard');
    }
}
