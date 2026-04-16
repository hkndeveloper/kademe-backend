<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::all());
    }

    public function update(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'value' => 'required',
        ]);

        $setting->update([
            'value' => $validated['value']
        ]);

        return response()->json($setting);
    }
    
    /**
     * Toplu ayar çekme
     */
    public function getGroup($group)
    {
        return response()->json(Setting::where('group', $group)->get());
    }

    /**
     * Toplu ayar güncelleme (Frontend Settings Page için)
     */
    public function bulkUpdate(Request $request)
    {
        $settings = $request->input('settings', []);
        
        foreach ($settings as $s) {
            Setting::where('key', $s['key'])->update(['value' => $s['value']]);
        }
        
        return response()->json(['message' => 'Tüm ayarlar güncellendi.']);
    }
}
