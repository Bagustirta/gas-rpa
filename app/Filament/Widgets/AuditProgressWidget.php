<?php

namespace App\Filament\Widgets;

use App\Models\AuditProgress;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AuditProgressWidget extends Widget
{
    protected string $view = 'filament.widgets.audit-progress-widget';

    // Tidak perlu polling lagi — pakai WebSocket Reverb
    protected static ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    // Data live dari WebSocket (diperbarui saat event diterima)
    public ?array $liveData = null;
    public ?int $authUserId = null;

    public function mount(): void
    {
        $this->authUserId = Auth::id();
        $progress = AuditProgress::where('user_id', $this->authUserId)->first();
        if ($progress) {
            // Auto-correct status if heartbeat is dead
            if ($progress->is_running && $progress->updated_at && $progress->updated_at->diffInSeconds(now()) > 10) {
                $progress->update([
                    'is_running' => false,
                    'finished_at' => now()->subMinutes(15),
                ]);
            }
            $this->liveData = $this->buildData($progress);
        }
    }

    /**
     * Listener WebSocket — dipanggil saat Reverb menerima event AuditProgressUpdated
     */
    #[On('echo-private:audit-progress.{authUserId},.AuditProgressUpdated')]
    public function onAuditProgressUpdated(array $data): void
    {
        // Jika status running mati dan sudah selesai di luar batas waktu 10 menit, sembunyikan
        $isRunning = $data['is_running'] ?? false;
        if (!$isRunning && isset($data['finished_at'])) {
            $finishedAt = \Carbon\Carbon::parse($data['finished_at']);
            if ($finishedAt->diffInMinutes(now()) >= 10) {
                $this->liveData = null;
                return;
            }
        }
        $this->liveData = $data;
    }

    /**
     * Dipanggil secara berkala via wire:poll untuk mendeteksi matinya bot RPA
     */
    public function checkHeartbeat(): void
    {
        $p = AuditProgress::where('user_id', $this->authUserId)->first();
        if ($p && $p->is_running) {
            if ($p->updated_at && $p->updated_at->diffInSeconds(now()) > 10) {
                $p->update([
                    'is_running' => false,
                    'finished_at' => now()->subMinutes(15),
                    'step' => 'error',
                    'step_label' => 'Audit terhenti (Terminal ditutup)',
                ]);
                $this->liveData = null;

                try {
                    broadcast(new \App\Events\AuditProgressUpdated($p));
                } catch (\Throwable $e) {}
            }
        }
    }

    public function getAuthUserIdProperty(): int
    {
        return Auth::id();
    }

    protected function buildData(AuditProgress $p): array
    {
        return [
            'step'            => $p->step,
            'step_label'      => $p->step_label,
            'pangkalan_name'  => $p->pangkalan_name,
            'pangkalan_index' => $p->pangkalan_index,
            'pangkalan_total' => $p->pangkalan_total,
            'nik_current'     => $p->nik_current,
            'nik_total'       => $p->nik_total,
            'nik_pct'         => $p->nikPercent(),
            'pangkalan_pct'   => $p->pangkalanPercent(),
            'is_running'      => $p->is_running,
            'started_at'      => $p->started_at?->format('H:i:s'),
            'finished_at'     => $p->finished_at?->format('H:i:s'),
        ];
    }

    protected function getViewData(): array
    {
        return ['liveData' => $this->liveData];
    }

    public static function canView(): bool
    {
        $p = AuditProgress::where('user_id', Auth::id())->first();
        if (!$p) return false;

        // Auto-correct status jika heartbeat mati
        if ($p->is_running && $p->updated_at && $p->updated_at->diffInSeconds(now()) > 10) {
            $p->update([
                'is_running' => false,
                'finished_at' => now()->subMinutes(15),
            ]);
            return false;
        }

        if ($p->is_running) return true;
        if ($p->finished_at && $p->finished_at->diffInMinutes(now()) < 10) return true;
        return false;
    }
}
