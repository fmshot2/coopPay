<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Contribution\Models\ExtraPayment;
use Modules\Loan\Models\LoanPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    public function index(): Response
    {
        $pending = ExtraPayment::with('user', 'loanPlan.loanType')
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn($c) => $this->formatContribution($c));

        $recent = ExtraPayment::with('user', 'loanPlan.loanType')
            ->whereIn('status', ['approved', 'rejected'])
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($c) => $this->formatContribution($c));

        return Inertia::render('Admin/Contributions/Index', [
            'pending' => $pending,
            'recent'  => $recent,
        ]);
    }

    public function approve(ExtraPayment $contribution)
    {
        if ($contribution->status !== 'pending') {
            return back()->with('error', 'This contribution has already been processed.');
        }

        // Update contribution status
        $contribution->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Recalculate loan plan — Option B
        $loan = $contribution->loanPlan;
        if ($loan && $loan->status === 'active') {
            $newAmountRemaining  = max(0, $loan->amount_remaining - $contribution->amount);
            $newMonthsRemaining  = $newAmountRemaining > 0
                ? (int) ceil($newAmountRemaining / $loan->repayment_per_month)
                : 0;
            $newStatus = $newMonthsRemaining === 0 ? 'completed' : 'active';

            $loan->update([
                'amount_remaining' => $newAmountRemaining,
                'months_remaining' => $newMonthsRemaining,
                'status'           => $newStatus,
            ]);
        }

        return back()->with('success', 'Contribution approved and loan plan recalculated.');
    }

    public function reject(Request $request, ExtraPayment $contribution)
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($contribution->status !== 'pending') {
            return back()->with('error', 'This contribution has already been processed.');
        }

        $contribution->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'admin_note'  => $request->admin_note,
        ]);

        return back()->with('success', 'Contribution rejected.');
    }

    private function formatContribution(ExtraPayment $c): array
    {
        return [
            'id'              => $c->id,
            'member_name'     => $c->user->name,
            'member_id'       => $c->user->member_id,
            'loan_type'       => $c->loanPlan?->loanType?->name ?? '—',
            'amount'          => $c->amount,
            'narration'       => $c->narration,
            'screenshot_path' => $c->screenshot_path,
            'status'          => $c->status,
            'admin_note'      => $c->admin_note,
            'approved_at'     => $c->approved_at?->format('M d, Y h:i A'),
            'created_at'      => $c->created_at->format('M d, Y h:i A'),
        ];
    }
}
