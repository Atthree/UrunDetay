<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/anasayfa-veri.php';
?>

<!-- Hero Section (yalnızca ana sayfada göster) -->
<?php if (!$urunlerGoster): ?>
<section class="hero-video-wrap">
    <video class="hero-video" autoplay muted loop playsinline
           poster="/UrunDetay/magaza/assets/img/hero-poster.jpg">
        <source src="/UrunDetay/magaza/assets/video/hero.mp4" type="video/mp4">
    </video>
    <div class="hero-video-katman"></div>

    <div class="hero-video-icerik">
        <span class="hero-etiket">Yeni Sezon</span>
        <h1>Tarzını Yansıtan<br>Ürünler Burada</h1>
        <p>Kaliteli ürünleri en uygun fiyatlarla keşfet, hemen alışverişe başla.</p>
        <a href="index.php?tumu=1#urunler" class="btn btn-light btn-lg rounded-pill px-4" style="font-weight:600;">
            Ürünleri İncele <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="hero-marquee">
        <div class="hero-marquee-track">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <span>Ücretsiz Kargo</span><span class="ayrac">✦</span>
                <span>14 Gün İade</span><span class="ayrac">✦</span>
                <span>Güvenli Ödeme</span><span class="ayrac">✦</span>
                <span>Kapıda Ödeme</span><span class="ayrac">✦</span>
                <span>%100 Orijinal Ürün</span><span class="ayrac">✦</span>
                <span>7/24 Müşteri Desteği</span><span class="ayrac">✦</span>
                <span>Yeni Sezon Ürünleri</span><span class="ayrac">✦</span>
            <?php endfor; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Kategori Vitrinleri -->
<?php
$vitrinKategorileri = ['kitchen-accessories', 'groceries', 'mens-watches', 'beauty'];
$vitrinBaslik = [
    'kitchen-accessories' => 'Mutfak Aksesuarları',
    'groceries'           => 'Market Ürünleri',
    'mens-watches'        => 'Erkek Saatleri',
    'beauty'              => 'Güzellik Ürünleri',
];
$vitrinSayilari = array_column($kategoriler, 'urun_sayisi', 'kategori');
$vitrinGorselleri = [
    'kitchen-accessories' => '/UrunDetay/magaza/assets/img/MutfakAksesuarlari.jpg',
    'groceries'           => '/UrunDetay/magaza/assets/img/MarketUrunleri.jpg',
    'mens-watches'        => '/UrunDetay/magaza/assets/img/ErkekSaatleri.jpg',
    'beauty'              => '/UrunDetay/magaza/assets/img/GuzellikUrunleri.jpg',
];
?>
<?php if (!$urunlerGoster): ?>
<section class="category-highlights" id="kategoriVitrinSatiri">
    <div class="container">
        <h2 class="category-highlights-baslik">Kategori Vitrinleri</h2>
        <p class="category-highlights-aciklama">Size özel seçilmiş kategorilerle alışverişin keyfini çıkarın.</p>

        <div class="kategori-vitrin-wrap">
            <button type="button" class="kategori-vitrin-ok kategori-vitrin-ok-sol" id="kategoriVitrinOkSol" aria-label="Önceki kategori">
                <i class="bi bi-chevron-left"></i>
            </button>

            <div class="category-highlights-grid kategori-vitrin-grid" id="kategoriVitrinGrid">
                <?php foreach ($vitrinKategorileri as $vk): ?>
                    <a href="index.php?kategori=<?php echo urlencode($vk); ?>#urunler" class="category-highlight-kart">
                        <img src="<?php echo htmlspecialchars($vitrinGorselleri[$vk]); ?>" alt="<?php echo htmlspecialchars($vitrinBaslik[$vk]); ?>" class="category-highlight-gorsel">
                        <div class="category-highlight-overlay"></div>
                        <div class="category-highlight-metin">
                            <span class="category-highlight-ad">
                                <?php echo htmlspecialchars($vitrinBaslik[$vk]); ?><sup><?php echo (int)($vitrinSayilari[$vk] ?? 0); ?></sup>
                            </span>
                            <span class="category-highlight-slogan">Mağazam—kaliteli ve güvenilir alışveriş.</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <button type="button" class="kategori-vitrin-ok kategori-vitrin-ok-sag" id="kategoriVitrinOkSag" aria-label="Sonraki kategori">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        <div class="kategori-vitrin-noktalar" id="kategoriVitrinNoktalar"></div>
    </div>
</section>
<?php endif; ?>

<!-- Öne Çıkan Kategoriler (sekmeli, ilk sekme dışında AJAX ile yüklenir) -->
<?php if (!$urunlerGoster && count($oneCikanKategoriler) > 0): ?>
<section class="container py-5">
    <h2 class="section-baslik text-center">Öne Çıkan Kategoriler</h2>

    <div class="kategori-tab-bar">
        <?php foreach ($oneCikanKategoriler as $i => $kat): ?>
            <button type="button"
                    class="kategori-tab<?php echo $i === 0 ? ' aktif' : ''; ?>"
                    data-kategori="<?php echo htmlspecialchars($kat['kategori']); ?>">
                <?php echo htmlspecialchars(ucfirst($kat['kategori'])); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="kategori-panel-alani">
        <div class="kategori-spinner" id="kategoriSpinner"></div>
        <div class="row g-4" id="kategoriUrunGrid">
            <?php foreach ($ilkKategoriUrunleri as $urun): ?>
                <div class="col-6 col-md-3 kategori-urun-slayt">
                    <a href="urun.php?id=<?php echo $urun['id']; ?>" class="urun-kart">
                        <button class="favori-btn<?php echo in_array($urun['id'], $favoriIdler ?? []) ? ' aktif' : ''; ?>"
                                data-id="<?php echo $urun['id']; ?>"
                                onclick="event.preventDefault();event.stopPropagation();toggleFavori(<?php echo $urun['id']; ?>, this);"
                                title="Favorilere Ekle">
                            <i class="bi <?php echo in_array($urun['id'], $favoriIdler ?? []) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                        </button>
                        <button class="hizli-sepet-btn"
                                data-id="<?php echo $urun['id']; ?>"
                                onclick="event.preventDefault();event.stopPropagation();window.hizliSepeteEkle(<?php echo $urun['id']; ?>, this);"
                                title="Sepete Ekle">
                            <i class="bi bi-bag-plus"></i>
                        </button>
                        <div class="urun-resim-wrap">
                            <div class="urun-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>')"></div>
                        </div>
                        <div class="urun-bilgi">
                            <span class="urun-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></span>
                            <?php if ($urun['yildiz_ortalama'] !== null): ?>
                                <span class="urun-yildizlar"><?php echo yildizlar_html($urun['yildiz_ortalama']); ?></span>
                            <?php endif; ?>
                            <span class="urun-fiyat">$<?php echo number_format($urun['fiyat_usd'], 2); ?></span>
                            <?php if ((int)$urun['miktar'] === 0): ?>
                                <span class="urun-stok-yok">Stokta Yok</span>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="kategori-urun-noktalar" id="kategoriUrunNoktalar"></div>

        <div class="text-center mt-4">
            <a href="index.php?kategori=<?php echo urlencode($oneCikanKategoriler[0]['kategori']); ?>#urunler"
               class="btn btn-outline-dark rounded-pill px-4" id="kategoriTumunuGorBtn">
                Tüm <?php echo htmlspecialchars(ucfirst($oneCikanKategoriler[0]['kategori'])); ?> Ürünlerini Gör
            </a>
        </div>
    </div>
</section>

<script>
    // İlk sekmenin verisini JS önbelleğine baştan koyuyoruz ki
    // kullanıcı ona geri dönünce tekrar sunucuya istek atılmasın.
    window.KATEGORI_ILK_VERI = {
        kategori: <?php echo json_encode($oneCikanKategoriler[0]['kategori'], JSON_UNESCAPED_UNICODE); ?>,
        urunler: <?php echo json_encode(array_map(function ($u) {
            return [
                'id' => $u['id'],
                'baslik_tr' => $u['baslik_tr'],
                'fiyat_usd' => (float)$u['fiyat_usd'],
                'miktar' => (int)$u['miktar'],
                'ana_resim' => resim_url($u['ana_resim']),
                'yildiz_ortalama' => $u['yildiz_ortalama'],
            ];
        }, $ilkKategoriUrunleri), JSON_UNESCAPED_UNICODE); ?>
    };
</script>
<?php endif; ?>

<!-- 3'lü Tanıtım Banner Grid'i (yalnızca ana sayfada) -->
<?php if (!$urunlerGoster): ?>
<section class="container py-5">
    <div class="tanitim-banner-grid">
        <a href="index.php?kategori=kitchen-accessories#urunler" class="tanitim-banner" style="background-image:url('/UrunDetay/magaza/assets/img/MutfakAksesuarlari.jpg')">
            <div class="tanitim-banner-katman"></div>
            <div class="tanitim-banner-icerik">
                <span class="tanitim-etiket">%50'ye Varan İndirim</span>
                <h3>Mutfak Aksesuarları</h3>
                <span class="btn btn-light rounded-pill px-4">Koleksiyonu Gör</span>
            </div>
        </a>

        <a href="index.php?kategori=mens-shoes#urunler" class="tanitim-banner tanitim-banner-video">
            <video class="tanitim-banner-video-el" autoplay muted loop playsinline>
                <source src="/UrunDetay/magaza/assets/video/hero.mp4" type="video/mp4">
            </video>
            <div class="tanitim-banner-katman"></div>
            <div class="tanitim-banner-icerik">
                <span class="tanitim-etiket">%50'ye Varan İndirim</span>
                <h3>Erkek Ayakkabı Modelleri</h3>
                <span class="btn btn-light rounded-pill px-4">Koleksiyonu Gör</span>
            </div>
        </a>

        <a href="index.php?kategori=beauty#urunler" class="tanitim-banner" style="background-image:url('/UrunDetay/magaza/assets/img/GuzellikUrunleri.jpg')">
            <div class="tanitim-banner-katman"></div>
            <div class="tanitim-banner-icerik">
                <span class="tanitim-etiket">Mükemmel Tasarım</span>
                <h3>Güzellik Ürünleri Koleksiyonu</h3>
                <span class="btn btn-light rounded-pill px-4">Koleksiyonu Gör</span>
            </div>
        </a>
    </div>

    <div class="tanitim-banner-noktalar" id="tanitimBannerNoktalar"></div>
</section>
<?php endif; ?>

<main class="container py-4" id="urunler">
    <?php if ($urunlerGoster): ?>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="section-baslik mb-0">
            <span id="urunlerBaslikMetin"><?php echo $filtreAktif ? htmlspecialchars(ucfirst($seciliKategori)) : 'Tüm Ürünler'; ?></span>
            <span id="urunlerSayacMetin" style="font-size:0.85rem;font-weight:400;color:var(--renk-metin-acik);margin-left:8px;">
                (<?php echo $toplamUrun; ?> ürün)
            </span>
        </h2>
        <div class="d-flex gap-2 align-items-center">
            <button class="filtre-mobil-btn d-lg-none" id="filtreMobilBtn" data-bs-toggle="offcanvas" data-bs-target="#filtreSidebar" aria-controls="filtreSidebar">
                <i class="bi bi-funnel"></i> Filtrele
            </button>
            <a href="index.php?tumu=1#urunler" id="filtreTemizleBtn" class="btn btn-outline-secondary btn-sm" style="border-radius:var(--yuvarlatma-kucuk);">
                <i class="bi bi-x"></i> Filtreyi Temizle
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($urunlerGoster): ?>
    <!-- Kategoriye girildiğinde veya "Tümü" seçildiğinde: Sol Filtre + Sağ Ürünler -->
    <div class="magaza-layout">
        <!-- Sol Filtre Sidebar -->
        <aside class="filtre-sidebar offcanvas-start offcanvas-lg" tabindex="-1" id="filtreSidebar" aria-labelledby="filtreSidebarLabel">
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title" id="filtreSidebarLabel">Filtrele</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#filtreSidebar" aria-label="Kapat"></button>
            </div>
            <div class="offcanvas-body d-block p-lg-0">
            <!-- Kategoriler -->
            <div class="filtre-kart">
                <div class="filtre-baslik"><i class="bi bi-grid"></i> Kategoriler</div>
                <ul class="filtre-liste">
                    <li>
                        <a href="index.php?tumu=1#urunler" data-tumu="1" class="<?php echo !$seciliKategori ? 'aktif' : ''; ?>">
                            Tümü
                        </a>
                    </li>
                    <?php foreach ($kategoriler as $kat): ?>
                        <li>
                            <a href="index.php?kategori=<?php echo urlencode($kat['kategori']); ?>#urunler"
                               data-kategori="<?php echo htmlspecialchars($kat['kategori']); ?>"
                               class="<?php echo ($seciliKategori === $kat['kategori']) ? 'aktif' : ''; ?>">
                                <?php echo htmlspecialchars(ucfirst($kat['kategori'])); ?>
                                <span class="sayi"><?php echo (int)$kat['urun_sayisi']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Fiyat Aralığı -->
            <div class="filtre-kart">
                <div class="filtre-baslik"><i class="bi bi-currency-exchange"></i> Fiyat Aralığı</div>
                <form method="get" action="index.php" id="fiyatForm">
                    <?php if ($seciliKategori): ?>
                        <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($seciliKategori); ?>">
                    <?php elseif ($tumuAktif): ?>
                        <input type="hidden" name="tumu" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="siralama" value="<?php echo htmlspecialchars($siralama); ?>">
                    <div class="filtre-fiyat-wrap">
                        <input type="number" name="fiyat_min" id="fiyatMinInput" placeholder="Min" min="0" step="1"
                               value="<?php echo $fiyatMin !== null ? (int)$fiyatMin : ''; ?>">
                        <span>—</span>
                        <input type="number" name="fiyat_max" id="fiyatMaxInput" placeholder="Max" min="0" step="1"
                               value="<?php echo $fiyatMax !== null ? (int)$fiyatMax : ''; ?>">
                    </div>
                    <button type="submit" class="filtre-uygula-btn">Uygula</button>
                    <a href="index.php?<?php echo http_build_query(array_filter(['kategori' => $seciliKategori, 'tumu' => $tumuAktif ? 1 : null, 'siralama' => $siralama])); ?>#urunler"
                       id="fiyatTemizleLink" class="filtre-temizle-link" style="<?php echo ($fiyatMin === null && $fiyatMax === null) ? 'display:none;' : ''; ?>">
                        Fiyat filtresini temizle
                    </a>
                </form>
            </div>

            <!-- Sıralama -->
            <div class="filtre-kart">
                <div class="filtre-baslik"><i class="bi bi-sort-down"></i> Sıralama</div>
                <form method="get" action="index.php" id="siralamaForm">
                    <?php if ($seciliKategori): ?>
                        <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($seciliKategori); ?>">
                    <?php elseif ($tumuAktif): ?>
                        <input type="hidden" name="tumu" value="1">
                    <?php endif; ?>
                    <?php if ($fiyatMin !== null): ?>
                        <input type="hidden" name="fiyat_min" value="<?php echo (int)$fiyatMin; ?>">
                    <?php endif; ?>
                    <?php if ($fiyatMax !== null): ?>
                        <input type="hidden" name="fiyat_max" value="<?php echo (int)$fiyatMax; ?>">
                    <?php endif; ?>
                    <select name="siralama" class="filtre-siralama">
                        <option value="yeni" <?php echo $siralama === 'yeni' ? 'selected' : ''; ?>>Yeni Eklenen</option>
                        <option value="fiyat_artan" <?php echo $siralama === 'fiyat_artan' ? 'selected' : ''; ?>>Fiyat: Düşük → Yüksek</option>
                        <option value="fiyat_azalan" <?php echo $siralama === 'fiyat_azalan' ? 'selected' : ''; ?>>Fiyat: Yüksek → Düşük</option>
                        <option value="ad_az" <?php echo $siralama === 'ad_az' ? 'selected' : ''; ?>>İsim: A → Z</option>
                    </select>
                </form>
            </div>
            </div>
        </aside>

        <!-- Sağ: Ürün Grid -->
        <div class="urun-grid-alani" id="urunGridAlani">
            <div class="urun-grid-spinner" id="urunGridSpinner"></div>
            <div id="urunGridIcerik">
                <?php if ($toplamUrun === 0): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size:3rem;color:#ccc;"></i>
                        <p class="text-muted mt-3">Bu filtrelere uygun ürün bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($urunler as $urun): ?>
                            <?php include __DIR__ . '/includes/urun-karti.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <?php if (!$urunlerGoster && count($paketUrunleri) > 0): ?>
<section class="container py-5" id="paketBolumu">
    <h2 class="section-baslik text-center">Paket Yap, %30 Kazan</h2>
    <p class="text-center text-muted mb-4">3 ürün seçin, %30 indirim kazanın.</p>

    <div class="paket-alan">
        <div class="paket-urun-satiri">
            <?php foreach ($paketUrunleri as $urun): ?>
                <div class="paket-urun-kart"
                     data-id="<?php echo $urun['id']; ?>"
                     data-baslik="<?php echo htmlspecialchars($urun['baslik_tr']); ?>"
                     data-fiyat="<?php echo (float)$urun['fiyat_usd']; ?>"
                     data-resim="<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>">
                    <div class="urun-resim-wrap paket-resim-wrap">
                        <button class="favori-btn<?php echo in_array($urun['id'], $favoriIdler ?? []) ? ' aktif' : ''; ?>"
                                data-id="<?php echo $urun['id']; ?>"
                                onclick="event.preventDefault();event.stopPropagation();toggleFavori(<?php echo $urun['id']; ?>, this);"
                                title="Favorilere Ekle">
                            <i class="bi <?php echo in_array($urun['id'], $favoriIdler ?? []) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                        </button>
                        <div class="urun-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>')"></div>
                    </div>
                    <div class="urun-bilgi">
                        <span class="urun-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></span>
                        <?php $puanBilgi = $paketPuanlar[$urun['id']] ?? null; ?>
                        <?php if ($puanBilgi): ?>
                            <span class="paket-urun-yildiz"><?php echo yildizlar_html($puanBilgi['ortalama']); ?></span>
                        <?php endif; ?>
                        <span class="urun-fiyat">$<?php echo number_format($urun['fiyat_usd'], 2); ?></span>
                    </div>
                    <button type="button" class="paket-ekle-btn">Pakete Ekle</button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="paket-ozet paket-ozet-cerceveli" id="paketOzet">
            <div class="paket-ozet-ust-satir">
                <div class="paket-ozet-baslik">Paket İçeriği</div>
                <button type="button" class="paket-ozet-toggle" id="paketOzetToggle" aria-label="Paket içeriğini aç/kapat">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
            <p class="paket-ozet-aciklama"><strong> 3</strong> ürün ekleyin ve <strong>%30 Kazanın.</strong></p>

            <div class="paket-ozet-liste" id="paketOzetListe">
                <div class="paket-iskelet-satir">
                    <div class="paket-iskelet-daire"></div>
                    <div class="paket-iskelet-cubuklar">
                        <div class="paket-iskelet-cubuk genis"></div>
                        <div class="paket-iskelet-cubuk dar"></div>
                    </div>
                </div>
                <div class="paket-iskelet-satir">
                    <div class="paket-iskelet-daire"></div>
                    <div class="paket-iskelet-cubuklar">
                        <div class="paket-iskelet-cubuk genis"></div>
                        <div class="paket-iskelet-cubuk dar"></div>
                    </div>
                </div>
                <div class="paket-iskelet-satir">
                    <div class="paket-iskelet-daire"></div>
                    <div class="paket-iskelet-cubuklar">
                        <div class="paket-iskelet-cubuk genis"></div>
                        <div class="paket-iskelet-cubuk dar"></div>
                    </div>
                </div>
            </div>

            <div class="paket-ozet-alt">
                <div class="paket-ozet-satir">
                    <span>Ara Toplam</span>
                    <span id="paketAraToplam">$0.00</span>
                </div>
                <div class="paket-ozet-satir paket-indirim-satiri" id="paketIndirimSatiri" style="display:none;">
                    <span>%30 İndirim</span>
                    <span id="paketIndirimTutari">-$0.00</span>
                </div>
                <div class="paket-ozet-satir paket-toplam-satiri">
                    <span>Toplam</span>
                    <span id="paketToplam">$0.00</span>
                </div>
                <button type="button" class="paket-sepete-ekle-btn" id="paketSepeteEkleBtn" disabled>
                    Tümünü Sepete Ekle
                </button>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
</main>

<?php
$urunYorumCarousel = $pdo->query("
    SELECT uy.*, u.baslik_tr, u.ana_resim, u.fiyat_usd
    FROM urun_yorumlari uy
    INNER JOIN urunler u ON u.id = uy.urun_id
    WHERE uy.puan >= 4
    ORDER BY RAND()
    LIMIT 8
")->fetchAll();
?>
<?php if (!$filtreAktif && count($urunYorumCarousel) > 0): ?>
<section class="yorum-bolum-arka py-5">
    <div class="container">
        <h2 class="section-baslik text-center">Müşteri Yorumları</h2>
        <p class="text-center text-muted mb-4">Müşterilerimiz ürünlerimizi seviyor, siz de deneyin.</p>

        <div class="yorum-carousel-alani">
            <button type="button" class="yorum-carousel-ok yorum-carousel-ok-sol" id="yorumCarouselOkSol" aria-label="Önceki yorumlar">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="yorum-carousel" id="yorumCarousel">
                <?php foreach ($urunYorumCarousel as $yorum): ?>
                    <div class="urun-yorum-kart">
                        <div class="urun-yorum-yildizlar">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?php echo $i <= (int)$yorum['puan'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="yorum-yazan"><?php echo htmlspecialchars($yorum['reviewer_adi'] ?? 'Müşteri'); ?></div>
                        <p class="urun-yorum-metin"><?php echo htmlspecialchars($yorum['yorum']); ?></p>
                        <div class="urun-yorum-urun">
                            <img src="<?php echo htmlspecialchars(resim_url($yorum['ana_resim'])); ?>" alt="">
                            <div>
                                <div class="ad"><?php echo htmlspecialchars($yorum['baslik_tr']); ?></div>
                                <div class="fiyat">$<?php echo number_format($yorum['fiyat_usd'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="yorum-carousel-ok yorum-carousel-ok-sag" id="yorumCarouselOkSag" aria-label="Sonraki yorumlar">
                <i class="bi bi-chevron-right"></i>
            </button>
            <div class="yorum-noktalar" id="yorumNoktalar"></div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Promosyon Banner'ları -->
<section class="promo-banners">
    <div class="container">
        <div class="promo-banners-grid">
            <div class="promo-banner promo-banner-koyu">
                <div class="promo-banner-metin">
                    <span class="promo-banner-etiket">İNDİRİM %30-50</span>
                    <h3 class="promo-banner-baslik">Mutfak Setleri</h3>
                    <p class="promo-banner-aciklama">149 TL'den başlayan fiyatlarla, kaçırmayın...</p>
                    <a href="index.php?tumu=1#urunler" class="promo-banner-btn">Alışverişe Git</a>
                </div>
                <img src="/UrunDetay/magaza/assets/img/MutfakAksesuarlari.jpg" alt="Mutfak Eşyaları" class="promo-banner-gorsel">
            </div>
            <div class="promo-banner promo-banner-bordo">
                <div class="promo-banner-metin">
                    <span class="promo-banner-etiket">%75'E VARAN İNDİRİM</span>
                    <h3 class="promo-banner-baslik">Sınırlı Süreli Fırsatlar</h3>
                    <p class="promo-banner-aciklama">99 TL'den başlayan fiyatlarla, kaçırmayın...</p>
                    <a href="index.php?tumu=1#urunler" class="promo-banner-btn">Alışverişe Git</a>
                </div>
                <img src="/UrunDetay/magaza/assets/img/MutfakAksesuarlari.jpg" alt="Mutfak Eşyaları" class="promo-banner-gorsel">
            </div>
        </div>

        <div class="indirim-kart-noktalar" id="indirimKartNoktalar"></div>
    </div>
</section>

<!-- Neden Bizi Seçmelisiniz -->
<section class="why-choose-us">
    <div class="container">
        <p class="why-choose-us-eyebrow">NEDEN BİZİ SEÇMELİSİNİZ</p>
        <h2 class="why-choose-us-baslik">Alışverişin En Kolay Hali</h2>
        <p class="why-choose-us-aciklama">
            Kaliteli ürünler, hızlı teslimat ve güvenilir hizmeti bir arada sunuyoruz.
        </p>

        <div class="why-choose-us-grid">
            <div class="why-choose-us-oge">
                <div class="feature-icon"><i class="fa-solid fa-box"></i></div>
                <h3>Orijinal Ürün Garantisi</h3>
                <p>Tüm ürünlerimiz %100 orijinal ve kalite garantilidir.</p>
            </div>
            <div class="why-choose-us-oge">
                <div class="feature-icon"><i class="fa-solid fa-truck-fast"></i></div>
                <h3>Hızlı ve Ücretsiz Kargo</h3>
                <p>Siparişleriniz özenle paketlenir, hızlıca kapınıza gelir.</p>
            </div>
            <div class="why-choose-us-oge">
                <div class="feature-icon"><i class="fa-solid fa-rotate-left"></i></div>
                <h3>14 Gün Kolay İade</h3>
                <p>Beğenmediğiniz ürünü sorgusuz sualsiz iade edebilirsiniz.</p>
            </div>
            <div class="why-choose-us-oge">
                <div class="feature-icon"><i class="fa-solid fa-headset"></i></div>
                <h3>7/24 Müşteri Desteği</h3>
                <p>Her sorunuzda size yardımcı olmak için buradayız.</p>
            </div>
        </div>

        <div class="ozellik-kart-noktalar" id="ozellikKartNoktalar"></div>
    </div>
</section>

<!-- İletişim Çağrı Bölümü (Video Arka Planlı) -->
<section class="video-cta-section">
    <video autoplay muted loop playsinline class="hero-bg-video">
        <source src="/UrunDetay/magaza/assets/video/hero.mp4" type="video/mp4">
    </video>
    <div class="video-cta-overlay"></div>
    <div class="video-cta-icerik">
        <div class="video-cta-ikon">
            <i class="fa-solid fa-headset"></i>
        </div>
        <p class="video-cta-etiket">BİZE ULAŞIN</p>
        <h2 class="video-cta-baslik">Sorularınız mı Var?<br>Size Yardımcı Olalım.</h2>
        <button type="button" class="video-cta-btn">İletişime Geç</button>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
