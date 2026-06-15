<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiGasResource\Pages;
use App\Models\Pelanggan;
use App\Models\TransaksiGas;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Get;
use Filament\Schemas\Components\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Schemas\Components\Section;

class TransaksiGasResource extends Resource
{
    protected static ?string $model = TransaksiGas::class;

    protected static ?string $navigationLabel = 'Transaksi Gas';

    protected static ?string $modelLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi Gas';

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-fire';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Input Transaksi LPG 3 Kg')
                    ->description('Isi data transaksi penjualan gas LPG 3 Kg kepada pelanggan bersubsidi.')
                    ->schema([
                        Forms\Components\TextInput::make('nik_search')
                            ->label('Cari NIK Pelanggan')
                            ->placeholder('Ketik 16 digit NIK...')
                            ->helperText('Masukkan NIK untuk auto-pilih pelanggan')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($get, $set, ?string $state) {
                                if (strlen((string)$state) === 16) {
                                    $pelanggan = Pelanggan::where('nik', $state)->where('status', true)->first();
                                    if ($pelanggan) {
                                        $set('pelanggan_id', $pelanggan->id);
                                        $set('akun_pangkalan_id', $pelanggan->akun_pangkalan_id);
                                        $terpakai = $pelanggan->totalTabungBulanIni();
                                        $set('kuota_preview', "Terpakai: {$terpakai} / 4 | Sisa: " . (4 - $terpakai) . " tabung");
                                    } else {
                                        $set('pelanggan_id', null);
                                        $set('akun_pangkalan_id', null);
                                        $set('kuota_preview', '❌ NIK tidak ditemukan / tidak aktif');
                                    }
                                }
                            })
                            ->dehydrated(false)
                            ->columnSpan(2),

                        Forms\Components\Select::make('pelanggan_id')
                            ->label('Pilih NIK Pelanggan')
                            ->options(
                                Pelanggan::where('status', true)
                                    ->orderBy('nik')
                                    ->get()
                                    ->pluck('nik', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($get, $set, $state) {
                                if ($state) {
                                    $pelanggan = Pelanggan::find($state);
                                    if ($pelanggan) {
                                        $set('nik_search', $pelanggan->nik);
                                        $set('akun_pangkalan_id', \App\Models\AkunPangkalan::where('user_id', auth()->id())->first()?->id);
                                        $terpakai = $pelanggan->totalTabungBulanIni();
                                        $set('kuota_preview', "Terpakai: {$terpakai} / 4 | Sisa: " . (4 - $terpakai) . " tabung");
                                    }
                                }
                             })
                             ->columnSpan(2),

                         Forms\Components\Placeholder::make('kuota_preview')
                             ->label('📊 Status Kuota Bulan ' . now()->translatedFormat('F Y'))
                             ->content(fn ($get) => $get('kuota_preview') ?: '— Pilih NIK pelanggan terlebih dahulu —')
                             ->columnSpan(2),

                         Forms\Components\Select::make('akun_pangkalan_id')
                             ->label('Akun Pangkalan')
                             ->relationship('akunPangkalan', 'nama_pangkalan', modifyQueryUsing: fn ($query) => $query->where('user_id', auth()->id()))
                             ->required()
                             ->placeholder('Pilih Akun Pangkalan...')
                             ->helperText('Akun Pangkalan yang dituju untuk transaksi ini')
                             ->columnSpan(2),

                        Forms\Components\TextInput::make('jumlah_tabung')
                            ->label('Jumlah Tabung')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(4)
                            ->default(1)
                            ->suffix('tabung')
                            ->helperText('Maks. 1 tabung per minggu per pangkalan, dan maks. 4 tabung per bulan')
                            ->rules([
                                fn ($get, $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $pelangganId = $get('pelanggan_id');
                                    $pangkalanId = $get('akun_pangkalan_id');
                                    $tanggal = $get('tanggal_transaksi');
                                    
                                    if (!$pelangganId || !$pangkalanId || !$tanggal) {
                                        return;
                                    }
                                    
                                    $date = \Carbon\Carbon::parse($tanggal);
                                    $startOfWeek = $date->copy()->startOfWeek();
                                    $endOfWeek = $date->copy()->endOfWeek();
                                    $startOfMonth = $date->copy()->startOfMonth();
                                    $endOfMonth = $date->copy()->endOfMonth();
                                    
                                    $recordId = $record?->id;
                                    
                                    // 1. Cek limit mingguan di pangkalan terpilih (maks 1 tabung)
                                    $weeklySum = \App\Models\TransaksiGas::where('pelanggan_id', $pelangganId)
                                        ->where('akun_pangkalan_id', $pangkalanId)
                                        ->whereBetween('tanggal_transaksi', [$startOfWeek, $endOfWeek])
                                        ->when($recordId, fn($q) => $q->where('id', '!=', $recordId))
                                        ->sum('jumlah_tabung');
                                        
                                    if (($weeklySum + $value) > 1) {
                                        $fail("Pelanggan ini sudah membeli {$weeklySum} tabung pada minggu ini di pangkalan terpilih. Batas mingguan adalah maksimal 1 tabung per pangkalan.");
                                        return;
                                    }
                                    
                                    // 2. Cek limit bulanan secara global (maks 4 tabung)
                                    $monthlySum = \App\Models\TransaksiGas::where('pelanggan_id', $pelangganId)
                                        ->whereBetween('tanggal_transaksi', [$startOfMonth, $endOfMonth])
                                        ->when($recordId, fn($q) => $q->where('id', '!=', $recordId))
                                        ->sum('jumlah_tabung');
                                        
                                    if (($monthlySum + $value) > 4) {
                                        $fail("Pelanggan ini sudah membeli {$monthlySum} tabung pada bulan ini secara keseluruhan. Batas bulanan adalah maksimal 4 tabung.");
                                    }
                                }
                            ])
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('tanggal_transaksi')
                            ->label('Tanggal & Waktu Transaksi')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll(fn () => 
                \App\Models\RpaSync::whereIn('status', ['pending', 'processing'])->exists() ||
                \App\Models\AuditProgress::where('user_id', auth()->id())->where('is_running', true)->exists()
                ? '5s' : null
            )
            ->columns([
                Tables\Columns\TextColumn::make('pelanggan.nik')
                    ->label('NIK')
                    ->searchable()
                    ->fontFamily('mono')
                    ->copyable(),

                Tables\Columns\IconColumn::make('pelanggan.status')
                    ->label('Status Pelanggan')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('akunPangkalan.nama_pangkalan')
                    ->label('Pangkalan')
                    ->placeholder('Belum Ditentukan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_tabung')
                    ->label('Jml. Tabung')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal Transaksi')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_lapor')
                    ->label('Status Lapor')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sukses' => 'success',
                        'gagal' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sukses' => 'Sukses',
                        'gagal' => 'Gagal',
                        default => 'Belum Lapor',
                    }),

                Tables\Columns\TextColumn::make('catatan_lapor')
                    ->label('Catatan Lapor')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(40),

                Tables\Columns\TextColumn::make('waktu_dilaporkan')
                    ->label('Waktu Lapor')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum dilaporkan')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Filter Bulan')
                    ->options(
                        collect(range(1, 12))
                            ->mapWithKeys(fn ($m) => [$m => Carbon::create(null, $m)->translatedFormat('F')])
                    )
                    ->query(fn ($query, array $data) =>
                        $data['value'] ? $query->whereMonth('tanggal_transaksi', $data['value']) : $query
                    ),

                Tables\Filters\SelectFilter::make('status_lapor')
                    ->label('Status Pelaporan')
                    ->options([
                        'belum_lapor' => 'Belum Lapor',
                        'sukses' => 'Sukses',
                        'gagal' => 'Gagal',
                    ]),

                Tables\Filters\SelectFilter::make('akun_pangkalan_id')
                    ->label('Pangkalan')
                    ->relationship('akunPangkalan', 'nama_pangkalan', modifyQueryUsing: fn ($query) => $query->where('user_id', auth()->id()))
                    ->placeholder('Semua Pangkalan'),

                Tables\Filters\TernaryFilter::make('pelanggan_status')
                    ->label('Status Pelanggan')
                    ->queries(
                        true: fn ($query) => $query->whereHas('pelanggan', fn ($q) => $q->where('status', true)),
                        false: fn ($query) => $query->whereHas('pelanggan', fn ($q) => $q->where('status', false)),
                    ),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),

                Actions\Action::make('tandai_lapor')
                    ->label('Tandai Dilaporkan')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (TransaksiGas $record) => $record->status_lapor !== 'sukses')
                    ->requiresConfirmation()
                    ->action(fn (TransaksiGas $record) => $record->update([
                        'dilaporkan_pertamina' => true,
                        'waktu_dilaporkan'     => now(),
                        'status_lapor'         => 'sukses',
                        'catatan_lapor'        => 'Ditandai manual oleh Admin',
                    ])),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\BulkAction::make('tandai_lapor_massal')
                        ->label('Tandai Semua Dilaporkan')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($r) => $r->update([
                            'dilaporkan_pertamina' => true,
                            'waktu_dilaporkan'     => now(),
                            'status_lapor'         => 'sukses',
                            'catatan_lapor'        => 'Ditandai manual massal oleh Admin',
                        ]))),
                ]),
            ])
            ->defaultSort('tanggal_transaksi', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTransaksiGas::route('/'),
            'create' => Pages\CreateTransaksiGas::route('/create'),
            'edit'   => Pages\EditTransaksiGas::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['pelanggan', 'akunPangkalan'])
            ->whereHas('akunPangkalan', function ($q) {
                $q->where('user_id', auth()->id());
            });
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->whereHas('akunPangkalan', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
