<?php

namespace Modules\Loan\Services;

// use Modules\Loan\DTOs\LoanApplicationData;

use Modules\Loan\DTOs\LoanApplicationData;
use App\Models\LoanApplication;
// Modules\Loan\app\DTOs

class LoanApplicationService
{
    const INTEREST_RATE = 10;

    public function computeAndCreate(int $userId, array $validated, string $source = 'member'): LoanApplication
    {
        $data = $this->buildData($userId, $validated, $source);
        return $this->create($data);
    }

    public function buildData(int $userId, array $validated, string $source): LoanApplicationData
    {
        $amount         = (float) $validated['amount'];
        $totalRepayable = round($amount + ($amount * self::INTEREST_RATE / 100), 2);
        $monthlyPayment = round($totalRepayable / $validated['duration_months'], 2);

        return new LoanApplicationData(
            user_id: $userId,
            loan_type_id: (int) $validated['loan_type_id'],
            amount: $amount,
            interest_rate: self::INTEREST_RATE,
            duration_months: (int) $validated['duration_months'],
            monthly_payment: $monthlyPayment,
            total_payment: $totalRepayable,
            purpose: $validated['purpose'] ?? null,
            source: $source
        );
    }

    public function create(LoanApplicationData $data): LoanApplication
    {
        return LoanApplication::create($data->toArray());
    }
}
