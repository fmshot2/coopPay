<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Loan\Models\LoanPlan;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class LoanController extends Controller
{
    public function index(): Response
    {
        $user  = Auth::user();
        $loans = LoanPlan::where('user_id', $user->id)
            ->with('loanType')
            ->latest()
            ->get()
            ->map(function ($loan) {
                // Build upcoming deductions
                $upcoming = [];
                if ($loan->status === 'active' && $loan->months_remaining > 0) {
                    $date = Carbon::parse($loan->next_due_date);
                    for ($i = 0; $i < $loan->months_remaining; $i++) {
                        $upcoming[] = [
                            'month_label'     => $date->format('F Y'),
                            'expected_amount' => $loan->repayment_per_month,
                        ];
                        $date->addMonth();
                    }
                }

                return [
                    'id'                  => $loan->id,
                    'loan_type'           => $loan->loanType?->name ?? 'General',
                    'loan_amount'         => $loan->loan_amount,
                    'interest_rate'       => $loan->interest_rate,
                    'repayment_per_month' => $loan->repayment_per_month,
                    'total_months'        => $loan->total_months,
                    'months_remaining'    => $loan->months_remaining,
                    'amount_remaining'    => $loan->amount_remaining,
                    'start_date'          => $loan->start_date?->format('M d, Y'),
                    'next_due_date'       => $loan->next_due_date?->format('M d, Y'),
                    'status'              => $loan->status,
                    'notes'               => $loan->notes,
                    'upcoming'            => $upcoming,
                ];
            });

        return Inertia::render('Member/Loan/Index', [
            'loans' => $loans,
        ]);
    }
}
