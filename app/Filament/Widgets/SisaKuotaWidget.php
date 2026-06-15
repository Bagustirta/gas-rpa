<?php

namespace App\Filament\Widgets;

use App\Models\Pelanggan;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SisaKuotaWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    // Filament v4: pollingInterval adalah non-static instance property
    protected ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $bulan     = Carbon::now()->month;
        $tahun     = Carbon::now()->year;
        $namaBulan = Carbon::now()->translatedFormat('F Y');

        return $table
            ->heading("📊 Monitoring Sisa Kuota NIK — Bulan {$namaBulan}")
            ->description("Kuota direset otomatis setiap bulan baru. Maks. 4 tabung per pelanggan per bulan.")
            ->query(
                Pelanggan::query()
                    ->where('user_id', auth()->id())
                    ->where('status', true)
                    ->withSum([
                        'transaksiGas as total_tabung_bulan_ini' => function (Builder $query) use ($bulan, $tahun) {
                            $query->whereMonth('tanggal_transaksi', $bulan)
                                  ->whereYear('tanggal_transaksi', $tahun);
                        }
                    ], 'jumlah_tabung')
                    ->orderBy('total_tabung_bulan_ini', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),


                Tables\Columns\TextColumn::make('total_tabung_bulan_ini')
                    ->label('Total Terpakai')
                    ->alignCenter()
                    ->state(fn (Pelanggan $record) => (int) ($record->total_tabung_bulan_ini ?? 0))
                    ->formatStateUsing(fn ($state) => $state . ' tabung')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 4 => 'danger',
                        $state >= 2 => 'warning',
                        $state >= 1 => 'info',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sisa_kuota')
                    ->label('Sisa Kuota')
                    ->alignCenter()
                    ->state(fn (Pelanggan $record) => max(0, 4 - ($record->total_tabung_bulan_ini ?? 0)))
                    ->formatStateUsing(fn ($state) => $state . ' tabung')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === 0 => 'danger',
                        $state === 1 => 'warning',
                        $state >= 2  => 'success',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('progress_kuota')
                    ->label('Progress')
                    ->state(fn (Pelanggan $record) => $record->total_tabung_bulan_ini ?? 0)
                    ->formatStateUsing(function ($state) {
                        $terpakai = (int) $state;
                        $filled   = str_repeat('█', min(4, $terpakai));
                        $empty    = str_repeat('░', max(0, 4 - $terpakai));
                        return "{$filled}{$empty} {$terpakai}/4";
                    })
                    ->fontFamily('mono'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kuota_pembelian')
                    ->label('Filter Jumlah Pembelian')
                    ->options([
                        '0' => '0 Tabung (Belum Beli)',
                        '1' => '1 Tabung',
                        '2' => '2 Tabung',
                        '3' => '3 Tabung',
                        '4' => '4 Tabung / Lebih (Limit Habis)',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'];
                        if ($value === null || $value === '') {
                            return $query;
                        }

                        $bulan = \Carbon\Carbon::now()->month;
                        $tahun = \Carbon\Carbon::now()->year;

                        $subquery = \App\Models\TransaksiGas::selectRaw('COALESCE(SUM(jumlah_tabung), 0)')
                            ->whereColumn('pelanggan_id', 'pelanggan.id')
                            ->whereMonth('tanggal_transaksi', $bulan)
                            ->whereYear('tanggal_transaksi', $tahun);

                        if ($value === '4') {
                            return $query->where($subquery, '>=', 4);
                        } else {
                            return $query->where($subquery, '=', (int) $value);
                        }
                    }),
            ])
            ->paginated([25, 50, 100, 'all']);
    }
}
