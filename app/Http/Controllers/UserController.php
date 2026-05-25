<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::select('id', 'name', 'avatar', 'role', 'last_activity')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->where('role', $request->role);
            });

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');

        if (! in_array($sort, ['name', 'role', 'last_activity', 'created_at'])) {
            $sort = 'created_at';
        }
        if (! in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        $query->orderBy($sort, $order);

        $users = $query->paginate(15)->withQueryString();

        $totalUsers = User::count();

        $onlineUsers = User::where('last_activity', '>=', now()->subMinutes(2))
            ->count();
        $offlineUsers = $totalUsers - $onlineUsers;
        $adminUsers = User::where('role', 'admin')->count();

        $onlinePercentage = $totalUsers > 0 ? round(($onlineUsers / $totalUsers) * 100) : 0;
        $offlinePercentage = $totalUsers > 0 ? round(($offlineUsers / $totalUsers) * 100) : 0;

        $newUsersThisWeek = User::where('created_at', '>=', now()->subWeek())->count();

        return view('users.index', compact(
            'users',
            'totalUsers',
            'onlineUsers',
            'offlineUsers',
            'adminUsers',
            'onlinePercentage',
            'offlinePercentage',
            'newUsersThisWeek'
        ));
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => trim((string) $request->name),
            'role' => strtolower(trim((string) $request->role)),
        ]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[A-Za-z][A-Za-z0-9_]{2,19}$/',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(name) = ?', [strtolower($value)])->exists()) {
                        $fail('Username sudah digunakan.');
                    }
                },
            ],
            'password' => ['required', 'string', Password::min(8)->letters()->mixedCase()->numbers()],
            'role' => 'required|in:admin,operator,user',
        ], [
            'name.min' => 'Username minimal 3 karakter.',
            'name.max' => 'Username maksimal 20 karakter.',
            'name.regex' => 'Username 3–20 karakter, hanya huruf/angka/underscore, dan diawali huruf.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.letters' => 'Password harus mengandung huruf.',
            'password.mixed' => 'Password harus mengandung huruf besar dan kecil.',
            'password.numbers' => 'Password harus mengandung minimal 1 angka.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => '-',
            'ac' => $user->name.' ('.$user->role.')',
            'activity' => 'add_user',
        ]);

        return back()->with('success', 'User berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'role' => strtolower(trim((string) $request->role)),
        ]);

        $request->validate([
            'role' => 'required|in:admin,operator,user',
        ]);

        $user = User::findOrFail($id);

        if ((int) $id === Auth::id()) {
            return $this->respondError($request, 'Tidak bisa mengubah role sendiri', 403);
        }

        // Guard: jangan biarkan admin terakhir didemote
        if ($user->role === 'admin' && $request->role !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return $this->respondError($request, 'Tidak bisa mengubah role admin terakhir.', 422);
        }

        $previousRole = $user->role;

        $user->role = $request->role;
        $user->save();

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => '-',
            'ac' => $user->name.' ('.$previousRole.' -> '.$user->role.')',
            'activity' => 'update_role',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Role user berhasil diubah');
    }

    public function destroy(Request $request, $id)
    {
        if ((int) $id === Auth::id()) {
            return $this->respondError($request, 'Tidak bisa hapus diri sendiri', 403);
        }

        $user = User::findOrFail($id);

        // Guard: jangan biarkan admin terakhir terhapus
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return $this->respondError($request, 'Tidak bisa menghapus admin terakhir.', 422);
        }

        $deletedUserName = $user->name;
        $deletedUserRole = $user->role;

        $user->delete();

        UserLog::create([
            'user_id' => Auth::id(),
            'room' => '-',
            'ac' => $deletedUserName.' ('.$deletedUserRole.')',
            'activity' => 'delete_user',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'User berhasil dihapus');
    }

    private function respondError(Request $request, string $message, int $status)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['error' => $message], $status);
        }

        return back()->with('error', $message);
    }

    public function profile()
    {
        $user = Auth::user();

        return view('profile.index', compact('user'));
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'avatar.required' => 'Pilih file gambar dulu.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Format yang didukung: JPG, PNG, WEBP.',
            'avatar.max' => 'Ukuran maksimal 2 MB.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function deleteAvatar()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = null;
        $user->save();

        return back()->with('success', 'Foto profil dihapus.');
    }

    public function changePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required', 'string', 'confirmed', 'different:current_password',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.letters' => 'Password baru harus mengandung huruf.',
            'password.mixed' => 'Password baru harus mengandung huruf besar dan kecil.',
            'password.numbers' => 'Password baru harus mengandung minimal 1 angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // Invalidate session lain (mis. perangkat yang dicuri),
        Auth::logoutOtherDevices($request->password);
        $request->session()->regenerate();

        UserLog::create([
            'user_id' => $user->id,
            'room' => '-',
            'ac' => '-',
            'activity' => 'change_password',
        ]);

        return back()->with('success', 'Password berhasil diubah');
    }
}
