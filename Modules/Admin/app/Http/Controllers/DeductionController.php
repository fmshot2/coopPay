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
    public function index(): Response
    {
        $pending = MonthlyDeduction::with('user', 'loanPlan.loanType')
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn($d) => $this->formatDeduction($d));

        $recent = MonthlyDeduction::with('user', 'loanPlan.loanType')
            ->whereIn('status', ['approved', 'rejected'])
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($d) => $this->formatDeduction($d));

        return Inertia::render('Admin/Deductions/Index', [
            'pending' => $pending,
            'recent'  => $recent,
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

    private function formatDeduction(MonthlyDeduction $d): array
    {
        return [
            'id'              => $d->id,
            'member_name'     => $d->user->name,
            'member_id'       => $d->user->member_id,
            'loan_type'       => $d->loanPlan?->loanType?->name ?? '—',
            'month'           => $d->month,
            'expected_amount' => $d->expected_amount,
            'status'          => $d->status,
            'member_note'     => $d->member_note,
            'admin_note'      => $d->admin_note,
            'confirmed_at'    => $d->confirmed_at?->format('M d, Y h:i A'),
            'approved_at'     => $d->approved_at?->format('M d, Y h:i A'),
        ];
    }
}
