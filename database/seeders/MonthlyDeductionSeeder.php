<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Modules\Loan\Models\LoanPlan;
use Modules\Payment\Models\MonthlyDeduction;
use Carbon\Carbon;

class MonthlyDeductionSeeder extends Seeder
{
    public function run(): void
    {
        $members = User::role('member')->get();

        foreach ($members as $member) {
            $loanPlan = LoanPlan::where('user_id', $member->id)->first();

            if (!$loanPlan) continue;

            // Generate 4 months of deductions (matching loan started 4 months ago)
            for ($i = 4; $i >= 1; $i--) {
                $month = Carbon::now()->subMonths($i)->format('Y-m');

                // last month is still pending (member hasnt confirmed yet)
                $isLastMonth = $i === 1;

                MonthlyDeduction::create([
                    'user_id'         => $member->id,
                    'loan_plan_id'    => $loanPlan->id,
                    'month'           => $month,
                    'expected_amount' => $loanPlan->repayment_per_month,
                    'status'          => $isLastMonth ? 'pending' : 'approved',
                    'confirmed_at'    => $isLastMonth ? null : Carbon::now()->subMonths($i)->day(5),
                    'approved_by'     => $isLastMonth ? null : 1, // admin user id
                    'approved_at'     => $isLastMonth ? null : Carbon::now()->subMonths($i)->day(7),
                    'member_note'     => null,
                    'admin_note'      => null,
                ]);
            }
        }
    }
}
