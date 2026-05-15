<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailKey;
use App\Models\Setting;
use App\Services\MailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailController extends Controller
{
    /**
     * Halaman gabungan: Konfigurasi email default + Mail Key Pool (admin/email.blade.php).
     */
    public function hub(MailService $mail): View
    {
        $keys = MailKey::orderBy('provider')->orderBy('priority')->orderBy('id')->get();
        return view('admin.email', [
            'providers'    => $mail->providers(),
            // Mail Key Pool tidak menyertakan SMTP (tidak ada API key)
            'keyProviders' => collect($mail->providers())->except('smtp')->all(),
            'current'      => $mail->provider(),
            'fromEmail'    => $mail->fromEmail(),
            'fromName'     => $mail->fromName(),
            'brevoSet'     => (bool) Setting::get('mail.brevo.api_key'),
            'mailerSet'    => (bool) Setting::get('mail.mailersend.api_key'),
            'sendpulseSet' => (bool) Setting::get('mail.sendpulse.client_id'),
            'keys'         => $keys,
        ]);
    }

    public function update(Request $request, MailService $mail): RedirectResponse
    {
        $data = $request->validate([
            'provider'   => 'required|in:'.implode(',', array_keys($mail->providers())),
            'from_email' => 'required|email|max:191',
            'from_name'  => 'required|string|max:120',

            'brevo_api_key'           => 'nullable|string|max:255',
            'mailersend_api_key'      => 'nullable|string|max:255',
            'sendpulse_client_id'     => 'nullable|string|max:255',
            'sendpulse_client_secret' => 'nullable|string|max:255',
        ]);

        Setting::put('mail.provider',   $data['provider'], 'mail');
        Setting::put('mail.from_email', $data['from_email'], 'mail');
        Setting::put('mail.from_name',  $data['from_name'], 'mail');

        if (! empty($data['brevo_api_key']))           Setting::put('mail.brevo.api_key', $data['brevo_api_key'], 'mail', true);
        if (! empty($data['mailersend_api_key']))      Setting::put('mail.mailersend.api_key', $data['mailersend_api_key'], 'mail', true);
        if (! empty($data['sendpulse_client_id']))     Setting::put('mail.sendpulse.client_id', $data['sendpulse_client_id'], 'mail');
        if (! empty($data['sendpulse_client_secret'])) Setting::put('mail.sendpulse.client_secret', $data['sendpulse_client_secret'], 'mail', true);

        return redirect('/admin/email?tab=general')->with('success', 'Konfigurasi email disimpan.');
    }

    public function test(Request $request, MailService $mail): RedirectResponse
    {
        $data = $request->validate(['to' => 'required|email']);
        $ok = $mail->sendHtml(
            $data['to'],
            'Tes notifikasi '.config('app.name'),
            '<p>Hai, ini adalah <strong>tes email</strong> dari '.e(config('app.name')).' melalui provider <code>'.e($mail->provider()).'</code>.</p>'.
            '<p>Jika kamu menerima ini, konfigurasi sudah benar.</p>',
        );
        return back()->with($ok ? 'success' : 'error',
            $ok ? 'Tes email terkirim ke '.$data['to'] : 'Tes email gagal. Cek log.');
    }
}
