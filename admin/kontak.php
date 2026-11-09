<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/admin.php';
admin_wajib();

/** Validasi isian kontak; return [data|null, pesan galat|null] */
function baca_kontak(): array
{
    $instansi = mb_substr(trim((string) ($_POST['instansi'] ?? '')), 0, 100);
    $nomor    = mb_substr(trim((string) ($_POST['nomor'] ?? '')), 0, 20);
    $ket      = mb_substr(trim((string) ($_POST['keterangan'] ?? '')), 0, 150);
    $urutan   = max(0, min(127, (int) ($_POST['urutan'] ?? 0)));
    $aktif    = !empty($_POST['aktif']) ? 1 : 0;

    if ($instansi === '') return [null, 'Nama instansi wajib diisi.'];
    if (!preg_match('/^[0-9+\-\s]{2,20}$/', $nomor)) return [null, 'Nomor hanya boleh berisi angka, spasi, + dan - (2-20 karakter).'];
    return [['instansi' => $instansi, 'nomor' => $nomor, 'ket' => $ket === '' ? null : $ket, 'urutan' => $urutan, 'aktif' => $aktif], null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash_set('danger', 'Sesi kedaluwarsa. Coba lagi.');
    } else {
        $aksi = $_POST['aksi'] ?? '';
        $id   = (int) ($_POST['id'] ?? 0);

        if ($aksi === 'tambah' || $aksi === 'simpan') {
            [$d, $err] = baca_kontak();
            if ($err) {
                flash_set('danger', $err);
            } elseif ($aksi === 'tambah') {
                $stmt = $koneksi->prepare("INSERT INTO kontak_darurat (instansi, nomor, keterangan, urutan, aktif) VALUES (?,?,?,?,?)");
                $stmt->bind_param('sssii', $d['instansi'], $d['nomor'], $d['ket'], $d['urutan'], $d['aktif']);
                $stmt->execute();
                flash_set('success', 'Kontak ditambahkan.');
            } else {
                $stmt = $koneksi->prepare("UPDATE kontak_darurat SET instansi=?, nomor=?, keterangan=?, urutan=?, aktif=? WHERE id=?");
                $stmt->bind_param('sssiii', $d['instansi'], $d['nomor'], $d['ket'], $d['urutan'], $d['aktif'], $id);
                $stmt->execute();
                flash_set('success', 'Perubahan disimpan.');
            }
        } elseif ($aksi === 'hapus' && $id > 0) {
            $stmt = $koneksi->prepare("DELETE FROM kontak_darurat WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash_set('success', 'Kontak dihapus.');
        }
    }
    header('Location: kontak.php');
    exit;
}

$daftar = $koneksi->query("SELECT * FROM kontak_darurat ORDER BY urutan, id")->fetch_all(MYSQLI_ASSOC);

admin_atas('Kontak Darurat', 'kontak');
?>
<h1 class="h4 fw-bold mb-1">Kontak Darurat</h1>
<p class="text-muted small">Tampil di halaman utama sebagai tombol telepon. Urutan terkecil tampil paling atas; kontak nonaktif disembunyikan.</p>

<div class="kartu table-responsive mb-4">
  <table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th>Instansi</th><th style="width:130px">Nomor</th><th>Keterangan</th><th style="width:80px">Urutan</th><th style="width:70px">Aktif</th><th style="width:150px"></th></tr></thead>
    <tbody>
    <?php foreach ($daftar as $k): $f = 'f' . (int) $k['id']; ?>
      <tr>
        <td><input form="<?= $f ?>" class="form-control form-control-sm" name="instansi" value="<?= e($k['instansi']) ?>" maxlength="100" required></td>
        <td><input form="<?= $f ?>" class="form-control form-control-sm" name="nomor" value="<?= e($k['nomor']) ?>" maxlength="20" required></td>
        <td><input form="<?= $f ?>" class="form-control form-control-sm" name="keterangan" value="<?= e($k['keterangan']) ?>" maxlength="150"></td>
        <td><input form="<?= $f ?>" class="form-control form-control-sm" type="number" name="urutan" value="<?= (int) $k['urutan'] ?>" min="0" max="127"></td>
        <td class="text-center"><input form="<?= $f ?>" class="form-check-input" type="checkbox" name="aktif" value="1" <?= $k['aktif'] ? 'checked' : '' ?> aria-label="Aktif"></td>
        <td class="text-nowrap">
          <button form="<?= $f ?>" class="btn btn-sm btn-dark" type="submit">Simpan</button>
          <button form="h<?= (int) $k['id'] ?>" class="btn btn-sm btn-outline-danger" type="submit"
                  onclick="return confirm('Hapus kontak ini?');"><i class="bi bi-trash"></i></button>
          <form id="<?= $f ?>" method="post" class="d-none"><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= (int) $k['id'] ?>"></form>
          <form id="h<?= (int) $k['id'] ?>" method="post" class="d-none"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int) $k['id'] ?>"></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="6" class="text-center text-muted py-3">Belum ada kontak.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<section class="kartu p-3">
  <h2 class="h6 fw-bold">Tambah kontak baru</h2>
  <form method="post" class="row g-2 align-items-end">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
    <div class="col-md-3"><label class="form-label small mb-1" for="n_instansi">Instansi</label>
      <input class="form-control" id="n_instansi" name="instansi" maxlength="100" required></div>
    <div class="col-md-2"><label class="form-label small mb-1" for="n_nomor">Nomor</label>
      <input class="form-control" id="n_nomor" name="nomor" maxlength="20" required></div>
    <div class="col-md-4"><label class="form-label small mb-1" for="n_ket">Keterangan</label>
      <input class="form-control" id="n_ket" name="keterangan" maxlength="150"></div>
    <div class="col-6 col-md-1"><label class="form-label small mb-1" for="n_urut">Urutan</label>
      <input class="form-control" id="n_urut" type="number" name="urutan" value="<?= count($daftar) + 1 ?>" min="0" max="127"></div>
    <div class="col-6 col-md-2 d-grid"><input type="hidden" name="aktif" value="1"><button class="btn btn-danger" type="submit"><i class="bi bi-plus-lg"></i> Tambah</button></div>
  </form>
</section>
<?php admin_bawah(); ?>
