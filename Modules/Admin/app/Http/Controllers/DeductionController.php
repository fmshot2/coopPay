<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payment\Models\MonthlyDeduction;
use Modules\Loan\Models\LoanPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DeductionController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 5);
        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $dateFilter = $request->input('date_filter', 'all');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $applyDateRange = function ($query) use ($dateFilter, $fromDate, $toDate) {
            return $query->when($dateFilter !== 'all', function ($q) use ($dateFilter, $fromDate, $toDate) {
                if ($dateFilter === 'today') {
                    return $q->whereDate('created_at', Carbon::today());
                } elseif ($dateFilter === 'last_week') {
                    return $q->whereBetween('created_at', [Carbon::now()->subWeek(), Carbon::now()]);
                } elseif ($dateFilter === 'last_month') {
                    return $q->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()]);
                } elseif ($dateFilter === 'last_year') {
                    return $q->whereBetween('created_at', [Carbon::now()->subYear(), Carbon::now()]);
                } elseif ($dateFilter === 'custom' && $fromDate && $toDate) {
                    return $q->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()]);
                }
            });
        };

        $query = MonthlyDeduction::with(['user', 'loanPlan.loanType'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                            ->orWhere('member_id', 'like', "%{$search}%");
                    });
                });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            });

        $deductions = $applyDateRange($query)
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($d) => [
                'id'              => $d->id,
                'member_name'     => $d->user->name,
                'member_id'       => $d->user->member_id,
                'loan_type'       => $d->loanPlan?->loanType?->name ?? '—',
                'month'           => Carbon::parse($d->month . '-01')->format('F Y'),
                'expected_amount' => $d->expected_amount,
                'status'          => $d->status,
                'member_note'     => $d->member_note,
                'admin_note'      => $d->admin_note,
                'confirmed_at'    => $d->confirmed_at?->format('M d, Y'),
                'approved_at'     => $d->approved_at?->format('M d, Y'),
                'created_at'      => $d->created_at->format('M d, Y'),
            ]);

        return Inertia::render('Admin/Deductions/Index', [
            'deductions' => $deductions,
            'filters'    => $request->only(['per_page', 'search', 'status', 'date_filter', 'from_date', 'to_date']),
            'stats'      => Inertia::defer(fn() => [
                'total_pending'   => $applyDateRange(MonthlyDeduction::where('status', 'pending'))->count(),
                'total_approved'  => $applyDateRange(MonthlyDeduction::where('status', 'approved'))->count(),
                'total_amount'    => $applyDateRange(MonthlyDeduction::where('status', 'approved'))->sum('expected_amount'),
            ]),
        ]);
    }

    public function approve(MonthlyDeduction $deduction)
    {
        if ($deduction->status !== 'pending') {
            return back()->with('error', 'This deduction has already been processed.');
        }

        // Update deduction status
        $deduction->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Recalculate loan plan
        $loan = $deduction->loanPlan;
        if ($loan && $loan->status === 'active') {
            $newMonthsRemaining = max(0, $loan->months_remaining - 1);
            $newAmountRemaining = max(0, $loan->amount_remaining - $loan->repayment_per_month);
            $newNextDueDate     = Carbon::parse($loan->next_due_date)->addMonth();
            $newStatus          = $newMonthsRemaining === 0 ? 'completed' : 'active';

            $loan->update([
                'months_remaining' => $newMonthsRemaining,
                'amount_remaining' => $newAmountRemaining,
                'next_due_date'    => $newNextDueDate,
                'status'           => $newStatus,
            ]);
        }

        return back()->with('success', 'Deduction approved and loan plan updated.');
    }

    public function reject(Request $request, MonthlyDeduction $deduction)
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($deduction->status !== 'pending') {
            return back()->with('error', 'This deduction has already been processed.');
        }

        $deduction->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'admin_note'  => $request->admin_note,
        ]);

        return back()->with('success', 'Deduction rejected.');
    }
}
