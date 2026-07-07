<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <span class="hero-etiket">Yeni Sezon</span>
            <h1>Tarzını Yansıtan<br>Ürünler Burada</h1>
            <p>Kaliteli ürünleri en uygun fiyatlarla keşfet, hemen alışverişe başla.</p>
            <a href="#urunler" class="btn btn-dark btn-lg rounded-pill px-4">
                Ürünleri İncele <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<?php
// Her kategoriden: kategori adı, kaç ürün olduğu ve o kategoriden bir görsel çekiyoruz
$kategoriler = $pdo->query("
    SELECT kategori, COUNT(*) AS urun_sayisi, MIN(ana_resim) AS ornek_resim
    FROM urunler
    WHERE kategori IS NOT NULL AND kategori != ''
    GROUP BY kategori
    ORDER BY urun_sayisi DESC
")->fetchAll();
?>

<?php if (count($kategoriler) > 0): ?>
<section class="container py-5">
    <h2 class="section-baslik">Kategoriler</h2>
    <div class="row g-3">
        <?php foreach ($kategoriler as $kat): ?>
            <div class="col-6 col-md-3">
                <a href="index.php?kategori=<?php echo urlencode($kat['kategori']); ?>#urunler" class="kategori-kart">
                    <div class="kategori-resim" style="background-image:url('/UrunDetay/<?php echo htmlspecialchars($kat['ornek_resim'] ?: ''); ?>')"></div>
                    <div class="kategori-bilgi">
                        <span class="kategori-ad"><?php echo htmlspecialchars(ucfirst($kat['kategori'])); ?></span>
                        <span class="kategori-sayi"><?php echo (int)$kat['urun_sayisi']; ?> ürün</span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php
// Kategoriye göre filtreleme (kategori kartına tıklanmışsa)
$seciliKategori = $_GET['kategori'] ?? null;

if ($seciliKategori) {
    $stmtUrunler = $pdo->prepare("
        SELECT * FROM urunler
        WHERE durum = 1 AND kategori = :kategori
        ORDER BY id DESC
    ");
    $stmtUrunler->execute([':kategori' => $seciliKategori]);
} else {
    $stmtUrunler = $pdo->query("
        SELECT * FROM urunler
        WHERE durum = 1
        ORDER BY id DESC
        LIMIT 24
    ");
}

$urunler = $stmtUrunler->fetchAll();
?>

<main class="container py-4" id="urunler">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-baslik mb-0">
            <?php echo $seciliKategori ? htmlspecialchars(ucfirst($seciliKategori)) : 'Tüm Ürünler'; ?>
        </h2>
        <?php if ($seciliKategori): ?>
            <a href="index.php#urunler" class="btn btn-outline-secondary btn-sm">Filtreyi Temizle</a>
        <?php endif; ?>
    </div>

    <?php if (count($urunler) === 0): ?>
        <p class="text-muted">Bu kategoride henüz ürün yok.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($urunler as $urun): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="urun.php?id=<?php echo $urun['id']; ?>" class="urun-kart">
                        <div class="urun-resim" style="background-image:url('/UrunDetay/<?php echo htmlspecialchars($urun['ana_resim'] ?: ''); ?>')"></div>
                        <div class="urun-bilgi">
                            <span class="urun-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></span>
                            <span class="urun-fiyat">
                                <?php if ((float)$urun['fiyat_tl'] > 0): ?>
                                    <?php echo number_format($urun['fiyat_tl'], 2, ',', '.'); ?> TL
                                <?php else: ?>
                                    $<?php echo number_format($urun['fiyat_usd'], 2); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>