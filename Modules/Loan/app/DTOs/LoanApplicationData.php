<?php

namespace Modules\Loan\DTOs;

class LoanApplicationData
{
    public function __construct(
        public readonly int    $user_id,
        public readonly int    $loan_type_id,
        public readonly float  $amount,
        public readonly int    $interest_rate,
        public readonly int    $duration_months,
        public readonly float  $monthly_payment,
        public readonly float  $total_payment,
        public readonly string $status = 'pending',
        public readonly ?string $purpose = null,
        public readonly ?string $source = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id'          => $this->user_id,
            'loan_type_id'     => $this->loan_type_id,
            'amount'           => $this->amount,
            'interest_rate'    => $this->interest_rate,
            'duration_months'  => $this->duration_months,
            'monthly_payment'  => $this->monthly_payment,
            'total_payment'    => $this->total_payment,
            'purpose'          => $this->purpose,
            'status'           => $this->status,
            'source'           => $this->source,
        ];
    }
}
