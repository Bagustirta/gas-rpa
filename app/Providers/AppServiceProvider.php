<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Verifikasi Email Pangkalan - Gas RPA')
                ->greeting('Halo ' . $notifiable->name . ',')
                ->line('Terima kasih telah mendaftar di sistem Otomasi Gas RPA kami.')
                ->line('Satu langkah lagi! Silakan klik tombol di bawah ini untuk memverifikasi email Anda dan memulai masa Trial 7 Hari secara gratis.')
                ->action('Verifikasi Email Saya', $url)
                ->line('Jika Anda merasa tidak mendaftar di aplikasi kami, abaikan saja email ini.')
                ->salutation('Salam Sukses, Tim Gas RPA');
        });
    }
}
