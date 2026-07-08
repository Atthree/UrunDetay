<?php
/**
 * Ürün grid'lerinde (kategori/tümü sayfası + AJAX filtre yanıtı) kullanılan
 * ortak ürün kartı. Çağrılmadan önce $urun ve $favoriIdler hazır olmalı.
 *
 * @var array $urun
 * @var array $favoriIdler
 */
?>
<div class="col-6 col-md-4">
    <a href="urun.php?id=<?php echo $urun['id']; ?>" class="urun-kart">
        <button class="favori-btn<?php echo in_array($urun['id'], $favoriIdler) ? ' aktif' : ''; ?>" data-id="<?php echo $urun['id']; ?>"
                onclick="event.preventDefault();event.stopPropagation();toggleFavori(<?php echo $urun['id']; ?>, this);"
                title="Favorilere Ekle">
            <i class="bi <?php echo in_array($urun['id'], $favoriIdler) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
        </button>
        <div class="urun-resim-wrap">
            <div class="urun-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>')"></div>
        </div>
        <div class="urun-bilgi">
            <span class="urun-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></span>
            <?php if ($urun['yildiz_ortalama'] !== null): ?>
                <span class="urun-yildizlar"><?php echo yildizlar_html($urun['yildiz_ortalama']); ?></span>
            <?php endif; ?>
            <span class="urun-fiyat"><?php echo urun_fiyat_goster($urun); ?></span>
            <?php if ((int)$urun['miktar'] === 0): ?>
                <span class="urun-stok-yok">Stokta Yok</span>
            <?php endif; ?>
        </div>
    </a>
</div>
