<?php
declare(strict_types=1);

// Parent Class
abstract class Pegawai {
    private string $id;
    private string $nama;

    public function __construct(string $id, string $nama) {
        $id = trim($id);
        $nama = trim($nama);
        if ($id === '' || $nama === '') {
            throw new InvalidArgumentException("ID dan Nama Pegawai wajib diisi.");
        }
        $this->id = $id;
        $this->nama = $nama;
    }

    public function getId(): string { return $this->id; }
    public function getNama(): string { return $this->nama; }
    
    // Abstract method overriding wajib di child
    abstract public function hitungGaji(): int;
}

// Child Class 1: Pegawai Tetap
final class PegawaiTetap extends Pegawai {
    private int $gajiPokok;

    public function __construct(string $id, string $nama, int $gajiPokok) {
        parent::__construct($id, $nama);
        if ($gajiPokok < 0) {
            throw new InvalidArgumentException("Gaji pokok tidak boleh negatif.");
        }
        $this->gajiPokok = $gajiPokok;
    }

    public function hitungGaji(): int {
        return $this->gajiPokok;
    }
}

// Child Class 2: Pegawai Kontrak
final class PegawaiKontrak extends Pegawai {
    private int $upahPerJam;
    private int $totalJamKerja;

    public function __construct(string $id, string $nama, int $upahPerJam, int $totalJamKerja) {
        parent::__construct($id, $nama);
        if ($upahPerJam < 0 || $totalJamKerja < 0) {
            throw new InvalidArgumentException("Upah per jam dan jam kerja tidak boleh negatif.");
        }
        $this->upahPerJam = $upahPerJam;
        $this->totalJamKerja = $totalJamKerja;
    }

    public function hitungGaji(): int {
        return $this->upahPerJam * $this->totalJamKerja;
    }
}

// --- Pengujian Program ---
try {
    $pegawai1 = new PegawaiTetap("P-001", "Budi Santoso", 6000000);
    $pegawai2 = new PegawaiKontrak("P-002", "Siti Aminah", 50000, 160);

    echo "ID: " . $pegawai1->getId() . " | Nama: " . $pegawai1->getNama() . " | Gaji: Rp" . number_format($pegawai1->hitungGaji(), 0, ',', '.') . PHP_EOL;
    echo "ID: " . $pegawai2->getId() . " | Nama: " . $pegawai2->getNama() . " | Gaji: Rp" . number_format($pegawai2->hitungGaji(), 0, ',', '.') . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}