<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Bucket per username+IP: lindungi 1 akun dari brute-force terarah
    private const MAX_PER_NAME = 5;

    // Bucket per IP saja: lindungi dari enumerasi banyak username dari 1 IP
    private const MAX_PER_IP = 20;

    // Window lockout (detik) — berlaku untuk kedua bucket
    private const LOCKOUT_SECONDS = 900;

    // Dummy bcrypt hash dipakai untuk Hash::check saat user tidak ada,
    // supaya timing response konstan dan tidak membocorkan eksistensi username.
    private const DUMMY_HASH = '$2y$12$wB6Vh7iJ7e8M0pZmYbZb0eS1lE.7N8Qx3i6n6q3PqYZyVxz3lE0Bm';

    private function rateLimitKeyName(Request $request): string
    {
        // Key by lowercased username + IP so attacker can't bypass via case swap
        return 'login:'.strtolower(trim((string) $request->input('name', ''))).'|'.$request->ip();
    }

    private function rateLimitKeyIp(Request $request): string
    {
        return 'login-ip:'.$request->ip();
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
            'name' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z][A-Za-z0-9_]{2,19}$/'],
            'password' => 'required|string',
        ], [
            'name.required' => 'Username is required.',
            'name.min' => 'Username must be at least 3 characters.',
            'name.max' => 'Username may not exceed 20 characters.',
            'name.regex' => 'Username must be 3–20 characters, start with a letter, and only contain letters, numbers, or underscore.',
            'password.required' => 'Password is required.',
        ]);

        $keyName = $this->rateLimitKeyName($request);
        $keyIp = $this->rateLimitKeyIp($request);

        // Bucket per username+IP penuh → akun ini dikunci
        if (RateLimiter::tooManyAttempts($keyName, self::MAX_PER_NAME)) {
            $minutes = ceil(RateLimiter::availableIn($keyName) / 60);
            throw ValidationException::withMessages([
                'name' => "Too many login attempts. Please try again in {$minutes} minute(s).",
            ]);
        }

        if (RateLimiter::tooManyAttempts($keyIp, self::MAX_PER_IP)) {
            $minutes = ceil(RateLimiter::availableIn($keyIp) / 60);
            throw ValidationException::withMessages([
                'name' => "Too many login attempts from your network. Please try again in {$minutes} minute(s).",
            ]);
        }

        $user = User::whereRaw('LOWER(name) = ?', [strtolower($credentials['name'])])->first();

        // Selalu jalankan Hash::check (entah pakai password user atau dummy hash)
        // supaya timing response konstan — cegah enumerasi username via timing attack.
        $hashToCheck = $user ? $user->password : self::DUMMY_HASH;
        $passwordOk = Hash::check($credentials['password'], $hashToCheck);

        if (! $user || ! $passwordOk) {
            $this->recordFailedLogin($request, $user);

            RateLimiter::hit($keyName, self::LOCKOUT_SECONDS);
            RateLimiter::hit($keyIp, self::LOCKOUT_SECONDS);
            throw ValidationException::withMessages([
                'name' => 'Incorrect username or password.',
            ]);
        }

        RateLimiter::clear($keyName);
        RateLimiter::clear($keyIp);

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

    private function recordFailedLogin(Request $request, ?User $user): void
    {
        try {
            UserLog::create([
                'user_id' => $user?->id,
                'room' => '-',
                'ac' => Str::limit('Username: '.$request->string('name')->toString().' | IP: '.$request->ip(), 255, ''),
                'activity' => 'login_failed',
            ]);
        } catch (\Throwable) {
            // Audit logging must never change the login response.
        }
    }

    private function isPageUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $apiPaths = [
            '/temperature', '/temperatures',
            '/device-status', '/ac-status',
            '/notifications/recent',
            '/dashboard/recent-activities',
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
            $user->last_logout_at = Carbon::now();
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
