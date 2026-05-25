<?php

namespace App\Filament\Resources\TransaksiGasResource\Pages;

use App\Filament\Resources\TransaksiGasResource;
use App\Models\TransaksiGas;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTransaksiGas extends EditRecord
{
    protected static string $resource = TransaksiGasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Override handleRecordUpdate untuk validasi kuota saat edit.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['nik_search'], $data['nama_preview'], $data['alamat_preview'], $data['kuota_preview']);

        $bulanTransaksi = \Carbon\Carbon::parse($data['tanggal_transaksi']);
        $totalSudah = TransaksiGas::where('pelanggan_id', $data['pelanggan_id'])
            ->where('id', '!=', $record->id) // exclude record yang sedang diedit
            ->whereMonth('tanggal_transaksi', $bulanTransaksi->month)
            ->whereYear('tanggal_transaksi', $bulanTransaksi->year)
            ->sum('jumlah_tabung');

        $totalBaru = (int) $data['jumlah_tabung'];

        if (($totalSudah + $totalBaru) > 4) {
            $sisaKuota = 4 - $totalSudah;
            Notification::make()
                ->title('❌ Transaksi Ditolak!')
                ->body("Transaksi ditolak! NIK ini telah mencapai batas maksimal 4 tabung bulan ini. Sudah terpakai: {$totalSudah} tabung. Sisa: {$sisaKuota} tabung.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        $record->update($data);

        return $record;
    }
}
