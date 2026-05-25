<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $rules = [
            'temp_retention_days'         => 'required|integer|min:1|max:365',
            'notification_retention_days' => 'required|integer|min:1|max:365',
            'log_retention_days'          => 'required|integer|min:1|max:365',
            'fuzzy_temp_cold'             => 'required|integer|min:16|max:28',
            'fuzzy_temp_hot'              => 'required|integer|min:24|max:40',
        ];

        $messages = [
            'fuzzy_temp_cold.min' => 'Batas suhu Dingin minimal 16°C.',
            'fuzzy_temp_cold.max' => 'Batas suhu Dingin maksimal 28°C.',
            'fuzzy_temp_hot.min'  => 'Batas suhu Panas minimal 24°C.',
            'fuzzy_temp_hot.max'  => 'Batas suhu Panas maksimal 40°C.',
        ];

        $validated = $request->validate($rules, $messages);

        if ($validated['fuzzy_temp_cold'] >= $validated['fuzzy_temp_hot']) {
            return back()->withErrors([
                'fuzzy_temp_cold' => 'Batas suhu Dingin harus lebih kecil dari batas suhu Panas.',
            ])->withInput();
        }

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
