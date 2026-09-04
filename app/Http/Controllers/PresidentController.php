<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Toda;
use App\Services\PresidentQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresidentController extends Controller
{
    protected $query;

    public function __construct(PresidentQueryService $query)
    {
        $this->query = $query;
    }

    protected function toda(): Toda
    {
        $toda = Auth::user()->presidentToda();

        if (!$toda) {
            abort(403, 'Your account is not assigned to a TODA.');
        }

        return $toda;
    }

    public function dashboard()
    {
        $toda = $this->toda();
        $user = Auth::user();

        $ownOperator = $this->query->presidentOperator($user);
        $summary = $this->query->summary($toda, $ownOperator);

        $summary['todaName'] = $toda->name;
        $summary['todaArea'] = $toda->area;

        $members = $this->query->members($toda, request()->query('search'), request()->query('status'));
        $ownRecentRatings = $this->query->ownRecentRatings($ownOperator);
        $breakdown = $this->query->memberBreakdown($toda);

        if (request()->wantsJson()) {
            $html = view('partials.president.members-table', ['members' => $members, 'memberDetailUrl' => route('president.members')])->render();
            return response()->json([
                'html' => $html,
                'pagination' => $members->links('pagination::tailwind')->render(),
            ]);
        }

        return view('president.dashboard', compact(
            'toda',
            'summary',
            'members',
            'ownOperator',
            'ownRecentRatings',
            'breakdown'
        ));
    }

    public function members(Request $request)
    {
        $toda = $this->toda();
        $user = Auth::user();

        $ownOperator = $this->query->presidentOperator($user);
        $summary = $this->query->summary($toda, $ownOperator);
        $summary['todaName'] = $toda->name;
        $summary['todaArea'] = $toda->area;

        $members = $this->query->members(
            $toda,
            $request->query('search'),
            $request->query('status')
        );
        $ownRecentRatings = $this->query->ownRecentRatings($ownOperator);
        $breakdown = $this->query->memberBreakdown($toda);

        // AJAX (members search inside the dashboard) returns a JSON table fragment.
        if ($request->wantsJson()) {
            $html = view('partials.president.members-table', ['members' => $members, 'memberDetailUrl' => route('president.members')])->render();
            return response()->json([
                'html' => $html,
                'pagination' => $members->links('pagination::tailwind')->render(),
                'count' => $members->total(),
            ]);
        }

        // Direct navigation (e.g. clicking "Members" in the sidebar) renders the
        // full dashboard page with the members section open.
        return view('president.dashboard', compact(
            'toda',
            'summary',
            'members',
            'ownOperator',
            'ownRecentRatings',
            'breakdown'
        ))->with('membersActive', true);
    }

    public function memberDetail(Operator $member)
    {
        $user = Auth::user();
        $toda = $user->presidentToda();

        // Enforce the org boundary: only allow if this member belongs to the
        // president's TODA, AND the president is assigned to a TODA.
        if (!$user->isOperatorPresident() || !$toda || (int) $member->toda_id !== (int) $toda->id) {
            abort(403);
        }

        $ratings = $this->query->memberRatings($toda, $member);

        $html = view('partials.president.member-detail', [
            'member' => $member,
            'ratings' => $ratings,
        ])->render();

        return response()->json(['html' => $html]);
    }
}
