<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Rating;
use App\Models\User;
use App\Models\Toda;
use App\Helpers\ActivityLogger;
use App\Services\AdminDashboardService;
use App\Services\AdminQueryService;
use App\Services\OperatorAdminService;
use App\Services\RatingAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        $data = app(AdminQueryService::class)->complaintsData($request);

        return view('superadmin.complaints', $data);
    }

    public function ratings()
    {
        $ratings = app(AdminQueryService::class)->ratingsData();

        return view('superadmin.ratings', compact('ratings'));
    }

    public function reports()
    {
        $operators = app(AdminQueryService::class)->reportsData();

        return view('superadmin.reports', compact('operators'));
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
            'html' => view('partials.report-trips-superadmin', $data)->render(),
        ]);
    }

    public function markReviewed(Rating $rating)
    {
        return app(RatingAdminService::class)->markReviewed($rating);
    }

    public function destroyComplaint(Rating $rating)
    {
        return app(RatingAdminService::class)->destroyComplaint($rating, 'complaint/rating');
    }

    public function operators(Request $request)
    {
        $data = app(AdminQueryService::class)->operatorsData($request);
        $operators = $data['operators'];
        if ($request->ajax()) {
            $html = view('superadmin.operators._table', compact('operators'))->render();
            $pagination = $operators->links()->render();
            return response()->json(compact('html', 'pagination'));
        }
        return view('superadmin.operators.index', $data);
    }

    public function createOperator()
    {
        $todas = Toda::orderBy('name')->get();
        return view('superadmin.operators.create', compact('todas'));
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

    public function todas()
    {
        $todas = app(AdminQueryService::class)->todasData();

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
        $members = app(AdminQueryService::class)->todaMembersData($toda);

        return response()->json(['members' => $members]);
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
