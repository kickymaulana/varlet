<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function login()
    {
        if (session()->has('admin_user')) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/Login');
    }

    public function redirect()
    {
        $query = http_build_query([
            'client_id' => config('services.sso.client_id'),
            'redirect_uri' => route('admin.callback'),
            'response_type' => 'code',
            'scope' => '',
        ]);

        return redirect(config('services.sso.base_url') . '/oauth/authorize?' . $query);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('admin.login')->withErrors(['message' => 'Gagal autentikasi']);
        }

        // Tukar code dengan access token
        $verifySsl = app()->environment('local') ? false : true;
        $tokenResponse = Http::withOptions(['verify' => $verifySsl])->asForm()->post(config('services.sso.base_url') . '/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.sso.client_id'),
            'client_secret' => config('services.sso.client_secret'),
            'redirect_uri' => route('admin.callback'),
            'code' => $code,
        ]);

        if ($tokenResponse->failed()) {
            return redirect()->route('admin.login')->withErrors(['message' => 'Gagal mendapatkan token']);
        }

        $accessToken = $tokenResponse->json('access_token');

        // Fetch user info dari SSO
        $userResponse = Http::withOptions(['verify' => $verifySsl])->withToken($accessToken)->get(config('services.sso.base_url') . '/api/user');

        if ($userResponse->failed()) {
            return redirect()->route('admin.login')->withErrors(['message' => 'Gagal mendapatkan data user']);
        }

        $ssoUser = $userResponse->json();

        // Cari user lokal berdasarkan NIK
        $user = User::where('nik', $ssoUser['nik'])->first();

        // Cek apakah user terdaftar dan punya role admin
        if (!$user || !$user->hasRole('admin')) {
            return redirect()->route('admin.login')->withErrors(['message' => 'Anda tidak memiliki akses ke admin panel']);
        }

        // Simpan session admin
        session(['admin_user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]]);

        return redirect()->route('admin.dashboard');
    }

    public function dashboard()
    {
        if (!session()->has('admin_user')) {
            return redirect()->route('admin.login');
        }

        return Inertia::render('Admin/Dashboard', [
            'admin' => session('admin_user'),
            'pin' => Setting::getValue('attendance_pin'),
        ]);
    }

    public function updatePin(Request $request)
    {
        if (!session()->has('admin_user')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'pin' => 'required|string|size:3',
        ]);

        Setting::setValue('attendance_pin', $request->pin);

        return response()->json(['message' => 'PIN berhasil diperbarui']);
    }

    public function logout()
    {
        session()->forget('admin_user');
        return redirect()->route('admin.login');
    }
}
