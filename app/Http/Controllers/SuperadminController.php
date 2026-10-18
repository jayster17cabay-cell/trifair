<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SuspendsOperator;
use App\Models\Operator;
use App\Models\OperatorToda;
use App\Models\Rating;
use App\Models\SuspensionViolation;
use App\Models\User;
use App\Models\Toda;
use App\Helpers\ActivityLogger;
use App\Services\AdminDashboardService;
use App\Services\AdminQueryService;
use App\Services\ExportService;
use App\Services\OperatorAdminService;
use App\Services\RatingAdminService;
use App\Services\SuspensionViolationService;
use App\Services\TodaMembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperadminController extends Controller
{
    use SuspendsOperator;

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

        $tally = User::where('role', 'tfrb_officer')->selectRaw(
            'COUNT(*) as total_count'
            . ', SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active_count'
            . ', SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_count'
        )->first();

        $totalOfficers = (int) $tally->total_count;
        $activeOfficers = (int) $tally->active_count;
        $verifiedOfficers = (int) $tally->verified_count;

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

        app(AdminDashboardService::class)->flush();

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

        app(AdminDashboardService::class)->flush();

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
            $html = view('partials.admin.presidents-table', ['presidents' => $presidents, 'routePrefix' => 'superadmin'])->render();
            return response()->json([
                'html' => $html,
                'pagination' => $presidents->links('pagination::tailwind')->render(),
            ]);
        }

        $tally = User::where('role', 'operator_president')->selectRaw(
            'COUNT(*) as total_count'
            . ', SUM(CASE WHEN toda_id IS NOT NULL THEN 1 ELSE 0 END) as assigned_count'
        )->first();

        $totalPresidents = (int) $tally->total_count;
        $assignedPresidents = (int) $tally->assigned_count;

        return view('superadmin.presidents', compact('presidents', 'search', 'totalPresidents', 'assignedPresidents'));
    }

    public function destroyPresident(User $user)
    {
        return app(OperatorAdminService::class)->removePresident($user, 'superadmin.presidents');
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

    public function ratings(Request $request)
    {
        extract(app(AdminQueryService::class)->ratingsData($request));
        $activeOperators = app(AdminQueryService::class)->activeOperators();

        return view('superadmin.ratings', compact('ratings', 'activeOperators', 'goodCount', 'reviewedCount', 'proofsCount', 'dateFrom', 'dateTo', 'operatorId', 'ratingFilter', 'starCounts', 'totalCount'));
    }

    public function reports(Request $request)
    {
        extract(app(AdminQueryService::class)->reportsData($request));
        $activeOperators = app(AdminQueryService::class)->activeOperators();

        return view('superadmin.reports', compact('operators', 'activeOperators', 'todas', 'dateFrom', 'dateTo', 'todaId', 'minRating', 'period', 'todaBreakdown'));
    }

    /**
     * AJAX endpoint for the reports page: renders the trip history for a
     * single operator. Loaded lazily (capped at 200 trips) so the reports
     * page stays fast even with 10k operators.
     */
    public function reportTrips(Request $request, Operator $operator)
    {
        $data = app(AdminQueryService::class)->reportTripsData(
            $operator,
            $request->query('date_from'),
            $request->query('date_to')
        );

        return response()->json([
            'html' => view('partials.report-trips', $data)->render(),
        ]);
    }

    public function markReviewed(Rating $rating)
    {
        return app(RatingAdminService::class)->markReviewed($rating);
    }

    public function complaintsAccept(Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsAccept($rating);
    }

    public function complaintsReject(Request $request, Rating $rating)
    {
        $request->validate([
            'resolution_note' => 'nullable|string|max:2000',
        ]);

        return app(RatingAdminService::class)->complaintsReject($rating, $request->input('resolution_note'));
    }

    public function complaintsResetReview(Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsResetReview($rating);
    }

    public function complaintsMarkSolved(Request $request, Rating $rating)
    {
        $request->validate([
            'resolution_note' => 'nullable|string|max:2000',
            'resolution_proofs' => 'nullable|array|max:5',
            'resolution_proofs.*' => 'nullable|file|max:10240',
        ]);

        return app(RatingAdminService::class)->complaintsMarkSolved(
            $rating,
            $request->input('resolution_note'),
            $request->file('resolution_proofs') ?: []
        );
    }

    public function complaintsReopen(Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsReopen($rating);
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

    public function violations(Request $request)
    {
        $data = app(AdminQueryService::class)->violationsData($request);

        return view('superadmin.violations', $data);
    }

    public function violationsReview(Request $request, SuspensionViolation $violation)
    {
        $request->validate([
            'review_note' => 'nullable|string|max:2000',
        ]);

        app(SuspensionViolationService::class)->markReviewed(
            $violation,
            Auth::id(),
            $request->input('review_note')
        );

        return back()->with('success', 'Violation report marked as reviewed.');
    }

    public function violationsSolve(Request $request, SuspensionViolation $violation)
    {
        $request->validate([
            'resolution_note' => 'required|string|max:2000',
            'recommended_action' => 'nullable|string|max:255',
            'penalty_imposed' => 'nullable|string|max:2000',
            'resolution_proofs' => 'nullable|array|max:5',
            'resolution_proofs.*' => 'nullable|file|max:10240',
        ]);

        app(SuspensionViolationService::class)->markSolved(
            $violation,
            Auth::id(),
            [
                'resolution_note' => $request->input('resolution_note'),
                'recommended_action' => $request->input('recommended_action'),
                'penalty_imposed' => $request->input('penalty_imposed'),
            ],
            $request->file('resolution_proofs') ?: []
        );

        return back()->with('success', 'Violation report marked as solved.');
    }

    public function violationsReopen(SuspensionViolation $violation)
    {
        app(SuspensionViolationService::class)->reopen($violation);

        return back()->with('success', 'Violation report reopened.');
    }

    public function operators(Request $request)
    {
        $data = app(AdminQueryService::class)->operatorsData($request);
        $data['todas'] = \App\Models\Toda::orderBy('name')->get();
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
        $operator->load('user', 'toda', 'todaMemberships');
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

    public function approveOperator(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->approve($request, $operator, 'superadmin.operators');
    }

    public function issueFranchise(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->issueFranchise($request, $operator, 'superadmin.operators');
    }

    public function renewFranchise(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->renewFranchise($request, $operator, 'superadmin.operators');
    }

    public function updateFranchise(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->updateFranchise($request, $operator, 'superadmin.operators');
    }

    public function rejectOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->reject($operator, 'superadmin.operators');
    }

    /**
     * Final approval of a TODA membership (the president and TFRB officer
     * already approved). Activating makes the operator rateable there.
     */
    public function approveMembership(OperatorToda $membership)
    {
        if (!$membership->isPendingSuperadmin()) {
            return back()->with('error', 'This membership is not awaiting final approval.');
        }

        app(TodaMembershipService::class)->finalApprove($membership, Auth::user());

        return back()->with('success', "Membership approved for {$membership->operator->user->name}.");
    }

    public function rejectMembership(Request $request, OperatorToda $membership)
    {
        if (!$membership->isPendingSuperadmin()) {
            return back()->with('error', 'This membership is not awaiting final approval.');
        }

        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        app(TodaMembershipService::class)->reject($membership, Auth::user(), $request->input('note'));

        return back()->with('success', 'Membership request rejected.');
    }

    public function archiveOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->archive($operator, 'superadmin.operators');
    }

    public function assignPresident(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->assignPresident(
            $operator,
            'superadmin.operators',
            $request->filled('toda_id') ? (int) $request->input('toda_id') : null
        );
    }

    public function resetPassword(Operator $operator)
    {
        return app(OperatorAdminService::class)->resetPassword($operator, 'superadmin.operators');
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
        $ratings = app(AdminQueryService::class)->ratingsForExport($request);
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

        $context = [];
        if ($request->query('toda_id')) {
            $toda = Toda::find((int) $request->query('toda_id'));
            if ($toda) {
                $context['toda'] = $toda->name;
            }
        }
        if ($request->query('operator_id')) {
            $operator = Operator::with('user')->find((int) $request->query('operator_id'));
            if ($operator) {
                $context['operator'] = $operator->user->name ?? 'Unknown';
            }
        }
        $period = app(AdminQueryService::class)->resolvePeriod($request);
        if ($label = app(AdminQueryService::class)->periodLabel($period)) {
            $context['period'] = $periodLabel = $label;
        } elseif ($request->query('date_from') || $request->query('date_to')) {
            $context['period'] = trim(($request->query('date_from') ?? 'start') . ' — ' . ($request->query('date_to') ?? 'today'));
        }

        return app(ExportService::class)->reportsFormat($reports, $format, $context);
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

        app(AdminDashboardService::class)->flush();

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

        app(AdminDashboardService::class)->flush();

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

        app(AdminDashboardService::class)->flush();

        return redirect()->route('superadmin.todas')
            ->with('success', 'TODA deleted successfully.');
    }

    public function todaMembers(Toda $toda)
    {
        $members = app(AdminQueryService::class)->todaMembersData($toda);
        $html = view('partials.toda-member-list', [
            'members' => $members,
            'routePrefix' => 'superadmin',
            'toda' => $toda,
        ])->render();

        return response()->json(['html' => $html, 'count' => $members->count()]);
    }
}
