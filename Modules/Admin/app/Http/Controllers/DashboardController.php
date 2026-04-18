<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\User;
use Modules\Loan\Models\LoanPlan;
use Modules\Payment\Models\MonthlyDeduction;
use Modules\Contribution\Models\ExtraPayment;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_members'           => User::role('member')->count(),
            'active_loans'            => LoanPlan::where('status', 'active')->count(),
            'pending_deductions'      => MonthlyDeduction::where('status', 'pending')->count(),
            'pending_contributions'   => ExtraPayment::where('status', 'pending')->count(),
            'completed_loans'         => LoanPlan::where('status', 'completed')->count(),
            'unapproved_loans'         => LoanApplication::count(),
            'total_loan_amount'       => LoanPlan::where('status', 'active')->sum('loan_amount'),
            'total_amount_remaining'  => LoanPlan::where('status', 'active')->sum('amount_remaining'),
        ];

        $recentDeductions = MonthlyDeduction::with('user')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($d) => [
                'id'              => $d->id,
                'member_name'     => $d->user->name,
                'member_id'       => $d->user->member_id,
                'month'           => $d->month,
                'expected_amount' => $d->expected_amount,
                'status'          => $d->status,
            ]);

        $recentContributions = ExtraPayment::with('user')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'member_name' => $c->user->name,
                'member_id'   => $c->user->member_id,
                'amount'      => $c->amount,
                'narration'   => $c->narration,
                'status'      => $c->status,
            ]);

        return Inertia::render('Admin/Dashboard/Index', [
            'stats'                => $stats,
            'recentDeductions'     => $recentDeductions,
            'recentContributions'  => $recentContributions,
        ]);
    }
}
