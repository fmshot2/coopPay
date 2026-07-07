<?php

namespace Modules\Admin\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Modules\Admin\Models\Year;
use Modules\Admin\Models\Month;
use Modules\Division\Models\Division;
use Modules\Loan\Models\LoanPlan;
use Modules\Loan\Models\MonthlyRepayment;
use Modules\Payment\Models\MonthlyDeduction;
use Modules\Contribution\Models\SavingsContribution;

class DeductionImport implements ToCollection, SkipsEmptyRows
{
    public int   $processed = 0;
    public int   $skipped   = 0;
    public array $errors    = [];

    protected Year  $year;
    protected Month $month;

    public function __construct(int $yearId, int $monthId)
    {
        $this->year  = Year::findOrFail($yearId);
        $this->month = Month::where('id', $monthId)
            ->where('year_id', $yearId)
            ->firstOrFail();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $this->processRow($index, $row);
            } catch (\Throwable $e) {
                Log::error('Row failed', [
                    'row'   => $index + 1,
                    'data'  => $row->toArray(),
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                ]);
                $this->errors[] = "Row " . ($index + 1) . " failed: {$e->getMessage()}";
                $this->skipped++;
            }
        }
    }

    private function processRow(int $index, $row): void
    {
        $rowNumber    = $index + 1;
        $name         = trim($row[0] ?? '');
        $divisionName = trim($row[1] ?? '');
        $amount       = trim($row[2] ?? '0');

        Log::info("Processing row {$rowNumber}", compact('name', 'divisionName', 'amount'));

        if ($name === '') {
            $this->errors[] = "Row {$rowNumber}: name is required.";
            $this->skipped++;
            return;
        }

        if ($divisionName === '') {
            $this->errors[] = "Row {$rowNumber}: division is required.";
            $this->skipped++;
            return;
        }
        // old usable version
        // $amount = is_numeric($amount) ? round((float) $amount, 2) : 0.00;
        // this new version checks for comma and removes it, then checks if it's numeric
        if (strpos($amount, ',') !== false) {
            $amount = str_replace(',', '', $amount);
        }
        $amount = is_numeric($amount) ? round((float) $amount, 2) : 0.00;


        if ($amount <= 0) {
            $this->errors[] = "Row {$rowNumber}: '{$name}' has no valid amount — skipped.";
            $this->skipped++;
            return;
        }

        // --- Resolve division ---
        $division = Division::whereRaw('LOWER(name) = ?', [mb_strtolower($divisionName)])->first();
        if (!$division) {
            $division = Division::create(['name' => $divisionName, 'is_active' => true]);
        }
        Log::info("Row {$rowNumber}: division resolved", ['division_id' => $division->id]);

        // --- Resolve member ---
        $user = User::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where('division_id', $division->id)
            ->first();

        if (!$user) {
            $user = User::create([
                'member_id'            => $this->generateMemberId(),
                'name'                 => $name,
                'email'                => null,
                'phone'                => null,
                'password'             => Hash::make('password'),
                'is_active'            => true,
                'must_change_password' => true,
                'is_temporary'         => true,
                'division_id'          => $division->id,
            ]);
            $user->assignRole('member');
            $this->errors[] = "Row {$rowNumber}: '{$name}' not found — created as new member.";
        }
        Log::info("Row {$rowNumber}: user resolved", ['user_id' => $user->id]);

        $monthValue = sprintf('%04d-%02d', $this->year->year, $this->month->number);

        // --- Guard: skip if deduction already exists ---
        $alreadyDeducted = MonthlyDeduction::where('user_id', $user->id)
            ->where('year_id', $this->year->id)
            ->where('month_id', $this->month->id)
            ->exists();

        if ($alreadyDeducted) {
            $this->errors[] = "Row {$rowNumber}: '{$name}' already deducted for {$monthValue} — skipped.";
            $this->skipped++;
            return;
        }

        Log::info("Row {$rowNumber}: creating deduction");

        // --- Record monthly deduction ---
        MonthlyDeduction::create([
            'user_id'            => $user->id,
            'year_id'            => $this->year->id,
            'month_id'           => $this->month->id,
            'month'              => $monthValue,
            'expected_amount'    => $amount,
            'status'             => 'approved',
            'confirmed_at'       => now(),
            'approved_by'        => auth()->id(),
            'approved_at'        => now(),
            'is_expected_amount' => true,
            'admin_note'         => "Imported from accounts CSV — {$monthValue}",
        ]);

        Log::info("Row {$rowNumber}: deduction created, routing...");

        // --- Route to loan or savings ---
        $activeLoanPlan = $user->activeLoanPlans()->latest()->first();

        Log::info("Row {$rowNumber}: routing decision", [
            'has_active_loan' => $activeLoanPlan?->id,
        ]);

        if ($activeLoanPlan) {
            $this->processLoanRepayment($user, $activeLoanPlan, $amount, $monthValue, $rowNumber);
        } else {
            $this->processSavings($user, $amount, $monthValue);
        }

        $this->processed++;
    }

    private function processLoanRepayment(
        User     $user,
        LoanPlan $loanPlan,
        float    $amount,
        string   $monthValue,
        int      $rowNumber
    ): void {
        // Find the pre-generated repayment row for this month
        $repayment = MonthlyRepayment::where('loan_plan_id', $loanPlan->id)
            ->where('year_id', $this->year->id)
            ->where('month_id', $this->month->id)
            ->first();

        if (!$repayment) {
            // Repayment row missing — could be a late approval or data gap
            $this->errors[] = "Row {$rowNumber}: no repayment schedule found for '{$user->name}' "
                . "in {$monthValue}. Deduction recorded but repayment row not updated.";
            return;
        }

        $amountDue = (float) $loanPlan->repayment_per_month;
        $status    = $amount >= $amountDue ? 'fully_paid' : 'partly_paid';

        $repayment->update([
            'amount_paid' => $amount,
            'status'      => $status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'admin_note'  => "Confirmed via accounts CSV import — {$monthValue}",
        ]);

        // --- Update loan plan balance ---
        $newAmountRemaining  = max(0, round((float) $loanPlan->amount_remaining - $amount, 2));
        $newMonthsRemaining  = max(0, $loanPlan->months_remaining - 1);

        $loanPlan->update([
            'amount_remaining'  => $newAmountRemaining,
            'months_remaining'  => $newMonthsRemaining,
            'next_due_date'     => now()->addMonth()->startOfMonth(),
            'status'            => $newAmountRemaining <= 0 ? 'completed' : 'active',
        ]);
    }

    private function processSavings(User $user, float $amount, string $monthValue): void
    {
        try {
            Log::info('Creating savings contribution', [
                'user_id' => $user->id,
                'name'    => $user->name,
                'amount'  => $amount,
                'month'   => $monthValue,
            ]);
            $contribution = SavingsContribution::create([
                'user_id'    => $user->id,
                'year_id'    => $this->year->id,
                'month_id'   => $this->month->id,
                'month'      => $monthValue,
                'amount'     => $amount,
                'status'     => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'narration'  => "Monthly deduction from accounts CSV — {$monthValue}",
            ]);

            Log::info('Savings created', [
                'user_id'  => $user->id,
                'name'     => $user->name,
                'amount'   => $amount,
                'month'    => $monthValue,
                'saved_id' => $contribution->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Savings creation failed', [
                'user_id' => $user->id,
                'name'    => $user->name,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $this->errors[] = "Savings failed for '{$user->name}': {$e->getMessage()}";
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
