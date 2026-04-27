<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Loan\Models\LoanPlan;
use Modules\Loan\Models\LoanType;
use Throwable;

class LoanController extends Controller
{
    use RespondsWithJson;
    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $user   = Auth::user();
            $search = $request->input('search');
            $status = $request->input('status', 'all');
            $type   = $request->input('loan_type', 'all');

            $query = LoanPlan::where('user_id', $user->id)
                ->with('loanType')
                ->when($status !== 'all', fn($q) => $q->where('status', $status))
                ->when($type !== 'all', fn($q) => $q->where('loan_type_id', $type))
                ->when($search, fn($q) => $q->whereHas(
                    'loanType',
                    fn($qu) =>
                    $qu->where('name', 'like', "%{$search}%")
                ))
                ->orderBy('created_at', 'desc');

            $loans = $query->get()->map(fn($loan) => [
                'id'                  => $loan->id,
                'loan_type'           => $loan->loanType?->name ?? '—',
                'loan_amount'         => $loan->loan_amount,
                'repayment_per_month' => $loan->repayment_per_month,
                'amount_remaining'    => $loan->amount_remaining,
                'next_due_date'       => $loan->next_due_date?->format('M d, Y'),
                'total_months'        => $loan->total_months,
                'months_remaining'    => $loan->months_remaining,
                'status'              => $loan->status,
                'start_date'          => $loan->start_date?->format('M d, Y'),
                'notes'               => $loan->notes,
            ]);

            // Stats
            $allLoans        = LoanPlan::where('user_id', $user->id);
            $activeLoans     = LoanPlan::where('user_id', $user->id)->where('status', 'active');
            $totalCollected  = (clone $allLoans)->sum('loan_amount');
            $totalOutstanding = (clone $activeLoans)->sum('amount_remaining');
            $totalMonthly    = (clone $activeLoans)->sum('repayment_per_month');
            $activeCount     = (clone $activeLoans)->count();

            $loanTypes = LoanType::where('is_active', true)
                ->get()
                ->map(fn($t) => ['id' => $t->id, 'name' => $t->name]);

            $cooperativeAccount = \App\Models\Setting::getValue('cooperative_account');

            return $this->respond('Member/Loans/Index', [
                'loans'              => $loans,
                'loanTypes'          => $loanTypes,
                'cooperativeAccount' => $cooperativeAccount,
                'filters'            => $request->only(['search', 'status', 'loan_type']),
                'stats'              => [
                    'active_count'     => $activeCount,
                    'total_collected'  => $totalCollected,
                    'total_outstanding' => $totalOutstanding,
                    'total_monthly'    => $totalMonthly,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loans.');
        }
    }

    public function create(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();
            $loanTypes = LoanType::where('is_active', true)->get();
            $maxLoanAmount = $user->max_loan_amount ?? 0;

            return $this->respond('Member/Loans/Create', [
                'loanTypes' => $loanTypes,
                'maxLoanAmount' => $maxLoanAmount,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan creation form.');
        }
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'loan_type_id' => 'required|exists:loan_types,id',
                'amount' => 'required|numeric|min:1|max:' . ($user->max_loan_amount ?? 999999999),
                'duration_months' => 'required|integer|min:1|max:60',
                'purpose' => 'nullable|string|max:500',
            ]);

            $interestRate = 10; // fixed 10% interest rate
            $totalRepayable = round($validated['amount'] + ($validated['amount'] * 0.10), 2);
            $monthlyPayment = round($totalRepayable / $validated['duration_months'], 2);

            LoanPlan::create([
                'user_id' => $user->id,
                'loan_type_id' => $validated['loan_type_id'],
                'loan_amount' => $validated['amount'],
                'interest_rate' => $interestRate,
                'repayment_per_month' => $monthlyPayment,
                'total_months' => $validated['duration_months'],
                'months_remaining' => $validated['duration_months'],
                'amount_remaining' => $totalRepayable,
                'start_date' => now(),
                'next_due_date' => now()->addMonth()->startOfMonth(),
                'status' => 'active',
                'notes' => $validated['purpose'],
            ]);

            return $this->respondSuccess('Loan created successfully!');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to create loan.');
        }
    }

    public function show(LoanPlan $loan): Response|JsonResponse|RedirectResponse
    {
        try {
            $loan->load('loanType');

            return $this->respond('Member/Loans/Show', [
                'loan' => $loan,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan details.');
        }
    }

    public function history(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();

            $loans = LoanPlan::where('user_id', $user->id)
                ->with('loanType')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($loan) => [
                    'id' => $loan->id,
                    'loan_type' => $loan->loanType,
                    'amount' => $loan->loan_amount,
                    'duration_months' => $loan->total_months,
                    'monthly_payment' => $loan->repayment_per_month,
                    'remaining_amount' => $loan->amount_remaining,
                    'status' => $loan->status,
                    'start_date' => optional($loan->start_date)->format('Y-m-d'),
                    'end_date' => $loan->start_date ? $loan->start_date->copy()->addMonths($loan->total_months)->format('Y-m-d') : null,
                    'notes' => $loan->notes,
                ]);

            return $this->respond('Member/Loans/History', [
                'loans' => $loans,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan history.');
        }
    }

    private function calculateMonthlyPayment($principal, $annualRate, $months)
    {
        if ($annualRate == 0) {
            return $principal / $months;
        }

        $monthlyRate = $annualRate / 100 / 12;
        $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);

        return round($payment, 2);
    }
}
