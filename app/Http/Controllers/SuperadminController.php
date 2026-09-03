<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\User;
use App\Models\Toda;
use App\Helpers\ActivityLogger;
use App\Services\AdminDashboardService;
use App\Services\AdminQueryService;
use App\Services\ExportService;
use App\Services\OperatorAdminService;
use App\Services\RatingAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function officers(Request $request)
    {
        $search = $request->query('search');

        $officers = User::where('role', 'tfrb_officer')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            $html = view('partials.admin.officers-table', ['officers' => $officers])->render();
            return response()->json([
                'html' => $html,
                'pagination' => $officers->links('pagination::tailwind')->render(),
            ]);
        }

        $totalOfficers = User::where('role', 'tfrb_officer')->count();
        $activeOfficers = User::where('role', 'tfrb_officer')->where('is_active', true)->count();
        $verifiedOfficers = User::where('role', 'tfrb_officer')->whereNotNull('email_verified_at')->count();

        return view('superadmin.officers', compact('officers', 'search', 'totalOfficers', 'activeOfficers', 'verifiedOfficers'));
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
            'phone' => 'nullable|string|max:20',
        ]);

        $officer = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
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

        return redirect()->route('superadmin.officers')->with('success', 'TFRB Officer removed successfully.');
    }

    public function presidents(Request $request)
    {
        $search = $request->query('search');

        $presidents = User::where('role', 'operator_president')
            ->with('toda')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            $html = view('partials.admin.presidents-table', ['presidents' => $presidents])->render();
            return response()->json([
                'html' => $html,
                'pagination' => $presidents->links('pagination::tailwind')->render(),
            ]);
        }

        $totalPresidents = User::where('role', 'operator_president')->count();
        $assignedPresidents = User::where('role', 'operator_president')->whereNotNull('toda_id')->count();

        return view('superadmin.presidents', compact('presidents', 'search', 'totalPresidents', 'assignedPresidents'));
    }

    public function createPresident()
    {
        $todas = Toda::orderBy('name')->get();
        return view('superadmin.presidents-create', compact('todas'));
    }

    public function storePresident(Request $request)
    {
        $request->merge(['email' => strtolower(trim($request->input('email')))]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'toda_id' => 'required|exists:todas,id',
        ]);

        $president = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
        ]);

        // role/is_active/toda_id are intentionally NOT mass-assignable by form
        // (only role is guarded via forceFill, mirroring officer creation).
        $president->forceFill([
            'role' => 'operator_president',
            'is_active' => true,
            'toda_id' => (int) $data['toda_id'],
        ])->save();

        // A president is also an operator so they can carry their own rating.
        Operator::updateOrCreate(
            ['user_id' => $president->id],
            [
                'toda_id' => (int) $data['toda_id'],
                'contact_number' => $data['phone'] ?? null,
                'address' => null,
                'qr_code' => Str::random(32),
                'status' => 'active',
            ]
        );

        $president->markEmailAsVerified();

        ActivityLogger::log('create_toda_president', "Created TODA President {$data['name']} ({$data['email']}) for TODA #{$data['toda_id']}", null, 'tfrb_officer');

        return redirect()->route('superadmin.presidents')
            ->with('success', 'TODA President created successfully.');
    }

    public function destroyPresident(User $user)
    {
        if ($user->role !== 'operator_president') {
            return back()->withErrors(['error' => 'User is not a TODA President.']);
        }
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot remove your own account.']);
        }
        $presidentName = $user->name;
        $user->delete();

        ActivityLogger::log('delete_toda_president', "Deleted TODA President {$presidentName}", null, 'tfrb_officer');

        return redirect()->route('superadmin.presidents')->with('success', 'TODA President removed successfully.');
    }

    public function showSettings()
    {
        return view('superadmin.settings');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($data['new_password']);
        $user->save();

        ActivityLogger::log(
            'update_password',
            "Updated own password",
            $user,
            'auth'
        );

        return back()->with('success', 'Password updated successfully.');
    }

    public function complaints(Request $request)
    {
        $data = app(AdminQueryService::class)->complaintsData($request);
        $data['activeOperators'] = app(AdminQueryService::class)->activeOperators();

        return view('superadmin.complaints', $data);
    }

    public function ratings()
    {
        extract(app(AdminQueryService::class)->ratingsData());
        $activeOperators = app(AdminQueryService::class)->activeOperators();

        return view('superadmin.ratings', compact('ratings', 'activeOperators', 'goodCount', 'reviewedCount', 'proofsCount'));
    }

    public function reports()
    {
        $operators = app(AdminQueryService::class)->reportsData();
        $activeOperators = app(AdminQueryService::class)->activeOperators();

        return view('superadmin.reports', compact('operators', 'activeOperators'));
    }

    /**
     * AJAX endpoint for the reports page: renders the trip history for a
     * single operator. Loaded lazily (capped at 200 trips) so the reports
     * page stays fast even with 10k operators.
     */
    public function reportTrips(Operator $operator)
    {
        $data = app(AdminQueryService::class)->reportTripsData($operator);

        return response()->json([
            'html' => view('partials.report-trips', $data)->render(),
        ]);
    }

    public function markReviewed(Rating $rating)
    {
        return app(RatingAdminService::class)->markReviewed($rating);
    }

    public function ratingsBulkReview(Request $request)
    {
        return app(RatingAdminService::class)->ratingsBulkReview($request);
    }

    public function complaintsBulkReview(Request $request)
    {
        return app(RatingAdminService::class)->complaintsBulkReview($request);
    }

    public function destroyComplaint(Rating $rating)
    {
        return app(RatingAdminService::class)->destroyComplaint($rating, 'complaint/rating');
    }

    public function operators(Request $request)
    {
        $data = app(AdminQueryService::class)->operatorsData($request);
        $data['activeOperators'] = app(AdminQueryService::class)->activeOperators();
        $operators = $data['operators'];
        if ($request->ajax()) {
            $html = view('partials.admin.operators-table', ['operators' => $operators, 'routePrefix' => 'superadmin'])->render();
            $pagination = $operators->links('pagination::tailwind')->render();
            return response()->json(compact('html', 'pagination'));
        }
        return view('superadmin.operators.index', $data);
    }

    public function createOperator(Request $request)
    {
        $todas = Toda::orderBy('name')->get();
        $selectedToda = $request->query('toda_id');

        return view('superadmin.operators.create', compact('todas', 'selectedToda'));
    }

    public function storeOperator(Request $request)
    {
        return app(OperatorAdminService::class)->store($request, 'superadmin.operators');
    }

    public function editOperator(Operator $operator)
    {
        $operator->load('user', 'toda');
        $todas = Toda::orderBy('name')->get();
        return view('superadmin.operators.edit', compact('operator', 'todas'));
    }

    public function updateOperator(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->update($request, $operator, 'superadmin.operators');
    }

    public function destroyOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->destroy($operator, 'superadmin.operators');
    }

    public function approveOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->approve($operator, 'superadmin.operators');
    }

    public function rejectOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->reject($operator, 'superadmin.operators');
    }

    public function archiveOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->archive($operator, 'superadmin.operators');
    }

    public function restoreOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->restore($operator, 'superadmin.operators');
    }

    public function exportOperators(Request $request)
    {
        $operators = app(AdminQueryService::class)->operatorsForExport($request);
        $format = $request->query('format', 'csv');

        return app(ExportService::class)->operatorsFormat($operators, $format);
    }

    public function exportRatings(Request $request)
    {
        $operatorId = $request->query('operator_id') ? (int) $request->query('operator_id') : null;
        $ratings = app(AdminQueryService::class)->ratingsForExport($operatorId);
        $format = $request->query('format', 'csv');

        return app(ExportService::class)->ratingsFormat($ratings, $format);
    }

    public function exportComplaints(Request $request)
    {
        $complaints = app(AdminQueryService::class)->complaintsForExport($request);
        $format = $request->query('format', 'csv');

        return app(ExportService::class)->complaintsFormat($complaints, $format);
    }

    public function exportReports(Request $request)
    {
        $reports = app(AdminQueryService::class)->reportsForExport($request);
        $format = $request->query('format', 'csv');

        return app(ExportService::class)->reportsFormat($reports, $format);
    }

    public function exportActivityLogs(Request $request)
    {
        $logs = app(AdminQueryService::class)->activityLogsForExport($request);
        $format = $request->query('format', 'csv');

        return app(ExportService::class)->activityLogsFormat($logs, $format);
    }

    public function showQrCode(Operator $operator)
    {
        $url = route('rate.operator', $operator->qr_code);
        return view('superadmin.operators.qrcode', compact('operator', 'url'));
    }

    public function activityLogs(Request $request)
    {
        $data = app(AdminQueryService::class)->activityLogsData($request);

        return view('superadmin.activity-logs', $data);
    }

    public function todas(Request $request)
    {
        $data = app(AdminQueryService::class)->todasData($request->query('search'));
        $todas = $data['todas'];

        if ($request->ajax()) {
            $html = view('partials.admin.toda-members-table', [
                'todas' => $todas,
                'routePrefix' => 'superadmin',
                'showManage' => true,
            ])->render();

            return response()->json(['html' => $html, 'pagination' => $todas->links('pagination::tailwind')->render()]);
        }

        return view('superadmin.todas.index', $data);
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
        $members = app(AdminQueryService::class)->todaMembersData($toda);
        $html = view('partials.toda-member-list', [
            'members' => $members,
            'routePrefix' => 'superadmin',
        ])->render();

        return response()->json(['html' => $html, 'count' => $members->count()]);
    }

    public function invalidRatings()
    {
        $ratings = app(AdminQueryService::class)->invalidRatingsData();

        return view('superadmin.invalid-ratings', compact('ratings'));
    }

    public function restoreRating(Rating $rating)
    {
        return app(RatingAdminService::class)->restore($rating);
    }
}
