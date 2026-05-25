<?php

namespace App\Filament\Resources\TransaksiGasResource\Pages;

use App\Filament\Resources\TransaksiGasResource;
use App\Models\Pelanggan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransaksiGas extends ListRecords
{
    protected static string $resource = TransaksiGasResource::class;

    protected function getExposedTableWidgetData(): array
    {
        $data = parent::getExposedTableWidgetData();
        $data['tableColumnSearches'] = $data['tableColumnSearches'] ?? [];
        $data['tableFilters'] = $data['tableFilters'] ?? [];
        return $data;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\TransaksiGasResource\Widgets\TransaksiGasStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('trigger_rpa')
                ->label('Mulai Sync Pertamina (RPA)')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Mulai Sinkronisasi Otomatis')
                ->modalDescription('Ini akan mengaktifkan Bot RPA di PC Anda untuk login secara berantai (sequential) ke semua akun pangkalan terdaftar, lalu menginput transaksi yang belum terlaporkan.')
                ->action(function () {
                    $belumLaporCount = \App\Models\TransaksiGas::whereMonth('tanggal_transaksi', now()->month)
                        ->whereYear('tanggal_transaksi', now()->year)
                        ->where('status_lapor', 'belum_lapor')
                        ->whereHas('akunPangkalan', function ($q) {
                            $q->where('user_id', auth()->id());
                        })
                        ->count();

                    if ($belumLaporCount === 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Semua Transaksi Sudah Dilaporkan')
                            ->body('Tidak ada transaksi baru bulan ini yang perlu dilaporkan ke Pertamina.')
                            ->success()
                            ->send();
                        return;
                    }

                    $activeSync = \App\Models\RpaSync::where('user_id', auth()->id())
                        ->whereIn('status', ['pending', 'processing'])
                        ->exists();
                    if ($activeSync) {
                        \Filament\Notifications\Notification::make()
                            ->title('Proses Sinkronisasi Sedang Berjalan')
                            ->body('RPA Bot sedang bekerja atau dalam antrean. Mohon tunggu proses sebelumnya selesai.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $sync = \App\Models\RpaSync::create([
                        'status' => 'pending',
                        'total_records' => $belumLaporCount,
                        'user_id' => auth()->id(),
                    ]);

                    // Panggil Bot RPA secara otomatis di Windows
                    if (str_contains(PHP_OS_FAMILY, 'Windows')) {
                        $botPath = base_path('rpa-bot');
                        pclose(popen("start cmd /k \"cd /d {$botPath} && node bot.js --sync-id={$sync->id}\"", "r"));
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Perintah Sinkronisasi Dikirim!')
                        ->body("Antrean dibuat untuk {$belumLaporCount} transaksi. Bot RPA otomatis berjalan berantai.")
                        ->info()
                        ->send();
                }),
            Actions\Action::make('audit_quota')
                ->label('Cek Kuota Pertamina (Audit)')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Mulai Audit Kuota Pelanggan')
                ->modalDescription('Ini akan otomatis membuka browser Chrome dan terminal di PC Anda untuk melakukan audit/pencocokan kuota pelanggan bulan berjalan secara berurutan di seluruh pangkalan terdaftar Anda.')
                ->action(function () {
                    $userId = auth()->id();

                    // Panggil Bot Audit secara otomatis di Windows
                    if (str_contains(PHP_OS_FAMILY, 'Windows')) {
                        $botPath = base_path('rpa-bot');
                        pclose(popen("start cmd /k \"cd /d {$botPath} && node check-quota.js --user-id {$userId}\"", "r"));
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Audit Kuota Dimulai')
                        ->body('Terminal audit telah terbuka di komputer Anda. Bot sedang melakukan pencocokan kuota untuk semua pangkalan Anda secara berantai.')
                        ->info()
                        ->send();
                }),
            Actions\Action::make('reset_sync')
                ->label('Reset Status Sync')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Antrean Sinkronisasi')
                ->modalDescription('Gunakan ini jika status sinkronisasi macet di "PROCESSING" akibat bot dimatikan paksa. Ini akan mengubah status antrean terakhir menjadi GAGAL.')
                ->visible(fn () => \App\Models\RpaSync::where('user_id', auth()->id())->whereIn('status', ['pending', 'processing'])->exists())
                ->action(function () {
                    \App\Models\RpaSync::where('user_id', auth()->id())->whereIn('status', ['pending', 'processing'])
                        ->update([
                            'status' => 'failed',
                            'message' => 'Dibatalkan/direset secara manual dari panel admin.',
                            'completed_at' => now(),
                        ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Status Sinkronisasi Direset')
                        ->body('Semua antrean yang menggantung telah diatur menjadi FAILED. Anda sekarang dapat memulai sinkronisasi baru.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('distribusi_otomatis')
                ->label('Distribusi Otomatis')
                ->icon('heroicon-o-cpu-chip')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('akun_pangkalan_id')
                        ->label('Akun Pangkalan')
                        ->options(\App\Models\AkunPangkalan::where('user_id', auth()->id())->pluck('nama_pangkalan', 'id'))
                        ->required()
                        ->helperText('Seluruh pelanggan aktif Anda akan diprioritaskan menerima alokasi tabung di pangkalan ini.'),
                    \Filament\Forms\Components\TextInput::make('total_tabung')
                        ->label('Total Tabung yang Diterima')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->helperText('Sistem akan mengalokasikan tabung ini ke pelanggan secara otomatis berdasarkan sisa kuota mereka.'),
                    \Filament\Forms\Components\DatePicker::make('tanggal_transaksi')
                        ->label('Tanggal Transaksi')
                        ->required()
                        ->default(now())
                        ->maxDate(now()),
                ])
                ->modalHeading('Distribusi Tabung Otomatis')
                ->modalDescription('Fitur ini akan otomatis membuat transaksi penjualan gas LPG 3 Kg untuk pelanggan yang kuotanya masih tersedia sampai seluruh tabung teralokasikan.')
                ->action(function (array $data) {
                    $totalTarget = (int) $data['total_tabung'];
                    $tanggal = $data['tanggal_transaksi'];
                    $baseDate = \Carbon\Carbon::parse($tanggal);
                    $bulan = $baseDate->month;
                    $tahun = $baseDate->year;
                    $pangkalanId = $data['akun_pangkalan_id'];

                    // Ambil pelanggan aktif milik user
                    $pelangganList = Pelanggan::where('status', true)
                        ->where('user_id', auth()->id())
                        ->get();

                    if ($pelangganList->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal')
                            ->body('Tidak ada pelanggan aktif yang terdaftar di akun Anda.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $allocated = 0;
                    $createdCount = 0;

                    // Lakukan pengalokasian bertahap (multi-pass)
                    $startOfWeek = $baseDate->copy()->startOfWeek();
                    $endOfWeek = $baseDate->copy()->endOfWeek();

                    while ($allocated < $totalTarget) {
                        $candidates = [];
                        foreach ($pelangganList as $p) {
                            $sisaBulan = 4 - $p->totalTabungBulanIni($bulan, $tahun);
                            if ($sisaBulan <= 0) {
                                continue;
                            }

                            $sisaMinggu = 1 - \App\Models\TransaksiGas::where('pelanggan_id', $p->id)
                                ->where('akun_pangkalan_id', $pangkalanId)
                                ->whereBetween('tanggal_transaksi', [$startOfWeek, $endOfWeek])
                                ->sum('jumlah_tabung');

                            if ($sisaMinggu > 0) {
                                $candidates[] = $p;
                            }
                        }

                        if (empty($candidates)) {
                            break;
                        }

                        shuffle($candidates);

                        foreach ($candidates as $p) {
                            if ($allocated >= $totalTarget) {
                                break;
                            }

                            // Acak jam transaksi antara 08:00 sampai 17:00
                            $randomHour = rand(8, 16);
                            $randomMinute = rand(0, 59);
                            $randomSecond = rand(0, 59);
                            $waktuTransaksi = $baseDate->copy()->setTime($randomHour, $randomMinute, $randomSecond);

                            \App\Models\TransaksiGas::create([
                                'pelanggan_id' => $p->id,
                                'akun_pangkalan_id' => $pangkalanId,
                                'jumlah_tabung' => 1,
                                'tanggal_transaksi' => $waktuTransaksi,
                                'dilaporkan_pertamina' => false,
                                'status_lapor' => 'belum_lapor',
                            ]);

                            $allocated++;
                            $createdCount++;
                        }
                    }
                    if ($allocated < $totalTarget) {
                        \Filament\Notifications\Notification::make()
                            ->title('Distribusi Selesai Sebagian')
                            ->body("Berhasil membuat {$createdCount} transaksi. Sebanyak " . ($totalTarget - $allocated) . " tabung tidak teralokasikan karena kuota mingguan/bulanan seluruh pelanggan aktif bulan ini sudah penuh.")
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Distribusi Otomatis Berhasil')
                            ->body("Berhasil membuat {$createdCount} transaksi baru secara otomatis dengan waktu terdistribusi acak.")
                            ->success()
                            ->send();
                    }
                }),

            Actions\CreateAction::make()
                ->label('Catat Transaksi Baru'),
        ];
    }
}
