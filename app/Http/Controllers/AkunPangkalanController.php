<?php

namespace App\Http\Controllers;

use App\Models\AkunPangkalan;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AkunPangkalanController extends Controller
{
    public function index()
    {
        $akuns = AkunPangkalan::where('user_id', Auth::id())
            ->orderBy('nama_pangkalan')
            ->get();

        return Inertia::render('AkunPangkalan/Index', [
            'akuns' => $akuns,
        ]);
    }

    public function store(Request $request)
    {
        if (\App\Models\AkunPangkalan::where('user_id', Auth::id())->count() >= 2) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'nama_pangkalan' => 'Batas maksimal 2 Pangkalan telah tercapai untuk akun Anda.',
            ]);
        }

        $validated = $request->validate([
            'nama_pangkalan' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $akun = AkunPangkalan::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_pangkalan',
            'description' => "Menambahkan akun pangkalan baru: {$akun->nama_pangkalan}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('akun-pangkalan.index')->with('success', 'Akun Pangkalan berhasil ditambahkan.');
    }

    public function update(Request $request, AkunPangkalan $akunPangkalan)
    {
        if ($akunPangkalan->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'nama_pangkalan' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $akunPangkalan->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_pangkalan',
            'description' => "Memperbarui data pangkalan: {$akunPangkalan->nama_pangkalan}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('akun-pangkalan.index')->with('success', 'Akun Pangkalan berhasil diperbarui.');
    }

    public function destroy(AkunPangkalan $akunPangkalan)
    {
        if ($akunPangkalan->user_id !== Auth::id()) {
            abort(403);
        }

        $namaPangkalan = $akunPangkalan->nama_pangkalan;
        $akunPangkalan->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_pangkalan',
            'description' => "Menghapus akun pangkalan: {$namaPangkalan}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('akun-pangkalan.index')->with('success', 'Akun Pangkalan berhasil dihapus.');
    }
}
