<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        // Hanya super_admin yang bisa akses ini (nanti di middleware / web.php)
        $users = User::withCount('akunPangkalans')->latest()->get();
        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:super_admin,agen',
            'is_active' => 'boolean',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        return redirect()->back()->with('success', 'Akun Agen berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:super_admin,agen',
            'is_active' => 'boolean',
        ];

        // Jika password diisi, maka update password juga
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->is_active = $request->has('is_active') ? $request->is_active : true;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->back()->with('success', 'Data Agen berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id() || $user->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus akun ini.');
        }

        // Sebaiknya delete cascade diatur di DB atau hapus manual relasinya. 
        // Untuk saat ini biarkan default Laravel / Constraint DB yang menangani
        $user->delete();

        return redirect()->back()->with('success', 'Akun Agen berhasil dihapus.');
    }
}
