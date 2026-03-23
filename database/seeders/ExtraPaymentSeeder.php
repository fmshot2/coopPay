<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Modules\Loan\Models\LoanPlan;
use Modules\Contribution\Models\ExtraPayment;
use Carbon\Carbon;

class ExtraPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $members = User::role('member')->get();

        // Only first 4 members have made extra payments
        $membersWithExtraPayments = $members->take(4);

        foreach ($membersWithExtraPayments as $index => $member) {
            $loanPlan = LoanPlan::where('user_id', $member->id)->first();

            if (!$loanPlan) continue;

            // First 2 members have approved extra payments
            // Last 2 members have pending extra payments
            $isApproved = $index < 2;

            ExtraPayment::create([
                'user_id'         => $member->id,
                'loan_plan_id'    => $loanPlan->id,
                'amount'          => $loanPlan->repayment_per_month * 2, // paid 2 months ahead
                'narration'       => 'Direct bank transfer to cooperative account',
                'screenshot_path' => null, // no screenshot in seeder
                'status'          => $isApproved ? 'approved' : 'pending',
                'approved_by'     => $isApproved ? 1 : null,
                'approved_at'     => $isApproved ? Carbon::now()->subMonths(2) : null,
                'admin_note'      => $isApproved ? 'Confirmed via bank statement' : null,
            ]);
        }
    }
}
