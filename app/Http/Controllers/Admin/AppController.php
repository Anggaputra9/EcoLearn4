<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppController extends Controller
{
    public function edit(): View
    {
        return view('admin.app', [
            'appName'     => Setting::get('app.name', config('app.name', 'Eko-Scribe')),
            'appTagline'  => Setting::get('app.tagline', 'Platform pembelajaran ekoteologi'),
            'appFooter'   => Setting::get('app.footer', '© '.now()->year.' '.config('app.name')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name'    => 'required|string|max:120',
            'app_tagline' => 'nullable|string|max:255',
            'app_footer'  => 'nullable|string|max:255',
        ]);
        Setting::put('app.name',    $data['app_name'], 'app');
        Setting::put('app.tagline', $data['app_tagline'] ?? '', 'app');
        Setting::put('app.footer',  $data['app_footer'] ?? '', 'app');
        return back()->with('success', 'Pengaturan aplikasi disimpan.');
    }
}
