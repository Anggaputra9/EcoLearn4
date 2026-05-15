<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan form pendaftaran.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Step 1 — terima form daftar, simpan ke session (belum buat user),
     * kemudian kirim OTP ke email & arahkan ke halaman verifikasi.
     */
    public function store(Request $request, NotificationService $notif): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Simpan calon user ke session sambil menunggu OTP
        $request->session()->put('register.pending', [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $otp = OtpCode::issue($data['email'], 'register');

        try {
            $notif->sendOtpCode($data['email'], $data['name'], $otp->plain);
        } catch (\Throwable $e) {
            // Tetap lanjut ke halaman OTP — user bisa minta kirim ulang
        }

        return redirect()->route('register.otp')->with('status', 'Kode OTP telah dikirim ke '.$data['email']);
    }

    /**
     * Tampilkan halaman input OTP.
     */
    public function otpForm(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('register.pending');
        if (! $pending) {
            return redirect()->route('register');
        }
        return view('auth.register-otp', ['email' => $pending['email']]);
    }

    /**
     * Step 2 — verifikasi kode OTP dan buat akun.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('register.pending');
        if (! $pending) {
            return redirect()->route('register')->with('error', 'Sesi pendaftaran berakhir. Silakan ulangi.');
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! OtpCode::verify($pending['email'], $request->input('code'), 'register')) {
            throw ValidationException::withMessages([
                'code' => 'Kode OTP salah, kedaluwarsa, atau sudah terlalu banyak percobaan.',
            ]);
        }

        $user = User::create([
            'name'              => $pending['name'],
            'email'             => $pending['email'],
            'password'          => $pending['password'], // sudah di-hash di store()
            'email_verified_at' => now(),
        ]);

        $request->session()->forget('register.pending');

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false))
            ->with('success', 'Akun berhasil dibuat dan email telah diverifikasi.');
    }

    /**
     * Kirim ulang kode OTP.
     */
    public function resendOtp(Request $request, NotificationService $notif): RedirectResponse
    {
        $pending = $request->session()->get('register.pending');
        if (! $pending) {
            return redirect()->route('register');
        }

        $otp = OtpCode::issue($pending['email'], 'register');

        try {
            $notif->sendOtpCode($pending['email'], $pending['name'], $otp->plain);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim ulang OTP: '.$e->getMessage());
        }

        return back()->with('status', 'Kode OTP baru telah dikirim ke '.$pending['email']);
    }
}
