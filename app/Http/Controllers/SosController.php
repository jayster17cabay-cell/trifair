<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Helpers\EmergencyNotifier;
use App\Models\EmergencyAlert;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SosController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'in:' . implode(',', EmergencyAlert::CATEGORIES)],
            'note' => ['nullable', 'string', 'max:280'],
            'passenger_id' => ['nullable', 'string', 'max:60'],
            'passenger_name' => ['nullable', 'string', 'max:100'],
            'passenger_contact' => ['nullable', 'string', 'max:20'],
            'operator_id' => ['nullable', 'integer'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        // Prefer the server-signed cookie (set by the rating flow). Fall back to
        // the client-generated id or mint a fresh one — passengers are anonymous.
        $clientId = (string) $request->cookie('tf_pid');
        if ($clientId === '') {
            $clientId = (string) ($data['passenger_id'] ?? '');
        }
        if ($clientId === '') {
            $clientId = Str::random(40);
        }

        // Operator/TODA are resolved server-side ONLY. The president that gets
        // notified is derived from the matching operator's TODA, never from a
        // value forwarded by the (anonymous) client.
        $operator = null;
        if (!empty($data['operator_id'])) {
            $operator = Operator::where('id', $data['operator_id'])
                ->where('status', 'active')
                ->notArchived()
                ->first();
        }

        $alert = EmergencyAlert::create([
            'passenger_id' => $clientId,
            'passenger_name' => $data['passenger_name'] ?? null,
            'passenger_contact' => $data['passenger_contact'] ?? null,
            'operator_id' => $operator ? $operator->id : null,
            'toda_id' => $operator ? $operator->toda_id : null,
            'category' => $data['category'],
            'note' => $data['note'] ?? null,
            'location_lat' => $data['location_lat'] ?? null,
            'location_lng' => $data['location_lng'] ?? null,
            'status' => 'active',
        ]);

        EmergencyNotifier::dispatch($alert);

        ActivityLogger::log(
            'emergency_alert',
            "Emergency alert #{$alert->id} ({$alert->category_label}) received" . ($operator ? " for operator {$operator->user->name}" : ' (no operator context)'),
            $alert,
            'emergency'
        );

        app(\App\Services\AdminDashboardService::class)->flush();

        return response()->json([
            'success' => true,
            'id' => $alert->id,
            'status' => $alert->status,
        ], 201);
    }
}