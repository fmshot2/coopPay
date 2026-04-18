<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Contribution\Models\ExtraPayment;
use Modules\Contribution\Models\SavingsContribution;
use Modules\Loan\Models\LoanPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\User\Models\User;
use Carbon\Carbon;

class ContributionController extends Controller
{
    // public function index(Request $request): Response
    // {
    //     $pending = ExtraPayment::with('user', 'loanPlan.loanType')
    //         ->where('status', 'pending')
    //         ->latest()
    //         ->get()
    //         ->map(fn($c) => $this->formatContribution($c));

    //     $perPage = $request->input('per_page', 10);
    //     $recent = ExtraPayment::with('user', 'loanPlan.loanType')
    //         ->whereIn('status', ['approved', 'rejected'])
    //         ->latest()
    //         ->paginate($perPage);

    //     return Inertia::render('Admin/Contributions/Index', [
    //         'pending' => $pending,
    //         'recent'  => $recent,
    //         'filters' => $request->only(['per_page']),
    //     ]);
    // }


    public function index(Request $request): Response
    {
        $perPage    = $request->input('per_page', 10);
        $search     = $request->input('search');
        $status     = $request->input('status', 'all');
        $type       = $request->input('type', 'all'); // 'all', 'loan', 'savings'
        $dateFilter = $request->input('date_filter', 'all');
        $fromDate   = $request->input('from_date');
        $toDate     = $request->input('to_date');

        $applyDateRange = function ($query) use ($dateFilter, $fromDate, $toDate) {
            return $query->when($dateFilter !== 'all', function ($q) use ($dateFilter, $fromDate, $toDate) {
                match ($dateFilter) {
                    'today'      => $q->whereDate('created_at', Carbon::today()),
                    'last_week'  => $q->whereBetween('created_at', [Carbon::now()->subWeek(), Carbon::now()]),
                    'last_month' => $q->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()]),
                    'last_year'  => $q->whereBetween('created_at', [Carbon::now()->subYear(), Carbon::now()]),
                    'custom'     => $fromDate && $toDate
                        ? $q->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
                        : $q,
                    default => $q,
                };
            });
        };

        // Build loan payments query
        $loanQuery = ExtraPayment::with('user', 'loanPlan.loanType')
            ->when($search, fn($q) => $q->whereHas(
                'user',
                fn($qu) =>
                $qu->where('name', 'like', "%{$search}%")
                    ->orWhere('member_id', 'like', "%{$search}%")
            ))
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->select(
                'id',
                'user_id',
                'loan_plan_id',
                'amount',
                'narration',
                'screenshot_path',
                'status',
                'admin_note',
                'approved_at',
                'created_at'
            );

        // Build savings query
        $savingsQuery = SavingsContribution::with('user')
            ->when($search, fn($q) => $q->whereHas(
                'user',
                fn($qu) =>
                $qu->where('name', 'like', "%{$search}%")
                    ->orWhere('member_id', 'like', "%{$search}%")
            ))
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->select(
                'id',
                'user_id',
                'amount',
                'narration',
                'screenshot_path',
                'status',
                'admin_note',
                'approved_at',
                'created_at'
            );

        // Apply type filter
        if ($type === 'loan') {
            $contributions = $applyDateRange($loanQuery)
                ->latest()
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn($c) => $this->formatContribution($c, 'loan'));
        } elseif ($type === 'savings') {
            $contributions = $applyDateRange($savingsQuery)
                ->latest()
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn($c) => $this->formatSavingsContribution($c, 'savings'));
        } else {
            // Merge both using union — format first then combine
            $loanItems    = $applyDateRange($loanQuery)->latest()->get()
                ->map(fn($c) => $this->formatContribution($c, 'loan'));
            $savingsItems = $applyDateRange($savingsQuery)->latest()->get()
                ->map(fn($c) => $this->formatSavingsContribution($c, 'savings'));

            $merged = $loanItems->concat($savingsItems)
                ->sortByDesc('created_at_raw')
                ->values();

            // Manual pagination
            $page    = $request->input('page', 1);
            $total   = $merged->count();
            $items   = $merged->forPage($page, $perPage);

            $contributions = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        // Stats — affected by type filter
        $statsLoan    = ExtraPayment::query();
        $statsSavings = SavingsContribution::query();

        return Inertia::render('Admin/Contributions/Index', [
            'contributions' => $contributions,
            'filters'       => $request->only(['per_page', 'search', 'status', 'type', 'date_filter', 'from_date', 'to_date']),
            'stats'         => Inertia::defer(fn() => [
                'total_pending'  => ($type !== 'savings' ? ExtraPayment::where('status', 'pending')->count() : 0)
                    + ($type !== 'loan'    ? SavingsContribution::where('status', 'pending')->count() : 0),
                'total_approved' => ($type !== 'savings' ? ExtraPayment::where('status', 'approved')->count() : 0)
                    + ($type !== 'loan'    ? SavingsContribution::where('status', 'approved')->count() : 0),
                'total_amount'   => ($type !== 'savings' ? ExtraPayment::where('status', 'approved')->sum('amount') : 0)
                    + ($type !== 'loan'    ? SavingsContribution::where('status', 'approved')->sum('amount') : 0),
            ]),
        ]);
    }


    public function savingsIndex(Request $request): Response
    {
        $pending = SavingsContribution::with('user')
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn($c) => $this->formatSavingsContribution($c));

        $perPage = $request->input('per_page', 10);
        $recent = SavingsContribution::with('user')
            ->whereIn('status', ['approved', 'rejected'])
            ->latest()
            ->paginate($perPage);

        return Inertia::render('Admin/Contributions/Savings', [
            'pending' => $pending,
            'recent'  => $recent,
            'filters' => $request->only(['per_page']),
        ]);
    }

    public function approve(ExtraPayment $contribution)
    {
        if ($contribution->status !== 'pending') {
            return back()->with('error', 'This contribution has already been processed.');
        }

        // Update contribution status
        $contribution->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Recalculate loan plan
        $loan = LoanPlan::find($contribution->loan_plan_id);
        if ($loan && $loan->status === 'active') {
            // Convert to float to handle Decimal casting properly
            $currentAmount = (float) $loan->amount_remaining;
            $paymentAmount = (float) $contribution->amount;
            $monthlyRepayment = (float) $loan->repayment_per_month;

            $newAmountRemaining = max(0, $currentAmount - $paymentAmount);

            // Validate repayment_per_month is not zero to avoid division errors
            $newMonthsRemaining = ($newAmountRemaining > 0 && $monthlyRepayment > 0)
                ? (int) ceil($newAmountRemaining / $monthlyRepayment)
                : 0;
            $newStatus = $newMonthsRemaining === 0 ? 'completed' : 'active';

            $loan->update([
                'amount_remaining' => $newAmountRemaining,
                'months_remaining' => $newMonthsRemaining,
                'status'           => $newStatus,
            ]);
        }

        return back()->with('success', 'Contribution approved and loan plan recalculated.');
    }

    public function approveSavings(SavingsContribution $contribution)
    {
        if ($contribution->status !== 'pending') {
            return back()->with('error', 'This savings contribution has already been processed.');
        }

        // Update contribution status
        $contribution->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Update user's savings balance
        $user = $contribution->user;
        $user->increment('savings_balance', $contribution->amount);

        return back()->with('success', 'Savings contribution approved and balance updated.');
    }

    public function reject(Request $request, ExtraPayment $contribution)
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($contribution->status !== 'pending') {
            return back()->with('error', 'This contribution has already been processed.');
        }

        $contribution->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'admin_note'  => $request->admin_note,
        ]);

        return back()->with('success', 'Contribution rejected.');
    }

    public function rejectSavings(Request $request, SavingsContribution $contribution)
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($contribution->status !== 'pending') {
            return back()->with('error', 'This savings contribution has already been processed.');
        }

        $contribution->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'admin_note'  => $request->admin_note,
        ]);

        return back()->with('success', 'Savings contribution rejected.');
    }


    private function formatContribution(ExtraPayment $c, string $type = 'loan'): array
    {
        return [
            'id'              => $c->id,
            'type'            => $type,
            'member_name'     => $c->user->name,
            'member_id'       => $c->user->member_id,
            'loan_type'       => $c->loanPlan?->loanType?->name ?? '—',
            'amount'          => $c->amount,
            'narration'       => $c->narration,
            'screenshot_path' => $c->screenshot_path,
            'status'          => $c->status,
            'admin_note'      => $c->admin_note,
            'approved_at'     => $c->approved_at?->format('M d, Y h:i A'),
            'created_at'      => $c->created_at->format('M d, Y h:i A'),
            'created_at_raw'  => $c->created_at->toISOString(),
        ];
    }

    private function formatSavingsContribution(SavingsContribution $c, string $type = 'savings'): array
    {
        return [
            'id'              => $c->id,
            'type'            => $type,
            'member_name'     => $c->user->name,
            'member_id'       => $c->user->member_id,
            'loan_type'       => '—',
            'amount'          => $c->amount,
            'narration'       => $c->narration,
            'screenshot_path' => $c->screenshot_path,
            'status'          => $c->status,
            'admin_note'      => $c->admin_note,
            'approved_at'     => $c->approved_at?->format('M d, Y h:i A'),
            'created_at'      => $c->created_at->format('M d, Y h:i A'),
            'created_at_raw'  => $c->created_at->toISOString(),
        ];
    }

    // private function formatContribution(ExtraPayment $c): array
    // {
    //     return [
    //         'id'              => $c->id,
    //         'member_name'     => $c->user->name,
    //         'member_id'       => $c->user->member_id,
    //         'loan_type'       => $c->loanPlan?->loanType?->name ?? '—',
    //         'amount'          => $c->amount,
    //         'narration'       => $c->narration,
    //         'screenshot_path' => $c->screenshot_path,
    //         'status'          => $c->status,
    //         'admin_note'      => $c->admin_note,
    //         'approved_at'     => $c->approved_at?->format('M d, Y h:i A'),
    //         'created_at'      => $c->created_at->format('M d, Y h:i A'),
    //     ];
    // }

    // private function formatSavingsContribution(SavingsContribution $c): array
    // {
    //     return [
    //         'id'              => $c->id,
    //         'member_name'     => $c->user->name,
    //         'member_id'       => $c->user->member_id,
    //         'amount'          => $c->amount,
    //         'narration'       => $c->narration,
    //         'screenshot_path' => $c->screenshot_path,
    //         'status'          => $c->status,
    //         'admin_note'      => $c->admin_note,
    //         'approved_at'     => $c->approved_at?->format('M d, Y h:i A'),
    //         'created_at'      => $c->created_at->format('M d, Y h:i A'),
    //     ];
    // }
}
