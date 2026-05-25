<?php

namespace App\Filament\Widgets;

use App\Models\Pelanggan;
use App\Models\TransaksiGas;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    // Di Filament v4, pollingInterval adalah non-static instance property
    protected ?string $pollingInterval = '10s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $bulan    = Carbon::now()->month;
        $tahun    = Carbon::now()->year;
        $namaBulan = Carbon::now()->translatedFormat('F Y');

        $totalPelanggan   = Pelanggan::where('status', true)->count();
        $transaksiHariIni = TransaksiGas::whereDate('tanggal_transaksi', today())->count();
        $tabungBulanIni   = TransaksiGas::whereMonth('tanggal_transaksi', $bulan)
                                ->whereYear('tanggal_transaksi', $tahun)
                                ->where('status_lapor', 'sukses') // Hanya hitung yang sudah sukses dilaporkan
                                ->sum('jumlah_tabung');

        // Pelanggan yang kuota habis bulan ini (total >= 4)
        // Dioptimalkan menggunakan query agregasi langsung pada tabel transaksi agar sangat ringan & instan
        $kuotaHabis = TransaksiGas::whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->selectRaw('pelanggan_id')
            ->groupBy('pelanggan_id')
            ->havingRaw('SUM(jumlah_tabung) >= 4')
            ->get()
            ->count();

        $belumDilaporkan = TransaksiGas::whereMonth('tanggal_transaksi', $bulan)
                                ->whereYear('tanggal_transaksi', $tahun)
                                ->where('dilaporkan_pertamina', false)
                                ->count();

        return [
            Stat::make('Total Pelanggan Aktif', number_format($totalPelanggan))
                ->description('Pelanggan bersubsidi terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Tabung Terjual ' . $namaBulan, number_format($tabungBulanIni) . ' tabung')
                ->description("Transaksi hari ini: {$transaksiHariIni} transaksi")
                ->descriptionIcon('heroicon-m-fire')
                ->color('primary'),

            Stat::make('NIK Kuota Habis', number_format($kuotaHabis) . ' NIK')
                ->description('Sudah capai 4 tabung bulan ini')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Belum Lapor ke Pertamina', number_format($belumDilaporkan) . ' transaksi')
                ->description('Perlu disinkronkan ke MAP Pertamina')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
