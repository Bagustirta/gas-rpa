<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\ActivityLog;
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
            $query->where('nik_bindex', hash_hmac('sha256', $request->search, config('app.key')));
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
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString()
            ->through(fn($item) => [
                'id' => $item->id,
                'nik' => $item->nik,
                'status' => (bool)$item->status,
                'total_tabung_bulan_ini' => (int) ($item->total_tabung_bulan_ini ?? 0),
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
            ]);

        $viewPath = base64_decode('UGVsYW5nZ2FuL0luZGV4'); // Pelanggan/Index
        return inertia($viewPath, [
            'pelanggans' => $pelanggans,
            'filters' => $request->only(['search', 'status', 'sisa_kuota']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => [
                'required', 'string', 'size:16', 'regex:/^\d{16}$/',
                function ($attribute, $value, $fail) {
                    if (Pelanggan::where('nik_bindex', hash_hmac('sha256', $value, config('app.key')))->exists()) {
                        $fail('NIK sudah terdaftar.');
                    }
                }
            ],
            'status' => ['required', 'boolean'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.size' => 'NIK harus tepat 16 digit.',
            'nik.regex' => 'NIK hanya boleh berisi angka.',
        ]);

        Pelanggan::create([
            'nik' => $validated['nik'],
            'status' => $validated['status'],
            'user_id' => Auth::id(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_pelanggan',
            'description' => "Menambahkan pelanggan baru dengan NIK tersensor",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect('/pelanggan')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, Pelanggan $pelanggan)
    {
        if ($pelanggan->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'nik' => [
                'required', 'string', 'size:16', 'regex:/^\d{16}$/',
                function ($attribute, $value, $fail) use ($pelanggan) {
                    if (Pelanggan::where('nik_bindex', hash_hmac('sha256', $value, config('app.key')))
                        ->where('id', '!=', $pelanggan->id)->exists()) {
                        $fail('NIK sudah terdaftar pada pelanggan lain.');
                    }
                }
            ],
            'status' => ['required', 'boolean'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.size' => 'NIK harus tepat 16 digit.',
            'nik.regex' => 'NIK hanya boleh berisi angka.',
        ]);

        $pelanggan->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_pelanggan',
            'description' => "Memperbarui data pelanggan NIK tersensor",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect('/pelanggan')->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function toggleStatus(Pelanggan $pelanggan)
    {
        if ($pelanggan->user_id !== Auth::id()) {
            abort(403);
        }

        $pelanggan->update([
            'status' => !$pelanggan->status,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'toggle_pelanggan',
            'description' => "Mengubah status pelanggan menjadi " . ($pelanggan->status ? 'Aktif' : 'Nonaktif'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect('/pelanggan')->with('success', 'Status pelanggan berhasil diubah.');
    }

    public function destroy(Pelanggan $pelanggan)
    {
        if ($pelanggan->user_id !== Auth::id()) {
            abort(403);
        }

        $pelanggan->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_pelanggan',
            'description' => "Menghapus data pelanggan",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect('/pelanggan')->with('success', 'Pelanggan berhasil dihapus.');
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
                return redirect('/pelanggan')
                    ->with('success', $message)
                    ->with('error', 'Beberapa baris gagal: ' . implode(', ', array_slice($errors, 0, 3)));
            }
            return redirect('/pelanggan')->with('success', $message);
        }

        return redirect('/pelanggan')->with('error', 'Semua baris data gagal diimpor. Periksa format NIK (harus 16 angka) atau pastikan tidak ada NIK duplikat.');
    }
}
