<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Modules\Loan\Models\LoanPlan;
use Modules\Loan\Models\LoanType;
use Modules\Loan\Models\MonthlyRepayment;
use Modules\Payment\Models\MonthlyDeduction;
use App\Models\LoanApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Admin\Models\Year;
use Modules\Admin\Models\Month;

class LoanApplicationController extends Controller
{
    use RespondsWithJson;
    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        $perPage    = $request->input('per_page', 10);
        $search     = $request->input('search');
        $status     = $request->input('status', 'all');
        $dateFilter = $request->input('date_filter', 'all');
        $fromDate   = $request->input('from_date');
        $toDate     = $request->input('to_date');

        $applyDateRange = function ($query) use ($dateFilter, $fromDate, $toDate) {
            return $query->when($dateFilter !== 'all', function ($q) use ($dateFilter, $fromDate, $toDate) {
                match ($dateFilter) {
                    'today'      => $q->whereDate('created_at', Carbon::today()),
                    'last_week'  => $q->whereBetween('created_at', [Carbon::now()->subWeek(), Carbon::now()]),
                    'last_month' => $q->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()]),
                    'last_year'  => $q->whereBetween('created_at', [Carbon::now()->subYear(), Carbon::now()]),
                    'custom'     => $fromDate && $toDate
                        ? $q->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
                        : $q,
                    default => $q,
                };
            });
        };

        $query = LoanApplication::with(['user', 'loanType'])
            ->when($search, fn($q) => $q->whereHas(
                'user',
                fn($qu) =>
                $qu->where('name', 'like', "%{$search}%")
                    ->orWhere('member_id', 'like', "%{$search}%")
            ))
            ->when($status !== 'all', fn($q) => $q->where('status', $status));

        $applications = $applyDateRange($query)
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($a) => [
                'id'               => $a->id,
                'member_name'      => $a->user->name,
                'member_id'        => $a->user->member_id,
                'loan_type'        => $a->loanType?->name ?? '—',
                'amount'           => $a->amount,
                'duration_months'  => $a->duration_months,
                'monthly_payment'  => $a->monthly_payment,
                'total_payment'    => $a->total_payment,
                'purpose'          => $a->purpose,
                'status'           => $a->status,
                'rejection_reason' => $a->rejection_reason,
                'approved_at'      => $a->approved_at?->format('M d, Y'),
                'created_at'       => $a->created_at->format('M d, Y'),
            ]);

        return $this->respond('Admin/LoanApplications/Index', [
            'applications' => $applications,
            'filters'      => $request->only(['per_page', 'search', 'status', 'date_filter', 'from_date', 'to_date']),
            'stats'        => Inertia::defer(fn() => [
                'total_pending'  => LoanApplication::where('status', 'pending')->count(),
                'total_approved' => LoanApplication::where('status', 'approved')->count(),
                'total_rejected' => LoanApplication::where('status', 'rejected')->count(),
                'total_amount'   => LoanApplication::where('status', 'approved')->sum('amount'),
            ]),
        ]);
    }

    public function show(LoanApplication $loanApplication): Response|JsonResponse|RedirectResponse
    {
        try {
            $loanApplication->load(['user', 'loanType', 'approvedBy']);

            return $this->respond('Admin/LoanApplications/Show', [
                'application' => [
                    'id'               => $loanApplication->id,
                    'member_name'      => $loanApplication->user->name,
                    'member_id'        => $loanApplication->user->member_id,
                    'member_email'     => $loanApplication->user->email,
                    'member_phone'     => $loanApplication->user->phone,
                    'loan_type'        => $loanApplication->loanType?->name ?? '—',
                    'amount'           => $loanApplication->amount,
                    'duration_months'  => $loanApplication->duration_months,
                    'monthly_payment'  => $loanApplication->monthly_payment,
                    'total_payment'    => $loanApplication->total_payment,
                    'interest_rate'    => $loanApplication->interest_rate,
                    'purpose'          => $loanApplication->purpose,
                    'status'           => $loanApplication->status,
                    'rejection_reason' => $loanApplication->rejection_reason,
                    'approved_by'      => $loanApplication->approvedBy?->name,
                    'approved_at'      => $loanApplication->approved_at?->format('M d, Y h:i A'),
                    'created_at'       => $loanApplication->created_at->format('M d, Y h:i A'),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan application.');
        }
    }

    public function approve(Request $request, LoanApplication $loanApplication)
    {
        if ($loanApplication->status !== 'pending') {
            return $this->respondSingleError('This application has already been processed.');
        }

        $loanApplication->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $interestRate   = 10;
        $totalRepayable = round($loanApplication->amount + ($loanApplication->amount * 0.10), 2);
        $monthlyPayment = round($totalRepayable / $loanApplication->duration_months, 2);

        DB::transaction(function () use ($loanApplication, $interestRate, $monthlyPayment, $totalRepayable) {
            $loanPlan = LoanPlan::create([
                'user_id'             => $loanApplication->user_id,
                'loan_type_id'        => $loanApplication->loan_type_id,
                'loan_amount'         => $loanApplication->amount,
                'interest_rate'       => $interestRate,
                'repayment_per_month' => $monthlyPayment,
                'total_months'        => $loanApplication->duration_months,
                'months_remaining'    => $loanApplication->duration_months,
                'amount_remaining'    => $totalRepayable,
                'start_date'          => now(),
                'next_due_date'       => now()->startOfMonth(),
                'status'              => 'active',
                'notes'               => $loanApplication->purpose,
            ]);

            $deductionDate = now()->startOfMonth(); // start THIS month, not next

            for ($i = 0; $i < $loanApplication->duration_months; $i++) {
                [$year, $month] = $this->resolveYearMonth($deductionDate);

                MonthlyRepayment::create([
                    'user_id'      => $loanApplication->user_id,
                    'loan_plan_id' => $loanPlan->id,
                    'year_id'      => $year->id,
                    'month_id'     => $month->id,
                    'month'        => $deductionDate->format('Y-m'),
                    'amount_due'   => $monthlyPayment,
                    'status'       => 'unpaid',
                ]);

                $deductionDate = $deductionDate->copy()->addMonth()->startOfMonth();
            }
        });

        return $this->respondSuccess('Loan application approved and loan plan created.');
    }

    /**
     * Resolve or create Year and Month records for a given date.
     * Returns [$year, $month]
     */
    private function resolveYearMonth(\Carbon\Carbon $date): array
    {
        $year = Year::firstOrCreate(
            ['year' => $date->year],
            ['is_active' => true]
        );

        $month = Month::firstOrCreate(
            ['year_id' => $year->id, 'number' => $date->month],
            [
                'name'      => $date->format('F'), // e.g. "July"
                'is_active' => true,
            ]
        );

        return [$year, $month];
    }

    public function reject(Request $request, LoanApplication $loanApplication)
    {
        try {
            if ($loanApplication->status !== 'pending') {
                return $this->respondSingleError('This application has already been processed.');
            }

            $request->validate([
                'rejection_reason' => ['required', 'string', 'max:1000'],
            ]);

            $loanApplication->update([
                'status'           => 'rejected',
                'approved_by'      => Auth::id(),
                'approved_at'      => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            return $this->respondSuccess('Loan application rejected.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to reject loan application.');
        }
    }
}
