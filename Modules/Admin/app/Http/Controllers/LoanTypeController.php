<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Modules\Loan\Models\LoanType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LoanTypeController extends Controller
{
    use RespondsWithJson;

    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $loanTypes = LoanType::withCount('loanPlans')
                ->latest()
                ->get()
                ->map(fn($type) => [
                    'id'          => $type->id,
                    'name'        => $type->name,
                    'slug'        => $type->slug,
                    'description' => $type->description,
                    'is_active'   => $type->is_active,
                    'loans_count' => $type->loan_plans_count,
                ]);

            return $this->respond('Admin/LoanTypes/Index', [
                'loanTypes' => $loanTypes,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load loan types.');
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'        => ['required', 'string', 'max:100', 'unique:loan_types,name'],
                'description' => ['nullable', 'string'],
            ]);

            LoanType::create([
                'name'        => $request->name,
                'slug'        => Str::slug($request->name),
                'description' => $request->description,
                'is_active'   => true,
            ]);

            return $this->respondSuccess("Loan type '{$request->name}' created successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to create loan type.');
        }
    }

    public function update(Request $request, LoanType $loanType)
    {
        try {
            $request->validate([
                'name'        => ['required', 'string', 'max:100', 'unique:loan_types,name,' . $loanType->id],
                'description' => ['nullable', 'string'],
            ]);

            $loanType->update([
                'name'        => $request->name,
                'slug'        => Str::slug($request->name),
                'description' => $request->description,
            ]);

            return $this->respondSuccess('Loan type updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update loan type.');
        }
    }

    public function toggleActive(LoanType $loanType)
    {
        try {
            $loanType->update(['is_active' => !$loanType->is_active]);
            $status = $loanType->is_active ? 'activated' : 'deactivated';
            return $this->respondSuccess("Loan type '{$loanType->name}' {$status}.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to toggle loan type status.');
        }
    }
}
