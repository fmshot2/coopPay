<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Admin\Imports\MembersImport;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;


use Modules\Loan\Models\LoanPlan;
use App\Models\LoanApplication;
use Modules\Division\Models\Division;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 5);
        $search = $request->input('search');
        $status = $request->input('status');
        $password = $request->input('password');
        $loan = $request->input('loan');
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

        $membersQuery = User::role('member')
            ->with(['loanPlan', 'roles', 'division'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('member_id', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== 'all', function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->when($password !== null && $password !== 'all', function ($query) use ($password) {
                $query->where('must_change_password', $password === 'must_change');
            })
            ->when($loan !== null && $loan !== 'all', function ($query) use ($loan) {
                if ($loan === 'no loan') {
                    $query->whereDoesntHave('loanPlans');
                } else {
                    $query->whereHas('loanPlans', function ($q) use ($loan) {
                        $q->where('status', $loan);
                    });
                }
            });

        $members = $applyDateRange($membersQuery)
            ->with(['division'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($user) => [
                'id'                   => $user->id,
                'member_id'            => $user->member_id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'phone'                => $user->phone,
                'division_id'           => $user->division_id,
                'division_name'        => $user->division?->name,
                'is_active'            => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'roles'                => $user->roles->pluck('name'),
                'loan_status'          => $user->loanPlan?->status ?? 'no loan',
                'created_at'           => $user->created_at->format('M d, Y'),
            ]);

        return Inertia::render('Admin/Members/Index', [
            'members' => $members,
            'filters' => $request->only(['per_page', 'search', 'status', 'password', 'loan', 'date_filter', 'from_date', 'to_date']),
            'stats'   => Inertia::defer(fn() => [
                'total_members'    => $applyDateRange(User::role('member'))->count(),
                'active_loans'     => $applyDateRange(LoanPlan::where('status', 'active'))->count(),
                'completed_loans'  => $applyDateRange(LoanPlan::where('status', 'completed'))->count(),
                'unapproved_loans' => $applyDateRange(LoanApplication::query())->count(),
            ]),
        ]);
    }

    public function create(): Response
    {
        $roles = Role::all()->pluck('name');
        $permissions = Permission::all()->pluck('name');
        $divisions = Division::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Members/Create', [
            'roles' => $roles,
            'permissions' => $permissions,
            'divisions' => $divisions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['nullable', 'email', 'unique:users,email'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'member_id'   => ['required', 'string', 'unique:users,member_id'],
            'division_id' => ['required', 'exists:divisions,id'],
            'roles'       => ['nullable', 'array'],
            'roles.*'     => ['exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $user = User::create([
            'member_id'            => $request->member_id,
            'name'                 => $request->name,
            'email'                => $request->email,
            'phone'                => $request->phone,
            'password'             => Hash::make('password'),
            'is_active'            => true,
            'must_change_password' => true,
            'division_id'          => $request->division_id,
        ]);

        // Assign roles
        if ($request->has('roles')) {
            $user->assignRole($request->roles);
        } else {
            $user->assignRole('member');
        }

        // Assign permissions
        if ($request->has('permissions')) {
            $user->givePermissionTo($request->permissions);
        }

        return redirect()
            ->route('admin.members.index')
            ->with('success', "Member {$user->name} created successfully. Default password is 'password'.");
    }

    public function show(User $user): Response
    {
        $user->load('loanPlans.loanType', 'roles', 'permissions', 'division');

        $activeLoans = $user->loanPlans->where('status', 'active');

        // Build upcoming deductions
        $upcoming = [];
        foreach ($activeLoans as $loan) {
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

        usort($upcoming, fn($a, $b) => strcmp($a['month'], $b['month']));

        return Inertia::render('Admin/Members/Show', [
            'member' => [
                'id'                   => $user->id,
                'member_id'            => $user->member_id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'phone'                => $user->phone,
                'division_id'          => $user->division_id,
                'division_name'        => $user->division?->name,
                'max_loan_amount'     => $user->max_loan_amount,
                'is_active'            => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'roles'                => $user->roles->pluck('name'),
                'permissions'          => $user->permissions->pluck('name'),
                'created_at'           => $user->created_at->format('M d, Y'),
                'loan_plans'           => $user->loanPlans->map(fn($loan) => [
                    'id'                  => $loan->id,
                    'loan_type'           => $loan->loanType?->name ?? '—',
                    'loan_amount'         => $loan->loan_amount,
                    'repayment_per_month' => $loan->repayment_per_month,
                    'total_months'        => $loan->total_months,
                    'months_remaining'    => $loan->months_remaining,
                    'amount_remaining'    => $loan->amount_remaining,
                    'next_due_date'       => $loan->next_due_date?->format('M d, Y'),
                    'status'              => $loan->status,
                    'start_date'          => $loan->start_date?->format('M d, Y'),
                ]),
            ],
            'upcoming' => $upcoming,
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load('roles', 'permissions', 'division');
        $roles = Role::all()->pluck('name');
        $permissions = Permission::all()->pluck('name');
        $divisions = Division::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Members/Edit', [
            'member' => [
                'id'         => $user->id,
                'member_id'  => $user->member_id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'is_active'  => $user->is_active,
                'division_id' => $user->division_id,
                'roles'      => $user->roles->pluck('name'),
                'permissions' => $user->permissions->pluck('name'),
            ],
            'roles' => $roles,
            'permissions' => $permissions,
            'divisions' => $divisions,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone'       => ['nullable', 'string', 'max:20'],
            'member_id'   => ['nullable', 'string', 'unique:users,member_id,' . $user->id],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'is_active'   => ['boolean'],
            'roles'       => ['nullable', 'array'],
            'roles.*'     => ['exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $user->update([
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'member_id'   => $request->member_id,
            'is_active'   => $request->boolean('is_active', $user->is_active),
            'division_id' => $request->division_id,
        ]);

        // Sync roles
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        // Sync permissions
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        } else {
            $user->syncPermissions([]);
        }

        return redirect()
            ->route('admin.members.show', $user->id)
            ->with('success', "Member {$user->name} updated successfully.");
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Member {$user->name} has been {$status}.");
    }

    public function updateMaxLoan(Request $request, User $user)
    {
        $request->validate([
            'max_loan_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user->update([
            'max_loan_amount' => $request->max_loan_amount ?: null,
        ]);

        return back()->with('success', "Maximum loan amount updated for {$user->name}.");
    }

    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('password'),
            'must_change_password' => true,
        ]);

        return back()->with('success', "Password for member {$user->name} has been reset to 'password'.");
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="members_template.csv"',
        ];

        $columns = ['name', 'email', 'phone', 'member_id'];
        $sample  = ['John Doe', 'john@example.com', '08012345678', 'COOP-020'];

        $callback = function () use ($columns, $sample) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $sample);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:2048'],
        ]);

        $import = new MembersImport();
        Excel::import($import, $request->file('file'));

        $message = "{$import->created} member(s) imported successfully.";
        if ($import->skipped > 0) {
            $message .= " {$import->skipped} row(s) skipped.";
        }

        return redirect()
            ->route('admin.members.index')
            ->with('success', $message)
            ->with('import_errors', $import->errors);
    }

    private function generateMemberId(): string
    {
        $last = User::whereNotNull('member_id')
            ->orderByDesc('member_id')
            ->value('member_id');

        if (!$last) return 'COOP-001';

        $number = intval(substr($last, 5)) + 1;
        return 'COOP-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}
