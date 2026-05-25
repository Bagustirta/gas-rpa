<?php

namespace App\Providers\Filament;

use App\Filament\Resources\PelangganResource;
use App\Filament\Resources\TransaksiGasResource;
use App\Filament\Widgets\SisaKuotaWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Aktifkan polling otomatis notifikasi tiap 5 detik
        DatabaseNotifications::pollingInterval('5s');

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('⛽ Pangkalan LPG 3 Kg')
            ->colors([
                'primary' => Color::Blue,
                'gray'    => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make('Manajemen Data')
                    ->icon('heroicon-o-circle-stack'),
                NavigationGroup::make('Laporan')
                    ->icon('heroicon-o-chart-bar'),
            ])
            ->databaseNotifications()                // Aktifkan panel notifikasi database
            ->databaseNotificationsPolling('5s')     // Polling setiap 5 detik
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => '
                    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js"></script>
                    <script>
                        window.Pusher = Pusher;
                        window.Echo = new Echo({
                            broadcaster: "reverb",
                            key: "' . config('broadcasting.connections.reverb.key') . '",
                            wsHost: "' . (config('broadcasting.connections.reverb.options.host') ?: 'localhost') . '",
                            wsPort: ' . (config('broadcasting.connections.reverb.options.port') ?: 80) . ',
                            wssPort: ' . (config('broadcasting.connections.reverb.options.port') ?: 443) . ',
                            forceTLS: ' . (config('broadcasting.connections.reverb.options.scheme') === 'https' ? 'true' : 'false') . ',
                            enabledTransports: ["ws", "wss"],
                        });
                    </script>
                ',
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_END,
                fn (): string => '
                    <script>
                        setInterval(async () => {
                            try {
                                const res = await fetch("/api/internal/badge-transaksi");
                                const data = await res.json();
                                const navLink = document.querySelector("a[href*=\'/admin/transaksi-gas\']");
                                if (navLink) {
                                    const badge = navLink.querySelector(".fi-badge");
                                    if (badge) {
                                        badge.textContent = data.count;
                                    }
                                }
                            } catch (e) {}
                        }, 5000);
                    </script>
                ',
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                \App\Filament\Widgets\AuditProgressWidget::class,
                StatsOverviewWidget::class,
                SisaKuotaWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
