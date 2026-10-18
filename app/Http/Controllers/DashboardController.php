<?php

namespace App\Http\Controllers;

use App\Support\TodaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Entry point for the mobile operator/president experience. An operator has a
 * single surface (their own ratings); a president has two, depending on which
 * TODA they are currently on — a presidency opens the president console, while
 * a TODA where they are only a member falls back to the plain operator view.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isOperatorPresident()) {
            $currentId = TodaContext::currentId($user);

            // Acting on a TODA they preside over -> the president console.
            if ($currentId && $user->isPresidentOf($currentId)) {
                return redirect()->route('president.dashboard');
            }

            // Otherwise they are "just a member" of the current TODA.
            return redirect()->route('operator.dashboard', $currentId ? ['toda' => $currentId] : []);
        }

        return redirect()->route('operator.dashboard');
    }

    /**
     * Change the TODA the account is acting on. Only TODAs the user is
     * connected to (home TODA or an active membership) can be selected.
     */
    public function switchToda(Request $request)
    {
        $user = Auth::user();

        if (!$user->isOperator() && !$user->isOperatorPresident()) {
            abort(403);
        }

        $data = $request->validate([
            'toda_id' => ['required', 'integer'],
        ]);

        $todaId = (int) $data['toda_id'];

        if (!TodaContext::isConnected($user, $todaId)) {
            return redirect()->back()->with('error', 'You are not a member of that TODA.');
        }

        TodaContext::set($todaId);

        return redirect()->route('dashboard');
    }
}
