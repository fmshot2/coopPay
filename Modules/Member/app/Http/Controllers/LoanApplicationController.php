<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Models\LoanApplication;
use Modules\Loan\Models\LoanType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class LoanApplicationController extends Controller
{
    use RespondsWithJson;
    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();
            $applications = LoanApplication::where('user_id', $user->id)
                ->with('loanType')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->respond('Member::LoanApplications/Index', [
                'applications' => $applications,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan applications.');
        }
    }

    public function create(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();
            $loanTypes = LoanType::where('is_active', true)->get();
            $maxLoanAmount = $user->max_loan_amount ?? 0;

            return $this->respond('Member::LoanApplications/Create', [
                'loanTypes' => $loanTypes,
                'maxLoanAmount' => $maxLoanAmount,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan application form.');
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

            LoanApplication::create([
                'user_id' => $user->id,
                'loan_type_id' => $validated['loan_type_id'],
                'amount' => $validated['amount'],
                'interest_rate' => $interestRate,
                'duration_months' => $validated['duration_months'],
                'monthly_payment' => $monthlyPayment,
                'total_payment' => $totalRepayable,
                'purpose' => $validated['purpose'],
                'status' => 'pending',
            ]);

            return $this->respondSuccess('Loan application submitted successfully!');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to submit loan application.');
        }
    }

    public function show(LoanApplication $loanApplication): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();

            if ($loanApplication->user_id !== $user->id) {
                abort(403);
            }

            $loanApplication->load('loanType');

            return $this->respond('Member::LoanApplications/Show', [
                'loanApplication' => $loanApplication,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan application.');
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
