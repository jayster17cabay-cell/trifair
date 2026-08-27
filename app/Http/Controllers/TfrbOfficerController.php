<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Services\AdminDashboardService;
use App\Services\AdminQueryService;
use App\Services\ExportService;
use App\Services\OperatorAdminService;
use App\Services\RatingAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ActivityLogger;

class TfrbOfficerController extends Controller
{
    public function dashboard()
    {
        $stats = app(AdminDashboardService::class)->stats([
            'recentLimit' => 5,
            'includePendingReview' => true,
        ]);

        if (request()->wantsJson()) {
            return response()->json(app(AdminDashboardService::class)->liveJson($stats));
        }

        return view('tfrb-officer.dashboard', $stats);
    }

    public function operators(Request $request)
    {
        $data = app(AdminQueryService::class)->operatorsData($request);
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

    public function approveOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->approve($operator, 'tfrb-officer.operators');
    }

    public function rejectOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->reject($operator, 'tfrb-officer.operators');
    }

    public function archiveOperator(Operator $operator)
    {
        return app(OperatorAdminService::class)->archive($operator, 'tfrb-officer.operators');
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

    public function ratings()
    {
        extract(app(AdminQueryService::class)->ratingsData());
        $activeOperators = app(AdminQueryService::class)->activeOperators();

        return view('tfrb-officer.ratings', compact('ratings', 'activeOperators', 'goodCount', 'reviewedCount', 'proofsCount', 'pendingCount'));
    }

    public function reports()
    {
        $operators = app(AdminQueryService::class)->reportsData();
        $activeOperators = app(AdminQueryService::class)->activeOperators();

        return view('tfrb-officer.reports', compact('operators', 'activeOperators'));
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

    public function complaints(Request $request)
    {
        $data = app(AdminQueryService::class)->complaintsData($request);
        $data['activeOperators'] = app(AdminQueryService::class)->activeOperators();

        return view('tfrb-officer.complaints', $data);
    }

    public function complaintsMarkReviewed(Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsMarkReviewed($rating);
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

    public function invalidRatings()
    {
        $ratings = app(AdminQueryService::class)->invalidRatingsData();

        return view('tfrb-officer.invalid-ratings', compact('ratings'));
    }

    public function restoreRating(Rating $rating)
    {
        return app(RatingAdminService::class)->restore($rating);
    }
}
