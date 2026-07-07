<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php
// Ürün bilgilerini çek
$urun = null;
$ekResimler = [];

if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM urunler WHERE id = :id AND durum = 1");
    $stmt->execute([':id' => $id]);
    $urun = $stmt->fetch();

    if ($urun) {
        // Ek resimleri çek
        $stmt2 = $pdo->prepare("SELECT * FROM urun_resimler WHERE urun_id = :id ORDER BY sira");
        $stmt2->execute([':id' => $id]);
        $ekResimler = $stmt2->fetchAll();
    }
}

if (!$urun) {
    echo '<div class="container py-5 text-center">';
    echo '<i class="bi bi-exclamation-triangle" style="font-size:3rem;color:#ccc;"></i>';
    echo '<h3 class="mt-3" style="color:#999;">Ürün bulunamadı</h3>';
    echo '<a href="/UrunDetay/magaza/index.php" class="btn btn-outline-dark btn-sm mt-3">Mağazaya Dön</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fiyat hesaplama
$fiyat = (float)$urun['fiyat_tl'];
$eskiFiyat = (float)($urun['fiyat2_tl'] ?? 0);
$stokVar = (int)$urun['miktar'] > 0;

// Benzer ürünler (aynı kategori)
$benzerUrunler = [];
if (!empty($urun['kategori'])) {
    $stmtBenzer = $pdo->prepare("
        SELECT * FROM urunler 
        WHERE kategori = :kategori AND id != :id AND durum = 1 
        ORDER BY RAND() 
        LIMIT 4
    ");
    $stmtBenzer->execute([':kategori' => $urun['kategori'], ':id' => $urun['id']]);
    $benzerUrunler = $stmtBenzer->fetchAll();
}
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="pt-3">
        <ol class="breadcrumb" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="/UrunDetay/magaza/index.php" style="color:var(--renk-vurgu);text-decoration:none;">Ana Sayfa</a></li>
            <?php if (!empty($urun['kategori'])): ?>
                <li class="breadcrumb-item"><a href="/UrunDetay/magaza/index.php?kategori=<?php echo urlencode($urun['kategori']); ?>#urunler" style="color:var(--renk-vurgu);text-decoration:none;"><?php echo htmlspecialchars(ucfirst($urun['kategori'])); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($urun['baslik_tr']); ?></li>
        </ol>
    </nav>

    <!-- Ürün Detay Grid -->
    <div class="urun-detay-wrap">
        <!-- Sol: Galeri -->
        <div class="urun-galeri">
            <div class="urun-galeri-ana" id="galeriAna">
                <img src="/UrunDetay/<?php echo htmlspecialchars($urun['ana_resim'] ?: ''); ?>" 
                     alt="<?php echo htmlspecialchars($urun['baslik_tr']); ?>"
                     id="galeriAnaImg">
            </div>
            <?php if (count($ekResimler) > 0 || !empty($urun['ana_resim'])): ?>
                <div class="urun-galeri-thumbnails">
                    <!-- Ana resim thumbnail -->
                    <?php if (!empty($urun['ana_resim'])): ?>
                        <div class="urun-galeri-thumb aktif" onclick="galeriDegistir(this, '/UrunDetay/<?php echo htmlspecialchars($urun['ana_resim']); ?>')">
                            <img src="/UrunDetay/<?php echo htmlspecialchars($urun['ana_resim']); ?>" alt="Ana">
                        </div>
                    <?php endif; ?>
                    <!-- Ek resimler -->
                    <?php foreach ($ekResimler as $resim): ?>
                        <div class="urun-galeri-thumb" onclick="galeriDegistir(this, '/UrunDetay/<?php echo htmlspecialchars($resim['resim_yolu']); ?>')">
                            <img src="/UrunDetay/<?php echo htmlspecialchars($resim['resim_yolu']); ?>" alt="Ek Resim">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sağ: Bilgiler -->
        <div class="urun-detay-bilgi">
            <!-- Kategori badge -->
            <?php if (!empty($urun['kategori'])): ?>
                <a href="/UrunDetay/magaza/index.php?kategori=<?php echo urlencode($urun['kategori']); ?>#urunler" class="urun-detay-kategori">
                    <?php echo htmlspecialchars(ucfirst($urun['kategori'])); ?>
                </a>
            <?php endif; ?>

            <!-- Başlık -->
            <h1 class="urun-detay-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></h1>

            <!-- Ürün Kodu -->
            <p style="font-size:0.82rem;color:var(--renk-metin-acik);">
                Ürün Kodu: <strong><?php echo htmlspecialchars($urun['urun_kodu']); ?></strong>
            </p>

            <!-- Fiyat -->
            <div class="urun-detay-fiyat-wrap">
                <span class="urun-detay-fiyat">
                    <?php if ($fiyat > 0): ?>
                        <?php echo number_format($fiyat, 2, ',', '.'); ?> TL
                    <?php else: ?>
                        $<?php echo number_format((float)$urun['fiyat_usd'], 2); ?>
                    <?php endif; ?>
                </span>
                <?php if ($eskiFiyat > 0 && $eskiFiyat > $fiyat): ?>
                    <span class="urun-detay-eski-fiyat"><?php echo number_format($eskiFiyat, 2, ',', '.'); ?> TL</span>
                <?php endif; ?>
                <span class="urun-detay-vergi">KDV Dahil (% <?php echo (int)$urun['vergi_orani']; ?>)</span>
            </div>

            <!-- Stok Durumu -->
            <?php if ($stokVar): ?>
                <div class="stok-durumu stokta">
                    <i class="bi bi-check-circle-fill"></i>
                    Stokta (<?php echo (int)$urun['miktar']; ?> <?php echo htmlspecialchars($urun['birim']); ?>)
                </div>
            <?php else: ?>
                <div class="stok-durumu stok-yok">
                    <i class="bi bi-x-circle-fill"></i>
                    Stokta Yok
                </div>
            <?php endif; ?>

            <hr class="urun-detay-ayirici">

            <!-- Açıklama -->
            <?php if (!empty($urun['aciklama_tr'])): ?>
                <div class="urun-detay-aciklama">
                    <?php echo $urun['aciklama_tr']; ?>
                </div>
            <?php endif; ?>

            <!-- Adet Seçici + Sepete Ekle -->
            <?php if ($stokVar): ?>
                <div class="adet-secici-wrap">
                    <span class="adet-secici-label">Adet:</span>
                    <div class="adet-secici">
                        <button type="button" onclick="adetDegistir(-1)">−</button>
                        <input type="number" class="adet-deger" id="urunAdet" value="1" min="1" max="<?php echo (int)$urun['miktar']; ?>">
                        <button type="button" onclick="adetDegistir(1)">+</button>
                    </div>
                </div>

                <div class="urun-detay-butonlar">
                    <button class="sepete-ekle-btn" id="sepeteEkleBtn"
                            data-id="<?php echo $urun['id']; ?>"
                            data-baslik="<?php echo htmlspecialchars($urun['baslik_tr']); ?>"
                            data-fiyat="<?php echo $fiyat; ?>"
                            data-resim="/UrunDetay/<?php echo htmlspecialchars($urun['ana_resim'] ?: ''); ?>">
                        <i class="bi bi-bag-plus"></i> Sepete Ekle
                    </button>
                    <button class="favori-ekle-btn" id="detayFavoriBtn" data-id="<?php echo $urun['id']; ?>" title="Favorilere Ekle">
                        <i class="bi bi-heart"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="urun-detay-butonlar">
                    <button class="sepete-ekle-btn" disabled style="opacity:0.5;cursor:not-allowed;">
                        <i class="bi bi-bag-x"></i> Stokta Yok
                    </button>
                    <button class="favori-ekle-btn" id="detayFavoriBtn" data-id="<?php echo $urun['id']; ?>" title="Favorilere Ekle">
                        <i class="bi bi-heart"></i>
                    </button>
                </div>
            <?php endif; ?>

            <hr class="urun-detay-ayirici">

            <!-- Özellikler -->
            <div class="urun-ozellikler">
                <?php if (!empty($urun['garanti_suresi'])): ?>
                    <div class="urun-ozellik-item">
                        <i class="bi bi-shield-check"></i>
                        <span class="etiket">Garanti</span>
                        <span class="deger"><?php echo (int)$urun['garanti_suresi']; ?> Ay</span>
                    </div>
                <?php endif; ?>
                <div class="urun-ozellik-item">
                    <i class="bi bi-truck"></i>
                    <span class="etiket">Kargo</span>
                    <span class="deger">Ücretsiz</span>
                </div>
                <?php if ((int)$urun['taksit']): ?>
                    <div class="urun-ozellik-item">
                        <i class="bi bi-credit-card"></i>
                        <span class="etiket">Taksit</span>
                        <span class="deger">Mevcut</span>
                    </div>
                <?php endif; ?>
                <div class="urun-ozellik-item">
                    <i class="bi bi-arrow-return-left"></i>
                    <span class="etiket">İade</span>
                    <span class="deger">14 Gün</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Benzer Ürünler -->
    <?php if (count($benzerUrunler) > 0): ?>
        <section class="benzer-urunler">
            <h2 class="section-baslik">Benzer Ürünler</h2>
            <div class="row g-4">
                <?php foreach ($benzerUrunler as $b): ?>
                    <div class="col-6 col-md-3">
                        <a href="urun.php?id=<?php echo $b['id']; ?>" class="urun-kart">
                            <div class="urun-resim-wrap">
                                <div class="urun-resim" style="background-image:url('/UrunDetay/<?php echo htmlspecialchars($b['ana_resim'] ?: ''); ?>')"></div>
                            </div>
                            <div class="urun-bilgi">
                                <span class="urun-baslik"><?php echo htmlspecialchars($b['baslik_tr']); ?></span>
                                <span class="urun-fiyat">
                                    <?php if ((float)$b['fiyat_tl'] > 0): ?>
                                        <?php echo number_format($b['fiyat_tl'], 2, ',', '.'); ?> TL
                                    <?php else: ?>
                                        $<?php echo number_format($b['fiyat_usd'], 2); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
// Galeri thumbnail değiştirme
function galeriDegistir(thumb, yeniSrc) {
    document.getElementById('galeriAnaImg').src = yeniSrc;
    document.querySelectorAll('.urun-galeri-thumb').forEach(t => t.classList.remove('aktif'));
    thumb.classList.add('aktif');
}

// Adet değiştirme
function adetDegistir(delta) {
    const input = document.getElementById('urunAdet');
    let val = parseInt(input.value) || 1;
    val += delta;
    const maxVal = parseInt(input.max) || 999;
    if (val < 1) val = 1;
    if (val > maxVal) val = maxVal;
    input.value = val;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
