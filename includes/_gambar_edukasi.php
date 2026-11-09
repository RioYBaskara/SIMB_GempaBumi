<?php
/**
 * includes/_gambar_edukasi.php
 * Komponen partial untuk menampilkan grid gambar edukasi
 * Variabel yang dibutuhkan:
 *   $gambar  — array hasil dari ambilGambarEdukasi()
 *   $bagian  — nama bagian (untuk atribut alt)
 */
// Fallback placeholder
$placeholder = BASE_URL . '/assets/img/placeholder.svg';
?>

<?php if (!empty($gambar)): ?>
<div class="grid-gambar">
    <?php foreach ($gambar as $idx => $img): ?>
    <div class="gambar-item">
        <!-- Gambar klik untuk besar (Bootstrap modal) -->
        <a href="#" data-bs-toggle="modal" data-bs-target="#modalGambar"
           data-url="<?= e($img['url_gambar']) ?>"
           data-judul="<?= e($img['judul']) ?>"
           data-sumber="<?= e($img['sumber']) ?>"
           data-link="<?= e($img['link_sumber']) ?>">
            <img src="<?= e($img['thumbnail'] ?: $img['url_gambar']) ?>"
                 alt="<?= e($img['judul'] ?: 'Ilustrasi ' . $bagian) ?>"
                 class="img-fluid rounded"
                 loading="lazy"
                 onerror="this.src='<?= $placeholder ?>'; this.onerror=null;">
        </a>
        <div class="sumber-gambar">
            Sumber:
            <?php if (!empty($img['link_sumber'])): ?>
            <a href="<?= e($img['link_sumber']) ?>" target="_blank" rel="noopener noreferrer">
                <?= e($img['sumber']) ?>
            </a>
            <?php else: ?>
            <?= e($img['sumber'] ?: 'Tidak diketahui') ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<!-- Placeholder saat gambar belum tersedia -->
<div class="text-center p-3">
    <img src="<?= $placeholder ?>" alt="Gambar belum tersedia"
         class="img-fluid rounded" style="max-height:160px; opacity:0.6">
    <p class="small text-muted mt-2">
        Gambar belum tersedia.<br>
        Konfigurasikan <a href="<?= BASE_URL ?>/README.md">Google API Key</a> atau
        <a href="<?= BASE_URL ?>/admin/gambar.php">tambahkan manual</a>.
    </p>
</div>
<?php endif; ?>

<!-- Modal untuk memperbesar gambar — dideklarasikan sekali, diisi via JS -->
<?php static $modal_dicetak = false; ?>
<?php if (!$modal_dicetak): $modal_dicetak = true; ?>
<div class="modal fade" id="modalGambar" tabindex="-1" aria-labelledby="judulModalGambar" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="judulModalGambar">Gambar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="gambarModalSrc" src="" alt="" class="img-fluid rounded">
            </div>
            <div class="modal-footer justify-content-start">
                <small class="text-muted" id="sumberModalGambar"></small>
                <a id="linkModalGambar" href="#" target="_blank" rel="noopener noreferrer"
                   class="btn btn-sm btn-outline-secondary ms-auto">
                    Buka halaman asal ↗
                </a>
            </div>
        </div>
    </div>
</div>
<script>
// Isi modal saat link gambar diklik
document.addEventListener('show.bs.modal', function(e) {
    if (e.target.id !== 'modalGambar') return;
    var trigger = e.relatedTarget;
    document.getElementById('gambarModalSrc').src      = trigger.dataset.url;
    document.getElementById('gambarModalSrc').alt      = trigger.dataset.judul;
    document.getElementById('judulModalGambar').textContent  = trigger.dataset.judul;
    document.getElementById('sumberModalGambar').textContent = 'Sumber: ' + trigger.dataset.sumber;
    document.getElementById('linkModalGambar').href          = trigger.dataset.link;
});
</script>
<?php endif; ?>
