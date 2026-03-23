<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Loan\Models\LoanPlan;
use Modules\Payment\Models\MonthlyDeduction;
use Modules\Contribution\Models\ExtraPayment;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user     = Auth::user();
        $loan     = LoanPlan::where('user_id', $user->id)->first();

        $recentDeductions = MonthlyDeduction::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($d) => [
                'id'              => $d->id,
                'month'           => $d->month,
                'expected_amount' => $d->expected_amount,
                'status'          => $d->status,
                'confirmed_at'    => $d->confirmed_at,
                'approved_at'     => $d->approved_at,
            ]);

        $recentContributions = ExtraPayment::where('user_id', $user->id)
            ->latest()
            ->take(3)
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'amount'      => $c->amount,
                'narration'   => $c->narration,
                'status'      => $c->status,
                'approved_at' => $c->approved_at,
            ]);

        return Inertia::render('Member/Dashboard/Index', [
            'loan'                => $loan ? [
                'loan_amount'          => $loan->loan_amount,
                'repayment_per_month'  => $loan->repayment_per_month,
                'total_months'         => $loan->total_months,
                'months_remaining'     => $loan->months_remaining,
                'amount_remaining'     => $loan->amount_remaining,
                'next_due_date'        => $loan->next_due_date?->format('M d, Y'),
                'status'               => $loan->status,
                'start_date'           => $loan->start_date?->format('M d, Y'),
            ] : null,
            'recentDeductions'    => $recentDeductions,
            'recentContributions' => $recentContributions,
        ]);
    }
}
