<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $registrationEnabled = Setting::get('registration_enabled', config('practice.registration_enabled'));
        $registrationRequiresApproval = Setting::get('registration_requires_approval', config('practice.registration_requires_approval'));
        $questionsPerSession = Setting::get('questions_per_session', config('practice.questions_per_session'));

        return view('admin.settings.index', compact(
            'registrationEnabled',
            'registrationRequiresApproval',
            'questionsPerSession'
        ));
    }

    public function update(Request $request)
    {
        $registrationEnabled = $request->input('registration_enabled') === '1';
        $registrationRequiresApproval = $request->input('registration_requires_approval') === '1';
        $questionsPerSession = max(1, (int) $request->input('questions_per_session', 10));

        Setting::set('registration_enabled', $registrationEnabled, 'boolean');
        Setting::set('registration_requires_approval', $registrationRequiresApproval, 'boolean');
        Setting::set('questions_per_session', $questionsPerSession, 'integer');

        return redirect()->route('admin.settings.index')->with('status', '设置已更新。');
    }
}