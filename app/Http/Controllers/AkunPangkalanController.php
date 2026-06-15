<?php

namespace App\Http\Controllers;

use App\Models\AkunPangkalan;
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
        $validated = $request->validate([
            'nama_pangkalan' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        AkunPangkalan::create([
            ...$validated,
            'user_id' => Auth::id(),
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
            'password' => ['required', 'string', 'max:255'],
        ]);

        $akunPangkalan->update($validated);

        return redirect()->route('akun-pangkalan.index')->with('success', 'Akun Pangkalan berhasil diperbarui.');
    }

    public function destroy(AkunPangkalan $akunPangkalan)
    {
        if ($akunPangkalan->user_id !== Auth::id()) {
            abort(403);
        }

        $akunPangkalan->delete();

        return redirect()->route('akun-pangkalan.index')->with('success', 'Akun Pangkalan berhasil dihapus.');
    }
}
