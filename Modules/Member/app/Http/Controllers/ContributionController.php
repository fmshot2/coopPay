<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use Modules\Contribution\Models\ExtraPayment;
use Modules\Loan\Models\LoanPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ContributionController extends Controller
{
    use RespondsWithJson;
    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user  = Auth::user();
            $loans = LoanPlan::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('loanType')
                ->get()
                ->map(fn($loan) => [
                    'id'                  => $loan->id,
                    'loan_type'           => $loan->loanType?->name ?? 'General',
                    'amount_remaining'    => $loan->amount_remaining,
                    'repayment_per_month' => $loan->repayment_per_month,
                    'months_remaining'    => $loan->months_remaining,
                ])->values();

            $history = ExtraPayment::where('user_id', $user->id)
                ->with('loanPlan.loanType')
                ->latest()
                ->get()
                ->map(fn($c) => [
                    'id'              => $c->id,
                    'loan_type'       => $c->loanPlan?->loanType?->name ?? '—',
                    'amount'          => $c->amount,
                    'narration'       => $c->narration,
                    'screenshot_path' => $c->screenshot_path,
                    'status'          => $c->status,
                    'admin_note'      => $c->admin_note,
                    'created_at'      => $c->created_at->format('M d, Y'),
                    'approved_at'     => $c->approved_at?->format('M d, Y'),
                ]);

            return $this->respond('Member/Payments/Index', [
                'loans'   => $loans,
                'history' => $history,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load payments.');
        }
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'loan_plan_id' => ['required', 'exists:loan_plans,id'],
                'amount'       => ['required', 'numeric', 'min:1'],
                'narration'    => ['required', 'string', 'max:500'],
                'screenshot'   => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            ]);

            $user = Auth::user();

            // Verify loan belongs to user
            $loan = LoanPlan::where('id', $request->loan_plan_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->firstOrFail();

            // Store screenshot
            $path = $request->file('screenshot')->store('contributions', 'public');

            ExtraPayment::create([
                'user_id'         => $user->id,
                'loan_plan_id'    => $loan->id,
                'amount'          => $request->amount,
                'narration'       => $request->narration,
                'screenshot_path' => $path,
                'status'          => 'pending',
            ]);

            return $this->respondSuccess('Extra payment submitted successfully. Awaiting admin approval.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to submit extra payment.');
        }
    }
}
