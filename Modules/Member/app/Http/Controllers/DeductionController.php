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
    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();
            $search = $request->input('search');
            $status = $request->input('status', 'all');
            $dateFilter = $request->input('date_filter', 'all');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $query = MonthlyDeduction::where('user_id', $user->id)
                ->with('loanPlan.loanType');

            // Apply status filter
            if ($status !== 'all') {
                if ($status === 'pending') {
                    $query->whereNull('confirmed_at');
                } else {
                    $query->whereNotNull('confirmed_at')
                        ->where('status', $status);
                }
            }

            // Apply search filter (search by loan type)
            if ($search) {
                $query->whereHas('loanPlan.loanType', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            // Apply date range filter
            if ($dateFilter !== 'all' || $fromDate || $toDate) {
                $now = Carbon::now();

                if ($dateFilter === 'today') {
                    $query->whereDate('month', $now->toDateString());
                } elseif ($dateFilter === 'last_week') {
                    $query->where('month', '>=', $now->clone()->subWeek()->format('Y-m'));
                } elseif ($dateFilter === 'last_month') {
                    $query->where('month', '>=', $now->clone()->subMonth()->format('Y-m'));
                } elseif ($dateFilter === 'last_year') {
                    $query->where('month', '>=', $now->clone()->subYear()->format('Y-m'));
                } elseif ($dateFilter === 'custom') {
                    if ($fromDate) {
                        $query->where('month', '>=', Carbon::createFromFormat('Y-m-d', $fromDate)->format('Y-m'));
                    }
                    if ($toDate) {
                        $query->where('month', '<=', Carbon::createFromFormat('Y-m-d', $toDate)->format('Y-m'));
                    }
                }
            }

            $deductions = $query->orderBy('month', 'desc')
                ->get()
                ->map(fn($d) => [
                    'id'              => $d->id,
                    'month'           => $d->month,
                    'loan_type'       => $d->loanPlan?->loanType?->name ?? 'General',
                    'expected_amount' => $d->expected_amount,
                    'status'          => $d->status,
                    'member_note'     => $d->member_note,
                    'admin_note'      => $d->admin_note,
                    'confirmed_at'    => $d->confirmed_at?->format('M d, Y'),
                    'approved_at'     => $d->approved_at?->format('M d, Y'),
                ]);

            return $this->respond('Member/Deductions/Index', [
                'deductions' => $deductions,
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'date_filter' => $dateFilter,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load deductions.');
        }
    }

    public function confirm(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'monthly_deduction_id' => ['required', 'exists:monthly_deductions,id'],
                'member_note'          => ['nullable', 'string', 'max:500'],
            ]);

            $user = Auth::user();

            $deduction = MonthlyDeduction::where('id', $request->monthly_deduction_id)
                ->where('user_id', $user->id)
                ->whereNull('confirmed_at')
                ->firstOrFail();

            $deduction->update([
                'confirmed_at' => now(),
                'member_note'  => $request->member_note,
            ]);

            return $this->respondSuccess('Deduction confirmed successfully. Awaiting admin approval.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to confirm deduction.');
        }
    }
}
