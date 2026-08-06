<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\Toda;
use App\Services\AdminDashboardService;
use App\Services\AdminQueryService;
use App\Services\OperatorAdminService;
use App\Services\RatingAdminService;
use Illuminate\Http\Request;

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
        $operators = $data['operators'];
        if ($request->ajax()) {
            $html = view('tfrb-officer.operators._table', compact('operators'))->render();
            $pagination = $operators->links()->render();
            return response()->json(compact('html', 'pagination'));
        }
        return view('tfrb-officer.operators.index', $data);
    }

    public function createOperator()
    {
        $todas = Toda::orderBy('name')->get();
        return view('tfrb-officer.operators.create', compact('todas'));
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

    public function showQrCode(Operator $operator)
    {
        $url = route('rate.operator', $operator->qr_code);
        return view('tfrb-officer.operators.qrcode', compact('operator', 'url'));
    }

    public function ratings()
    {
        $ratings = app(AdminQueryService::class)->ratingsData();

        return view('tfrb-officer.ratings', compact('ratings'));
    }

    public function reports()
    {
        $operators = app(AdminQueryService::class)->reportsData();

        return view('tfrb-officer.reports', compact('operators'));
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
            'html' => view('partials.report-trips-tfrb', $data)->render(),
        ]);
    }

    public function markReviewed(Rating $rating)
    {
        return app(RatingAdminService::class)->markReviewed($rating);
    }

    public function complaints(Request $request)
    {
        $data = app(AdminQueryService::class)->complaintsData($request);

        return view('tfrb-officer.complaints', $data);
    }

    public function complaintsMarkReviewed(Rating $rating)
    {
        return app(RatingAdminService::class)->complaintsMarkReviewed($rating);
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

    public function todas()
    {
        $todas = app(AdminQueryService::class)->todasData();

        return view('tfrb-officer.todas.index', compact('todas'));
    }

    public function todaMembers(Toda $toda)
    {
        $members = app(AdminQueryService::class)->todaMembersData($toda);

        return response()->json(['members' => $members]);
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
