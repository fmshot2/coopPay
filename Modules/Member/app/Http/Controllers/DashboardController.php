<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use Modules\Loan\Models\LoanPlan;
use App\Models\LoanApplication;
use App\Models\Setting;
use Modules\Payment\Models\MonthlyDeduction;
use Modules\Contribution\Models\ExtraPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    use RespondsWithJson;

    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user     = Auth::user();
            $loan = LoanPlan::where('user_id', $user->id)->first();
            $activeLoansCount = LoanPlan::where('user_id', $user->id)->where('status', 'active')->count();

            $activeLoans = LoanPlan::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('loanType')
                ->get();

            $loanRequests = LoanApplication::where('user_id', $user->id)
                ->with('loanType')
                ->orderBy('created_at', 'desc')
                ->get();

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

            $cooperativeAccount = Setting::getValue('cooperative_account');

            return $this->respond('Member/Dashboard/Index', [
                'loan'                => $loan ? [
                    'loan_amount'          => $loan->loan_amount,
                    'repayment_per_month'  => $loan->repayment_per_month,
                    'total_months'         => $loan->total_months,
                    'months_remaining'     => $loan->months_remaining,
                    'amount_remaining'     => $loan->amount_remaining,
                    'next_due_date'        => optional($loan->next_due_date)->format('M d, Y'),
                    'status'               => $loan->status,
                    'start_date'           => optional($loan->start_date)->format('M d, Y'),
                ] : null,
                'activeLoansCount'    => $activeLoansCount,
                'activeLoans'         => $activeLoans->map(fn($loan) => [
                    'id'               => $loan->id,
                    'loan_type'        => $loan->loanType?->name ?? 'Loan',
                    'loan_amount'      => $loan->loan_amount,
                    'monthly_payment'  => $loan->repayment_per_month,
                    'amount_remaining' => $loan->amount_remaining,
                    'status'           => $loan->status,
                    'start_date'       => $loan->start_date?->format('M d, Y'),
                    'next_due_date'    => $loan->next_due_date?->format('M d, Y'),
                ])->toArray(),
                'loanRequests'       => $loanRequests->map(fn($request) => [
                    'id'               => $request->id,
                    'loan_type'        => $request->loanType?->name ?? 'Loan',
                    'amount'           => $request->amount,
                    'duration_months'  => $request->duration_months,
                    'monthly_payment'  => $request->monthly_payment,
                    'status'           => $request->status,
                    'created_at'       => $request->created_at?->format('M d, Y'),
                ])->toArray(),
                'recentDeductions'    => $recentDeductions,
                'recentContributions' => $recentContributions,
                'cooperativeAccount'  => $cooperativeAccount,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load dashboard.');
        }
    }
}
