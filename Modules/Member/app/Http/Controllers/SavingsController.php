<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Contribution\Models\SavingsContribution;

class SavingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $contributions = SavingsContribution::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $totalSavings = SavingsContribution::where('user_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount');

        $monthlyTarget = $user->monthly_savings_target ?? 0;
        $minimumSavings = Setting::getValue('minimum_savings', 1000);

        return Inertia::render('Member::Savings/Index', [
            'contributions' => $contributions,
            'totalSavings' => $totalSavings,
            'monthlyTarget' => $monthlyTarget,
            'minimumSavings' => $minimumSavings,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $minimumSavings = Setting::getValue('minimum_savings', 1000);

        return Inertia::render('Member::Savings/Create', [
            'minimumSavings' => $minimumSavings,
            'monthlyTarget' => $user->monthly_savings_target ?? 0,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $minimumSavings = Setting::getValue('minimum_savings', 1000);

        $request->validate([
            'amount' => 'required|numeric|min:' . $minimumSavings,
            'narration' => 'nullable|string|max:500',
            'screenshot' => 'required|image|max:2048',
        ]);

        $screenshotPath = $request->file('screenshot')->store('savings-screenshots', 'public');

        SavingsContribution::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'narration' => $request->narration,
            'screenshot_path' => $screenshotPath,
            'status' => 'pending',
        ]);

        return redirect()->route('member.savings.index')
            ->with('success', 'Savings contribution submitted successfully. Awaiting admin approval.');
    }

    public function show($id)
    {
        $user = Auth::user();

        $contribution = SavingsContribution::where('user_id', $user->id)
            ->findOrFail($id);

        return Inertia::render('Member::Savings/Show', [
            'contribution' => $contribution,
        ]);
    }
}
