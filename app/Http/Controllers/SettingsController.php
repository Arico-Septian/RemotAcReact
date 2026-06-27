<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\UserLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Settings', [
            'retentionSettings' => AppSetting::retentionSettings(),
            'monitoringSettings' => AppSetting::monitoringSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $definitions = AppSetting::settingDefinitions();

        $rules = collect($definitions)
            ->mapWithKeys(fn (array $definition, string $key) => [
                $key => ['required', 'integer', 'min:'.$definition['min'], 'max:'.$definition['max']],
            ])
            ->all();

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => 'System',
            'ac' => 'Database cleanup retention',
            'activity' => 'update_settings',
        ]);

        return back()->with('success', 'Settings berhasil disimpan');
    }
}
