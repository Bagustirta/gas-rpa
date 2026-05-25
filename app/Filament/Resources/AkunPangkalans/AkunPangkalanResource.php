<?php

namespace App\Filament\Resources\AkunPangkalans;

use App\Filament\Resources\AkunPangkalans\Pages\CreateAkunPangkalan;
use App\Filament\Resources\AkunPangkalans\Pages\EditAkunPangkalan;
use App\Filament\Resources\AkunPangkalans\Pages\ListAkunPangkalans;
use App\Filament\Resources\AkunPangkalans\Schemas\AkunPangkalanForm;
use App\Filament\Resources\AkunPangkalans\Tables\AkunPangkalansTable;
use App\Models\AkunPangkalan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AkunPangkalanResource extends Resource
{
    protected static ?string $model = AkunPangkalan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $pluralModelLabel = 'Akun Pangkalan';
    protected static ?string $modelLabel = 'Akun Pangkalan';
    protected static \UnitEnum|string|null $navigationGroup = 'RPA Bot Settings';

    protected static ?string $recordTitleAttribute = 'nama_pangkalan';

    public static function form(Schema $schema): Schema
    {
        return AkunPangkalanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AkunPangkalansTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAkunPangkalans::route('/'),
            'create' => CreateAkunPangkalan::route('/create'),
            'edit' => EditAkunPangkalan::route('/{record}/edit'),
        ];
    }
}
