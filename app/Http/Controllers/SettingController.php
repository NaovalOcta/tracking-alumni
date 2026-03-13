<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Show tracking settings page.
     */
    public function index()
    {
        $settings = Setting::where('group', 'tracking')->get()->pluck('value', 'key');
        
        // Ensure defaults if not in DB
        $config = [
            'tracking_frequency'    => $settings->get('tracking_frequency', 'daily'),
            'tracking_time'         => $settings->get('tracking_time', '00:00'),
            'tracking_day_of_week'  => $settings->get('tracking_day_of_week', '1'),
            'tracking_day_of_month' => $settings->get('tracking_day_of_month', '1'),
            'tracking_filter_prodi' => $settings->get('tracking_filter_prodi', ''),
            'tracking_filter_year'  => $settings->get('tracking_filter_year', ''),
            'tracking_enabled'      => $settings->get('tracking_enabled', '1'),
        ];

        $prodiList = \App\Models\Alumni::select('prodi')->distinct()->orderBy('prodi')->pluck('prodi');

        return view('settings.index', compact('config', 'prodiList'));
    }

    /**
     * Update tracking settings.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'tracking_frequency'    => 'required|in:daily,weekly,monthly',
            'tracking_time'         => 'required|string',
            'tracking_day_of_week'  => 'nullable|string',
            'tracking_day_of_month' => 'nullable|string',
            'tracking_filter_prodi' => 'nullable|string',
            'tracking_filter_year'  => 'nullable|integer',
            'tracking_enabled'      => 'nullable|string', // Checkbox sends 'on' or nothing
        ]);

        // Handle checkbox (if not present in request, it's off)
        $data['tracking_enabled'] = $request->has('tracking_enabled') ? '1' : '0';

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'tracking', 'type' => 'string']
            );
        }

        return redirect()->back()->with('success', 'Pengaturan jadwal otomatis berhasil diperbarui.');
    }
}
