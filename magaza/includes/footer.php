<?php $sepetAdetToplam = array_sum(array_column($sepetOgeleri ?? [], 'adet')); ?>

<nav class="mobil-alt-menu">
    <a href="/UrunDetay/magaza/index.php" class="mobil-alt-menu-item">
        <i class="bi bi-house"></i>
        <span>Ana Sayfa</span>
    </a>
    <a href="<?php echo girisYapmisMi() ? '/UrunDetay/magaza/profil.php' : '/UrunDetay/magaza/giris.php'; ?>" class="mobil-alt-menu-item">
        <i class="bi bi-person"></i>
        <span>Hesabım</span>
    </a>
    <a href="/UrunDetay/magaza/index.php#urunler" class="mobil-alt-menu-item">
        <i class="bi bi-grid"></i>
        <span>Ürünler</span>
    </a>
    <a href="<?php echo girisYapmisMi() ? '/UrunDetay/magaza/favorilerim.php' : '/UrunDetay/magaza/giris.php'; ?>" class="mobil-alt-menu-item">
        <i class="bi bi-heart"></i>
        <span>Favoriler</span>
        <span class="mobil-alt-menu-badge<?php echo ($favoriSayisi ?? 0) > 0 ? ' aktif' : ''; ?>" id="mobilFavoriBadge"><?php echo $favoriSayisi ?? 0; ?></span>
    </a>
    <button type="button" class="mobil-alt-menu-item mobil-alt-menu-buton" id="mobilSepetAcBtn">
        <i class="bi bi-bag"></i>
        <span>Sepetim</span>
        <span class="mobil-alt-menu-badge<?php echo $sepetAdetToplam > 0 ? ' aktif' : ''; ?>" id="mobilSepetBadge"><?php echo $sepetAdetToplam; ?></span>
    </button>
</nav>

<footer class="site-footer">
    <div class="container">
        <div class="footer-ust">
            <div class="footer-marka">
                <div class="footer-logo"><i class="bi bi-bag-heart"></i> Mağazam</div>
                <p>Kaliteli ürünleri en uygun fiyatlarla sunan güvenilir alışveriş platformu.</p>
            </div>

            <div class="footer-iletisim-bilgi">
                <p><i class="bi bi-envelope"></i> info@magazam.com</p>
                <p><i class="bi bi-telephone"></i> +90 212 000 00 00</p>
                <p><i class="bi bi-geo-alt"></i> İstanbul, Türkiye</p>
            </div>

            <div class="footer-sosyal">
                <a href="#" class="footer-sosyal-ikon" title="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" class="footer-sosyal-ikon" title="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" class="footer-sosyal-ikon" title="TikTok"><i class="bi bi-tiktok"></i></a>
                <a href="#" class="footer-sosyal-ikon" title="YouTube"><i class="bi bi-youtube"></i></a>
                <a href="#" class="footer-sosyal-ikon" title="X"><i class="bi bi-twitter-x"></i></a>
            </div>
        </div>

        <div class="footer-accordion">
            <div class="footer-accordion-item">
                <button type="button" class="footer-accordion-baslik">
                    Şirketimiz
                    <i class="bi bi-plus-lg"></i>
                </button>
                <div class="footer-accordion-icerik">
                    <a href="#">Hakkımızda</a>
                    <a href="#">İletişim</a>
                    <a href="#">Gizlilik Politikası</a>
                    <a href="#">Kullanım Koşulları</a>
                </div>
            </div>

            <div class="footer-accordion-item">
                <button type="button" class="footer-accordion-baslik">
                    Mağaza Kategorileri
                    <i class="bi bi-plus-lg"></i>
                </button>
                <div class="footer-accordion-icerik">
                    <a href="/UrunDetay/magaza/index.php">Ana Sayfa</a>
                    <a href="/UrunDetay/magaza/index.php#urunler">Tüm Ürünler</a>
                    <a href="#">Sipariş Takibi</a>
                    <a href="#">İade &amp; Değişim</a>
                </div>
            </div>

            <div class="footer-accordion-item">
                <button type="button" class="footer-accordion-baslik">
                    Bültenimize Katılın
                    <i class="bi bi-plus-lg"></i>
                </button>
                <div class="footer-accordion-icerik">
                    <p class="footer-bulten-aciklama">Kampanyalardan ve yeni ürünlerden ilk siz haberdar olun.</p>
                    <form class="footer-bulten-form" onsubmit="return false;">
                        <input type="email" placeholder="E-posta adresiniz" class="footer-bulten-input">
                        <button type="submit" class="footer-bulten-btn">Katıl</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="footer-alt">
            &copy; <?php echo date('Y'); ?> Mağazam. Tüm hakları saklıdır.
        </div>
    </div>
</footer>

<script>
    window.SEPET_BASLANGIC = <?php echo json_encode($sepetOgeleri ?? [], JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="/UrunDetay/magaza/assets/js/magaza.js"></script>
</body>
</html>