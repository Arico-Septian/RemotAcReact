<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function rateLimitKey(Request $request): string
    {
        // Key by lowercased username + IP so attacker can't bypass via case swap
        return 'login:'.strtolower(trim((string) $request->input('name', ''))).'|'.$request->ip();
    }

    public function login(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $request->merge([
            'name' => trim((string) $request->name),
        ]);

        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z]\S*$/'],
            'password' => 'required|string',
        ], [
            'name.regex' => 'Username harus diawali huruf dan tidak boleh mengandung spasi.',
        ]);

        $key = $this->rateLimitKey($request);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            throw ValidationException::withMessages([
                'name' => "Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit.",
            ]);
        }

        $user = User::whereRaw('LOWER(name) = ?', [strtolower($credentials['name'])])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 900); // 15 menit lockout
            $remaining = 5 - RateLimiter::attempts($key);
            $msg = $remaining > 0
                ? "Username atau password salah. Sisa percobaan: {$remaining}."
                : 'Akun dikunci sementara selama 15 menit karena terlalu banyak percobaan.';
            throw ValidationException::withMessages(['name' => $msg]);
        }

        RateLimiter::clear($key);

        Auth::login($user);
        $request->session()->regenerate();

        $user->last_login_at = now();
        $user->last_activity = now();

        $user->save();

        UserLog::create([
            'user_id' => $user->id,
            'room' => '-',
            'ac' => '-',
            'activity' => 'login',
        ]);

        $intended = $request->session()->pull('url.intended');
        if ($intended && $this->isPageUrl($intended)) {
            return redirect($intended);
        }

        return redirect()->route('dashboard');
    }

    private function isPageUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $apiPaths = [
            '/temperature', '/temperatures',
            '/device-status', '/ac-status',
            '/notifications/recent',
            '/dashboard/recent-activities',
            '/session/ping',
            '/logout',
            '/suhu-raspi',
            '/raspi-monitor',
            '/cek-driver',
            '/test-cache',
        ];
        foreach ($apiPaths as $p) {
            if (str_starts_with($path, $p)) {
                return false;
            }
        }
        if (str_starts_with($path, '/api/')) {
            return false;
        }

        return true;
    }

    public function logout(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user) {
            $user->last_logout_at = now();
            $user->last_activity = null;

            $user->save();

            UserLog::create([
                'user_id' => $user->id,
                'room' => '-',
                'ac' => '-',
                'activity' => 'logout',
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
