<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use Modules\Payment\Models\MonthlyDeduction;
use Modules\Loan\Models\LoanPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Throwable;

class DeductionController extends Controller
{
    use RespondsWithJson;
    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user  = Auth::user();
            $loans = LoanPlan::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('loanType')
                ->get();

            // Build confirmable months per active loan
            $confirmable = [];
            foreach ($loans as $loan) {
                $currentMonth = Carbon::now()->format('Y-m');

                $existing = MonthlyDeduction::where('user_id', $user->id)
                    ->where('loan_plan_id', $loan->id)
                    ->where('month', $currentMonth)
                    ->first();

                $confirmable[] = [
                    'loan_id'             => $loan->id,
                    'loan_type'           => $loan->loanType?->name ?? 'General',
                    'expected_amount'     => $loan->repayment_per_month,
                    'month'               => $currentMonth,
                    'already_confirmed'   => $existing !== null,
                    'confirmation_status' => $existing?->status,
                ];
            }

            // Build upcoming deductions per active loan
            $upcoming = [];
            foreach ($loans as $loan) {
                $date = Carbon::parse($loan->next_due_date);
                for ($i = 0; $i < $loan->months_remaining; $i++) {
                    $upcoming[] = [
                        'loan_type'       => $loan->loanType?->name ?? 'General',
                        'month'           => $date->format('Y-m'),
                        'month_label'     => $date->format('F Y'),
                        'expected_amount' => $loan->repayment_per_month,
                    ];
                    $date->addMonth();
                }
            }

            // Sort upcoming by month
            usort($upcoming, fn($a, $b) => strcmp($a['month'], $b['month']));

            $history = MonthlyDeduction::where('user_id', $user->id)
                ->with('loanPlan.loanType')
                ->latest()
                ->get()
                ->map(fn($d) => [
                    'id'              => $d->id,
                    'loan_type'       => $d->loanPlan?->loanType?->name ?? '—',
                    'month'           => $d->month,
                    'expected_amount' => $d->expected_amount,
                    'status'          => $d->status,
                    'member_note'     => $d->member_note,
                    'admin_note'      => $d->admin_note,
                    'confirmed_at'    => $d->confirmed_at?->format('M d, Y'),
                    'approved_at'     => $d->approved_at?->format('M d, Y'),
                ]);

            return $this->respond('Member/Deductions/Index', [
                'confirmable' => $confirmable,
                'upcoming'    => $upcoming,
                'history'     => $history,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load deductions.');
        }
    }

    public function confirm(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'loan_plan_id' => ['required', 'exists:loan_plans,id'],
                'member_note'  => ['nullable', 'string', 'max:500'],
            ]);

            $user         = Auth::user();
            $currentMonth = Carbon::now()->format('Y-m');

            $loan = LoanPlan::where('id', $request->loan_plan_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->firstOrFail();

            // Check for duplicate — silently return success
            $existing = MonthlyDeduction::where('user_id', $user->id)
                ->where('loan_plan_id', $loan->id)
                ->where('month', $currentMonth)
                ->first();

            if ($existing) {
                return $this->respondSingleError('You have already confirmed this deduction.');
            }

            MonthlyDeduction::create([
                'user_id'         => $user->id,
                'loan_plan_id'    => $loan->id,
                'month'           => $currentMonth,
                'expected_amount' => $loan->repayment_per_month,
                'status'          => 'pending',
                'confirmed_at'    => now(),
                'member_note'     => $request->member_note,
            ]);

            return $this->respondSuccess('Deduction confirmed successfully. Awaiting admin approval.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to confirm deduction.');
        }
    }
}
