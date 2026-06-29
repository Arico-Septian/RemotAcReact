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
            'mqttSettings' => AppSetting::mqttSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $definitions = AppSetting::settingDefinitions();
        $settingKeys = collect($request->keys())
            ->filter(fn (string $key): bool => array_key_exists($key, $definitions))
            ->values();

        $rules = $settingKeys
            ->mapWithKeys(fn (string $key): array => [
                $key => $definitions[$key]['rules'] ?? ['required', 'integer', 'min:'.$definitions[$key]['min'], 'max:'.$definitions[$key]['max']],
            ])
            ->all();

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }

        $settingsGroup = $request->string('_settings_group')->toString();
        $logLabel = match ($settingsGroup) {
            'mqtt' => 'MQTT broker settings',
            'retention' => 'Database cleanup retention',
            'monitoring' => 'Device monitoring settings',
            default => 'Settings',
        };

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => 'System',
            'ac' => $logLabel,
            'activity' => 'update_settings',
        ]);

        return back()->with('success', $logLabel.' berhasil disimpan');
    }
}
