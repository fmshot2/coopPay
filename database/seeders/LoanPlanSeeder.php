<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Modules\Loan\Models\LoanPlan;
use Carbon\Carbon;
use Modules\Loan\Models\LoanType;

class LoanPlanSeeder extends Seeder
{
    public function run(): void
    {
        $members = User::role('member')->get();

        $loanData = [
            ['loan_amount' => 500000,  'repayment_per_month' => 25000,  'total_months' => 24],
            ['loan_amount' => 300000,  'repayment_per_month' => 15000,  'total_months' => 24],
            ['loan_amount' => 750000,  'repayment_per_month' => 37500,  'total_months' => 24],
            ['loan_amount' => 200000,  'repayment_per_month' => 20000,  'total_months' => 12],
            ['loan_amount' => 1000000, 'repayment_per_month' => 50000,  'total_months' => 24],
            ['loan_amount' => 450000,  'repayment_per_month' => 22500,  'total_months' => 24],
            ['loan_amount' => 600000,  'repayment_per_month' => 30000,  'total_months' => 24],
            ['loan_amount' => 350000,  'repayment_per_month' => 17500,  'total_months' => 24],
            ['loan_amount' => 800000,  'repayment_per_month' => 40000,  'total_months' => 24],
            ['loan_amount' => 250000,  'repayment_per_month' => 25000,  'total_months' => 12],
        ];

        foreach ($members as $index => $member) {
            $data        = $loanData[$index];
            $startDate   = Carbon::now()->subMonths(4); // started 4 months ago
            $monthsPaid  = 4;
            $monthsRemaining = $data['total_months'] - $monthsPaid;
            $amountRemaining = $monthsRemaining * $data['repayment_per_month'];

            LoanPlan::create([
                'user_id'              => $member->id,
                'loan_amount'          => $data['loan_amount'],
                'interest_rate'        => 0,
                'repayment_per_month'  => $data['repayment_per_month'],
                'total_months'         => $data['total_months'],
                'months_remaining'     => $monthsRemaining,
                'amount_remaining'     => $amountRemaining,
                'start_date'           => $startDate,
                'next_due_date'        => Carbon::now()->startOfMonth()->addMonth(),
                'status'               => 'active',
                'notes'                => null,
                'loan_type_id' => LoanType::inRandomOrder()->first()->id,
            ]);
        }
    }
}
