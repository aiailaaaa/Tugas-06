<?php
declare(strict_types=1);
require_once __DIR__ . '/MahasiswaReguler.php';
require_once __DIR__ . '/MahasiswaBeasiswa.php';

/** Repository sederhana; menyimpan object Mahasiswa (Reguler/Beasiswa) di array berdasarkan NIM. */
final class DataMahasiswa
{
    /** @var array<string, Mahasiswa> */
    private array $data = [];

    /** @param array<string, Mahasiswa> $dataAwal */
    public function __construct(array $dataAwal = [])
    {
        foreach ($dataAwal as $item) {
            if ($item instanceof Mahasiswa) {
                $this->data[$item->getNim()] = $item;
            }
        }
    }

    /** @return array<string, Mahasiswa> */
    public function semua(): array { return $this->data; }

    public function cari(string $nim): ?Mahasiswa { return $this->data[$nim] ?? null; }

    /** Pencarian kata kunci pada NIM, nama, prodi, dan jenis. @return array<string, Mahasiswa> */
    public function filter(string $kata): array
    {
        $kata = trim($kata);
        if ($kata === '') return $this->data;
        return array_filter($this->data, static function (Mahasiswa $m) use ($kata): bool {
            foreach ([$m->getNim(), $m->getNama(), $m->getProdi(), $m->getJenis()] as $teks) {
                if (stripos($teks, $kata) !== false) return true;
            }
            return false;
        });
    }

    public function tambah(Mahasiswa $mahasiswa): bool
    {
        $nim = $mahasiswa->getNim(); // getter, bukan properti private
        if (isset($this->data[$nim])) return false; // NIM harus unik
        $this->data[$nim] = $mahasiswa;
        return true;
    }

    public function ubah(string $nim, Mahasiswa $mahasiswa): bool
    {
        if (!isset($this->data[$nim]) || $nim !== $mahasiswa->getNim()) return false; // NIM tidak boleh berubah
        $this->data[$nim] = $mahasiswa; // boleh beda jenis (Reguler <-> Beasiswa)
        return true;
    }

    public function hapus(string $nim): bool
    {
        if (!isset($this->data[$nim])) return false;
        unset($this->data[$nim]);
        return true;
    }
}
