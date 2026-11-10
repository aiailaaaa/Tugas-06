<?php
declare(strict_types=1);

// Class harus dimuat SEBELUM session_start() agar objek di session dapat di-unserialize.
require_once __DIR__ . '/DataMahasiswa.php';
session_start();

function aman(mixed $nilai): string {
    return htmlspecialchars((string)$nilai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function teksPost(string $kunci): string {
    return trim((string)($_POST[$kunci] ?? ''));
}

function rupiah(int $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function kembali(string $tujuan = 'index.php'): never {
    header('Location: ' . $tujuan, true, 303);
    exit;
}

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$repo = new DataMahasiswa(
    is_array($_SESSION['mahasiswa'] ?? null) ? $_SESSION['mahasiswa'] : []
);
$error = '';
$pesan = (string)($_SESSION['pesan'] ?? '');
unset($_SESSION['pesan']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = teksPost('aksi');

    if (!hash_equals((string)$_SESSION['csrf'], teksPost('csrf'))) {
        $error = 'Token keamanan tidak cocok. Muat ulang halaman.';
    } else {
        try {
            if ($aksi === 'hapus') {
                if (!$repo->hapus(teksPost('nim_lama'))) {
                    throw new InvalidArgumentException('Data tidak ditemukan.');
                }
                $_SESSION['pesan'] = 'Data mahasiswa berhasil dihapus.';

            } elseif ($aksi === 'tambah' || $aksi === 'ubah') {
                $semester = filter_var(teksPost('semester'), FILTER_VALIDATE_INT);
                $ukt = filter_var(teksPost('ukt'), FILTER_VALIDATE_INT);
                if ($semester === false || $ukt === false) {
                    throw new InvalidArgumentException('Semester dan UKT harus berupa bilangan bulat.');
                }

                $nim = $aksi === 'ubah' ? teksPost('nim_lama') : teksPost('nim');
                if ($aksi === 'ubah' && $repo->cari($nim) === null) {
                    throw new InvalidArgumentException('Mahasiswa yang akan diubah tidak ditemukan.');
                }

                $umum = [
                    $nim, teksPost('nama'), teksPost('fakultas'), teksPost('prodi'),
                    teksPost('asalSekolah'), $semester, teksPost('hobi'), teksPost('agama'),
                ];

                $jenis = teksPost('jenis');
                if ($jenis === 'reguler') {
                    $m = new MahasiswaReguler(...[...$umum, $ukt]);
                } elseif ($jenis === 'beasiswa') {
                    $potongan = filter_var(teksPost('potongan'), FILTER_VALIDATE_INT);
                    if ($potongan === false) {
                        throw new InvalidArgumentException('Potongan harus berupa bilangan bulat 0–100.');
                    }
                    $m = new MahasiswaBeasiswa(...[...$umum, $ukt, $potongan]);
                } else {
                    throw new InvalidArgumentException('Jenis mahasiswa tidak dikenal.');
                }

                $berhasil = $aksi === 'tambah' ? $repo->tambah($m) : $repo->ubah($nim, $m);
                if (!$berhasil) {
                    throw new InvalidArgumentException('NIM sudah ada atau perubahan tidak dapat disimpan.');
                }

                $_SESSION['pesan'] = $aksi === 'tambah'
                    ? 'Mahasiswa berhasil ditambahkan.'
                    : 'Mahasiswa berhasil diperbarui.';
            } else {
                throw new InvalidArgumentException('Aksi tidak dikenal.');
            }

            $_SESSION['mahasiswa'] = $repo->semua();
            kembali();
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        }
    }
}

// ---------- Mode edit & pencarian ----------
$nimEdit = trim((string)($_GET['edit'] ?? ''));
$edit = $nimEdit !== '' ? $repo->cari($nimEdit) : null;
if ($nimEdit !== '' && $edit === null && $error === '') {
    $error = 'NIM untuk edit tidak ditemukan.';
}
$q = trim((string)($_GET['q'] ?? ''));
$daftar = $repo->filter($q);

$fields = [
    'nim' => 'NIM', 'nama' => 'Nama', 'fakultas' => 'Fakultas', 'prodi' => 'Program Studi',
    'asalSekolah' => 'Asal Sekolah', 'semester' => 'Semester', 'hobi' => 'Hobi', 'agama' => 'Agama',
];

$nilai = ['jenis' => 'reguler', 'ukt' => '', 'potongan' => '0'];
if ($edit) {
    $nilai = [
        'nim' => $edit->getNim(), 'nama' => $edit->getNama(), 'fakultas' => $edit->getFakultas(),
        'prodi' => $edit->getProdi(), 'asalSekolah' => $edit->getAsalSekolah(),
        'semester' => $edit->getSemester(), 'hobi' => $edit->getHobi(), 'agama' => $edit->getAgama(),
        'jenis' => strtolower($edit->getJenis()), 'ukt' => $edit->getUkt(),
        'potongan' => $edit->getPotonganPersen(),
    ];
}

// Pertahankan input pengguna jika validasi gagal.
if ($error !== '' && $_SERVER['REQUEST_METHOD'] === 'POST' && teksPost('aksi') !== 'hapus') {
    foreach (array_merge(array_keys($fields), ['jenis', 'ukt', 'potongan']) as $k) {
        if ($k !== 'nim' || $edit === null) {
            $nilai[$k] = teksPost($k);
        }
    }
}
$jenisAktif = ($nilai['jenis'] ?? 'reguler') === 'beasiswa' ? 'beasiswa' : 'reguler';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Praktikum 6 — CRUD Mahasiswa (Inheritance)</title>
    <style>
        :root { font-family: system-ui, Arial, sans-serif; color: #16324f; background: #f2f7fc; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { max-width: 1250px; margin: 35px auto; padding: 0 18px; }
        h1 { margin-bottom: 5px; }
        .sub { color: #61758c; margin-top: 0; }
        .card { background: white; border: 1px solid #dae6f0; border-radius: 14px; padding: 24px; margin: 20px 0; box-shadow: 0 5px 18px #173b5810; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
        label { font-weight: 600; font-size: 14px; display: block; }
        input, select { width: 100%; padding: 10px; border: 1px solid #aabfd0; border-radius: 8px; margin-top: 6px; font: inherit; }
        input[readonly] { background: #eaf0f5; }
        .actions { display: flex; gap: 10px; margin-top: 18px; align-items: center; }
        .btn, button { display: inline-block; background: #126aa0; color: white; padding: 9px 15px; border: 0; border-radius: 8px; text-decoration: none; font: inherit; cursor: pointer; }
        .btn.alt { background: #dcebf4; color: #16324f; }
        .danger { background: #b63e52; }
        .notice { padding: 13px; border-radius: 9px; background: #e4f7ee; color: #176a47; }
        .error { padding: 13px; border-radius: 9px; background: #ffebed; color: #9d2535; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 11px 8px; border-bottom: 1px solid #e4edf4; text-align: left; white-space: nowrap; }
        th { background: #e8f2f9; }
        .scroll { overflow-x: auto; }
        .inline { display: inline; }
        .small { font-size: 12px; color: #6b7c8d; }
        .badge { padding: 3px 9px; border-radius: 99px; font-size: 12px; background: #dcebf4; }
        .badge.beasiswa { background: #fff1cc; color: #7a5a00; }
        .cari { display: flex; gap: 10px; margin-bottom: 14px; }
        .cari input { margin: 0; }
        .hidden { display: none; }
        @media (max-width: 650px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <h1>CRUD Data Mahasiswa</h1>
    <p class="sub">Pertemuan 6 — Inheritance: parent abstract Mahasiswa, child MahasiswaReguler &amp; MahasiswaBeasiswa.</p>

    <?php if ($pesan !== ''): ?>
        <p class="notice"><?= aman($pesan) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="error"><?= aman($error) ?></p>
    <?php endif; ?>

    <!-- Form Tambah / Edit -->
    <section class="card">
        <h2><?= $edit ? 'Edit Mahasiswa' : 'Tambah Mahasiswa' ?></h2>

        <form method="post" action="<?= $edit ? 'index.php?edit=' . rawurlencode($edit->getNim()) : 'index.php' ?>">
            <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
            <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
            <?php if ($edit): ?>
                <input type="hidden" name="nim_lama" value="<?= aman($edit->getNim()) ?>">
            <?php endif; ?>

            <div class="grid">
                <?php foreach ($fields as $key => $label): ?>
                    <label><?= aman($label) ?>
                        <?php if ($key === 'agama'): ?>
                            <select name="agama" required>
                                <option value="">-- Pilih Agama --</option>
                                <?php foreach (Mahasiswa::AGAMA as $agama): ?>
                                    <option value="<?= aman($agama) ?>" <?= ($nilai['agama'] ?? '') === $agama ? 'selected' : '' ?>>
                                        <?= aman($agama) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input
                                name="<?= aman($key) ?>"
                                value="<?= aman($nilai[$key] ?? '') ?>"
                                <?= $key === 'semester' ? 'type="number" min="1" max="14"' : 'type="text"' ?>
                                <?= $key === 'nim' && $edit ? 'readonly' : '' ?>
                                <?= $key !== 'hobi' ? 'required' : '' ?>
                            >
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>

                <!-- Data khusus per jenis (inheritance) -->
                <label>Jenis Mahasiswa
                    <select name="jenis" id="jenis" required>
                        <option value="reguler" <?= $jenisAktif === 'reguler' ? 'selected' : '' ?>>Reguler</option>
                        <option value="beasiswa" <?= $jenisAktif === 'beasiswa' ? 'selected' : '' ?>>Beasiswa</option>
                    </select>
                </label>
                <label>UKT (Rp)
                    <input type="number" name="ukt" min="0" value="<?= aman($nilai['ukt'] ?? '') ?>" required>
                </label>
                <label id="boxPotongan" class="<?= $jenisAktif === 'beasiswa' ? '' : 'hidden' ?>">Potongan Beasiswa (%)
                    <input type="number" name="potongan" id="potongan" min="0" max="100" value="<?= aman($nilai['potongan'] ?? '0') ?>">
                </label>
            </div>

            <div class="actions">
                <button type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Data' ?></button>
                <?php if ($edit): ?>
                    <a class="btn alt" href="index.php">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>

        <p class="small">NIM tidak dapat diubah saat edit, tetapi jenis boleh diganti. Validasi utama dilakukan oleh class (parent &amp; child), bukan hanya atribut HTML.</p>
    </section>

    <!-- Tabel Daftar Mahasiswa -->
    <section class="card">
        <h2>Daftar Mahasiswa (<?= count($daftar) ?><?= $q !== '' ? ' dari ' . count($repo->semua()) : '' ?>)</h2>

        <form method="get" class="cari">
            <input type="search" name="q" value="<?= aman($q) ?>" placeholder="Cari NIM / nama / prodi / jenis...">
            <button type="submit">Cari</button>
            <?php if ($q !== ''): ?><a class="btn alt" href="index.php">Reset</a><?php endif; ?>
        </form>

        <div class="scroll">
            <table>
                <thead>
                    <tr>
                        <?php foreach ($fields as $label): ?>
                            <th><?= aman($label) ?></th>
                        <?php endforeach; ?>
                        <th>Jenis</th>
                        <th>UKT</th>
                        <th>Potongan</th>
                        <th>Biaya Akhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftar as $m): ?>
                        <tr>
                            <td><?= aman($m->getNim()) ?></td>
                            <td><?= aman($m->getNama()) ?></td>
                            <td><?= aman($m->getFakultas()) ?></td>
                            <td><?= aman($m->getProdi()) ?></td>
                            <td><?= aman($m->getAsalSekolah()) ?></td>
                            <td><?= aman($m->getSemester()) ?></td>
                            <td><?= aman($m->getHobi()) ?></td>
                            <td><?= aman($m->getAgama()) ?></td>
                            <td><span class="badge <?= aman(strtolower($m->getJenis())) ?>"><?= aman($m->getJenis()) ?></span></td>
                            <td><?= aman(rupiah($m->getUkt())) ?></td>
                            <td><?= aman($m->getPotonganPersen()) ?>%</td>
                            <td><strong><?= aman(rupiah($m->hitungBiaya())) ?></strong></td>
                            <td>
                                <a class="btn" href="?edit=<?= rawurlencode($m->getNim()) ?>">Edit</a>
                                <form method="post" class="inline" onsubmit="return confirm('Hapus mahasiswa ini?')">
                                    <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="nim_lama" value="<?= aman($m->getNim()) ?>">
                                    <button class="danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$daftar): ?>
                        <tr>
                            <td colspan="13"><?= $q !== '' ? 'Data tidak ditemukan.' : 'Belum ada data mahasiswa.' ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    // Tampilkan kolom potongan hanya untuk jenis Beasiswa.
    const jenis = document.getElementById('jenis');
    const box = document.getElementById('boxPotongan');
    const potongan = document.getElementById('potongan');
    function aturPotongan() {
        const beasiswa = jenis.value === 'beasiswa';
        box.classList.toggle('hidden', !beasiswa);
        potongan.required = beasiswa;
        if (!beasiswa) potongan.value = '0';
    }
    jenis.addEventListener('change', aturPotongan);
    aturPotongan();
</script>
</body>
</html>
