<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Helpers\SupabaseStorage;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\User;
use App\Models\Toda;
use App\Services\AdminDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Write-side operator operations shared by the Superadmin and TFRB Officer
 * roles. Both roles hit the exact same routes against the same validation,
 * so the logic lives here once; each controller just passes its own
 * redirect route name.
 */
class OperatorAdminService
{
    public function store(Request $request, string $redirectRoute): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim($request->input('email')))]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'plate_number' => 'required|string|max:20|unique:operators,plate_number',
            'body_number' => 'required|string|max:20|unique:operators,body_number',
            'motorcycle_model' => 'nullable|string|max:50',
            'toda_id' => 'required|exists:todas,id',
        ], [
            'plate_number.unique' => 'This plate number is already registered in the system.',
            'body_number.unique' => 'This body number is already registered in the system.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
        ]);

        // role/is_active are intentionally NOT mass-assignable (see User model)
        $user->forceFill(['role' => 'operator', 'is_active' => true])->save();

        $qrCode = Str::random(32);

        $operator = Operator::create([
            'user_id' => $user->id,
            'license_number' => $data['license_number'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'plate_number' => $data['plate_number'],
            'body_number' => $data['body_number'],
            'motorcycle_model' => $data['motorcycle_model'] ?? null,
            'qr_code' => $qrCode,
            'toda_id' => $data['toda_id'],
            'status' => 'active',
        ]);

        ActivityLogger::log('create_operator', "Created operator {$user->name} ({$user->email})", $operator, 'operator');
        app(AdminDashboardService::class)->flush();

        return redirect()->route($redirectRoute)
            ->with('success', 'Operator created successfully.')
            ->with('qr_code', $qrCode)
            ->with('operator_name', $user->name);
    }

    public function update(Request $request, Operator $operator, string $redirectRoute): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim($request->input('email')))]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $operator->user_id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'plate_number' => 'required|string|max:20|unique:operators,plate_number,' . $operator->id,
            'body_number' => 'required|string|max:20|unique:operators,body_number,' . $operator->id,
            'motorcycle_model' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'toda_id' => 'required|exists:todas,id',
        ], [
            'plate_number.unique' => 'This plate number is already registered in the system.',
            'body_number.unique' => 'This body number is already registered in the system.',
        ]);

        $operator->user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (!empty($data['password'])) {
            $operator->user->update(['password' => Hash::make($data['password'])]);
        }

        $operator->update([
            'license_number' => $data['license_number'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'body_number' => $data['body_number'] ?? null,
            'motorcycle_model' => $data['motorcycle_model'] ?? null,
            'status' => $data['status'],
            'toda_id' => $data['toda_id'],
        ]);

        ActivityLogger::log('update_operator', "Updated operator {$operator->user->name} ({$operator->user->email})", $operator, 'operator');
        app(AdminDashboardService::class)->flush();

        return redirect()->route($redirectRoute)
            ->with('success', 'Operator updated successfully.');
    }

    public function destroy(Operator $operator, string $redirectRoute): RedirectResponse
    {
        if ($operator->ratings()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete operator with existing rating history. Deactivate the account instead.');
        }

        $operatorName = $operator->user->name;
        $operator->delete();
        $operator->user->delete();
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('delete_operator', "Deleted operator {$operatorName}", null, 'operator');

        return redirect()->route($redirectRoute)
            ->with('success', 'Operator deleted successfully.');
    }

    public function archive(Operator $operator, string $redirectRoute): RedirectResponse
    {
        if ($operator->isArchived()) {
            return redirect()->back()->with('error', 'Operator is already archived.');
        }

        $operator->update(['archived_at' => now()]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('archive_operator', "Archived operator {$operator->user->name} ({$operator->user->email})", $operator, 'operator');

        return redirect()->route($redirectRoute)
            ->with('success', "Operator {$operator->user->name} archived successfully.");
    }

    public function restore(Operator $operator, string $redirectRoute): RedirectResponse
    {
        if (!$operator->isArchived()) {
            return redirect()->back()->with('error', 'Operator is not archived.');
        }

        $operator->update(['archived_at' => null]);
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('restore_operator', "Restored operator {$operator->user->name} ({$operator->user->email})", $operator, 'operator');

        return redirect()->route($redirectRoute, ['status' => 'archived'])
            ->with('success', "Operator {$operator->user->name} restored successfully.");
    }

    public function approve(Operator $operator, string $redirectRoute): RedirectResponse
    {
        $operator->load('user');
        $operator->update(['status' => 'active']);
        $operator->user->forceFill(['is_active' => true])->save();
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('approve_operator', "Approved operator {$operator->user->name} ({$operator->user->email})", $operator, 'operator');

        return redirect()->route($redirectRoute, ['status' => 'pending'])
            ->with('success', "Operator {$operator->user->name} approved successfully.");
    }

    public function reject(Operator $operator, string $redirectRoute): RedirectResponse
    {
        $operator->load('user');
        $operatorName = $operator->user->name;
        $operatorEmail = $operator->user->email;

        if ($operator->ratings()->exists()) {
            return redirect()->back()
                ->with('error', "Cannot reject operator {$operatorName}: rating history exists. Deactivate the account instead.");
        }

        $operator->delete();
        $operator->user->delete();
        app(AdminDashboardService::class)->flush();

        ActivityLogger::log('reject_operator', "Rejected and deleted operator {$operatorName} ({$operatorEmail})", null, 'operator');

        return redirect()->route($redirectRoute, ['status' => 'pending'])
            ->with('success', "Operator {$operatorName} rejected and removed.");
    }
}
