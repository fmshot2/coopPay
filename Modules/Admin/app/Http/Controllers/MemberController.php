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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;


class MemberController extends Controller
{
    public function index(): Response
    {
        $members = User::role('member')
            ->with('loanPlan')
            ->latest()
            ->get()
            ->map(fn($user) => [
                'id'          => $user->id,
                'member_id'   => $user->member_id,
                'name'        => $user->name,
                'email'       => $user->email,
                'phone'       => $user->phone,
                'is_active'   => $user->is_active,
                'loan_status' => $user->loanPlan?->status ?? 'no loan',
                'created_at'  => $user->created_at->format('M d, Y'),
            ]);

        return Inertia::render('Admin/Members/Index', [
            'members' => $members,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Members/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'member_id' => ['nullable', 'string', 'unique:users,member_id'],
        ]);

        $memberId = $request->member_id ?? $this->generateMemberId();

        $user = User::create([
            'member_id'            => $memberId,
            'name'                 => $request->name,
            'email'                => $request->email,
            'phone'                => $request->phone,
            'password'             => Hash::make('password'),
            'is_active'            => true,
            'must_change_password' => true,
        ]);

        $user->assignRole('member');

        return redirect()
            ->route('admin.members.index')
            ->with('success', "Member {$user->name} created successfully. Default password is 'password'.");
    }

    public function show(User $user): Response
    {
        $user->load('loanPlans.loanType');

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
                'is_active'            => $user->is_active,
                'must_change_password' => $user->must_change_password,
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

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Member {$user->name} has been {$status}.");
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
