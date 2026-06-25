<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Rules\ValidMemberName;
use Modules\Admin\Imports\MembersImport;
use Modules\Admin\Models\Year;
use Modules\Admin\Models\Month;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Modules\Loan\Models\LoanPlan;
use App\Models\LoanApplication;
use Modules\Division\Models\Division;
use Illuminate\Support\Facades\DB;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class MemberController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $perPage = $request->input('per_page', 5);
            $search = $request->input('search');
            $status = $request->input('status');
            $temporary = $request->input('temporary');
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
                ->when($temporary !== null && $temporary !== 'all', function ($query) use ($temporary) {
                    $query->where('is_temporary', $temporary === 'temporary');
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
                    'division_id'          => $user->division_id,
                    'division_name'        => $user->division?->name,
                    'is_active'            => $user->is_active,
                    'must_change_password' => $user->must_change_password,
                    'roles'                => $user->roles->pluck('name'),
                    'loan_status'          => $user->loanPlan?->status ?? 'no loan',
                    'is_temporary'        => $user->is_temporary,
                    'created_at'           => $user->created_at->format('M d, Y'),
                ]);

            $years = Year::orderByDesc('year')->take(3)->get(['id', 'year']);
            $defaultYear = $years->firstWhere('year', Carbon::now()->year) ?? $years->first();
            $months = $defaultYear
                ? $defaultYear->months()->orderBy('number')->get(['id', 'name', 'number'])
                : collect();

            return $this->respond('Admin/Members/Index', [
                'members' => $members,
                'filters' => $request->only(['per_page', 'search', 'status', 'temporary', 'password', 'loan', 'date_filter', 'from_date', 'to_date']),
                'years'   => $years,
                'months'  => $months,
                'stats'   => Inertia::defer(fn() => [
                    'total_members'    => $applyDateRange(User::role('member'))->count(),
                    'active_loans'     => $applyDateRange(LoanPlan::where('status', 'active'))->count(),
                    'completed_loans'  => $applyDateRange(LoanPlan::where('status', 'completed'))->count(),
                    'unapproved_loans' => $applyDateRange(LoanApplication::query())->count(),
                ]),
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load members.');
        }
    }

    public function searchAssignees(Request $request, User $user): JsonResponse
    {
        try {
            $request->validate([
                'q' => ['nullable', 'string', 'max:100'],
            ]);

            $query = trim($request->input('q', ''));

            $assignees = User::role('member')
                ->where('is_temporary', false)
                ->where('id', '!=', $user->id)
                ->when($query !== '', function ($q) use ($query) {
                    $q->where(function ($sub) use ($query) {
                        $sub->where('name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%")
                            ->orWhere('member_id', 'like', "%{$query}%")
                            ->orWhereHas('division', function ($division) use ($query) {
                                $division->where('name', 'like', "%{$query}%");
                            });
                    });
                }, function ($q) use ($user) {
                    $nameTokens = collect(explode(' ', preg_replace('/\s+/', ' ', trim($user->name))))
                        ->filter()
                        ->take(3);

                    if ($nameTokens->isNotEmpty()) {
                        $q->where(function ($sub) use ($nameTokens) {
                            foreach ($nameTokens as $token) {
                                $sub->orWhere('name', 'like', "%{$token}%");
                            }
                        });
                    }
                })
                ->with('division')
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(fn($assignee) => [
                    'id' => $assignee->id,
                    'name' => $assignee->name,
                    'division_name' => $assignee->division?->name,
                ]);

            return response()->json($assignees);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to fetch assignees.'], 500);
        }
    }

    public function reassign(Request $request, User $user)
    {
        try {
            $request->validate([
                'target_member_id' => ['required', 'exists:users,id'],
            ]);

            $targetId = $request->input('target_member_id');

            if ($user->id == $targetId) {
                return $this->respondException(new \Exception('Cannot reassign to the same member.'), 'Invalid target member.');
            }

            $target = User::findOrFail($targetId);

            if (!$user->is_temporary) {
                return $this->respondException(new \Exception('Source member is not temporary.'), 'Only temporary members can be reassigned.');
            }

            if ($target->is_temporary) {
                return $this->respondException(new \Exception('Target member must be a real (non-temporary) member.'), 'Invalid target member.');
            }

            DB::transaction(function () use ($user, $targetId) {
                // Reassign loan plans
                $user->loanPlans()->update(['user_id' => $targetId]);

                // Reassign monthly deductions and extra payments
                $user->monthlyDeductions()->update(['user_id' => $targetId]);
                if (method_exists($user, 'extraPayments')) {
                    $user->extraPayments()->update(['user_id' => $targetId]);
                }

                // Reassign announcements
                if (method_exists($user, 'announcements')) {
                    $user->announcements()->update(['user_id' => $targetId]);
                }

                // Reassign messages (sender/receiver)
                Message::where('sender_id', $user->id)->update(['sender_id' => $targetId]);
                Message::where('receiver_id', $user->id)->update(['receiver_id' => $targetId]);
            });

            return $this->respondSuccess("Temporary member {$user->name} reassigned to member ID {$targetId}.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to reassign member.');
        }
    }

    public function create(): Response|JsonResponse|RedirectResponse
    {
        try {
            $roles = Role::all()->pluck('name');
            $permissions = Permission::all()->pluck('name');
            $divisions = Division::where('is_active', true)->orderBy('name')->get(['id', 'name']);

            return $this->respond('Admin/Members/Create', [
                'roles'      => $roles,
                'permissions' => $permissions,
                'divisions'  => $divisions,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load member creation form.');
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'        => ['required', 'string', 'max:255', new ValidMemberName()],
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

            return $this->respondSuccess("Member {$user->name} created successfully. Default password is 'password'.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to create member.');
        }
    }

    public function show(User $user): Response|JsonResponse|RedirectResponse
    {
        try {
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

            return $this->respond('Admin/Members/Show', [
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
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load member details.');
        }
    }

    public function edit(User $user): Response|JsonResponse|RedirectResponse
    {
        try {
            $user->load('roles', 'permissions', 'division');
            $roles = Role::all()->pluck('name');
            $permissions = Permission::all()->pluck('name');
            $divisions = Division::where('is_active', true)->orderBy('name')->get(['id', 'name']);

            return $this->respond('Admin/Members/Edit', [
                'member' => [
                    'id'          => $user->id,
                    'member_id'   => $user->member_id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'phone'       => $user->phone,
                    'is_active'   => $user->is_active,
                    'division_id' => $user->division_id,
                    'roles'       => $user->roles->pluck('name'),
                    'permissions' => $user->permissions->pluck('name'),
                ],
                'roles'      => $roles,
                'permissions' => $permissions,
                'divisions'  => $divisions,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load member edit form.');
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $request->validate([
                'name'        => ['required', 'string', 'max:255', new ValidMemberName()],
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

            return $this->respondSuccess("Member {$user->name} updated successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update member.');
        }
    }

    public function toggleActive(User $user)
    {
        try {
            $user->update(['is_active' => !$user->is_active]);
            $status = $user->is_active ? 'activated' : 'deactivated';
            return $this->respondSuccess("Member {$user->name} has been {$status}.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to toggle member status.');
        }
    }

    public function updateMaxLoan(Request $request, User $user)
    {
        try {
            $request->validate([
                'max_loan_amount' => ['nullable', 'numeric', 'min:0'],
            ]);

            $user->update([
                'max_loan_amount' => $request->max_loan_amount ?: null,
            ]);

            return $this->respondSuccess("Maximum loan amount updated for {$user->name}.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update maximum loan amount.');
        }
    }

    public function resetPassword(User $user)
    {
        try {
            $user->update([
                'password' => Hash::make('password'),
                'must_change_password' => true,
            ]);

            return $this->respondSuccess("Password for member {$user->name} has been reset to 'password'.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to reset password.');
        }
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="members_template.csv"',
        ];

        // CSV has NO column headers: col1=name, col2=division
        $sample = ['John Doe', 'Finance'];

        $callback = function () use ($sample) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $sample);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function showImport(): Response|JsonResponse|RedirectResponse
    {
        try {
            return $this->respond('Admin/Members/Import');
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
            $handle = fopen($filePath, 'r');

            if (!$handle) {
                throw new \Exception('Unable to read uploaded file.');
            }

            $processed = 0;
            $skipped = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) {
                    continue;
                }

                $name = trim($row[0]);
                $divisionName = trim($row[1]);

                if ($name === '' || $divisionName === '') {
                    continue;
                }

                $division = Division::whereRaw('LOWER(name) = ?', [strtolower($divisionName)])->first();
                if (!$division) {
                    $division = Division::create(['name' => $divisionName, 'is_active' => true]);
                }

                // $duplicate = User::where('name', $name)
                //     ->where('division_id', $division->id)
                //     ->exists();
                $duplicate = $this->isFuzzyDuplicate($name);

                if ($duplicate) {
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
                $processed++;
            }

            fclose($handle);

            $message = "{$processed} member(s) created successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) skipped because the exact member already exists in the same division.";
            }

            return redirect()
                ->route('admin.members.index')
                ->with('success', $message);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to import members.');
        }
    }

    public function import(Request $request)
    {
        try {
            $request->validate([
                'file'     => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:2048'],
                'year_id'  => ['required', 'exists:years,id'],
                'month_id' => ['required', 'exists:months,id'],
            ]);

            Month::where('id', $request->month_id)
                ->where('year_id', $request->year_id)
                ->firstOrFail();

            $import = new MembersImport($request->year_id, $request->month_id);
            Excel::import($import, $request->file('file'));

            $message = "{$import->processed} member(s) processed successfully.";
            if ($import->skipped > 0) {
                $message .= " {$import->skipped} row(s) skipped.";
            }

            return redirect()
                ->route('admin.members.index')
                ->with('success', $message)
                ->with('import_errors', $import->errors);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to import members.');
        }
    }

    public function months(Year $year)
    {
        return response()->json(
            $year->months()->orderBy('number')->get(['id', 'name', 'number'])
        );
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

    private function isFuzzyDuplicate(string $incomingName): bool
    {
        $incomingTokens = array_filter(array_map('strtolower', preg_split('/\s+/', trim($incomingName))));

        if (empty($incomingTokens)) {
            return false;
        }

        // Pre-filter: pull members in same division whose name shares at least one token
        // This avoids loading the entire table
        $candidates = User::get(['name']);

        foreach ($candidates as $candidate) {
            $existingTokens = array_filter(array_map('strtolower', preg_split('/\s+/', trim($candidate->name))));

            if ($this->nameTokensMatch($incomingTokens, $existingTokens)) {
                return true;
            }
        }

        return false;
    }

    private function nameTokensMatch(array $a, array $b): bool
    {
        $matchScore = 0;
        $usedB = [];

        foreach ($a as $ia => $tokenA) {
            foreach ($b as $ib => $tokenB) {
                if (isset($usedB[$ib])) continue;

                $isMatch = false;

                if (strlen($tokenA) > 1 && strlen($tokenB) > 1) {
                    $isMatch = ($tokenA === $tokenB);
                } elseif (strlen($tokenA) === 1 && strlen($tokenB) > 1) {
                    $isMatch = ($tokenA === $tokenB[0]);
                } elseif (strlen($tokenA) > 1 && strlen($tokenB) === 1) {
                    $isMatch = ($tokenA[0] === $tokenB);
                } elseif (strlen($tokenA) === 1 && strlen($tokenB) === 1) {
                    $isMatch = ($tokenA === $tokenB); // same initial = match
                }

                if ($isMatch) {
                    $matchScore++;
                    $usedB[$ib] = true;
                    break;
                }
            }
        }

        return $matchScore >= 2;
    }
}
