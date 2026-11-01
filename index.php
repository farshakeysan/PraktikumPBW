<?php

const BOBOT_TUGAS = 0.30;
const BOBOT_UTS   = 0.30;
const BOBOT_UAS   = 0.40;
const NILAI_MIN   = 0;
const NILAI_MAKS  = 100;
const BATAS_LULUS = 65;

function hitungNilaiAkhir($tugas, $uts, $uas)
{
    $nilai = ($tugas * BOBOT_TUGAS) + ($uts * BOBOT_UTS) + ($uas * BOBOT_UAS);
    return round($nilai, 2);
}

function tentukanGrade($nilaiAkhir)
{
    if ($nilaiAkhir >= 85) {
        return 'A';
    } elseif ($nilaiAkhir >= 75) {
        return 'B';
    } elseif ($nilaiAkhir >= 65) {
        return 'C';
    } elseif ($nilaiAkhir >= 50) {
        return 'D';
    }
    return 'E';
}

function tentukanStatus($nilaiAkhir)
{
    return $nilaiAkhir >= BATAS_LULUS ? 'Lulus' : 'Tidak Lulus';
}

function validasiNilai($nilai)
{
    return is_numeric($nilai) && $nilai >= NILAI_MIN && $nilai <= NILAI_MAKS;
}

function formatAngka($angka)
{
    return rtrim(rtrim(number_format($angka, 2, ',', '.'), '0'), ',');
}

function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function validasiMahasiswa($m)
{
    $errors = [];

    if (trim($m['nama']) === '') {
        $errors[] = 'Nama wajib diisi.';
    }
    if (!preg_match('/^[0-9]{5,20}$/', trim($m['nim']))) {
        $errors[] = 'NIM harus berupa angka (5-20 digit).';
    }
    foreach (['tugas' => 'Nilai Tugas', 'uts' => 'Nilai UTS', 'uas' => 'Nilai UAS'] as $kunci => $label) {
        if (!validasiNilai($m[$kunci])) {
            $errors[] = "$label harus berupa angka antara " . NILAI_MIN . " sampai " . NILAI_MAKS . '.';
        }
    }
    return $errors;
}

$dataMahasiswa = [
    ['nama' => 'Andi', 'nim' => '231234567', 'tugas' => '80', 'uts' => '75', 'uas' => '90'],
];

$hasil      = [];
$errorBaris = [];
$dikirim    = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($dikirim) {
    $dataMahasiswa = [];
    $input = isset($_POST['mhs']) && is_array($_POST['mhs']) ? $_POST['mhs'] : [];

    foreach ($input as $baris) {
        $m = [
            'nama'  => $baris['nama']  ?? '',
            'nim'   => $baris['nim']   ?? '',
            'tugas' => $baris['tugas'] ?? '',
            'uts'   => $baris['uts']   ?? '',
            'uas'   => $baris['uas']   ?? '',
        ];
        if (implode('', array_map('trim', $m)) === '') {
            continue;
        }
        $dataMahasiswa[] = $m;
    }

    if (count($dataMahasiswa) === 0) {
        $dataMahasiswa[] = ['nama' => '', 'nim' => '', 'tugas' => '', 'uts' => '', 'uas' => ''];
        $errorBaris[0]   = ['Isi minimal satu data mahasiswa.'];
    }
}

if ($dikirim) {
    foreach ($dataMahasiswa as $i => $m) {
        $errors = isset($errorBaris[$i]) ? $errorBaris[$i] : validasiMahasiswa($m);

        if (!empty($errors)) {
            $errorBaris[$i] = $errors;
            continue;
        }

        $tugas = (float) $m['tugas'];
        $uts   = (float) $m['uts'];
        $uas   = (float) $m['uas'];

        $nilaiAkhir = hitungNilaiAkhir($tugas, $uts, $uas);

        $hasil[] = [
            'nama'   => trim($m['nama']),
            'nim'    => trim($m['nim']),
            'tugas'  => $tugas,
            'uts'    => $uts,
            'uas'    => $uas,
            'akhir'  => $nilaiAkhir,
            'grade'  => tentukanGrade($nilaiAkhir),
            'status' => tentukanStatus($nilaiAkhir),
        ];
    }
} else {
    foreach ($dataMahasiswa as $m) {
        $nilaiAkhir = hitungNilaiAkhir((float) $m['tugas'], (float) $m['uts'], (float) $m['uas']);
        $hasil[] = [
            'nama'   => $m['nama'],
            'nim'    => $m['nim'],
            'tugas'  => (float) $m['tugas'],
            'uts'    => (float) $m['uts'],
            'uas'    => (float) $m['uas'],
            'akhir'  => $nilaiAkhir,
            'grade'  => tentukanGrade($nilaiAkhir),
            'status' => tentukanStatus($nilaiAkhir),
        ];
    }
}

$jumlahLulus = count(array_filter($hasil, fn($h) => $h['status'] === 'Lulus'));
$rataRata    = count($hasil) ? array_sum(array_column($hasil, 'akhir')) / count($hasil) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Penghitung Nilai Mahasiswa</title>
    <style>
        :root {
            --bg: #f3f5f9;
            --surface: #ffffff;
            --ink: #1b2437;
            --muted: #5d6882;
            --line: #dde2ec;
            --brand: #1f4fa3;
            --brand-dark: #173d80;
            --accent: #f2a81d;
            --ok-bg: #e3f4e8;   --ok-ink: #17692f;
            --bad-bg: #fbe5e5;  --bad-ink: #a12525;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.5;
        }
        header {
            background: var(--brand);
            color: #fff;
            padding: 28px 20px;
            border-bottom: 5px solid var(--accent);
        }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 0 20px; }
        header h1 { margin: 0 0 4px; font-size: 1.7rem; }
        header p  { margin: 0; opacity: .85; }

        main { padding: 28px 0 56px; }
        section {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 24px;
        }
        h2 { margin: 0 0 6px; font-size: 1.2rem; }
        .hint { margin: 0 0 18px; color: var(--muted); font-size: .92rem; }

                .baris {
            display: grid;
            grid-template-columns: 1.4fr 1.2fr repeat(3, .8fr) auto;
            gap: 10px;
            align-items: end;
            padding: 14px 0;
            border-top: 1px solid var(--line);
        }
        .baris:first-of-type { border-top: 0; padding-top: 0; }
        label { display: block; font-size: .85rem; color: var(--muted); margin-bottom: 4px; }
        input {
            width: 100%;
            padding: 9px 10px;
            font: inherit;
            border: 1px solid #b9c1d3;
            border-radius: 6px;
            background: #fff;
            color: var(--ink);
        }
        input:focus-visible, button:focus-visible {
            outline: 3px solid #9bbcf3;
            outline-offset: 1px;
        }
        .pesan-error {
            grid-column: 1 / -1;
            margin: 0;
            padding: 8px 12px;
            background: var(--bad-bg);
            color: var(--bad-ink);
            border-radius: 6px;
            font-size: .88rem;
        }
        .pesan-error ul { margin: 0; padding-left: 18px; }

        .aksi { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
        button {
            font: inherit;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .btn-utama { background: var(--brand); color: #fff; }
        .btn-utama:hover { background: var(--brand-dark); }
        .btn-sekunder { background: #fff; color: var(--brand); border-color: var(--brand); }
        .btn-sekunder:hover { background: #eef3fc; }
        .btn-hapus { background: #fff; color: var(--bad-ink); border-color: #e3b4b4; padding: 9px 12px; }
        .btn-hapus:hover { background: var(--bad-bg); }

                .ringkasan { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
        .ringkasan div {
            flex: 1 1 150px;
            background: var(--bg);
            border-radius: 8px;
            padding: 12px 14px;
        }
        .ringkasan strong { display: block; font-size: 1.4rem; }
        .ringkasan span { color: var(--muted); font-size: .85rem; }

                .tabel-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 760px; }
        th, td { padding: 11px 12px; text-align: left; border-bottom: 1px solid var(--line); }
        th { background: var(--bg); font-size: .85rem; color: var(--muted); font-weight: 600; }
        td.angka, th.angka { text-align: right; font-variant-numeric: tabular-nums; }
        td.tengah, th.tengah { text-align: center; }

        .grade {
            display: inline-block;
            min-width: 32px;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 700;
            text-align: center;
            background: #e8eefb;
            color: var(--brand-dark);
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 600;
        }
        .badge.lulus { background: var(--ok-bg);  color: var(--ok-ink); }
        .badge.gagal { background: var(--bad-bg); color: var(--bad-ink); }

        .rumus {
            margin: 18px 0 0;
            padding: 12px 14px;
            background: var(--bg);
            border-left: 4px solid var(--accent);
            border-radius: 4px;
            font-size: .9rem;
            color: var(--muted);
        }
        .kosong { color: var(--muted); margin: 0; }

        @media (max-width: 820px) {
            .baris { grid-template-columns: 1fr 1fr; }
            .baris .btn-hapus { grid-column: 1 / -1; }
        }
    </style>
</head>
<body>

<header>
    <div class="wrap">
        <h1>Penghitung Nilai Mahasiswa</h1>
        <p>Pemrograman Berbasis Web &middot; D3 Manajemen Informatika &middot; Universitas Lampung</p>
    </div>
</header>

<main class="wrap">

    <section>
        <h2>Data mahasiswa</h2>
        <p class="hint">Nilai harus berupa angka 0&ndash;100. Gunakan tombol &ldquo;Tambah mahasiswa&rdquo; untuk memasukkan lebih dari satu data.</p>

        <form method="post" action="" id="form-nilai" novalidate>
            <div id="daftar-baris">
                <?php foreach ($dataMahasiswa as $i => $m): ?>
                    <div class="baris">
                        <div>
                            <label>Nama</label>
                            <input type="text" name="mhs[<?= $i ?>][nama]" value="<?= e($m['nama']) ?>" required>
                        </div>
                        <div>
                            <label>NIM</label>
                            <input type="text" name="mhs[<?= $i ?>][nim]" value="<?= e($m['nim']) ?>" inputmode="numeric" required>
                        </div>
                        <div>
                            <label>Tugas</label>
                            <input type="number" name="mhs[<?= $i ?>][tugas]" value="<?= e($m['tugas']) ?>" min="0" max="100" step="any" required>
                        </div>
                        <div>
                            <label>UTS</label>
                            <input type="number" name="mhs[<?= $i ?>][uts]" value="<?= e($m['uts']) ?>" min="0" max="100" step="any" required>
                        </div>
                        <div>
                            <label>UAS</label>
                            <input type="number" name="mhs[<?= $i ?>][uas]" value="<?= e($m['uas']) ?>" min="0" max="100" step="any" required>
                        </div>
                        <button type="button" class="btn-hapus" onclick="hapusBaris(this)">Hapus</button>

                        <?php if (!empty($errorBaris[$i])): ?>
                            <div class="pesan-error">
                                <ul>
                                    <?php foreach ($errorBaris[$i] as $pesan): ?>
                                        <li><?= e($pesan) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="aksi">
                <button type="button" class="btn-sekunder" onclick="tambahBaris()">Tambah mahasiswa</button>
                <button type="submit" class="btn-utama">Hitung nilai</button>
            </div>
        </form>
    </section>

    <section>
        <h2>Hasil perhitungan</h2>

        <?php if (empty($hasil)): ?>
            <p class="kosong">Belum ada data yang valid. Perbaiki isian pada form lalu klik &ldquo;Hitung nilai&rdquo;.</p>
        <?php else: ?>
            <div class="ringkasan">
                <div><strong><?= count($hasil) ?></strong><span>Mahasiswa dihitung</span></div>
                <div><strong><?= $jumlahLulus ?></strong><span>Lulus</span></div>
                <div><strong><?= count($hasil) - $jumlahLulus ?></strong><span>Tidak lulus</span></div>
                <div><strong><?= formatAngka($rataRata) ?></strong><span>Rata-rata nilai akhir</span></div>
            </div>

            <div class="tabel-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>NIM</th>
                            <th class="angka">Tugas</th>
                            <th class="angka">UTS</th>
                            <th class="angka">UAS</th>
                            <th class="angka">Nilai akhir</th>
                            <th class="tengah">Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hasil as $no => $h): ?>
                            <tr>
                                <td><?= $no + 1 ?></td>
                                <td><?= e($h['nama']) ?></td>
                                <td><?= e($h['nim']) ?></td>
                                <td class="angka"><?= formatAngka($h['tugas']) ?></td>
                                <td class="angka"><?= formatAngka($h['uts']) ?></td>
                                <td class="angka"><?= formatAngka($h['uas']) ?></td>
                                <td class="angka"><strong><?= formatAngka($h['akhir']) ?></strong></td>
                                <td class="tengah"><span class="grade"><?= e($h['grade']) ?></span></td>
                                <td>
                                    <span class="badge <?= $h['status'] === 'Lulus' ? 'lulus' : 'gagal' ?>">
                                        <?= e($h['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="rumus">
            Nilai akhir = (Tugas &times; 30%) + (UTS &times; 30%) + (UAS &times; 40%).
            Grade: A (85&ndash;100), B (75&ndash;84,99), C (65&ndash;74,99), D (50&ndash;64,99), E (&lt; 50).
            Lulus jika nilai akhir &ge; 65.
        </p>
    </section>

</main>

<script>
    const daftar = document.getElementById('daftar-baris');

    let indeks = daftar.querySelectorAll('.baris').length;

    function tambahBaris() {
        const i = indeks++;
        const baris = document.createElement('div');
        baris.className = 'baris';
        baris.innerHTML = `
            <div><label>Nama</label>
                <input type="text" name="mhs[${i}][nama]" required></div>
            <div><label>NIM</label>
                <input type="text" name="mhs[${i}][nim]" inputmode="numeric" required></div>
            <div><label>Tugas</label>
                <input type="number" name="mhs[${i}][tugas]" min="0" max="100" step="any" required></div>
            <div><label>UTS</label>
                <input type="number" name="mhs[${i}][uts]" min="0" max="100" step="any" required></div>
            <div><label>UAS</label>
                <input type="number" name="mhs[${i}][uas]" min="0" max="100" step="any" required></div>
            <button type="button" class="btn-hapus" onclick="hapusBaris(this)">Hapus</button>`;
        daftar.appendChild(baris);
        baris.querySelector('input').focus();
    }

    function hapusBaris(tombol) {
        if (daftar.querySelectorAll('.baris').length > 1) {
            tombol.closest('.baris').remove();
        }
    }
</script>

</body>
</html>