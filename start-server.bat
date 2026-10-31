@echo off
cd /d "%~dp0"
title RPA Server Startup

echo ============================================================
echo   MEMULAI SEMUA BACKGROUND WORKERS (Gas + POS + RPA)
echo ============================================================
echo.

REM -- Gunakan startOrRestart agar aman dijalankan berkali-kali
REM -- Jika proses sudah online, PM2 akan restart. Jika belum ada, baru di-start.
echo [1/3] Menjalankan worker via ecosystem.json...
pm2 startOrRestart ecosystem.json
if %errorlevel% neq 0 (
    echo [ERROR] Gagal menjalankan ecosystem.json. Pastikan PM2 sudah terinstall.
    echo         Jalankan: npm install -g pm2
    pause
    exit /b 1
)

echo.
echo [2/3] Mereset job RPA yang mungkin stuck (orphaned)...
php artisan tinker --execute="$n = App\Models\RpaSync::whereIn('status', ['processing', 'pending'])->where('updated_at', '<', now()->subMinutes(30))->update(['status' => 'pending', 'started_at' => null]); echo $n > 0 ? \"Reset $n job orphaned ke pending.\" : 'Tidak ada job orphaned.';"
echo.

echo [3/3] Status PM2 saat ini:
pm2 list

echo.
echo ============================================================
echo   Semua worker sudah berjalan. Bot RPA siap menerima job.
echo ============================================================
pause
