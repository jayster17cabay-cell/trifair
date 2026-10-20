<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\OperatorToda;
use App\Models\SuspensionViolation;
use App\Models\Toda;
use App\Models\User;
use App\Services\PresidentQueryService;
use App\Services\SuspensionViolationService;
use App\Services\TodaMembershipService;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        // A suspended TODA is still openable — it just reports the suspension
        // and nothing else. The TODA chosen at sign-in is honoured as-is: there
        // is no silent jump to a different organisation.
        if ($ownOperator && $ownOperator->isSuspendedForToda((int) $toda->id)) {
            if (request()->wantsJson()) {
                return response()->json(['suspended' => true], 423);
            }

            return view('president.suspended', ['toda' => $toda, 'operator' => $ownOperator]);
        }

        $members = $this->query->members($toda, request()->query('search'), request()->query('status'));
        $ownRecentRatings = $this->query->ownRecentRatings($toda, $ownOperator);
        $joinRequests = app(TodaMembershipService::class)->presidentQueue($toda);

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
            'joinRequests'
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
        $ownRecentRatings = $this->query->ownRecentRatings($toda, $ownOperator);
        $joinRequests = app(TodaMembershipService::class)->presidentQueue($toda);

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
            'joinRequests'
        ))->with('membersActive', true);
    }

    public function memberDetail(Operator $member)
    {
        $toda = $this->authorizeMember($member);

        $member->load('user', 'toda');

        $ratings = $this->query->memberRatings($toda, $member);
        $stats = $this->query->memberStats($toda, $member);

        // A lapsed suspension must not linger, so reconcile before rendering.
        $member->syncSuspensionStatus();

        $currentSuspension = $member->currentSuspension;
        $suspensionHistory = $member->suspensions()
            ->with('suspender', 'ender')
            ->take(10)
            ->get();

        // Violation reports the president has filed against this member for
        // this TODA, so they can track each report's review status.
        $violations = SuspensionViolation::where('operator_id', $member->id)
            ->where('toda_id', $toda->id)
            ->with(['reviewer', 'proofs'])
            ->latest()
            ->take(10)
            ->get();

        $html = view('partials.president.member-detail', [
            'member' => $member,
            'ratings' => $ratings,
            'stats' => $stats,
            'currentSuspension' => $currentSuspension,
            'suspensionHistory' => $suspensionHistory,
            'violations' => $violations,
        ])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Suspend one of the president's own TODA members, recording the
     * resolution and blocking the member until the end date.
     */
    public function suspendMember(Request $request, Operator $member)
    {
        $toda = $this->authorizeMember($member);

        if ($member->isSuspended()) {
            return redirect()->back()->with('error', 'This member is already suspended.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'suspended_at' => ['required', 'date'],
            'suspended_until' => ['required', 'date', 'after_or_equal:suspended_at'],
        ]);

        $member->suspend([
            'toda_id' => $toda->id,
            'suspended_by' => Auth::id(),
            'reason' => $validated['reason'],
            'suspended_at' => $validated['suspended_at'],
            'suspended_until' => $validated['suspended_until'],
        ]);

        ActivityLogger::log(
            'suspend_member',
            "Suspended TODA member {$member->user->name} ({$member->user->email}) until {$validated['suspended_until']}",
            $member,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return redirect()->back()->with('success', "{$member->user->name} has been suspended.");
    }

    /**
     * End a member's suspension early. The record is kept for history and the
     * member's account is reactivated (unless the franchise has lapsed).
     */
    public function endSuspension(Operator $member)
    {
        $this->authorizeMember($member);

        if (!$member->endSuspension(Auth::user())) {
            return redirect()->back()->with('error', 'This member has no active suspension.');
        }

        ActivityLogger::log(
            'end_member_suspension',
            "Ended the suspension of TODA member {$member->user->name} ({$member->user->email})",
            $member,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return redirect()->back()->with('success', "{$member->user->name}'s suspension has been lifted.");
    }

    /**
     * Report a member who was suspended but is still observed operating. The
     * report is queued for review by a TFRB officer or superadmin, who can
     * impose a heavier penalty. Proof attachments, incident date/time and the
     * location are captured so the finding is actionable.
     */
    public function reportViolation(Request $request, Operator $member)
    {
        $toda = $this->authorizeMember($member);

        // Only a member under an active suspension can be reported this way.
        $suspension = $member->currentSuspensionForToda($toda->id);
        if (!$suspension || $suspension->isExpired()) {
            return redirect()->back()->with('error', 'This member is not currently suspended, so a violation cannot be reported.');
        }

        $validated = $request->validate([
            'violation_type' => ['required', 'string', 'max:100'],
            'details' => ['required', 'string', 'max:2000'],
            'incident_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'proofs' => ['nullable', 'array', 'max:5'],
            'proofs.*' => ['file', 'mimes:jpg,jpeg,png,pdf,mp4', 'max:10240'],
        ]);

        app(SuspensionViolationService::class)->report(
            $member,
            $toda,
            Auth::id(),
            [
                'suspension_id' => $suspension->id,
                'violation_type' => $validated['violation_type'],
                'details' => $validated['details'],
                'incident_at' => $validated['incident_at'],
                'location' => $validated['location'] ?? null,
            ],
            $request->file('proofs', [])
        );

        return redirect()
            ->back()
            ->with('success', 'Violation report submitted. A TFRB officer or superadmin will review it.');
    }

    /**
     * Onboard a new TODA member on the president's behalf. The account is
     * created in the pending state, so it still has to be reviewed and approved
     * by a superadmin or TFRB officer before it can be used — the president
     * cannot self-approve.
     */
    public function storeMember(Request $request)
    {
        $toda = $this->toda();

        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'contact_number' => 'required|string|max:20',
            'plate_number' => 'required|string|max:20|unique:operators,plate_number',
            'body_number' => 'required|string|max:20|unique:operators,body_number',
            'motorcycle_model' => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'plate_number.unique' => 'This plate number is already registered in the system.',
            'body_number.unique' => 'This body number is already registered in the system.',
        ]);

        $user = DB::transaction(function () use ($data, $toda) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['contact_number'],
            ]);

            // role/is_active are intentionally NOT mass-assignable (see User model)
            $user->forceFill(['role' => 'operator', 'is_active' => true])->save();

            $operator = Operator::create([
                'user_id' => $user->id,
                'toda_id' => $toda->id,
                'contact_number' => $data['contact_number'],
                'plate_number' => $data['plate_number'],
                'body_number' => $data['body_number'],
                'motorcycle_model' => $data['motorcycle_model'],
                'qr_code' => Str::random(32),
                'status' => 'pending',
            ]);

            // Register the TODA membership alongside the home TODA so the
            // rating flow recognizes the member even before approval.
            $operator->joinToda($toda->id);

            return $user;
        });

        ActivityLogger::log(
            'add_member',
            "Added member {$user->name} ({$user->email}) to {$toda->name} — pending approval",
            $user,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return redirect()
            ->route('president.members', ['status' => 'pending'])
            ->with('success', "{$user->name} added. The account is pending — superadmin/TFRB must approve it before it becomes active.");
    }

    /**
     * Remove a member from the president's TODA. The account is kept but
     * detached from the TODA (it becomes a TODA-less operator) so their rating
     * history is preserved and an admin can reassign them later.
     */
    public function removeMember(Operator $member)
    {
        $toda = $this->authorizeMember($member);

        // The president cannot remove their own operator record.
        $ownOperator = $this->query->presidentOperator(Auth::user());
        if ($ownOperator && $member->id === $ownOperator->id) {
            return redirect()->back()->with('error', 'You cannot remove your own account from the TODA.');
        }

        $name = $member->user->name ?? 'Member';

        DB::transaction(function () use ($member, $toda) {
            // Withdraw this TODA's membership. If it is also the operator's
            // home TODA, clear that too so they become a fully TODA-less
            // operator (rating flow then finds no TODA for them).
            $member->leaveToda($toda->id);

            if ((int) $member->toda_id === (int) $toda->id) {
                $member->update(['toda_id' => null]);

                if ($member->user) {
                    $member->user->forceFill(['toda_id' => null])->save();
                }
            }
        });

        ActivityLogger::log(
            'remove_member',
            "Removed member {$name} from {$toda->name} — now a TODA-less operator",
            $member,
            ActivityLogger::CATEGORY_OPERATOR
        );

        return redirect()
            ->route('president.members')
            ->with('success', "{$name} was removed from {$toda->name}.");
    }

    /**
     * Enforce the org boundary: only allow if this member belongs to the
     * president's TODA, AND the president is assigned to a TODA.
     */
    protected function authorizeMember(Operator $member): Toda
    {
        $user = Auth::user();
        $toda = $user->presidentToda();

        if (!$user->isOperatorPresident() || !$toda || !$member->isMemberOfToda($toda->id)) {
            abort(403);
        }

        return $toda;
    }

    /**
     * President's first-level decision on a self-service join request: approve
     * (forwards it to the TFRB officer) or reject.
     */
    public function approveMembership(OperatorToda $membership)
    {
        $this->authorizeMembership($membership);

        if ($membership->operator && (int) $membership->operator->user_id === (int) Auth::id()) {
            return redirect()->back()->with('error', 'You cannot approve your own membership request.');
        }

        if ($membership->status !== OperatorToda::STATUS_PENDING) {
            return redirect()->back()->with('error', 'This membership request is no longer awaiting your approval.');
        }

        app(TodaMembershipService::class)->presidentApprove($membership, Auth::user());

        return redirect()
            ->route('president.members')
            ->with('success', 'Approved. The request is now with the TFRB officer for verification.');
    }

    public function rejectMembership(Request $request, OperatorToda $membership)
    {
        $this->authorizeMembership($membership);

        if ($membership->status !== OperatorToda::STATUS_PENDING) {
            return redirect()->back()->with('error', 'This membership request is no longer awaiting your decision.');
        }

        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        app(TodaMembershipService::class)->reject($membership, Auth::user(), $request->input('note'));

        return redirect()->route('president.members')->with('success', 'Membership request rejected.');
    }

    /**
     * Only a membership belonging to the president's own TODA can be decided on
     * here.
     */
    protected function authorizeMembership(OperatorToda $membership): Toda
    {
        $user = Auth::user();
        $toda = $user->presidentToda();

        if (!$user->isOperatorPresident() || !$toda
            || !$membership
            || (int) $membership->toda_id !== (int) $toda->id) {
            abort(403);
        }

        return $toda;
    }
}
