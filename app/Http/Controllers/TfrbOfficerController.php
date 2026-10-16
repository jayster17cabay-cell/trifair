<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SuspendsOperator;
use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\Services\AdminQueryService;
use App\Services\ExportService;
use App\Services\OperatorAdminService;
use App\Services\RatingAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Helpers\ActivityLogger;

class TfrbOfficerController extends Controller
{
    use SuspendsOperator;

    public function dashboard()
    {
        $stats = app(AdminDashboardService::class)->stats([
            'recentLimit' => 5,
        ]);

        if (request()->wantsJson()) {
            return response()->json(app(AdminDashboardService::class)->liveJson($stats));
        }

        return view('tfrb-officer.dashboard', $stats);
    }

    public function operators(Request $request)
    {
        $data = app(AdminQueryService::class)->operatorsData($request);
        $data['todas'] = \App\Models\Toda::orderBy('name')->get();
        $data['activeOperators'] = app(AdminQueryService::class)->activeOperators();
        $operators = $data['operators'];
        if ($request->ajax()) {
            $html = view('partials.admin.operators-table', ['operators' => $operators, 'routePrefix' => 'tfrb-officer'])->render();
            $pagination = $operators->links('pagination::tailwind')->render();
            return response()->json(compact('html', 'pagination'));
        }
        return view('tfrb-officer.operators.index', $data);
    }

    public function createOperator(Request $request)
    {
        $todas = Toda::orderBy('name')->get();
        $selectedToda = $request->query('toda_id');

        return view('tfrb-officer.operators.create', compact('todas', 'selectedToda'));
    }

    public function storeOperator(Request $request)
    {
        return app(OperatorAdminService::class)->store($request, 'tfrb-officer.operators');
    }

    public function editOperator(Operator $operator)
    {
        $operator->load('user', 'toda');
        $todas = Toda::orderBy('name')->get();
        return view('tfrb-officer.operators.edit', compact('operator', 'todas'));
    }

    public function updateOperator(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->update($request, $operator, 'tfrb-officer.operators');
    }

    public function destroyOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->destroy($operator, 'tfrb-officer.operators');
    }

    public function approveOperator(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->approve($request, $operator, 'tfrb-officer.operators');
    }

    public function issueFranchise(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->issueFranchise($request, $operator, 'tfrb-officer.operators');
    }

    public function renewFranchise(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->renewFranchise($request, $operator, 'tfrb-officer.operators');
    }

    public function updateFranchise(Request $request, Operator $operator)
    {
        return app(OperatorAdminService::class)->updateFranchise($request, $operator, 'tfrb-officer.operators');
    }

    public function rejectOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->reject($operator, 'tfrb-officer.operators');
    }

    public function archiveOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->archive($operator, 'tfrb-officer.operators');
    }

    public function assignPresident(Operator $operator)
    {
        return app(OperatorAdminService::class)->assignPresident($operator, 'tfrb-officer.operators');
    }

    public function resetPassword(Operator $operator)
    {
        return app(OperatorAdminService::class)->resetPassword($operator, 'tfrb-officer.operators');
    }

    public function restoreOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->restore($operator, 'tfrb-officer.operators');
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
        if ($request->query('date_from') || $request->query('date_to')) {
            $context['period'] = trim(($request->query('date_from') ?? 'start') . ' — ' . ($request->query('date_to') ?? 'today'));
        }
        if ($label = app(AdminQueryService::class)->periodLabel($request->query('period', 'all'))) {
            $context['period'] = $label;
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
        return view('tfrb-officer.operators.qrcode', compact('operator', 'url'));
    }

    public function showSettings()
    {
        return view('tfrb-officer.settings');
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
            'tfrb_officer'
        );

        return back()->with('success', 'Password updated successfully.');
    }

    public function ratings(Request $request)
    {
        extract(app(AdminQueryService::class)->ratingsData($request));
        $activeOperators = app(AdminQueryService::class)->activeOperators();

        return view('tfrb-officer.ratings', compact('ratings', 'activeOperators', 'goodCount', 'reviewedCount', 'proofsCount', 'dateFrom', 'dateTo', 'operatorId', 'ratingFilter', 'starCounts', 'totalCount'));
    }

    public function reports(Request $request)
    {
        extract(app(AdminQueryService::class)->reportsData($request));
        $activeOperators = app(AdminQueryService::class)->activeOperators();

        return view('tfrb-officer.reports', compact('operators', 'activeOperators', 'todas', 'dateFrom', 'dateTo', 'todaId', 'minRating', 'period'));
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

    public function ratingsBulkReview(Request $request)
    {
        return app(RatingAdminService::class)->ratingsBulkReview($request);
    }

    public function complaints(Request $request)
    {
        $data = app(AdminQueryService::class)->complaintsData($request);
        $data['activeOperators'] = app(AdminQueryService::class)->activeOperators();

        return view('tfrb-officer.complaints', $data);
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

    public function complaintsBulkReview(Request $request)
    {
        return app(RatingAdminService::class)->complaintsBulkReview($request);
    }

    public function destroyComplaint(Rating $rating)
    {
        return app(RatingAdminService::class)->destroyComplaint($rating, 'complaint');
    }

    public function activityLogs(Request $request)
    {
        $data = app(AdminQueryService::class)->activityLogsData($request);

        return view('tfrb-officer.activity-logs', $data);
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
            $html = view('partials.admin.presidents-table', ['presidents' => $presidents, 'routePrefix' => 'tfrb-officer'])->render();
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

        return view('tfrb-officer.presidents', compact('presidents', 'search', 'totalPresidents', 'assignedPresidents'));
    }

    public function destroyPresident(User $user)
    {
        return app(OperatorAdminService::class)->removePresident($user, 'tfrb-officer.presidents');
    }

    public function todas(Request $request)
    {
        $data = app(AdminQueryService::class)->todasData($request->query('search'));
        $todas = $data['todas'];

        if ($request->ajax()) {
            $html = view('partials.admin.toda-members-table', [
                'todas' => $todas,
                'routePrefix' => 'tfrb-officer',
                'showManage' => false,
            ])->render();

            return response()->json(['html' => $html, 'pagination' => $todas->links('pagination::tailwind')->render()]);
        }

        return view('tfrb-officer.todas.index', $data);
    }

    public function todaMembers(Toda $toda)
    {
        $members = app(AdminQueryService::class)->todaMembersData($toda);
        $html = view('partials.toda-member-list', [
            'members' => $members,
            'routePrefix' => 'tfrb-officer',
        ])->render();

        return response()->json(['html' => $html, 'count' => $members->count()]);
    }
}
