<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Contribution\Models\SavingsContribution;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class SavingsController extends Controller
{
    use RespondsWithJson;
    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();

            $contributions = SavingsContribution::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $totalSavings = SavingsContribution::where('user_id', $user->id)
                ->where('status', 'approved')
                ->sum('amount');

            $monthlyTarget = $user->monthly_savings_target ?? 0;
            $minimumSavings = Setting::getValue('minimum_savings', 1000);

            return $this->respond('Member::Savings/Index', [
                'contributions' => $contributions,
                'totalSavings' => $totalSavings,
                'monthlyTarget' => $monthlyTarget,
                'minimumSavings' => $minimumSavings,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load savings page.');
        }
    }

    public function create(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();
            $minimumSavings = Setting::getValue('minimum_savings', 1000);

            return $this->respond('Member::Savings/Create', [
                'minimumSavings' => $minimumSavings,
                'monthlyTarget' => $user->monthly_savings_target ?? 0,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load savings creation page.');
        }
    }

    public function store(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
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

            return $this->respondSuccess('Savings contribution submitted successfully. Awaiting admin approval.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to submit savings contribution.');
        }
    }

    public function show($id): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();

            $contribution = SavingsContribution::where('user_id', $user->id)
                ->findOrFail($id);

            return $this->respond('Member::Savings/Show', [
                'contribution' => $contribution,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load savings details.');
        }
    }
}
