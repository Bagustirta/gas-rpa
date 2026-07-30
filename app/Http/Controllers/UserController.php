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
        return inertia(implode('/', ['Users', 'Index']), [
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:agen',
            'is_active' => 'boolean',
        ]);

        // Cek Keamanan Ekstra: Pembuatan Super Admin dinonaktifkan total dari antarmuka Web.
        if ($request->role === 'super_admin') {
            return redirect()->back()->with('error', 'Pembuatan Super Admin dinonaktifkan dari web untuk keamanan. Silakan ubah manual di Database.');
        }

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
            'role' => 'required|in:agen',
            'is_active' => 'boolean',
            'subscription_status' => 'required|in:trial,aktif,non-aktif',
            'subscription_expired_at' => 'nullable|date',
        ];

        // Jika password diisi, maka update password juga
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);

        // Cek Keamanan Ekstra: Pengangkatan Super Admin dinonaktifkan total dari antarmuka Web.
        if ($request->role === 'super_admin' && $user->role !== 'super_admin') {
            return redirect()->back()->with('error', 'Pengangkatan Super Admin dinonaktifkan dari web untuk keamanan. Silakan ubah manual di Database.');
        }

        // Cek Keamanan: Tidak boleh merubah role Super Admin (Demosi) selain oleh dirinya sendiri
        if ($user->role === 'super_admin' && $validated['role'] !== 'super_admin' && \Illuminate\Support\Facades\Auth::id() !== $user->id) {
            return redirect()->back()->with('error', 'Tidak diizinkan menurunkan jabatan Super Admin.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->is_active = $request->has('is_active') ? $request->is_active : true;
        
        $user->subscription_status = $validated['subscription_status'];
        $user->subscription_expired_at = $validated['subscription_expired_at'] ? \Carbon\Carbon::parse($validated['subscription_expired_at']) : null;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->back()->with('success', 'Data Agen berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === \Illuminate\Support\Facades\Auth::id() || $user->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus akun ini.');
        }

        // Sebaiknya delete cascade diatur di DB atau hapus manual relasinya. 
        // Untuk saat ini biarkan default Laravel / Constraint DB yang menangani
        $user->delete();

        return redirect()->back()->with('success', 'Akun Agen berhasil dihapus.');
    }

    public function quickRenew(Request $request, User $user)
    {
        $days = $request->integer('days', 30);
        
        $baseDate = ($user->subscription_expired_at && $user->subscription_expired_at->isFuture())
            ? $user->subscription_expired_at
            : \Carbon\Carbon::now();

        $user->update([
            'subscription_status' => 'aktif',
            'subscription_expired_at' => $baseDate->copy()->addDays($days),
        ]);

        return redirect()->back()->with('success', "Masa langganan {$user->name} berhasil diperpanjang +{$days} hari.");
    }
}
