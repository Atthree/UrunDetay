<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php
$urun = null;

if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM urunler WHERE id = :id AND durum = 1");
    $stmt->execute([':id' => $id]);
    $urun = $stmt->fetch();
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

require_once __DIR__ . '/includes/urun-detay-veri.php';
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
                <img src="<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>"
                    alt="<?php echo htmlspecialchars($urun['baslik_tr']); ?>"
                    id="galeriAnaImg">
            </div>
            <?php if (count($ekResimler) > 0 || !empty($urun['ana_resim'])): ?>
                <div class="urun-galeri-thumbnails">
                    <!-- Ana resim thumbnail -->
                    <?php if (!empty($urun['ana_resim'])): ?>
                        <div class="urun-galeri-thumb aktif" onclick="galeriDegistir(this, '<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>')">
                            <img src="<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>" alt="Ana">
                        </div>
                    <?php endif; ?>
                    <!-- Ek resimler -->
                    <?php foreach ($ekResimler as $resim): ?>
                        <div class="urun-galeri-thumb" onclick="galeriDegistir(this, '<?php echo htmlspecialchars(resim_url($resim['resim_yolu'])); ?>')">
                            <img src="<?php echo htmlspecialchars(resim_url($resim['resim_yolu'])); ?>" alt="Ek Resim">
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
            <?php if ($yorumSayisi > 0): ?>
            <div class="urun-detay-puan">
                <span class="urun-detay-yildizlar"><?php echo yildizlar_html($ortalamaPuan); ?></span>
                <a href="#yorumlar" class="urun-detay-puan-sayi">
                    <?php echo $yorumSayisi; ?> review<?php echo $yorumSayisi > 1 ? 's' : ''; ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- Ürün Kodu -->
            <p style="font-size:0.82rem;color:var(--renk-metin-acik);">
                Ürün Kodu: <strong><?php echo htmlspecialchars($urun['urun_kodu']); ?></strong>
            </p>

            <!-- Fiyat -->
            <div class="urun-detay-fiyat-wrap">
                <span class="urun-detay-fiyat">
                    $<?php echo number_format((float)$urun['fiyat_usd'], 2); ?>
                </span>
                <?php if ($eskiFiyat > 0 && $eskiFiyat > (float)$urun['fiyat_usd']): ?>
                    <span class="urun-detay-eski-fiyat">$<?php echo number_format($eskiFiyat, 2); ?></span>
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
                    <button class="sepete-ekle-btn" id="sepeteEkleBtn" data-id="<?php echo $urun['id']; ?>">
                        <i class="bi bi-bag-plus"></i> Sepete Ekle
                    </button>
                    <button class="favori-ekle-btn<?php echo $favoride ? ' aktif' : ''; ?>" id="detayFavoriBtn" data-id="<?php echo $urun['id']; ?>" title="Favorilere Ekle">
                        <i class="bi <?php echo $favoride ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
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

    <!-- Müşteri Yorumları -->
    <section class="yorum-ozet-bolumu" id="yorumlar">
        <h2 class="section-baslik text-center">Customer Reviews</h2>

        <?php if ($yorumSayisi > 0): ?>
        <div class="yorum-ozet-grid">
            <div class="yorum-ozet-sol">
                <div class="yorum-yildiz-buyuk">
                    <?php echo yildizlar_html($ortalamaPuan); ?>
                    <span class="yorum-puan-sayi"><?php echo $ortalamaPuan; ?> out of 5</span>
                </div>
                <p class="yorum-toplam-metin">Based on <?php echo $yorumSayisi; ?> review<?php echo $yorumSayisi > 1 ? 's' : ''; ?></p>
            </div>

            <div class="yorum-ozet-orta">
                <?php for ($star = 5; $star >= 1; $star--): ?>
                    <div class="yorum-dagilim-satir">
                        <span class="yorum-dagilim-yildiz"><?php echo yildizlar_html($star); ?></span>
                        <div class="yorum-dagilim-bar">
                            <div class="yorum-dagilim-dolu" style="width:<?php echo $yorumSayisi > 0 ? round(($dagilim[$star] / $yorumSayisi) * 100) : 0; ?>%;"></div>
                        </div>
                        <span class="yorum-dagilim-adet"><?php echo $dagilim[$star]; ?></span>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="yorum-ozet-sag">
                <?php if (girisYapmisMi() && !$dahaOnceYorumYapmis): ?>
                    <button type="button" class="yorum-yaz-btn" onclick="document.getElementById('yorumFormWrap').style.display='block';this.style.display='none';">
                        Write a review
                    </button>
                <?php elseif (!girisYapmisMi()): ?>
                    <a href="/UrunDetay/magaza/giris.php" class="yorum-yaz-btn">Write a review</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="yorum-liste">
            <?php foreach ($urunYorumlari as $y): ?>
                <div class="yorum-liste-item">
                    <div class="yorum-liste-ust">
                        <span class="yorum-liste-yildiz"><?php echo yildizlar_html((int)$y['puan']); ?></span>
                        <span class="yorum-liste-tarih">
                            <?php echo date('F j, Y', strtotime($y['olusturma_tarihi'])); ?>
                        </span>
                    </div>
                    <div class="yorum-liste-yazar">
                        <i class="bi bi-person-circle"></i>
                        <span><?php echo htmlspecialchars($y['yazan_ad']); ?></span>
                    </div>
                    <p class="yorum-liste-metin"><?php echo htmlspecialchars($y['yorum']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="yorum-bos-durum">
            <p class="text-muted">Henüz yorum yapılmamış. İlk yorumu sen yaz!</p>
            <?php if (girisYapmisMi()): ?>
                <button type="button" class="yorum-yaz-btn" onclick="document.getElementById('yorumFormWrap').style.display='block';this.style.display='none';">
                    Write a review
                </button>
            <?php else: ?>
                <a href="/UrunDetay/magaza/giris.php" class="yorum-yaz-btn">Write a review</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (girisYapmisMi() && !$dahaOnceYorumYapmis): ?>
        <div class="yorum-form-wrap" id="yorumFormWrap" style="display:none;">
            <h3 class="yorum-form-baslik">Yorumunu Yaz</h3>
            <form method="post" action="/UrunDetay/magaza/yorum-ekle.php">
                <input type="hidden" name="urun_id" value="<?php echo $urun['id']; ?>">

                <div class="yorum-form-yildiz-secici" id="yildizSecici">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star" data-deger="<?php echo $i; ?>"></i>
                    <?php endfor; ?>
                    <input type="hidden" name="puan" id="puanInput" value="0" required>
                </div>

                <textarea name="yorum" class="yorum-form-textarea" rows="4" placeholder="Ürün hakkındaki düşüncelerini yaz..." required></textarea>

                <button type="submit" class="yorum-yaz-btn">Gönder</button>
            </form>
        </div>
        <?php endif; ?>
    </section>

    <!-- Benzer Ürünler -->
    <?php if (count($benzerUrunler) > 0): ?>
        <section class="benzer-urunler">
            <h2 class="section-baslik">Benzer Ürünler</h2>
            <div class="row g-4">
                <?php foreach ($benzerUrunler as $b): ?>
                    <div class="col-6 col-md-3">
                        <a href="urun.php?id=<?php echo $b['id']; ?>" class="urun-kart">
                            <div class="urun-resim-wrap">
                                <div class="urun-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($b['ana_resim'])); ?>')"></div>
                            </div>
                            <div class="urun-bilgi">
                                <span class="urun-baslik"><?php echo htmlspecialchars($b['baslik_tr']); ?></span>
                                <span class="urun-fiyat"><?php echo urun_fiyat_goster($b); ?></span>
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

    // Yorum formu — tıklanabilir yıldız seçici
    const yildizSecici = document.getElementById('yildizSecici');
    if (yildizSecici) {
        const yildizlar = yildizSecici.querySelectorAll('i');
        const puanInput = document.getElementById('puanInput');

        yildizlar.forEach(yildiz => {
            yildiz.addEventListener('click', function () {
                const secilenDeger = parseInt(this.dataset.deger);
                puanInput.value = secilenDeger;
                yildizlar.forEach(y => {
                    const d = parseInt(y.dataset.deger);
                    y.className = d <= secilenDeger ? 'bi bi-star-fill' : 'bi bi-star';
                });
            });
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
