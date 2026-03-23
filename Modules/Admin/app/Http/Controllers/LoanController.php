<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Loan\Models\LoanPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class LoanController extends Controller
{
    public function index(): Response
    {
        $loans = LoanPlan::with(['user', 'loanType'])
            ->latest()
            ->get()
            ->map(fn($loan) => [
                'id'                  => $loan->id,
                'member_name'         => $loan->user->name,
                'member_id'           => $loan->user->member_id,
                'user_id'             => $loan->user_id,
                'loan_type'           => $loan->loanType?->name ?? '—',
                'loan_amount'         => $loan->loan_amount,
                'repayment_per_month' => $loan->repayment_per_month,
                'total_months'        => $loan->total_months,
                'months_remaining'    => $loan->months_remaining,
                'amount_remaining'    => $loan->amount_remaining,
                'next_due_date'       => $loan->next_due_date?->format('M d, Y'),
                'status'              => $loan->status,
                'start_date'          => $loan->start_date?->format('M d, Y'),
            ]);

        return Inertia::render('Admin/Loans/Index', [
            'loans' => $loans,
        ]);
    }

    public function create(): Response
    {
        $members = User::role('member')
            ->get()
            ->map(fn($user) => [
                'id'        => $user->id,
                'name'      => $user->name,
                'member_id' => $user->member_id,
            ]);

        $loanTypes = \Modules\Loan\Models\LoanType::where('is_active', true)
            ->get()
            ->map(fn($type) => [
                'id'   => $type->id,
                'name' => $type->name,
            ]);

        return Inertia::render('Admin/Loans/Create', [
            'members'   => $members,
            'loanTypes' => $loanTypes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'       => ['required', 'exists:users,id'],
            'loan_type_id'  => ['required', 'exists:loan_types,id'],
            'loan_amount'   => ['required', 'numeric', 'min:1'],
            'total_months'  => ['required', 'integer', 'min:1'],
            'start_date'    => ['required', 'date'],
            'notes'         => ['nullable', 'string'],
        ]);

        $interestRate   = 0.10;
        $totalRepayable = $request->loan_amount + ($request->loan_amount * $interestRate);
        $monthlyPayment = round($totalRepayable / $request->total_months, 2);
        $startDate      = Carbon::parse($request->start_date);
        $nextDueDate    = $startDate->copy()->addMonth()->startOfMonth();

        LoanPlan::create([
            'user_id'             => $request->user_id,
            'loan_type_id'        => $request->loan_type_id,
            'loan_amount'         => $request->loan_amount,
            'interest_rate'       => 10,
            'repayment_per_month' => $monthlyPayment,
            'total_months'        => $request->total_months,
            'months_remaining'    => $request->total_months,
            'amount_remaining'    => $totalRepayable,
            'start_date'          => $startDate,
            'next_due_date'       => $nextDueDate,
            'status'              => 'active',
            'notes'               => $request->notes,
        ]);

        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Loan plan created successfully.');
    }


    public function edit(LoanPlan $loan): Response
    {
        $loan->load('user');

        return Inertia::render('Admin/Loans/Edit', [
            'loan' => [
                'id'                  => $loan->id,
                'user_id'             => $loan->user_id,
                'member_name'         => $loan->user->name,
                'member_id'           => $loan->user->member_id,
                'loan_amount'         => $loan->loan_amount,
                'interest_rate'       => $loan->interest_rate,
                'repayment_per_month' => $loan->repayment_per_month,
                'total_months'        => $loan->total_months,
                'months_remaining'    => $loan->months_remaining,
                'amount_remaining'    => $loan->amount_remaining,
                'start_date'          => $loan->start_date?->format('Y-m-d'),
                'next_due_date'       => $loan->next_due_date?->format('Y-m-d'),
                'status'              => $loan->status,
                'notes'               => $loan->notes,
            ],
        ]);
    }

    public function update(Request $request, LoanPlan $loan)
    {
        $request->validate([
            'loan_amount'          => ['required', 'numeric', 'min:1'],
            'repayment_per_month'  => ['required', 'numeric', 'min:1'],
            'total_months'         => ['required', 'integer', 'min:1'],
            'months_remaining'     => ['required', 'integer', 'min:0'],
            'start_date'           => ['required', 'date'],
            'next_due_date'        => ['nullable', 'date'],
            'interest_rate'        => ['nullable', 'numeric', 'min:0'],
            'status'               => ['required', 'in:active,completed,suspended'],
            'notes'                => ['nullable', 'string'],
        ]);

        $amountRemaining = $request->repayment_per_month * $request->months_remaining;

        $loan->update([
            'loan_amount'          => $request->loan_amount,
            'interest_rate'        => $request->interest_rate ?? 0,
            'repayment_per_month'  => $request->repayment_per_month,
            'total_months'         => $request->total_months,
            'months_remaining'     => $request->months_remaining,
            'amount_remaining'     => $amountRemaining,
            'start_date'           => $request->start_date,
            'next_due_date'        => $request->next_due_date,
            'status'               => $request->status,
            'notes'                => $request->notes,
        ]);

        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Loan plan updated successfully.');
    }

    // mark loan as complete
    public function markComplete(LoanPlan $loan)
    {
        $loan->update([
            'status'           => 'completed',
            'months_remaining' => 0,
            'amount_remaining' => 0,
            'notes'            => ($loan->notes ? $loan->notes . ' | ' : '') .
                'Marked completed by admin on ' . now()->format('M d, Y'),
        ]);

        return back()->with('success', 'Loan marked as completed.');
    }

    public function cancel(LoanPlan $loan)
    {
        $loan->update([
            'status' => 'suspended',
            'notes'  => ($loan->notes ? $loan->notes . ' | ' : '') .
                'Cancelled by admin on ' . now()->format('M d, Y'),
        ]);

        return back()->with('success', 'Loan cancelled successfully.');
    }
}
