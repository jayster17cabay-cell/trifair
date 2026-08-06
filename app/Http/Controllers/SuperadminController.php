<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\User;
use App\Models\Toda;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Helpers\ActivityLogger;
use App\Services\AdminDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperadminController extends Controller
{
    public function dashboard()
    {
        $stats = app(AdminDashboardService::class)->stats([
            'recentLimit' => 10,
            'includeOfficers' => true,
        ]);

        if (request()->wantsJson()) {
            return response()->json(app(AdminDashboardService::class)->liveJson($stats));
        }

        return view('superadmin.dashboard', $stats);
    }

    public function officers()
    {
        $officers = User::where('role', 'tfrb_officer')->latest()->paginate(20);
        return view('superadmin.officers', compact('officers'));
    }

    public function createOfficer()
    {
        return view('superadmin.officers-create');
    }

    public function storeOfficer(Request $request)
    {
        $request->merge(['email' => strtolower(trim($request->input('email')))]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $officer = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // role/is_active are intentionally NOT mass-assignable (see User model)
        $officer->forceFill(['role' => 'tfrb_officer', 'is_active' => true])->save();

        // Admin-created accounts skip the self-service email verification step.
        $officer->markEmailAsVerified();

        ActivityLogger::log('create_tfrb_officer', "Created TFRB Officer {$data['name']} ({$data['email']})", null, 'tfrb_officer');

        return redirect()->route('superadmin.officers')
            ->with('success', 'TFRB Officer created successfully.');
    }

    public function destroyOfficer(User $user)
    {
        if ($user->role !== 'tfrb_officer') {
            return back()->withErrors(['error' => 'User is not a TFRB Officer.']);
        }
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot remove your own account.']);
        }
        $officerName = $user->name;
        $user->delete();

        ActivityLogger::log('delete_tfrb_officer', "Deleted TFRB Officer {$officerName}", null, 'tfrb_officer');

        return back()->with('success', 'TFRB Officer removed successfully.');
    }

    public function complaints(Request $request)
    {
        $filter = $request->query('filter', 'pending');

        $base = Rating::isValid()->where('rating', '<=', 2);

        $pendingCount = (clone $base)->where('is_reviewed', false)->count();
        $reviewedCount = (clone $base)->where('is_reviewed', true)->count();
        $totalCount = (clone $base)->count();

        if ($filter === 'pending') {
            $base->where('is_reviewed', false);
        } elseif ($filter === 'reviewed') {
            $base->where('is_reviewed', true);
        } elseif ($filter !== 'all') {
            $filter = 'pending';
            $base->where('is_reviewed', false);
        }

        $complaints = $base->with(['operator.user', 'proofs', 'response'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.complaints', compact('complaints', 'filter', 'pendingCount', 'reviewedCount', 'totalCount'));
    }

    public function ratings()
    {
        $ratings = Rating::isValid()->with(['operator.user', 'proofs', 'response'])
            ->latest()
            ->paginate(15);

        return view('superadmin.ratings', compact('ratings'));
    }

    public function reports()
    {
        $operators = Operator::with('user')
            ->leftJoin(
                DB::raw('(select operator_id, avg(rating) as valid_ratings_avg_rating, count(*) as valid_ratings_count from ratings where is_valid = true group by operator_id) as vr'),
                'vr.operator_id',
                '=',
                'operators.id'
            )
            ->whereNotIn('operators.status', ['pending', 'rejected'])
            ->select('operators.*', 'vr.valid_ratings_avg_rating', 'vr.valid_ratings_count')
            ->orderByDesc('valid_ratings_count')
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.reports', compact('operators'));
    }

    /**
     * AJAX endpoint for the reports page: renders the trip history for a
     * single operator. Loaded lazily (capped at 200 trips) so the reports
     * page stays fast even with 10k operators.
     */
    public function reportTrips(Operator $operator)
    {
        abort_unless(in_array($operator->status, ['active', 'inactive'], true), 404);

        $totalTrips = $operator->validRatings()->count();
        $operator->load(['validRatings' => function ($query) {
            $query->latest()->limit(200);
        }]);

        return response()->json([
            'html' => view('partials.report-trips-superadmin', compact('operator', 'totalTrips'))->render(),
        ]);
    }

    public function markReviewed(Rating $rating)
    {
        $rating->update(['is_reviewed' => true]);

        ActivityLogger::log('mark_reviewed', "Marked rating #{$rating->id} as reviewed (operator: {$rating->operator->user->name})", $rating, 'review');

        return back()->with('success', 'Rating marked as reviewed.');
    }

    public function destroyComplaint(Rating $rating)
    {
        $operatorName = $rating->operator->user->name ?? 'Unknown';

        foreach ($rating->proofs as $proof) {
            \App\Helpers\SupabaseStorage::delete($proof->file_path);
        }

        $rating->proofs()->delete();
        $rating->response()->delete();
        \App\Models\Notification::where('rating_id', $rating->id)->delete();
        $rating->delete();

        ActivityLogger::log('delete_complaint', "Deleted complaint/rating #{$rating->id} (operator: {$operatorName})", null, 'review');

        return back()->with('success', 'Complaint deleted successfully.');
    }

    public function operators(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $query = Operator::with('user', 'toda');
        if ($status && in_array($status, ['active', 'inactive', 'pending', 'rejected'])) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['active', 'inactive']);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                })->orWhere('body_number', 'like', "%{$search}%");
            });
        }
        $operators = $query->latest()->paginate(10);
        if ($request->ajax()) {
            $html = view('superadmin.operators._table', compact('operators'))->render();
            $pagination = $operators->links()->render();
            return response()->json(compact('html', 'pagination'));
        }
        return view('superadmin.operators.index', compact('operators', 'search', 'status'));
    }

    public function createOperator()
    {
        $todas = Toda::orderBy('name')->get();
        return view('superadmin.operators.create', compact('todas'));
    }

    public function storeOperator(Request $request)
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
            'tricycle_color' => 'nullable|string|max:50',
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
            'tricycle_color' => $data['tricycle_color'] ?? null,
            'qr_code' => $qrCode,
            'toda_id' => $data['toda_id'],
            'status' => 'active',
        ]);

        ActivityLogger::log('create_operator', "Created operator {$user->name} ({$user->email})", $operator, 'operator');

        return redirect()->route('superadmin.operators')
            ->with('success', 'Operator created successfully.')
            ->with('qr_code', $qrCode)
            ->with('operator_name', $user->name);
    }

    public function editOperator(Operator $operator)
    {
        $operator->load('user', 'toda');
        $todas = Toda::orderBy('name')->get();
        return view('superadmin.operators.edit', compact('operator', 'todas'));
    }

    public function updateOperator(Request $request, Operator $operator)
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
            'tricycle_color' => 'nullable|string|max:50',
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
            'tricycle_color' => $data['tricycle_color'] ?? null,
            'status' => $data['status'],
            'toda_id' => $data['toda_id'],
        ]);

        ActivityLogger::log('update_operator', "Updated operator {$operator->user->name} ({$operator->user->email})", $operator, 'operator');

        return redirect()->route('superadmin.operators')
            ->with('success', 'Operator updated successfully.');
    }

    public function destroyOperator(Operator $operator)
    {
        if ($operator->ratings()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete operator with existing rating history. Deactivate the account instead.');
        }

        $operatorName = $operator->user->name;
        $operator->delete();
        $operator->user->delete();

        ActivityLogger::log('delete_operator', "Deleted operator {$operatorName}", null, 'operator');

        return redirect()->route('superadmin.operators')
            ->with('success', 'Operator deleted successfully.');
    }

    public function approveOperator(Operator $operator)
    {
        $operator->load('user');
        $operator->update(['status' => 'active']);
        $operator->user->forceFill(['is_active' => true])->save();

        ActivityLogger::log('approve_operator', "Approved operator {$operator->user->name} ({$operator->user->email})", $operator, 'operator');

        return redirect()->route('superadmin.operators', ['status' => 'pending'])
            ->with('success', "Operator {$operator->user->name} approved successfully.");
    }

    public function rejectOperator(Operator $operator)
    {
        $operator->load('user');
        $operatorName = $operator->user->name;
        $operatorEmail = $operator->user->email;

        if ($operator->ratings()->exists()) {
            return redirect()->back()
                ->with('error', "Cannot reject operator {$operatorName}: rating history exists. Deactivate the account instead.");
        }

        $operator->ratings()->with('proofs')->get()->each(function ($rating) {
            foreach ($rating->proofs as $proof) {
                \App\Helpers\SupabaseStorage::delete($proof->file_path);
            }
        });

        $operator->delete();
        $operator->user->delete();

        ActivityLogger::log('reject_operator', "Rejected and deleted operator {$operatorName} ({$operatorEmail})", null, 'operator');

        return redirect()->route('superadmin.operators', ['status' => 'pending'])
            ->with('success', "Operator {$operatorName} rejected and removed.");
    }

    public function showQrCode(Operator $operator)
    {
        $url = route('rate.operator', $operator->qr_code);
        return view('superadmin.operators.qrcode', compact('operator', 'url'));
    }

    public function activityLogs(Request $request)
    {
        $category = $request->query('category');

        $query = ActivityLog::with('user');

        if ($category && in_array($category, ['auth', 'tfrb_officer', 'operator', 'review', 'system'])) {
            $query->where('category', $category);
        }

        $logs = $query->latestFirst()->paginate(20);

        return view('superadmin.activity-logs', compact('logs', 'category'));
    }

    public function todas()
    {
        $todas = Toda::withCount([
            'operators',
            'operators as active_operators_count' => function ($query) {
                $query->where('status', 'active');
            },
        ])->latest()->paginate(20);

        return view('superadmin.todas.index', compact('todas'));
    }

    public function createToda()
    {
        return view('superadmin.todas.create');
    }

    public function storeToda(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:todas,name',
            'area' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        Toda::create($data);

        ActivityLogger::log('create_toda', "Created TODA: {$data['name']}", null, 'operator');

        return redirect()->route('superadmin.todas')
            ->with('success', 'TODA created successfully.');
    }

    public function editToda(Toda $toda)
    {
        return view('superadmin.todas.edit', compact('toda'));
    }

    public function updateToda(Request $request, Toda $toda)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:todas,name,' . $toda->id,
            'area' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ]);

        $toda->update($data);

        ActivityLogger::log('update_toda', "Updated TODA: {$data['name']}", null, 'operator');

        return redirect()->route('superadmin.todas')
            ->with('success', 'TODA updated successfully.');
    }

    public function destroyToda(Toda $toda)
    {
        if ($toda->operators()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete TODA with existing operators. Reassign operators first.']);
        }

        $todaName = $toda->name;
        $toda->delete();

        ActivityLogger::log('delete_toda', "Deleted TODA: {$todaName}", null, 'operator');

        return redirect()->route('superadmin.todas')
            ->with('success', 'TODA deleted successfully.');
    }

    public function todaMembers(Toda $toda)
    {
        $operators = $toda->operators()->with('user')->get()->map(function ($operator) {
            return [
                'name' => $operator->user->name ?? 'Unknown',
                'body_number' => $operator->body_number,
                'plate_number' => $operator->plate_number,
                'status' => $operator->status,
            ];
        });

        return response()->json(['members' => $operators]);
    }

    public function invalidRatings()
    {
        $ratings = Rating::with(['operator.user', 'proofs'])
            ->where('is_valid', false)
            ->latest()
            ->paginate(15);

        return view('superadmin.invalid-ratings', compact('ratings'));
    }

    public function restoreRating(Rating $rating)
    {
        $rating->update(['is_valid' => $rating->evaluateValidity()]);

        ActivityLogger::log('restore_rating', "Restored rating #{$rating->id} as valid (operator: {$rating->operator->user->name})", $rating, 'review');

        $message = $rating->is_valid
            ? "Rating restored as valid. It will count towards the operator's average again."
            : 'Rating still missing required data (route location and/or proof for low ratings) and remains invalid.';

        return redirect()->back()->with('success', $message);
    }
}
