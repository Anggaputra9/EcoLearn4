<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /* ============== USERS ============== */
    public function users(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $roleId = $request->get('role_id');

        $users = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->select('users.*', 'roles.nama_role')
            ->when($q, fn ($qq) => $qq->where(function ($w) use ($q) {
                $w->where('users.name', 'like', "%$q%")
                  ->orWhere('users.email', 'like', "%$q%");
            }))
            ->when($roleId, fn ($qq) => $qq->where('users.role_id', $roleId))
            ->orderByDesc('users.id')
            ->paginate(10)
            ->withQueryString();

        $roles = DB::table('roles')->get();

        return view('admin.users', compact('users', 'roles', 'q', 'roleId'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id'  => 'required|integer|exists:roles,id',
        ]);
        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id'  => $data['role_id'],
        ]);
        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role_id' => 'required|integer|exists:roles,id',
            'password'=> 'nullable|string|min:8',
        ]);
        $user->name    = $data['name'];
        $user->email   = $data['email'];
        $user->role_id = $data['role_id'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        $user->delete();
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    /* ============== MENUS ============== */
    public function menus(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $roleId = $request->get('role_id');

        $menus = DB::table('menus')
            ->join('roles', 'menus.role_id', '=', 'roles.id')
            ->select('menus.*', 'roles.nama_role')
            ->when($q, fn ($qq) => $qq->where(function ($w) use ($q) {
                $w->where('menus.nama_menu', 'like', "%$q%")
                  ->orWhere('menus.url', 'like', "%$q%");
            }))
            ->when($roleId, fn ($qq) => $qq->where('menus.role_id', $roleId))
            ->orderBy('menus.role_id')
            ->orderBy('menus.id')
            ->paginate(15)
            ->withQueryString();

        $roles = DB::table('roles')->get();

        return view('admin.menus', compact('menus', 'roles', 'q', 'roleId'));
    }

    public function storeMenu(Request $request)
    {
        $data = $request->validate([
            'nama_menu' => 'required|string|max:255',
            'url'       => 'required|string|max:255',
            'role_id'   => 'required|integer|exists:roles,id',
            'konten'    => 'nullable|string',
        ]);
        DB::table('menus')->insert($data + ['created_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Menu berhasil dibuat.');
    }

    public function updateMenu(Request $request, $id)
    {
        $data = $request->validate([
            'nama_menu' => 'required|string|max:255',
            'url'       => 'required|string|max:255',
            'role_id'   => 'required|integer|exists:roles,id',
            'konten'    => 'nullable|string',
        ]);
        DB::table('menus')->where('id', $id)->update($data + ['updated_at' => now()]);
        return back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroyMenu($id)
    {
        DB::table('menus')->where('id', $id)->delete();
        return back()->with('success', 'Menu berhasil dihapus.');
    }
}
