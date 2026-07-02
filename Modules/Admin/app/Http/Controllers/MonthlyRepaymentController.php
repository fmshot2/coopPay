<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Modules\Payment\Models\MonthlyDeduction;
use Modules\Loan\Models\LoanPlan;
use Modules\Admin\Imports\MembersImport;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Admin\Models\Year;
use Modules\Admin\Models\Month;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Imports\DeductionImport;
use Modules\Loan\Models\MonthlyRepayment;

class   MonthlyRepaymentController extends Controller
{
    use RespondsWithJson;
    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $perPage    = $request->input('per_page', 15);
            $search     = $request->input('search');
            $status     = $request->input('status', 'all');
            $dateFilter = $request->input('date_filter', 'all');
            $fromDate   = $request->input('from_date');
            $toDate     = $request->input('to_date');

            $applyDateRange = function ($query) use ($dateFilter, $fromDate, $toDate) {
                return $query->when($dateFilter !== 'all', function ($q) use ($dateFilter, $fromDate, $toDate) {
                    match ($dateFilter) {
                        'today'       => $q->whereDate('created_at', Carbon::today()),
                        'last_week'   => $q->whereBetween('created_at', [Carbon::now()->subWeek(), Carbon::now()]),
                        'last_month'  => $q->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()]),
                        'last_year'   => $q->whereBetween('created_at', [Carbon::now()->subYear(), Carbon::now()]),
                        'custom'      => $fromDate && $toDate
                            ? $q->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
                            : null,
                        default       => null,
                    };
                });
            };

            $query = MonthlyRepayment::with(['user', 'loanPlan.loanType', 'year', 'month'])
                ->when(
                    $search,
                    fn($q) =>
                    $q->whereHas(
                        'user',
                        fn($qu) =>
                        $qu->where('name', 'like', "%{$search}%")
                            ->orWhere('member_id', 'like', "%{$search}%")
                    )
                )
                ->when($status !== 'all', fn($q) => $q->where('status', $status));

            $repayments = $applyDateRange($query)
                ->latest()
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn($r) => [
                    'id'          => $r->id,
                    'member_name' => $r->user->name,
                    'member_id'   => $r->user->member_id,
                    'loan_type'   => $r->loanPlan?->loanType?->name ?? '—',
                    'month'       => $r->year->year . ' ' . $r->month->name, // e.g. "2025 July"
                    'amount_due'  => $r->amount_due,
                    'amount_paid' => $r->amount_paid,
                    'status'      => $r->status,
                    'narration'   => $r->narration,
                    'admin_note'  => $r->admin_note,
                    'approved_at' => $r->approved_at?->format('M d, Y'),
                    'created_at'  => $r->created_at->format('M d, Y'),
                ]);

            return $this->respond('Admin/LoanRepaymentSchedule/Index', [
                'repayments' => $repayments,
                'filters'    => $request->only(['per_page', 'search', 'status', 'date_filter', 'from_date', 'to_date']),
                'stats'      => Inertia::defer(fn() => [
                    'total_due'       => MonthlyRepayment::where('status', 'unpaid')->sum('amount_due'),
                    'total_paid'      => MonthlyRepayment::where('status', 'fully_paid')->sum('amount_paid'),
                    'total_partly'    => MonthlyRepayment::where('status', 'partly_paid')->count(),
                    'total_unpaid'    => MonthlyRepayment::where('status', 'unpaid')->count(),
                ]),
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load repayment schedule.');
        }
    }
}
