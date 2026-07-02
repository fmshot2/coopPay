<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Http\Services\FuzzyNameMatcher;
use App\Models\LoanApplication;
use App\Models\User;
use Modules\Loan\Models\LoanPlan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Loan\Services\LoanApplicationService;


use Maatwebsite\Excel\Facades\Excel;
use App\Rules\ValidMemberName;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Imports\MembersImport;
use Modules\Admin\Models\ImportConflict;
use Modules\Admin\Models\Year;
use Modules\Admin\Models\Month;
use Modules\Division\Models\Division;
use Modules\Loan\Models\MonthlyRepayment;

class LoanPlanController extends Controller
{
    use RespondsWithJson;
    public LoanApplicationService $loanApplicationService;

    public function __construct(LoanApplicationService $loanApplicationService)
    {
        $this->loanApplicationService = $loanApplicationService;
    }
    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $perPage = $request->input('per_page', 5);
            $search = $request->input('search');
            $status = $request->input('status', 'all');
            $loanType = $request->input('loan_type', 'all');
            $dateFilter = $request->input('date_filter', 'all');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $applyDateRange = function ($query) use ($dateFilter, $fromDate, $toDate) {
                return $query->when($dateFilter !== 'all', function ($q) use ($dateFilter, $fromDate, $toDate) {
                    if ($dateFilter === 'today') {
                        return $q->whereDate('created_at', Carbon::today());
                    } elseif ($dateFilter === 'last_week') {
                        return $q->whereBetween('created_at', [Carbon::now()->subWeek(), Carbon::now()]);
                    } elseif ($dateFilter === 'last_month') {
                        return $q->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()]);
                    } elseif ($dateFilter === 'last_year') {
                        return $q->whereBetween('created_at', [Carbon::now()->subYear(), Carbon::now()]);
                    } elseif ($dateFilter === 'custom' && $fromDate && $toDate) {
                        return $q->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()]);
                    }
                });
            };

            $query = LoanPlan::with(['user', 'loanType'])
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('user', function ($qu) use ($search) {
                            $qu->where('name', 'like', "%{$search}%")
                                ->orWhere('member_id', 'like', "%{$search}%");
                        });
                    });
                })
                ->when($status !== 'all', function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($loanType !== 'all', function ($query) use ($loanType) {
                    $query->where('loan_type_id', $loanType);
                });

            $loans = $applyDateRange($query)
                ->latest()
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn($loan) => [
                    'id'                  => $loan->id,
                    'member_name'         => $loan->user->name,
                    'member_id'           => $loan->user->member_id,
                    'user_id'             => $loan->user_id,
                    'loan_type'           => $loan->loanType?->name ?? '—',
                    'loan_amount'         => $loan->loan_amount,
                    'repayment_per_month' => $loan->repayment_per_month,
                    'total_months'        => $loan->total_months,
                    'months_remaining'    => $loan->months_remaining,
                    'amount_remaining'    => $loan->amount_remaining,
                    'next_due_date'       => $loan->next_due_date?->format('M d, Y'),
                    'status'              => $loan->status,
                    'start_date'          => $loan->start_date?->format('M d, Y'),
                ]);

            $loanTypes = \Modules\Loan\Models\LoanType::all()->map(fn($type) => [
                'id'   => $type->id,
                'name' => $type->name,
            ]);

            return $this->respond('Admin/Loans/Index', [
                'loans'     => $loans,
                'loanTypes' => $loanTypes,
                'filters'   => $request->only(['per_page', 'search', 'status', 'loan_type', 'date_filter', 'from_date', 'to_date']),
                'stats'     => Inertia::defer(fn() => [
                    'total_loans'      => $applyDateRange(LoanPlan::query())->count(),
                    'active_loans'     => $applyDateRange(LoanPlan::where('status', 'active'))->count(),
                    'completed_loans'  => $applyDateRange(LoanPlan::where('status', 'completed'))->count(),
                    'total_disbursed'  => $applyDateRange(LoanPlan::query())->sum('loan_amount'),
                ]),
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan plans.');
        }
    }

    public function loan_applications(Request $request)
    {
        try {
            $query = LoanApplication::with(['user', 'loanType']);

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $perPage = $request->input('per_page', 15);
            $applications = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->respond('Admin/LoanApplications/Index', [
                'applications' => $applications,
                'filters' => [
                    'status' => $request->status ?? 'all',
                    'per_page' => $request->per_page ?? 15,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan applications.');
        }
    }

    public function create(): Response|JsonResponse|RedirectResponse
    {
        try {
            $members = User::role('member')
                ->get()
                ->map(fn($user) => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'member_id' => $user->member_id,
                ]);

            $loanTypes = \Modules\Loan\Models\LoanType::where('is_active', true)
                ->get()
                ->map(fn($type) => [
                    'id'   => $type->id,
                    'name' => $type->name,
                ]);

            return $this->respond('Admin/Loans/Create', [
                'members'   => $members,
                'loanTypes' => $loanTypes,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan creation form.');
        }
    }

    public function store(Request $request)
    {
        $transaction = DB::transaction(function () use ($request) {
            $request->validate([
                'user_id'       => ['required', 'exists:users,id'],
                'loan_type_id'  => ['required', 'exists:loan_types,id'],
                'loan_amount'   => ['required', 'numeric', 'min:1'],
                'total_months'  => ['required', 'integer', 'min:1'],
                'start_date'    => ['required', 'date'],
                'notes'         => ['nullable', 'string'],
            ]);

            $interestRate   = 0.10;
            $totalRepayable = $request->loan_amount + ($request->loan_amount * $interestRate);
            $monthlyPayment = round($totalRepayable / $request->total_months, 2);
            $startDate      = Carbon::parse($request->start_date);
            $nextDueDate    = $startDate->copy()->addMonth()->startOfMonth();

            $loanApplicationData = [
                'loan_type_id'    => $request->loan_type_id,
                'amount'          => $request->loan_amount,
                'duration_months' => $request->total_months,
                'purpose'         => $request->notes ?? null,
            ];

            $loanApplication = $this->loanApplicationService->computeAndCreate($request->user_id, $loanApplicationData, 'admin');
            try {
                $request->validate([
                    'user_id'       => ['required', 'exists:users,id'],
                    'loan_type_id'  => ['required', 'exists:loan_types,id'],
                    'loan_amount'   => ['required', 'numeric', 'min:1'],
                    'total_months'  => ['required', 'integer', 'min:1'],
                    'start_date'    => ['required', 'date'],
                    'notes'         => ['nullable', 'string'],
                ]);

                $interestRate   = 0.10;
                $totalRepayable = $request->loan_amount + ($request->loan_amount * $interestRate);
                $monthlyPayment = round($totalRepayable / $request->total_months, 2);
                $startDate      = Carbon::parse($request->start_date);
                $deductionDate    = $startDate->copy()->startOfMonth();
                // $deductionDate = now()->startOfMonth(); // start THIS month, not next

                $loanPlan = LoanPlan::create([
                    'user_id'             => $request->user_id,
                    'loan_type_id'        => $request->loan_type_id,
                    'loan_amount'         => $request->loan_amount,
                    'interest_rate'       => 10,
                    'repayment_per_month' => $monthlyPayment,
                    'total_months'        => $request->total_months,
                    'months_remaining'    => $request->total_months,
                    'amount_remaining'    => $totalRepayable,
                    'start_date'          => $startDate,
                    'next_due_date'       => $deductionDate,
                    'status'              => 'active',
                    'notes'               => $request->notes,
                ]);

                //create monthly repayment schedule for each loan:
                for ($i = 0; $i < $loanApplication->duration_months; $i++) {
                    [$year, $month] = $this->resolveYearMonth($deductionDate);
                    MonthlyRepayment::create([
                        'user_id'      => $request->user_id,
                        'loan_plan_id' => $loanPlan->id,
                        'year_id'      => $year->id,
                        'month_id'     => $month->id,
                        'month'        => $nextDueDate->format('Y-m'),
                        'amount_due'   => $monthlyPayment,
                        'status'       => 'unpaid',
                    ]);
                    $deductionDate = $deductionDate->copy()->addMonth()->startOfMonth();
                }

                return $this->respondSuccess('Loan plan created successfully.');
            } catch (\Throwable $e) {
                return $this->respondException($e, 'Failed to create loan plan.');
            }
        });
    }


    /**
     * Resolve or create Year and Month records for a given date.
     * Returns [$year, $month]
     */
    private function resolveYearMonth(Carbon $date): array
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

    public function edit(LoanPlan $loan): Response|JsonResponse|RedirectResponse
    {
        try {
            $loan->load('user');

            return $this->respond('Admin/Loans/Edit', [
                'loan' => [
                    'id'                  => $loan->id,
                    'user_id'             => $loan->user_id,
                    'member_name'         => $loan->user->name,
                    'member_id'           => $loan->user->member_id,
                    'loan_amount'         => $loan->loan_amount,
                    'interest_rate'       => $loan->interest_rate,
                    'repayment_per_month' => $loan->repayment_per_month,
                    'total_months'        => $loan->total_months,
                    'months_remaining'    => $loan->months_remaining,
                    'amount_remaining'    => $loan->amount_remaining,
                    'start_date'          => $loan->start_date?->format('Y-m-d'),
                    'next_due_date'       => $loan->next_due_date?->format('Y-m-d'),
                    'status'              => $loan->status,
                    'notes'               => $loan->notes,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan for editing.');
        }
    }

    public function update(Request $request, LoanPlan $loan)
    {
        try {
            $request->validate([
                'loan_amount'          => ['required', 'numeric', 'min:1'],
                'repayment_per_month'  => ['required', 'numeric', 'min:1'],
                'total_months'         => ['required', 'integer', 'min:1'],
                'months_remaining'     => ['required', 'integer', 'min:0'],
                'start_date'           => ['required', 'date'],
                'next_due_date'        => ['nullable', 'date'],
                'interest_rate'        => ['nullable', 'numeric', 'min:0'],
                'status'               => ['required', 'in:active,completed,suspended'],
                'notes'                => ['nullable', 'string'],
            ]);

            $amountRemaining = $request->repayment_per_month * $request->months_remaining;

            $loan->update([
                'loan_amount'          => $request->loan_amount,
                'interest_rate'        => $request->interest_rate ?? 0,
                'repayment_per_month'  => $request->repayment_per_month,
                'total_months'         => $request->total_months,
                'months_remaining'     => $request->months_remaining,
                'amount_remaining'     => $amountRemaining,
                'start_date'           => $request->start_date,
                'next_due_date'        => $request->next_due_date,
                'status'               => $request->status,
                'notes'                => $request->notes,
            ]);

            return $this->respondSuccess('Loan plan updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update loan plan.');
        }
    }

    // mark loan as complete
    public function markComplete(LoanPlan $loan)
    {
        try {
            $loan->update([
                'status'           => 'completed',
                'months_remaining' => 0,
                'amount_remaining' => 0,
                'notes'            => ($loan->notes ? $loan->notes . ' | ' : '') .
                    'Marked completed by admin on ' . now()->format('M d, Y'),
            ]);

            return $this->respondSuccess('Loan marked as completed.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to mark loan as completed.');
        }
    }

    public function cancel(LoanPlan $loan)
    {
        try {
            $loan->update([
                'status' => 'suspended',
                'notes'  => ($loan->notes ? $loan->notes . ' | ' : '') .
                    'Cancelled by admin on ' . now()->format('M d, Y'),
            ]);

            return $this->respondSuccess('Loan cancelled successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to cancel loan.');
        }
    }

    public function showImport(): Response|JsonResponse|RedirectResponse
    {
        try {
            return $this->respond('Admin/Loans/Import');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load member import page.');
        }
    }

    public function importCsv(Request $request)
    {
        try {
            $request->validate([
                'file' => ['required', 'file', 'mimes:csv', 'max:2048'],
            ]);

            $filePath = $request->file('file')->getRealPath();
            $handle   = fopen($filePath, 'r');

            if (!$handle) {
                throw new \Exception('Unable to read uploaded file.');
            }

            $matcher    = new FuzzyNameMatcher();
            $allUsers   = User::get(['id', 'name']); // load once, reuse per row
            $processed  = 0;
            $skipped    = 0;
            $conflicts  = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) continue;

                $name         = trim($row[0]);
                $divisionName = trim($row[1]);

                if ($name === '' || $divisionName === '') continue;

                $division = Division::whereRaw('LOWER(name) = ?', [strtolower($divisionName)])->first();
                if (!$division) {
                    $division = Division::create(['name' => $divisionName, 'is_active' => true]);
                }

                $matches = $matcher->findMatches($name, $allUsers);

                if ($matches->isNotEmpty()) {
                    $alreadyLogged = ImportConflict::where('name', $name)
                        ->where('division_id', $division->id)
                        ->where('source', 'member_import')
                        ->where('status', 'pending')
                        ->exists();

                    if (!$alreadyLogged) {
                        ImportConflict::create([
                            'name'                 => $name,
                            'division_id'          => $division->id,
                            'importable_type'      => 'member_import',
                            'importable_id'        => null,
                            'source'               => 'member_import',
                            'conflicting_user_ids' => $matches->pluck('id')->toArray(),
                            'status'               => 'pending',
                        ]);
                        $conflicts++;
                    }

                    $skipped++;
                    continue;
                }

                $user = User::create([
                    'member_id'            => $this->generateMemberId(),
                    'name'                 => $name,
                    'email'                => null,
                    'phone'                => null,
                    'password'             => Hash::make('password'),
                    'is_active'            => true,
                    'must_change_password' => true,
                    'division_id'          => $division->id,
                    'is_temporary'         => true,
                ]);

                $user->assignRole('member');

                // Add newly created user to in-memory collection
                // so subsequent rows in the same CSV can match against them
                $allUsers->push((object)['id' => $user->id, 'name' => $user->name]);

                $processed++;
            }

            fclose($handle);

            $message = "{$processed} member(s) imported successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) flagged — {$conflicts} new conflict record(s) saved for review.";
            }

            return redirect()
                ->route('admin.members.index')
                ->with('success', $message);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to import members.');
        }
    }

    // public function import(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'file'     => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:2048'],
    //             // 'year_id'  => ['required', 'exists:years,id'],
    //             // 'month_id' => ['required', 'exists:months,id'],
    //         ]);

    //         // Month::where('id', $request->month_id)
    //         //     ->where('year_id', $request->year_id)
    //         //     ->firstOrFail();

    //         $import = new MembersImport($request->year_id, $request->month_id);
    //         Excel::import($import, $request->file('file'));

    //         $message = "{$import->processed} member(s) processed successfully.";
    //         if ($import->skipped > 0) {
    //             $message .= " {$import->skipped} row(s) skipped.";
    //         }

    //         return redirect()
    //             ->route('admin.members.index')
    //             ->with('success', $message)
    //             ->with('import_errors', $import->errors);
    //     } catch (\Throwable $e) {
    //         return $this->respondException($e, 'Failed to import members.');
    //     }
    // }
}
