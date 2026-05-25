<?php

namespace App\Filament\Resources\TransaksiGasResource\Widgets;

use App\Filament\Resources\TransaksiGasResource\Pages\ListTransaksiGas;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class TransaksiGasStats extends StatsOverviewWidget
{
    #[Reactive]
    public $paginators = [];

    #[Reactive]
    public ?array $tableColumnSearches = [];

    #[Reactive]
    public ?string $tableGrouping = null;

    #[Reactive]
    public ?array $tableFilters = null;

    #[Reactive]
    public $tableRecordsPerPage = null;

    #[Reactive]
    public ?string $tableSearch = '';

    #[Reactive]
    public ?string $tableSort = null;

    #[Reactive]
    public ?string $activeTab = null;

    protected function getPageTableQuery()
    {
        $page = app('livewire')->new(ListTransaksiGas::class);
        
        \Livewire\trigger('mount', $page, [
            'activeTab' => $this->activeTab,
            'paginators' => $this->paginators,
            'tableColumnSearches' => $this->tableColumnSearches ?? [],
            'tableFilters' => $this->tableFilters ?? [],
            'tableGrouping' => $this->tableGrouping,
            'tableRecordsPerPage' => $this->tableRecordsPerPage,
            'tableSearch' => $this->tableSearch ?? '',
            'tableSort' => $this->tableSort,
        ], null, null);

        return $page->getFilteredSortedTableQuery();
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();
        
        $totalTabung = $query->sum('jumlah_tabung');
        $totalTransaksi = $query->count();

        return [
            Stat::make('Total Tabung (Terfilter)', $totalTabung . ' Tabung')
                ->description('Dari ' . $totalTransaksi . ' transaksi yang tampil di tabel')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('primary')
                ->icon('heroicon-o-fire'),
        ];
    }
}
