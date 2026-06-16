<?php

namespace App\Http\Controllers;

use App\Events\UsersChanged;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

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

        $authId = Auth::id();
        $items = $users->getCollection()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => null,
            'avatar_url' => $u->avatar_url,
            'role' => $u->role,
            'is_online' => $u->last_activity && $u->last_activity >= now()->subMinutes(2),
            'is_self' => $u->id === $authId,
        ])->values();

        return Inertia::render('Users', [
            'users' => $items,
            'stats' => [
                'total' => $totalUsers,
                'online' => $onlineUsers,
                'offline' => $offlineUsers,
                'admins' => $adminUsers,
                'online_pct' => $onlinePercentage,
                'offline_pct' => $offlinePercentage,
                'new_this_week' => $newUsersThisWeek,
            ],
            'filters' => [
                'search' => $request->query('search', ''),
                'role' => $request->query('role', ''),
            ],
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem() ?? 0,
                'to' => $users->lastItem() ?? 0,
                'total' => $users->total(),
                'prev_url' => $users->previousPageUrl(),
                'next_url' => $users->nextPageUrl(),
            ],
        ]);
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
                'regex:/^[A-Za-z][A-Za-z0-9_ ]{2,19}$/',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(name) = ?', [strtolower($value)])->exists()) {
                        $fail('Username is already taken.');
                    }
                },
            ],
            'password' => ['required', 'string', Password::min(8)->letters()->mixedCase()->numbers()],
            'role' => 'required|in:admin,operator,user',
        ], [
            'name.min' => 'Username must be at least 3 characters.',
            'name.max' => 'Username must be at most 20 characters.',
            'name.regex' => 'Username must be 3–20 characters (letters, numbers, underscore, spaces) and start with a letter.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.letters' => 'Password must contain letters.',
            'password.mixed' => 'Password must contain both uppercase and lowercase letters.',
            'password.numbers' => 'Password must contain at least 1 number.',
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

        $this->broadcastUsersChanged('created');

        return back()->with('success', 'User berhasil ditambahkan');
    }

    public function update(Request $request, int|string $id)
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

    public function destroy(Request $request, int|string $id)
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

        $this->broadcastUsersChanged('deleted');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'User berhasil dihapus');
    }

    private function broadcastUsersChanged(string $action): void
    {
        try {
            event(new UsersChanged($action));
        } catch (\Throwable $e) {
            // broadcasting opsional — jangan gagalkan request kalau Reverb mati.
        }
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
        /** @var User $user */
        $user = Auth::user();

        return Inertia::render('Profile', [
            'profileUser' => [
                'name' => $user->name,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
                'has_avatar' => (bool) $user->avatar,
                'joined' => $user->created_at?->format('d M Y'),
                'last_login' => $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'Never',
            ],
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'avatar.required' => 'Pilih file gambar dulu.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Format yang didukung: JPG, PNG, WEBP.',
            'avatar.max' => 'Ukuran maksimal 5 MB.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'avatar_url' => $user->avatar_url,
                'message' => 'Foto profil berhasil diperbarui.',
            ]);
        }

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
            'current_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'New password must be at least 8 characters.',
            'password.letters' => 'New password must contain letters.',
            'password.mixed' => 'New password must contain both uppercase and lowercase letters.',
            'password.numbers' => 'New password must contain at least 1 number.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.different' => 'New password must be different from the current password.',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
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

        return back()->with('success', 'Password changed successfully.');
    }
}
