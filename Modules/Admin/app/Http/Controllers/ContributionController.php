<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Modules\Contribution\Models\ExtraPayment;
use Modules\Contribution\Models\SavingsContribution;
use Modules\Loan\Models\LoanPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\User\Models\User;
use Carbon\Carbon;

class ContributionController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $perPage    = $request->input('per_page', 10);
            $search     = $request->input('search');
            $status     = $request->input('status', 'all');
            $type       = $request->input('type', 'all');
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
                $loanItems    = $applyDateRange($loanQuery)->latest()->get()
                    ->map(fn($c) => $this->formatContribution($c, 'loan'));
                $savingsItems = $applyDateRange($savingsQuery)->latest()->get()
                    ->map(fn($c) => $this->formatSavingsContribution($c, 'savings'));

                $merged = $loanItems->concat($savingsItems)
                    ->sortByDesc('created_at_raw')
                    ->values();

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

            return $this->respond('Admin/Contributions/Index', [
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
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load contributions.');
        }
    }

    public function savingsIndex(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            $status = $request->input('status', 'all');
            $dateFilter = $request->input('date_filter', 'all');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

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

            $query = SavingsContribution::with('user')
                ->when($search, fn($q) => $q->whereHas(
                    'user',
                    fn($qu) => $qu->where('name', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%")
                ))
                ->when($status !== 'all', fn($q) => $q->where('status', $status));

            $savings = $applyDateRange($query)
                ->latest()
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn($c) => $this->formatSavingsContribution($c));

            return $this->respond('Admin/Savings/Index', [
                'savings' => $savings,
                'filters' => $request->only(['per_page', 'search', 'status', 'date_filter', 'from_date', 'to_date']),
                'stats'   => Inertia::defer(fn() => [
                    'total_pending'  => SavingsContribution::where('status', 'pending')->count(),
                    'total_approved' => SavingsContribution::where('status', 'approved')->count(),
                    'total_rejected' => SavingsContribution::where('status', 'rejected')->count(),
                ]),
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load savings contributions.');
        }
    }

    public function approve(ExtraPayment $contribution)
    {
        try {
            if ($contribution->status !== 'pending') {
                return $this->respondSingleError('This contribution has already been processed.');
            }

            DB::transaction(function () use ($contribution) {
                $contribution->update([
                    'status'      => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                $remainingAmount = (float) $contribution->amount;
                $user = $contribution->user;

                $activeLoans = $user->loanPlans()
                    ->where('status', 'active')
                    ->orderBy('start_date')
                    ->orderBy('id')
                    ->get();

                foreach ($activeLoans as $loan) {
                    if ($remainingAmount <= 0) {
                        break;
                    }

                    $currentAmount    = (float) $loan->amount_remaining;
                    if ($currentAmount <= 0) {
                        $loan->update(['status' => 'completed', 'months_remaining' => 0, 'amount_remaining' => 0]);
                        continue;
                    }

                    $appliedAmount = min($remainingAmount, $currentAmount);
                    $newAmountRemaining = max(0, $currentAmount - $appliedAmount);
                    $monthlyRepayment = (float) $loan->repayment_per_month;
                    $newMonthsRemaining = ($newAmountRemaining > 0 && $monthlyRepayment > 0)
                        ? (int) ceil($newAmountRemaining / $monthlyRepayment)
                        : 0;
                    $newStatus = $newAmountRemaining === 0 ? 'completed' : 'active';

                    $loan->update([
                        'amount_remaining' => $newAmountRemaining,
                        'months_remaining' => $newMonthsRemaining,
                        'status'           => $newStatus,
                    ]);

                    $remainingAmount -= $appliedAmount;
                }

                if ($remainingAmount > 0) {
                    SavingsContribution::create([
                        'user_id' => $user->id,
                        'amount' => $remainingAmount,
                        'narration' => 'Excess from loan contribution #' . $contribution->id,
                        'screenshot_path' => $contribution->screenshot_path,
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);
                    // $user->increment('savings_balance', $remainingAmount);
                }
            });

            return $this->respondSuccess('Contribution approved and applied to loans. Any remaining amount was added to savings.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to approve contribution.');
        }
    }

    public function approveSavings(SavingsContribution $contribution)
    {
        try {
            if ($contribution->status !== 'pending') {
                return $this->respondSingleError('This savings contribution has already been processed.');
            }

            $contribution->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $user = $contribution->user;
            $user->increment('savings_balance', $contribution->amount);

            return $this->respondSuccess('Savings contribution approved and balance updated.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to approve savings contribution.');
        }
    }

    public function reject(Request $request, ExtraPayment $contribution)
    {
        try {
            $request->validate([
                'admin_note' => ['required', 'string', 'max:500'],
            ]);

            if ($contribution->status !== 'pending') {
                return $this->respondSingleError('This contribution has already been processed.');
            }

            $contribution->update([
                'status'      => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'admin_note'  => $request->admin_note,
            ]);

            return $this->respondSuccess('Contribution rejected.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to reject contribution.');
        }
    }

    public function rejectSavings(Request $request, SavingsContribution $contribution)
    {
        try {
            $request->validate([
                'admin_note' => ['required', 'string', 'max:500'],
            ]);

            if ($contribution->status !== 'pending') {
                return $this->respondSingleError('This savings contribution has already been processed.');
            }

            $contribution->update([
                'status'      => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'admin_note'  => $request->admin_note,
            ]);

            return $this->respondSuccess('Savings contribution rejected.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to reject savings contribution.');
        }
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
}
