<?php

namespace App\Imports;

use App\Models\Pelanggan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PelangganImport implements
    ToCollection,
    SkipsEmptyRows,
    WithBatchInserts,
    WithChunkReading
{
    private int $imported = 0;
    private int $skipped = 0;
    private array $errors = [];
    private ?int $userId = null;

    private ?int $nikColIndex = null;
    private ?int $statusColIndex = null;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows): void
    {
        // Cek baris pertama untuk menentukan apakah itu header (hanya di chunk pertama)
        if ($this->nikColIndex === null) {
            $this->nikColIndex = 0; // Default kolom A
            $this->statusColIndex = 1; // Default kolom B
            
            $firstRow = $rows->first();
            if ($firstRow) {
                $isHeaderRow = true;
                
                // 1. Cek apakah baris pertama ini langsung berisi DATA (mencari angka 16 digit)
                foreach ($firstRow as $i => $val) {
                    // Hanya ambil angkanya saja
                    $cleanVal = preg_replace('/[^0-9]/', '', (string)$val);
                    if (strlen($cleanVal) === 16) {
                        // Ketemu 16 digit angka! Berarti baris pertama ini langsung DATA, bukan judul
                        $this->nikColIndex = $i;
                        // Asumsikan kolom status ada di kolom sebelahnya
                        $this->statusColIndex = ($i === 0) ? 1 : 0;
                        $isHeaderRow = false;
                        break;
                    }
                }
                
                // 2. Jika tidak ada angka 16 digit, kita anggap itu JUDUL (atau baris invalid/typo)
                // Kita coba tebak posisi kolomnya dari teks judul (kebal typo)
                if ($isHeaderRow) {
                    $nikFound = false;
                    $statusFound = false;
                    
                    foreach ($firstRow as $i => $val) {
                        $valStr = strtolower(trim((string)$val));
                        
                        // Deteksi kolom NIK (bisa typo jadi 'nki', 'no ktp', 'nomor', 'id', dll)
                        if (!$nikFound && (str_contains($valStr, 'nik') || str_contains($valStr, 'nki') || str_contains($valStr, 'ktp') || str_contains($valStr, 'nomor') || str_contains($valStr, 'no'))) {
                            $this->nikColIndex = $i;
                            $nikFound = true;
                        }
                        // Deteksi kolom Status (bisa 'status', 'aktif', 'ket', 'kondisi', dll)
                        elseif (!$statusFound && (str_contains($valStr, 'status') || str_contains($valStr, 'aktif') || str_contains($valStr, 'ket') || str_contains($valStr, 'kondisi'))) {
                            $this->statusColIndex = $i;
                            $statusFound = true;
                        }
                    }
                    
                    // Karena ini dianggap judul/baris invalid, kita hapus agar tidak masuk ke database
                    $rows->shift();
                }
            }
        }

        foreach ($rows as $index => $row) {
            // Ambil NIK dan pastikan bertipe string (cegah format saintifik dari Excel)
            $nik = $this->sanitizeNik($row[$this->nikColIndex] ?? '');

            // Jika tidak ada NIK maka tidak dimasukkan (skip)
            if (empty($nik)) {
                $this->skipped++;
                continue;
            }

            // Tentukan status
            $statusInput = $row[$this->statusColIndex] ?? null;
            $status = true; // default aktif
            
            if ($statusInput !== null && trim($statusInput) !== '') {
                $statusStr = strtolower(trim($statusInput));
                $status = ($statusStr === 'aktif' || $statusStr === '1' || $statusStr === 'true' || $statusStr === 'ya');
            }

            $rowData = [
                'nik'    => $nik,
            ];

            // Validasi baris
            $validator = Validator::make($rowData, [
                'nik'    => ['required', 'string', 'size:16', 'regex:/^\d{16}$/'],
            ], [
                'nik.size'    => "NIK harus tepat 16 digit",
                'nik.regex'   => "NIK hanya boleh berisi angka",
                'nik.required'=> "NIK wajib diisi",
            ]);

            if ($validator->fails()) {
                $this->skipped++;
                $this->errors[] = "Baris " . ($index + 2) . ": " . $validator->errors()->first();
                continue;
            }

            $bindex = hash_hmac('sha256', $nik, config('app.key'));
            $existing = Pelanggan::where('nik_bindex', $bindex)->first();

            if ($existing) {
                if ($existing->user_id !== $this->userId) {
                    $this->skipped++;
                    $this->errors[] = "Baris " . ($index + 2) . ": NIK sudah terdaftar pada pelanggan lain.";
                    continue;
                }
                
                // Jika NIK milik user ini, update statusnya sesuai Excel
                $existing->update(['status' => $status]);
            } else {
                // Jika NIK baru, buat baru
                Pelanggan::create([
                    'nik' => $nik,
                    'status' => $status,
                    'user_id' => $this->userId,
                ]);
            }

            $this->imported++;
        }
    }

    /**
     * Sanitasi NIK: konversi dari notasi saintifik, float, dsb. ke string 16 digit.
     */
    private function sanitizeNik(mixed $nik): string
    {
        // Jika angka (bisa dari Excel yang membaca sebagai float)
        if (is_float($nik) || is_int($nik)) {
            // Format tanpa notasi saintifik
            $nik = number_format($nik, 0, '', '');
        }

        $nik = trim((string) $nik);

        // Jika berupa angka dan panjangnya kurang dari 16, tambahkan zero-padding di depan (biasanya nol terpotong oleh Excel)
        if (is_numeric($nik) && strlen($nik) < 16 && strlen($nik) > 0) {
            $nik = str_pad($nik, 16, '0', STR_PAD_LEFT);
        }

        return $nik;
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
