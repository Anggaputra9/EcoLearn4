<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * - Jika user mengaktifkan OTP login (2FA), kredensial diverifikasi tanpa
     *   melakukan login. Lalu kirim OTP ke email & redirect ke form OTP.
     * - Jika tidak, login normal.
     */
    public function store(LoginRequest $request, NotificationService $notif): RedirectResponse
    {
        $request->authenticateCredentials();

        $email    = (string) $request->input('email');
        $password = (string) $request->input('password');

        $user = User::where('email', $email)->first();

        // 2FA via email OTP
        if ($user && $user->otp_login_enabled) {
            $otp = OtpCode::issue($email, 'login');

            // Best-effort: kalau email gagal terkirim, tetap arahkan ke form
            // (pengguna bisa minta kirim ulang) dan munculkan pesan ramah.
            $sent = false;
            try {
                $sent = $notif->sendOtpCode($email, $user->name, $otp->plain);
            } catch (\Throwable $e) {
                $sent = false;
            }

            // Simpan tiket login pending di session (TTL 10 menit, identik dgn OTP)
            $request->session()->put('login.pending', [
                'email'      => $email,
                'remember'   => (bool) $request->boolean('remember'),
                // hash agar password tidak disimpan plaintext di session
                'password'   => Hash::make($password),
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()
                ->route('login.otp')
                ->with('status', $sent
                    ? 'Kode OTP telah dikirim ke '.self::maskEmail($email)
                    : 'Kode OTP telah dibuat namun pengiriman email gagal. Coba kirim ulang.');
        }

        // Login normal
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        RateLimiter::clear($request->throttleKey());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Tampilkan form input OTP login (step 2 setelah password benar).
     */
    public function otpForm(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('login.pending');
        if (! $pending || ($pending['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('login.pending');
            return redirect()->route('login')->with('error', 'Sesi login berakhir. Silakan masuk lagi.');
        }
        return view('auth.login-otp', ['email' => $pending['email']]);
    }

    /**
     * Verifikasi OTP login → masuk.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('login.pending');
        if (! $pending || ($pending['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('login.pending');
            return redirect()->route('login')->with('error', 'Sesi login berakhir. Silakan masuk lagi.');
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        // Throttle khusus verifikasi OTP login (selain attempts pada model)
        $throttleKey = 'login-otp:'.Str::lower($pending['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 8)) {
            $sec = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'code' => 'Terlalu banyak percobaan. Coba lagi dalam '.$sec.' detik.',
            ]);
        }

        if (! OtpCode::verify($pending['email'], $request->input('code'), 'login')) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'code' => 'Kode OTP salah, kedaluwarsa, atau sudah terlalu banyak percobaan.',
            ]);
        }

        $user = User::where('email', $pending['email'])->first();
        if (! $user) {
            $request->session()->forget('login.pending');
            return redirect()->route('login')->with('error', 'Akun tidak ditemukan.');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->forget('login.pending');

        Auth::login($user, (bool) ($pending['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Kirim ulang kode OTP login.
     */
    public function resendOtp(Request $request, NotificationService $notif): RedirectResponse
    {
        $pending = $request->session()->get('login.pending');
        if (! $pending) {
            return redirect()->route('login');
        }

        $user = User::where('email', $pending['email'])->first();
        if (! $user) {
            $request->session()->forget('login.pending');
            return redirect()->route('login')->with('error', 'Akun tidak ditemukan.');
        }

        $otp = OtpCode::issue($pending['email'], 'login');

        // Perpanjang sesi agar selaras dengan TTL OTP baru.
        $pending['expires_at'] = now()->addMinutes(10)->timestamp;
        $request->session()->put('login.pending', $pending);

        try {
            $notif->sendOtpCode($pending['email'], $user->name, $otp->plain);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim ulang OTP: '.$e->getMessage());
        }

        return back()->with('status', 'Kode OTP baru telah dikirim ke '.self::maskEmail($pending['email']));
    }

    /**
     * Batalkan flow OTP login (kembali ke /login).
     */
    public function cancelOtp(Request $request): RedirectResponse
    {
        $request->session()->forget('login.pending');
        return redirect()->route('login');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /** Mask email "ang****@gmail.com" untuk tampilan UI. */
    protected static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        [$user, $domain] = $parts;
        $keep = max(1, min(3, intdiv(strlen($user), 3)));
        return substr($user, 0, $keep).str_repeat('*', max(2, strlen($user) - $keep)).'@'.$domain;
    }
}
