<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Loan\Models\LoanType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class LoanTypeController extends Controller
{
    public function index(): Response
    {
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

        return Inertia::render('Admin/LoanTypes/Index', [
            'loanTypes' => $loanTypes,
        ]);
    }

    public function store(Request $request)
    {
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

        return back()->with('success', "Loan type '{$request->name}' created successfully.");
    }

    public function update(Request $request, LoanType $loanType)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:loan_types,name,' . $loanType->id],
            'description' => ['nullable', 'string'],
        ]);

        $loanType->update([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return back()->with('success', "Loan type updated successfully.");
    }

    public function toggleActive(LoanType $loanType)
    {
        $loanType->update(['is_active' => !$loanType->is_active]);
        $status = $loanType->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Loan type '{$loanType->name}' {$status}.");
    }
}
