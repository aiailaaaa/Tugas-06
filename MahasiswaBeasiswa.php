<?php
declare(strict_types=1);
require_once __DIR__ . '/Mahasiswa.php';

final class MahasiswaBeasiswa extends Mahasiswa
{
    private int $ukt;
    private int $potonganPersen;

    public function __construct(
        string $nim, string $nama, string $fakultas, string $prodi,
        string $asalSekolah, int $semester, string $hobi, string $agama,
        int $ukt, int $potonganPersen
    ) {
        parent::__construct($nim, $nama, $fakultas, $prodi, $asalSekolah, $semester, $hobi, $agama);
        if ($ukt < 0) {
            throw new InvalidArgumentException('UKT tidak boleh negatif.');
        }
        if ($potonganPersen < 0 || $potonganPersen > 100) {
            throw new InvalidArgumentException('Potongan beasiswa harus 0–100 persen.');
        }
        $this->ukt = $ukt;
        $this->potonganPersen = $potonganPersen;
    }

    public function getUkt(): int { return $this->ukt; }
    public function getPotonganPersen(): int { return $this->potonganPersen; }

    // Overriding/implementasi: rumus biaya berbeda dengan Reguler.
    public function hitungBiaya(): int
    {
        return intdiv($this->ukt * (100 - $this->potonganPersen), 100);
    }

    public function getJenis(): string { return 'Beasiswa'; }
}
