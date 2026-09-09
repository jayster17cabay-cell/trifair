<?php

namespace App\Helpers;

use App\Models\EmergencyAlert;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates the in-app notification rows for a new emergency alert.
 * Recipients: every superadmin + every TFRB officer, plus — when an operator
 * could be matched server-side — that operator's TODA president (one row).
 */
class EmergencyNotifier
{
    public static function dispatch(EmergencyAlert $alert): void
    {
        $operatorName = $alert->operator && $alert->operator->user
            ? $alert->operator->user->name
            : 'Unknown operator';

        $title = 'Emergency Alert — ' . $alert->category_label;
        $message = self::buildMessage($alert, $operatorName);

        $recipients = User::whereIn('role', ['superadmin', 'tfrb_officer'])->get();
        $president = $alert->toda_id ? User::where('role', 'operator_president')
            ->where('toda_id', $alert->toda_id)
            ->first() : null;

        foreach ($recipients as $officer) {
            self::notify($officer, $alert, $title, $message);
        }

        if ($president) {
            self::notify($president, $alert, $title, $message);
        }
    }

    private static function notify(User $user, EmergencyAlert $alert, string $title, string $message): void
    {
        Notification::create([
            'user_id' => $user->id,
            'emergency_alert_id' => $alert->id,
            'type' => 'emergency',
            'title' => $title,
            'message' => $message,
        ]);
    }

    private static function buildMessage(EmergencyAlert $alert, string $operatorName): string
    {
        $parts = [];

        if ($alert->passenger_name) {
            $parts[] = "Passenger: {$alert->passenger_name}";
        }
        if ($alert->location_lat !== null && $alert->location_lng !== null) {
            $parts[] = 'Location captured';
        } else {
            $parts[] = 'Location unavailable';
        }
        if ($alert->toda) {
            $parts[] = "TODA: {$alert->toda->name}";
        }
        $parts[] = "Operator: {$operatorName}";
        if ($alert->note) {
            $parts[] = "Note: " . Str::limit($alert->note, 100);
        }

        return implode(' · ', $parts);
    }
}