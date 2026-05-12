<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminController extends Controller
{
    // === CRUD PENGGUNA ===
    public function users() {
        $users = DB::table('users')->join('roles', 'users.role_id', '=', 'roles.id')->select('users.*', 'roles.nama_role')->orderBy('users.id', 'desc')->get();
        return view('admin.users', compact('users'));
    }
    public function createUser() {
        $roles = DB::table('roles')->get(); return view('admin.users-form', compact('roles'));
    }
    public function storeUser(Request $request) {
        $request->validate(['name' => 'required|string|max:255', 'email' => 'required|string|email|max:255|unique:users', 'password' => 'required|string|min:8', 'role_id' => 'required|integer|exists:roles,id']);
        User::create(['name' => $request->name, 'email' => $request->email, 'password' => Hash::make($request->password), 'role_id' => $request->role_id]);
        return redirect('/admin/users')->with('success', 'Pengguna berhasil ditambahkan!');
    }
    public function editUser($id) {
        $user = User::findOrFail($id); $roles = DB::table('roles')->get(); return view('admin.users-form', compact('user', 'roles'));
    }
    public function updateUser(Request $request, $id) {
        $user = User::findOrFail($id);
        $request->validate(['name' => 'required|string|max:255', 'email' => 'required|string|email|max:255|unique:users,email,'.$user->id, 'role_id' => 'required|integer|exists:roles,id']);
        $user->name = $request->name; $user->email = $request->email; $user->role_id = $request->role_id;
        if ($request->filled('password')) { $user->password = Hash::make($request->password); }
        $user->save(); return redirect('/admin/users')->with('success', 'Pengguna berhasil diperbarui!');
    }
    public function destroyUser($id) {
        $user = User::findOrFail($id); if ($user->id === auth()->id()) return redirect('/admin/users')->with('error', 'Tidak bisa menghapus akun sendiri!');
        $user->delete(); return redirect('/admin/users')->with('success', 'Pengguna berhasil dihapus!');
    }

    // === CRUD MENU (DENGAN KONTEN) ===
    public function menus() {
        $menus = DB::table('menus')->join('roles', 'menus.role_id', '=', 'roles.id')->select('menus.*', 'roles.nama_role')->orderBy('menus.role_id', 'asc')->orderBy('menus.id', 'asc')->get();
        return view('admin.menus', compact('menus'));
    }
    public function createMenu() {
        $roles = DB::table('roles')->get(); return view('admin.menus-form', compact('roles'));
    }
    public function storeMenu(Request $request) {
        $request->validate(['nama_menu' => 'required|string|max:255', 'url' => 'required|string|max:255', 'role_id' => 'required|integer|exists:roles,id', 'konten' => 'nullable|string']);
        DB::table('menus')->insert(['nama_menu' => $request->nama_menu, 'url' => $request->url, 'role_id' => $request->role_id, 'konten' => $request->konten]);
        return redirect('/admin/menus')->with('success', 'Menu dan halaman baru berhasil dibuat!');
    }
    public function editMenu($id) {
        $menu = DB::table('menus')->where('id', $id)->first(); if (!$menu) abort(404);
        $roles = DB::table('roles')->get(); return view('admin.menus-form', compact('menu', 'roles'));
    }
    public function updateMenu(Request $request, $id) {
        $request->validate(['nama_menu' => 'required|string|max:255', 'url' => 'required|string|max:255', 'role_id' => 'required|integer|exists:roles,id', 'konten' => 'nullable|string']);
        DB::table('menus')->where('id', $id)->update(['nama_menu' => $request->nama_menu, 'url' => $request->url, 'role_id' => $request->role_id, 'konten' => $request->konten]);
        return redirect('/admin/menus')->with('success', 'Data menu dan halaman berhasil diperbarui!');
    }
    public function destroyMenu($id) {
        DB::table('menus')->where('id', $id)->delete(); return redirect('/admin/menus')->with('success', 'Menu berhasil dihapus!');
    }
}
