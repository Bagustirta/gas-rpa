<?php

namespace App\Filament\Resources\TransaksiGasResource\Pages;

use App\Filament\Resources\TransaksiGasResource;
use App\Models\Pelanggan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransaksiGas extends ListRecords
{
    protected static string $resource = TransaksiGasResource::class;

    public function mount(): void
    {
        parent::mount();

        // Bersihkan otomatis data transaksi bulan-bulan sebelumnya
        \App\Models\TransaksiGas::where('tanggal_transaksi', '<', now()->startOfMonth())->delete();
    }

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
                        ->maxDate(now()->endOfMonth())
                        ->helperText('Transaksi akan diutamakan pada minggu dari tanggal ini, lalu didistribusikan ke minggu lain pada bulan yang sama jika kuota minggu ini habis.'),
                ])
                ->modalHeading('Distribusi Tabung Otomatis')
                ->modalDescription('Fitur ini akan otomatis membuat transaksi penjualan gas LPG 3 Kg (maksimal 1 tabung per minggu per pelanggan) secara merata di seluruh minggu pada bulan berjalan.')
                ->action(function (array $data) {
                    $totalTarget    = (int) $data['total_tabung'];
                    $tanggal        = $data['tanggal_transaksi'];
                    $baseDate       = \Carbon\Carbon::parse($tanggal);
                    $bulan          = $baseDate->month;
                    $tahun          = $baseDate->year;
                    $pangkalanId    = $data['akun_pangkalan_id'];

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

                    // 1. Generate semua minggu di bulan ini (ISO Week: Senin - Minggu)
                    $startOfMonth = $baseDate->copy()->startOfMonth();
                    $endOfMonth = $baseDate->copy()->endOfMonth();

                    $weeks = [];
                    $current = $startOfMonth->copy();
                    while ($current->lte($endOfMonth)) {
                        $sOfWeek = $current->copy()->startOfWeek();
                        $eOfWeek = $current->copy()->endOfWeek();
                        
                        $weekKey = $sOfWeek->format('Y-m-d') . '_' . $eOfWeek->format('Y-m-d');
                        if (!isset($weeks[$weekKey])) {
                            // Tanggal transaksi riil harus dibatasi dalam bulan ini
                            $intersectStart = $sOfWeek->copy()->max($startOfMonth);
                            $intersectEnd = $eOfWeek->copy()->min($endOfMonth);
                            
                            $weeks[$weekKey] = [
                                'query_start' => $sOfWeek,
                                'query_end' => $eOfWeek,
                                'trx_start' => $intersectStart,
                                'trx_end' => $intersectEnd,
                            ];
                        }
                        $current->addDay();
                    }

                    // 2. Urutkan minggu: prioritaskan minggu dari tanggal_transaksi yang dipilih user, baru minggu lainnya
                    $selectedWeekKey = $baseDate->copy()->startOfWeek()->format('Y-m-d') . '_' . $baseDate->copy()->endOfWeek()->format('Y-m-d');
                    
                    $prioritizedWeeks = [];
                    if (isset($weeks[$selectedWeekKey])) {
                        $prioritizedWeeks[] = $weeks[$selectedWeekKey];
                        unset($weeks[$selectedWeekKey]);
                    }
                    foreach ($weeks as $w) {
                        $prioritizedWeeks[] = $w;
                    }

                    // 3. Distribusikan tabung (1 per minggu per pelanggan, max 4 per bulan, maks 1 per hari lintas pangkalan)
                    $allocated = 0;
                    $createdCount = 0;

                    // Track kuota bulanan pelanggan secara in-memory untuk menghemat query dan akurat
                    $customerMonthlyTotals = [];
                    foreach ($pelangganList as $p) {
                        $customerMonthlyTotals[$p->id] = $p->totalTabungBulanIni($bulan, $tahun);
                    }

                    // Ambil semua tanggal transaksi yang sudah ada di bulan ini untuk tiap pelanggan (lintas seluruh pangkalan)
                    $existingTrxDates = \App\Models\TransaksiGas::whereMonth('tanggal_transaksi', $bulan)
                        ->whereYear('tanggal_transaksi', $tahun)
                        ->select('pelanggan_id', 'tanggal_transaksi')
                        ->get()
                        ->groupBy('pelanggan_id')
                        ->map(function ($trxs) {
                            return $trxs->map(fn ($t) => \Carbon\Carbon::parse($t->tanggal_transaksi)->toDateString())
                                ->unique()
                                ->values()
                                ->toArray();
                        })
                        ->toArray();

                    $newTrxDates = []; // Track tanggal transaksi baru yang dibuat di run/request ini
                    $newTrxDatesForPangkalan = []; // Track jumlah alokasi per pangkalan per minggu di run/request ini

                    foreach ($prioritizedWeeks as $week) {
                        $weekKey = $week['query_start']->format('Y-m-d') . '_' . $week['query_end']->format('Y-m-d');
                        
                        if ($allocated >= $totalTarget) {
                            break;
                        }

                        // Tentukan tanggal transaksi untuk minggu ini (sesuai hari dari tanggal_transaksi yang dipilih)
                        $targetDate = $week['query_start']->copy()->startOfWeek()->addDays($baseDate->dayOfWeek - 1);
                        // Batasi agar tetap berada di bulan berjalan
                        if ($targetDate->lt($startOfMonth)) {
                            $targetDate = $startOfMonth->copy();
                        } elseif ($targetDate->gt($endOfMonth)) {
                            $targetDate = $endOfMonth->copy();
                        }
                        
                        $dStr = $targetDate->toDateString();

                        // Cari pelanggan yang memenuhi syarat di minggu ini
                        $candidates = [];
                        foreach ($pelangganList as $p) {
                            // Cek limit bulanan
                            if ($customerMonthlyTotals[$p->id] >= 4) {
                                continue;
                            }

                            // Cek limit mingguan di pangkalan terpilih (gabungan DB + in-memory baru)
                            $weeklySum = \App\Models\TransaksiGas::where('pelanggan_id', $p->id)
                                ->where('akun_pangkalan_id', $pangkalanId)
                                ->whereBetween('tanggal_transaksi', [$week['query_start'], $week['query_end']])
                                ->sum('jumlah_tabung');

                            $newWeeklySum = $newTrxDatesForPangkalan[$p->id][$weekKey] ?? 0;

                            if (($weeklySum + $newWeeklySum) >= 1) {
                                continue;
                            }

                            // Cek apakah hari ini kosong lintas pangkalan
                            $isFree = !in_array($dStr, $existingTrxDates[$p->id] ?? []) 
                                && !in_array($dStr, $newTrxDates[$p->id] ?? []);

                            if ($isFree) {
                                $candidates[] = $p;
                            }
                        }

                        // Acak daftar kandidat agar merata
                        shuffle($candidates);

                        foreach ($candidates as $p) {
                            if ($allocated >= $totalTarget) {
                                break;
                            }

                            if ($customerMonthlyTotals[$p->id] >= 4) {
                                continue;
                            }

                            // Pastikan kembali hari ini belum terpakai oleh transaksi baru dari pelanggan ini (double check)
                            if (in_array($dStr, $newTrxDates[$p->id] ?? [])) {
                                continue;
                            }

                            // Acak jam transaksi antara 08:00 sampai 16:59
                            $randomHour = rand(8, 16);
                            $randomMinute = rand(0, 59);
                            $randomSecond = rand(0, 59);
                            $waktuTransaksi = $targetDate->copy()->setTime($randomHour, $randomMinute, $randomSecond);

                            \App\Models\TransaksiGas::create([
                                'pelanggan_id' => $p->id,
                                'akun_pangkalan_id' => $pangkalanId,
                                'jumlah_tabung' => 1,
                                'tanggal_transaksi' => $waktuTransaksi,
                                'dilaporkan_pertamina' => false,
                                'status_lapor' => 'belum_lapor',
                            ]);

                            $customerMonthlyTotals[$p->id]++;
                            $newTrxDates[$p->id][] = $dStr;
                            $newTrxDatesForPangkalan[$p->id][$weekKey] = ($newTrxDatesForPangkalan[$p->id][$weekKey] ?? 0) + 1;
                            $allocated++;
                            $createdCount++;
                        }
                    }

                    if ($allocated < $totalTarget) {
                        $sisa = $totalTarget - $allocated;
                        \Filament\Notifications\Notification::make()
                            ->title("Distribusi Selesai Sebagian")
                            ->body("Berhasil membuat {$createdCount} transaksi ({$allocated} tabung). Sisa {$sisa} tabung tidak teralokasikan karena seluruh kuota bulanan/harian pelanggan aktif Anda sudah penuh.")
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title("Distribusi Otomatis Berhasil ✅")
                            ->body("Berhasil membuat {$createdCount} transaksi, total {$allocated} tabung terdistribusi merata tanpa bentrok tanggal lintas pangkalan.")
                            ->success()
                            ->send();
                    }
                }),

            Actions\CreateAction::make()
                ->label('Catat Transaksi Baru'),
        ];
    }
}
