<?php

namespace App\Imports;

use App\Models\Pelanggan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PelangganImport implements
    ToCollection,
    WithHeadingRow,
    SkipsEmptyRows,
    WithBatchInserts,
    WithChunkReading
{
    private int $imported = 0;
    private int $skipped = 0;
    private array $errors = [];
    private ?int $userId = null;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // Ambil NIK dan pastikan bertipe string (cegah format saintifik dari Excel)
            $nik = $this->sanitizeNik($row['nik'] ?? $row['NIK'] ?? '');

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

            // Insert jika baru, abaikan jika sudah ada agar status terakhir (aktif/nonaktif) tetap terjaga
            Pelanggan::firstOrCreate(
                ['nik_bindex' => hash_hmac('sha256', $rowData['nik'], config('app.key'))],
                [
                    'nik' => $rowData['nik'],
                    'status' => true,
                    'user_id' => $this->userId,
                ]
            );

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
