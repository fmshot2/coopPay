<?php

namespace Modules\Admin\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class MembersImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $created = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // account for header row

            // Basic validation
            if (empty($row['name']) || empty($row['email'])) {
                $this->errors[] = "Row {$rowNumber}: name and email are required.";
                $this->skipped++;
                continue;
            }

            if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Row {$rowNumber}: invalid email '{$row['email']}'.";
                $this->skipped++;
                continue;
            }

            // Skip if email already exists
            if (User::where('email', $row['email'])->exists()) {
                $this->errors[] = "Row {$rowNumber}: email '{$row['email']}' already exists.";
                $this->skipped++;
                continue;
            }

            // Skip if member_id already exists
            if (!empty($row['member_id']) && User::where('member_id', $row['member_id'])->exists()) {
                $this->errors[] = "Row {$rowNumber}: member ID '{$row['member_id']}' already exists.";
                $this->skipped++;
                continue;
            }

            // Generate member_id if not provided
            $memberId = !empty($row['member_id'])
                ? $row['member_id']
                : $this->generateMemberId();

            $user = User::create([
                'member_id'            => $memberId,
                'name'                 => $row['name'],
                'email'                => $row['email'],
                'phone'                => $row['phone'] ?? null,
                'password'             => Hash::make('password'),
                'is_active'            => true,
                'must_change_password' => true,
            ]);

            $user->assignRole('member');
            $this->created++;
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
