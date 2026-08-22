<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', [
            'wa' => Setting::getValue('wa_number', '6287777626067'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'wa_number' => ['required', 'string', 'max:20'],
        ]);

        Setting::setValue('wa_number', preg_replace('/\D+/', '', $data['wa_number']));

        return redirect()->route('admin.settings.edit')->with('ok', 'Pengaturan tersimpan.');
    }
}
