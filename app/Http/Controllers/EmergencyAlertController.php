<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\EmergencyAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmergencyAlertController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $status = in_array($request->query('status'), EmergencyAlert::STATUSES, true)
            ? $request->query('status') : null;
        $category = in_array($request->query('category'), EmergencyAlert::CATEGORIES, true)
            ? $request->query('category') : null;

        $base = EmergencyAlert::with(['operator.user', 'toda', 'resolver'])
            ->inScopeFor($user);

        $counts = [
            'active' => (clone $base)->where('status', 'active')->count(),
            'responding' => (clone $base)->where('status', 'responding')->count(),
            'resolved' => (clone $base)->where('status', 'resolved')->count(),
            'false_alarm' => (clone $base)->where('status', 'false_alarm')->count(),
            'total' => (clone $base)->count(),
        ];

        $query = clone $base;
        if ($status) {
            $query->where('status', $status);
        }
        if ($category) {
            $query->where('category', $category);
        }

        $alertIndex = $user->isSuperadmin()
            ? 'superadmin.alerts'
            : ($user->isOperatorPresident() ? 'president.alerts' : 'tfrb-officer.alerts');

        $alerts = $query->latest()->paginate(15)->withQueryString();

        $highlightId = $request->query('alert');
        $pollUrl = $request->url() . '?json=1'
            . ($status ? '&status=' . $status : '')
            . ($category ? '&category=' . $category : '');

        if ($request->has('json') || $request->wantsJson()) {
            $html = view('emergency-alerts.list', compact('alerts', 'highlightId'))->with('updateRoute', $alertIndex . '.update')->render();

            return response()->json([
                'html' => $html,
                'counts' => $counts,
                'signature' => md5(
                    $alerts->pluck('id')->implode(',') .
                    ($alerts->hasMorePages() ? 'M' : 'E') .
                    $counts['active'] .
                    $counts['responding']
                ),
                'hasItems' => $alerts->count() > 0,
            ]);
        }

        return view('emergency-alerts.index', compact(
            'alerts',
            'counts',
            'status',
            'category',
            'highlightId',
            'pollUrl'
        ))->with('updateRoute', $alertIndex . '.update');
    }

    public function update(Request $request, EmergencyAlert $alert)
    {
        $user = Auth::user();

        if (!EmergencyAlert::inScopeFor($user)->whereKey($alert->id)->exists()) {
            abort(404);
        }

        $to = $request->validate([
            'status' => ['required', 'in:responding,resolved,false_alarm'],
        ])['status'];

        if ((int) $alert->resolved_by) {
            abort(403, 'This alert has already been finalized.');
        }

        $terminal = in_array($to, ['resolved', 'false_alarm'], true);

        $alert->update([
            'status' => $to,
            'resolved_at' => $terminal ? now() : null,
            'resolved_by' => $terminal ? $user->id : null,
            'resolution_note' => $terminal ? ($request->input('resolution_note') ?: null) : null,
        ]);

        ActivityLogger::log('emergency_alert_' . $to, "Emergency alert #{$alert->id} marked as {$to}", $alert, 'emergency');

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $alert->status]);
        }

        return back()->with('success', 'Alert updated.');
    }
}