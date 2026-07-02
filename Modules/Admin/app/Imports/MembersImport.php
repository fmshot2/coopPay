<?php

namespace Modules\Admin\Imports;

use App\Models\User;
use App\Rules\ValidMemberName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Modules\Division\Models\Division;
use Modules\Admin\Models\Year;
use Modules\Admin\Models\Month;
use Modules\Loan\Models\LoanPlan;
use Modules\Payment\Models\MonthlyDeduction;

class MembersImport implements ToCollection, SkipsEmptyRows
{
    public int $processed = 0;
    public int $skipped = 0;
    public array $errors = [];
    protected int $yearId;
    protected int $monthId;

    public function __construct(int $yearId, int $monthId)
    {
        $this->yearId = $yearId;
        $this->monthId = $monthId;
    }

    public function collection(Collection $rows)
    {
        $year = Year::findOrFail($this->yearId);
        $month = Month::where('id', $this->monthId)
            ->where('year_id', $this->yearId)
            ->firstOrFail();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            $name = trim($row[0] ?? '');
            $divisionName = trim($row[1] ?? '');
            $amount = trim($row[2] ?? '0');
            $email = trim($row[3] ?? '');
            $memberIdentifier = trim($row[4] ?? '');

            $amount = is_numeric($amount) ? (float) $amount : 0.00;

            if (empty($name)) {
                $this->errors[] = "Row {$rowNumber}: name is required.";
                $this->skipped++;
                continue;
            }

            if (!(new ValidMemberName())->passes('name', $name)) {
                $this->errors[] = "Row {$rowNumber}: {$name} is not a valid member name.";
                $this->skipped++;
                continue;
            }

            if (empty($divisionName)) {
                $this->errors[] = "Row {$rowNumber}: division is required.";
                $this->skipped++;
                continue;
            }

            $division = Division::whereRaw('LOWER(name) = ?', [mb_strtolower($divisionName)])->first();

            if (!$division) {
                $division = Division::create([
                    'name'        => $divisionName,
                    'description' => null,
                    'is_active'   => true,
                ]);
            }

            $user = null;
            if (!empty($email)) {
                $user = User::where('email', $email)->first();
            }

            if (!$user && !empty($memberIdentifier)) {
                $user = User::where('member_id', $memberIdentifier)->first();
            }

            if (!$user) {
                $user = User::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->where('division_id', $division->id)
                    ->first();
            }

            $isNewUser = false;
            if (!$user) {
                $user = User::create([
                    'member_id'            => $this->generateMemberId(),
                    'name'                 => $name,
                    'email'                => $email ?: null,
                    'phone'                => null,
                    'password'             => Hash::make('password'),
                    'is_active'            => true,
                    'must_change_password' => true,
                    'is_temporary'         => true,
                    'division_id'          => $division->id,
                ]);

                $user->assignRole('member');
                $this->processed++;
                $isNewUser = true;
            } else {
                $user->update([
                    'name'        => $name,
                    'email'       => $email ?: $user->email,
                    'division_id' => $division->id,
                ]);
                if (!$user->hasRole('member')) {
                    $user->assignRole('member');
                }
            }

            $loanPlan = $user->activeLoanPlans()->latest()->first() ?? $user->loanPlans()->latest()->first();
            if (!$loanPlan) {
                $this->errors[] = "Row {$rowNumber}: no loan plan found for member {$name}.";
                $this->skipped++;
                continue;
            }

            $monthValue = sprintf('%04d-%02d', $year->year, $month->number);

            $deduction = MonthlyDeduction::firstOrNew([
                'user_id'      => $user->id,
                'loan_plan_id' => $loanPlan->id,
                'month'        => $monthValue,
            ]);

            if (!$deduction->exists && !$deduction->expected_amount) {
                $deduction->expected_amount = $loanPlan->repayment_per_month;
            }

            $deduction->status = $amount > 0 ? 'approved' : 'pending';
            $deduction->confirmed_at = $amount > 0 ? now() : $deduction->confirmed_at;
            $deduction->approved_by = $amount > 0 ? auth()->id() : $deduction->approved_by;
            $deduction->approved_at = $amount > 0 ? now() : $deduction->approved_at;
            $deduction->admin_note = $amount > 0 ? "Imported payment of {$amount} for {$monthValue}" : $deduction->admin_note;
            $deduction->is_expected_amount = $amount ==  $loanPlan->repayment_per_month ? true : false;
            $deduction->save();

            if (!$isNewUser) {
                $this->processed++;
            }
        }
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
