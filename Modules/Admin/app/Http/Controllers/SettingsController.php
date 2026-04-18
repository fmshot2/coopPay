<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $settings = [
            'cooperative_account' => Setting::getValue('cooperative_account', ''),
            'organization_name' => Setting::getValue('organization_name', ''),
            'contact_email' => Setting::getValue('contact_email', ''),
            'contact_phone' => Setting::getValue('contact_phone', ''),
            'address' => Setting::getValue('address', ''),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'cooperative_account' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        // Update each setting if provided
        if ($request->has('cooperative_account')) {
            Setting::setValue('cooperative_account', $request->cooperative_account);
        }

        if ($request->has('organization_name')) {
            Setting::setValue('organization_name', $request->organization_name);
        }

        if ($request->has('contact_email')) {
            Setting::setValue('contact_email', $request->contact_email);
        }

        if ($request->has('contact_phone')) {
            Setting::setValue('contact_phone', $request->contact_phone);
        }

        if ($request->has('address')) {
            Setting::setValue('address', $request->address);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}
