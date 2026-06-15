<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Imports\PelangganImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Carbon\Carbon;

class PelangganController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $bulan = Carbon::now()->month;
        $tahun = Carbon::now()->year;

        $query = Pelanggan::where('user_id', $userId)
            ->withSum(['transaksiGas as total_tabung_bulan_ini' => function ($query) use ($bulan, $tahun) {
                $query->whereMonth('tanggal_transaksi', $bulan)
                      ->whereYear('tanggal_transaksi', $tahun);
            }], 'jumlah_tabung');

        if ($request->filled('search')) {
            $query->where('nik', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $status = $request->status === 'aktif';
            $query->where('status', $status);
        }

        if ($request->filled('sisa_kuota')) {
            $sisa = (int) $request->sisa_kuota;
            $targetUsed = 4 - $sisa;
            
            $query->where(function ($q) use ($bulan, $tahun, $sisa, $targetUsed) {
                $subquery = \App\Models\TransaksiGas::whereColumn('pelanggan_id', 'pelanggan.id')
                    ->whereMonth('tanggal_transaksi', $bulan)
                    ->whereYear('tanggal_transaksi', $tahun)
                    ->selectRaw('COALESCE(SUM(jumlah_tabung), 0)');

                if ($sisa === 4) {
                    $q->whereRaw("({$subquery->toSql()}) = 0", $subquery->getBindings());
                } elseif ($sisa === 0) {
                    $q->whereRaw("({$subquery->toSql()}) >= 4", $subquery->getBindings());
                } else {
                    $q->whereRaw("({$subquery->toSql()}) = ?", array_merge($subquery->getBindings(), [$targetUsed]));
                }
            });
        }

        $pelanggans = $query->orderBy('nik')
            ->paginate(15)
            ->withQueryString()
            ->through(fn($item) => [
                'id' => $item->id,
                'nik' => $item->nik,
                'status' => (bool)$item->status,
                'total_tabung_bulan_ini' => (int) ($item->total_tabung_bulan_ini ?? 0),
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Pelanggan/Index', [
            'pelanggans' => $pelanggans,
            'filters' => $request->only(['search', 'status', 'sisa_kuota']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'size:16', 'regex:/^\d{16}$/', 'unique:pelanggan,nik'],
            'status' => ['required', 'boolean'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.size' => 'NIK harus tepat 16 digit.',
            'nik.regex' => 'NIK hanya boleh berisi angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
        ]);

        Pelanggan::create([
            'nik' => $validated['nik'],
            'status' => $validated['status'],
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('pelanggan.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, Pelanggan $pelanggan)
    {
        if ($pelanggan->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'nik' => ['required', 'string', 'size:16', 'regex:/^\d{16}$/', 'unique:pelanggan,nik,' . $pelanggan->id],
            'status' => ['required', 'boolean'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.size' => 'NIK harus tepat 16 digit.',
            'nik.regex' => 'NIK hanya boleh berisi angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
        ]);

        $pelanggan->update($validated);

        return redirect()->route('pelanggan.index')->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function toggleStatus(Pelanggan $pelanggan)
    {
        if ($pelanggan->user_id !== Auth::id()) {
            abort(403);
        }

        $pelanggan->update([
            'status' => !$pelanggan->status,
        ]);

        return redirect()->route('pelanggan.index')->with('success', 'Status pelanggan berhasil diubah.');
    }

    public function destroy(Pelanggan $pelanggan)
    {
        if ($pelanggan->user_id !== Auth::id()) {
            abort(403);
        }

        $pelanggan->delete();

        return redirect()->route('pelanggan.index')->with('success', 'Pelanggan berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');
        $filePath = Storage::disk('local')->path($path);

        $import = new PelangganImport(Auth::id());
        Excel::import($import, $filePath);

        Storage::disk('local')->delete($path);

        $imported = $import->getImportedCount();
        $skipped = $import->getSkippedCount();
        $errors = $import->getErrors();

        $message = "Import berhasil: {$imported} diimport.";
        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati.";
        }

        if ($imported > 0) {
            if (!empty($errors)) {
                return redirect()->route('pelanggan.index')
                    ->with('success', $message)
                    ->with('error', 'Beberapa baris gagal: ' . implode(', ', array_slice($errors, 0, 3)));
            }
            return redirect()->route('pelanggan.index')->with('success', $message);
        }

        return redirect()->route('pelanggan.index')->with('error', 'Gagal mengimport file: ' . ($errors[0] ?? 'Format file salah.'));
    }
}
