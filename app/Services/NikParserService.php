<?php

namespace App\Services;

use Carbon\Carbon;

class NikParserService
{
    protected $provinces = [];
    protected $regencies = [];
    protected $districts = [];

    public function __construct()
    {
        $this->loadData();
    }

    protected function loadData()
    {
        $wilayahPath = storage_path('app/wilayah.json');
        if (file_exists($wilayahPath)) {
            $data = json_decode(file_get_contents($wilayahPath), true);
            $this->provinces = $data['provinsi'] ?? [];
            $this->regencies = $data['kabkot'] ?? [];
            
            // For districts, we want to strip the postal code if it exists (e.g. "MENGWI -- 80351")
            $kecamatans = $data['kecamatan'] ?? [];
            foreach ($kecamatans as $id => $name) {
                $this->districts[$id] = explode(' -- ', $name)[0];
            }
        }
    }

    public function parse($nik)
    {
        // Pastikan NIK berupa string dan persis 16 karakter angka
        $nik = preg_replace('/[^0-9]/', '', (string) $nik);
        
        if (strlen($nik) !== 16) {
            return false;
        }

        $provinsiCode = substr($nik, 0, 2);
        $kotaCode = substr($nik, 0, 4);
        $kecamatanCode = substr($nik, 0, 6);
        $tanggalLahirCode = substr($nik, 6, 6);
        $unikCode = substr($nik, 12, 4);

        // Parse Date and Gender
        $hari = (int) substr($tanggalLahirCode, 0, 2);
        $bulan = (int) substr($tanggalLahirCode, 2, 2);
        $tahun = (int) substr($tanggalLahirCode, 4, 2);

        $jenisKelamin = 'LAKI-LAKI';
        if ($hari > 40) {
            $jenisKelamin = 'PEREMPUAN';
            $hari -= 40;
        }

        // Handle century
        $currentYear = (int) date('y');
        $fullTahun = ($tahun > $currentYear) ? 1900 + $tahun : 2000 + $tahun;

        try {
            $tanggalLahir = Carbon::createFromDate($fullTahun, $bulan, $hari)->format('Y-m-d');
        } catch (\Exception $e) {
            $tanggalLahir = null;
        }

        $provinsi = $this->provinces[$provinsiCode] ?? null;
        $kota = $this->regencies[$kotaCode] ?? null;
        $kecamatan = $this->districts[$kecamatanCode] ?? null;

        return [
            'provinsi' => $provinsi,
            'kabupaten_kota' => $kota,
            'kecamatan' => $kecamatan,
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => $tanggalLahir,
            'id_unik' => $unikCode,
        ];
    }
}
