<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($request->hasFile('photo')) {
            $request->validate(['photo' => 'image|mimes:jpg,jpeg,png,webp|max:2048']);
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('photo')->store('avatars', 'public');
            $user->profile_photo_path = $path;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|string|min:8|confirmed',
        ]);
        $request->user()->update(['password' => Hash::make($data['password'])]);
        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }
        return back()->with('success', 'Foto profil dihapus.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);
        $user = $request->user();
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return Redirect::to('/');
    }

    /** Persist tema (light|dark|system) ke akun user. */
    public function updateTheme(Request $request): JsonResponse
    {
        $data = $request->validate(['theme' => 'required|in:light,dark,system']);
        $request->user()->update(['theme' => $data['theme']]);
        return response()->json(['ok' => true, 'theme' => $data['theme']]);
    }

    /**
     * Aktif/nonaktifkan OTP login (2FA via email) untuk akun ini.
     * Dilindungi dengan konfirmasi password agar tidak bisa diubah lewat XSRF
     * iseng bila session ter-hijack tanpa kredensial.
     */
    public function updateOtpLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled'          => 'required|boolean',
            'current_password' => 'required|current_password',
        ]);

        $request->user()->update(['otp_login_enabled' => (bool) $data['enabled']]);

        return back()->with('success', $data['enabled']
            ? 'Verifikasi 2 langkah (OTP email) diaktifkan.'
            : 'Verifikasi 2 langkah (OTP email) dimatikan.');
    }
}
