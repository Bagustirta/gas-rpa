<?php

namespace App\Filament\Resources\TransaksiGasResource\Pages;

use App\Filament\Resources\TransaksiGasResource;
use App\Models\Pelanggan;
use App\Models\TransaksiGas;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateTransaksiGas extends CreateRecord
{
    protected static string $resource = TransaksiGasResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Override handleRecordCreation untuk menerapkan validasi kuota sebelum simpan.
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Bersihkan field virtual dari form
        unset($data['nik_search'], $data['nama_preview'], $data['alamat_preview'], $data['kuota_preview']);

        $pelanggan = Pelanggan::find($data['pelanggan_id']);

        if (!$pelanggan) {
            Notification::make()
                ->title('Pelanggan tidak ditemukan!')
                ->danger()
                ->send();
            $this->halt();
        }

        // Hitung total tabung bulan ini
        $bulanTransaksi = \Carbon\Carbon::parse($data['tanggal_transaksi']);
        $totalSudah = TransaksiGas::where('pelanggan_id', $pelanggan->id)
            ->whereMonth('tanggal_transaksi', $bulanTransaksi->month)
            ->whereYear('tanggal_transaksi', $bulanTransaksi->year)
            ->sum('jumlah_tabung');

        $totalBaru = (int) $data['jumlah_tabung'];

        // KUNCI VALIDASI KUOTA: maksimal 4 tabung per bulan
        if (($totalSudah + $totalBaru) > 4) {
            $sisaKuota = 4 - $totalSudah;
            $pesanError = "Transaksi ditolak! NIK {$pelanggan->nik} ({$pelanggan->nama}) telah mencapai batas maksimal 4 tabung bulan ini. "
                . "Sudah terpakai: {$totalSudah} tabung. "
                . ($sisaKuota > 0 ? "Sisa kuota: {$sisaKuota} tabung." : "Kuota bulan ini habis.");

            Notification::make()
                ->title('❌ Transaksi Ditolak!')
                ->body($pesanError)
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        return static::getModel()::create($data);
    }
}
