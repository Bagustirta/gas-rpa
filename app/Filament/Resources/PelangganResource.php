<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelangganResource\Pages;
use App\Models\Pelanggan;
use App\Imports\PelangganImport;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Schemas\Components\Section;
use Maatwebsite\Excel\Facades\Excel;

class PelangganResource extends Resource
{
    protected static ?string $model = Pelanggan::class;

    protected static ?string $navigationLabel = 'Data Pelanggan';

    protected static ?string $modelLabel = 'Pelanggan';

    protected static ?string $pluralModelLabel = 'Data Pelanggan';

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Pelanggan')
                    ->schema([
                         Forms\Components\TextInput::make('nik')
                            ->label('NIK (16 Digit)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->length(16)
                            ->numeric()
                            ->placeholder('Contoh: 3273012345678901')
                            ->helperText('Masukkan 16 digit NIK sesuai KTP')
                            ->columnSpan(2),
                        Forms\Components\Toggle::make('status')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan untuk menangguhkan pelanggan')
                            ->columnSpan(2),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll(fn () => \App\Models\RpaSync::whereIn('status', ['pending', 'processing'])->exists() ? '10s' : null)
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('kuota_terpakai')
                    ->label('Terpakai Bulan Ini')
                    ->state(fn (Pelanggan $record) => $record->totalTabungBulanIni() . ' / 4')
                    ->badge()
                    ->color(fn (Pelanggan $record) => match(true) {
                        $record->totalTabungBulanIni() >= 4 => 'danger',
                        $record->totalTabungBulanIni() >= 2 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([


                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                Tables\Filters\SelectFilter::make('kuota_pembelian')
                    ->label('Kuota Terpakai Bulan Ini')
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
            ->headerActions([
                Actions\Action::make('import_excel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Section::make('Import Data Pelanggan dari Excel')
                            ->description('File Excel harus memiliki kolom: nik.')
                            ->schema([
                                Forms\Components\FileUpload::make('file_excel')
                                    ->label('File Excel (.xlsx, .xls, .csv)')
                                    ->required()
                                    ->acceptedFileTypes([
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'application/vnd.ms-excel',
                                        'text/csv',
                                    ])
                                    ->maxSize(10240)
                                    ->disk('local')
                                    ->directory('imports'),

                                Forms\Components\Placeholder::make('petunjuk')
                                    ->label('📋 Petunjuk Format Excel')
                                    ->content(new \Illuminate\Support\HtmlString('
                                        <div class="text-sm">
                                            <p><strong>Kolom wajib:</strong> nik</p>
                                            <p><strong>Format NIK:</strong> Set kolom NIK sebagai <em>Text</em> di Excel sebelum input data</p>
                                            <p><strong>Baris 1:</strong> Header. NIK duplikat akan dilewati/diabaikan (tidak error).</p>
                                        </div>
                                    ')),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file_excel']);
                        $import   = new PelangganImport(auth()->id());
                        Excel::import($import, $filePath);

                        $imported = $import->getImportedCount();
                        $skipped  = $import->getSkippedCount();
                        $errors   = $import->getErrors();

                        if ($imported > 0) {
                            Notification::make()
                                ->title("Import Berhasil! {$imported} data diimport." . ($skipped > 0 ? " {$skipped} baris dilewati." : ''))
                                ->success()
                                ->send();
                        }

                        if (!empty($errors)) {
                            Notification::make()
                                ->title('Beberapa Baris Gagal')
                                ->body(implode("\n", array_slice($errors, 0, 5)))
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\BulkAction::make('toggle_status')
                        ->label('Toggle Status')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => !$record->status]);
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('nik');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPelanggan::route('/'),
            'create' => Pages\CreatePelanggan::route('/create'),
            'edit'   => Pages\EditPelanggan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', true)->where('user_id', auth()->id())->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}
