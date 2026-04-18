<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Loan\Models\LoanPlan;

class LoanRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('member::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('member::create');
    }

    /**
     * Store a newly created resource in storage.
     */
     public function store(Request $request)
    {
        $request->validate([
            // 'user_id'       => ['required', 'exists:users,id'],
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

        LoanPlan::create([
            'user_id'             => request()->user_id,
            'loan_type_id'        => $request->loan_type_id,
            'loan_amount'         => $request->loan_amount,
            'interest_rate'       => 10,
            'repayment_per_month' => $monthlyPayment,
            'total_months'        => $request->total_months,
            'months_remaining'    => $request->total_months,
            'amount_remaining'    => $totalRepayable,
            'start_date'          => $startDate,
            'next_due_date'       => $nextDueDate,
            'status'              => 'active',
            'notes'               => $request->notes,
        ]);

        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Loan plan created successfully.');
    }
    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('member::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('member::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
