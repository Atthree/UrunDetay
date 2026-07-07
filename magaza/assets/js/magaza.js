/**
 * Mağazam — Ana JavaScript
 * Sepet, Favoriler, Arama, Mega Menü, Filtre Sidebar
 */

document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // SEPET YÖNETİMİ (localStorage)
    // ==========================================

    const SEPET_KEY = 'magazam_sepet';
    const FAVORI_KEY = 'magazam_favoriler';

    function sepetGetir() {
        try {
            return JSON.parse(localStorage.getItem(SEPET_KEY)) || [];
        } catch { return []; }
    }

    function sepetKaydet(sepet) {
        localStorage.setItem(SEPET_KEY, JSON.stringify(sepet));
        sepetBadgeGuncelle();
        sepetSidebarGuncelle();
    }

    function sepeteEkle(id, baslik, fiyat, resim, adet) {
        const sepet = sepetGetir();
        const mevcut = sepet.find(u => u.id == id);
        if (mevcut) {
            mevcut.adet += adet;
        } else {
            sepet.push({ id, baslik, fiyat: parseFloat(fiyat), resim, adet });
        }
        sepetKaydet(sepet);
        bildirimGoster('Ürün sepete eklendi!', 'basarili');
    }

    function sepettenSil(id) {
        let sepet = sepetGetir().filter(u => u.id != id);
        sepetKaydet(sepet);
    }

    function sepetAdetGuncelle(id, delta) {
        const sepet = sepetGetir();
        const urun = sepet.find(u => u.id == id);
        if (urun) {
            urun.adet += delta;
            if (urun.adet < 1) {
                sepettenSil(id);
                return;
            }
        }
        sepetKaydet(sepet);
    }

    function sepetBadgeGuncelle() {
        const sepet = sepetGetir();
        const toplamAdet = sepet.reduce((t, u) => t + u.adet, 0);
        const badge = document.getElementById('sepetBadge');
        if (badge) {
            badge.textContent = toplamAdet;
            badge.classList.toggle('aktif', toplamAdet > 0);
        }
    }

    function sepetSidebarGuncelle() {
        const sepet = sepetGetir();
        const icerik = document.getElementById('sepetIcerik');
        const alt = document.getElementById('sepetAlt');
        const toplamEl = document.getElementById('sepetToplam');

        if (!icerik) return;

        if (sepet.length === 0) {
            icerik.innerHTML = '<div class="sepet-bos"><i class="bi bi-bag-x"></i><p>Sepetiniz boş</p></div>';
            if (alt) alt.style.display = 'none';
            return;
        }

        let html = '';
        let toplam = 0;

        sepet.forEach(u => {
            const araToplam = u.fiyat * u.adet;
            toplam += araToplam;

            html += `
                <div class="sepet-urun">
                    <img src="${u.resim}" alt="${u.baslik}">
                    <div class="sepet-urun-bilgi">
                        <div class="ad">${u.baslik}</div>
                        <div class="fiyat">${formatFiyat(araToplam)} TL</div>
                        <div class="sepet-urun-adet">
                            <button onclick="window.sepetAdetGuncelle(${u.id}, -1)">−</button>
                            <span>${u.adet}</span>
                            <button onclick="window.sepetAdetGuncelle(${u.id}, 1)">+</button>
                        </div>
                    </div>
                    <button class="sepet-urun-sil" onclick="window.sepettenSil(${u.id})">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            `;
        });

        icerik.innerHTML = html;
        if (alt) {
            alt.style.display = 'block';
            toplamEl.textContent = formatFiyat(toplam) + ' TL';
        }
    }

    // Global erişim (onclick'ler için)
    window.sepetAdetGuncelle = function(id, delta) { sepetAdetGuncelle(id, delta); };
    window.sepettenSil = function(id) { sepettenSil(id); };

    // ==========================================
    // FAVORİ YÖNETİMİ (localStorage)
    // ==========================================

    function favorileriGetir() {
        try {
            return JSON.parse(localStorage.getItem(FAVORI_KEY)) || [];
        } catch { return []; }
    }

    function favorileriKaydet(favoriler) {
        localStorage.setItem(FAVORI_KEY, JSON.stringify(favoriler));
        favoriBadgeGuncelle();
    }

    function toggleFavoriId(id) {
        let favoriler = favorileriGetir();
        const index = favoriler.indexOf(id);
        if (index > -1) {
            favoriler.splice(index, 1);
            bildirimGoster('Favorilerden çıkarıldı');
        } else {
            favoriler.push(id);
            bildirimGoster('Favorilere eklendi!', 'basarili');
        }
        favorileriKaydet(favoriler);
        return favoriler.includes(id);
    }

    function favoriBadgeGuncelle() {
        const favoriler = favorileriGetir();
        const badge = document.getElementById('favoriBadge');
        if (badge) {
            badge.textContent = favoriler.length;
            badge.classList.toggle('aktif', favoriler.length > 0);
        }
    }

    // Favori butonlarını güncelle (sayfa yüklendiğinde)
    function favoriButonlariGuncelle() {
        const favoriler = favorileriGetir();
        document.querySelectorAll('.favori-btn[data-id]').forEach(btn => {
            const id = parseInt(btn.dataset.id);
            if (favoriler.includes(id)) {
                btn.classList.add('aktif');
                btn.querySelector('i').className = 'bi bi-heart-fill';
            }
        });

        // Ürün detay favori butonu
        const detayBtn = document.getElementById('detayFavoriBtn');
        if (detayBtn) {
            const id = parseInt(detayBtn.dataset.id);
            if (favoriler.includes(id)) {
                detayBtn.classList.add('aktif');
                detayBtn.querySelector('i').className = 'bi bi-heart-fill';
            }
        }
    }

    // Global toggleFavori
    window.toggleFavori = function (id, btn) {
        const aktif = toggleFavoriId(id);
        if (btn) {
            btn.classList.toggle('aktif', aktif);
            btn.querySelector('i').className = aktif ? 'bi bi-heart-fill' : 'bi bi-heart';
        }
    };

    // ==========================================
    // SEPET SİDEBAR AÇ/KAPA
    // ==========================================

    const sepetAcBtn = document.getElementById('sepetAcBtn');
    const sepetSidebar = document.getElementById('sepetSidebar');
    const sepetOverlay = document.getElementById('sepetOverlay');
    const sepetKapatBtn = document.getElementById('sepetKapatBtn');

    function sepetAc() {
        sepetSidebar?.classList.add('aktif');
        sepetOverlay?.classList.add('aktif');
        document.body.style.overflow = 'hidden';
    }

    function sepetKapat() {
        sepetSidebar?.classList.remove('aktif');
        sepetOverlay?.classList.remove('aktif');
        document.body.style.overflow = '';
    }

    sepetAcBtn?.addEventListener('click', sepetAc);
    sepetKapatBtn?.addEventListener('click', sepetKapat);
    sepetOverlay?.addEventListener('click', sepetKapat);

    // ==========================================
    // ARAMA OVERLAY
    // ==========================================

    const aramaAcBtn = document.getElementById('aramaAcBtn');
    const aramaOverlay = document.getElementById('aramaOverlay');
    const aramaKapatBtn = document.getElementById('aramaKapatBtn');
    const aramaInput = document.getElementById('aramaInput');
    const aramaSonuclar = document.getElementById('aramaSonuclar');

    function aramaAc() {
        aramaOverlay?.classList.add('aktif');
        document.body.style.overflow = 'hidden';
        setTimeout(() => aramaInput?.focus(), 300);
    }

    function aramaKapat() {
        aramaOverlay?.classList.remove('aktif');
        document.body.style.overflow = '';
        if (aramaInput) aramaInput.value = '';
        if (aramaSonuclar) {
            aramaSonuclar.style.display = 'none';
            aramaSonuclar.innerHTML = '';
        }
    }

    aramaAcBtn?.addEventListener('click', aramaAc);
    aramaKapatBtn?.addEventListener('click', aramaKapat);
    aramaOverlay?.addEventListener('click', function (e) {
        if (e.target === aramaOverlay) aramaKapat();
    });

    // ESC tuşu ile kapatma
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            aramaKapat();
            sepetKapat();
        }
    });

    // Arama — canlı arama
    let aramaTimer = null;
    aramaInput?.addEventListener('input', function () {
        clearTimeout(aramaTimer);
        const sorgu = this.value.trim();

        if (sorgu.length < 2) {
            if (aramaSonuclar) {
                aramaSonuclar.style.display = 'none';
                aramaSonuclar.innerHTML = '';
            }
            return;
        }

        aramaTimer = setTimeout(() => {
            fetch('/UrunDetay/magaza/arama.php?q=' + encodeURIComponent(sorgu))
                .then(r => r.json())
                .then(data => {
                    if (!aramaSonuclar) return;
                    if (data.length === 0) {
                        aramaSonuclar.innerHTML = '<div class="arama-bos"><i class="bi bi-search"></i> "' + sorgu + '" için sonuç bulunamadı</div>';
                    } else {
                        let html = '';
                        data.forEach(u => {
                            html += `
                                <a href="/UrunDetay/magaza/urun.php?id=${u.id}" class="arama-sonuc-item">
                                    <img src="${u.ana_resim || ''}" alt="">
                                    <div class="arama-sonuc-bilgi">
                                        <div class="ad">${u.baslik_tr}</div>
                                        <div class="fiyat">${u.fiyat_tl > 0 ? formatFiyat(u.fiyat_tl) + ' TL' : '$' + parseFloat(u.fiyat_usd).toFixed(2)}</div>
                                    </div>
                                </a>
                            `;
                        });
                        aramaSonuclar.innerHTML = html;
                    }
                    aramaSonuclar.style.display = 'block';
                })
                .catch(() => {
                    if (aramaSonuclar) {
                        aramaSonuclar.innerHTML = '<div class="arama-bos">Arama sırasında hata oluştu</div>';
                        aramaSonuclar.style.display = 'block';
                    }
                });
        }, 300);
    });

    // ==========================================
    // MEGA MENÜ — Mobilde tıklama desteği
    // ==========================================

    const megaTriggers = document.querySelectorAll('.mega-trigger');
    megaTriggers.forEach(trigger => {
        trigger.addEventListener('click', function (e) {
            if (window.innerWidth <= 767) {
                e.preventDefault();
                const megaMenu = this.nextElementSibling;
                if (megaMenu?.classList.contains('mega-menu')) {
                    megaMenu.classList.toggle('aktif');
                    const chevron = this.querySelector('.bi-chevron-down');
                    if (chevron) {
                        chevron.style.transform = megaMenu.classList.contains('aktif') ? 'rotate(180deg)' : '';
                    }
                }
            }
        });
    });

    // Hamburger Menü
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const anaNav = document.getElementById('anaNav');

    hamburgerBtn?.addEventListener('click', function () {
        anaNav?.classList.toggle('aktif');
        const icon = this.querySelector('i');
        if (anaNav?.classList.contains('aktif')) {
            icon.className = 'bi bi-x-lg';
        } else {
            icon.className = 'bi bi-list';
        }
    });

    // ==========================================
    // FİLTRE SIDEBAR — Mobil açma/kapama
    // ==========================================

    const filtreMobilBtn = document.getElementById('filtreMobilBtn');
    const filtreSidebar = document.getElementById('filtreSidebar');

    filtreMobilBtn?.addEventListener('click', function () {
        filtreSidebar?.classList.toggle('aktif');
    });

    // Sidebar dışına tıklanınca kapat
    document.addEventListener('click', function (e) {
        if (filtreSidebar?.classList.contains('aktif') &&
            !filtreSidebar.contains(e.target) &&
            e.target !== filtreMobilBtn &&
            !filtreMobilBtn?.contains(e.target)) {
            filtreSidebar.classList.remove('aktif');
        }
    });

    // ==========================================
    // SEPETE EKLE BUTONU (Ürün Detay Sayfası)
    // ==========================================

    const sepeteEkleBtn = document.getElementById('sepeteEkleBtn');
    if (sepeteEkleBtn) {
        sepeteEkleBtn.addEventListener('click', function () {
            const id = parseInt(this.dataset.id);
            const baslik = this.dataset.baslik;
            const fiyat = this.dataset.fiyat;
            const resim = this.dataset.resim;
            const adetInput = document.getElementById('urunAdet');
            const adet = adetInput ? parseInt(adetInput.value) || 1 : 1;

            sepeteEkle(id, baslik, fiyat, resim, adet);

            // Buton animasyonu
            const orijinalHTML = this.innerHTML;
            this.innerHTML = '<i class="bi bi-check2"></i> Eklendi!';
            this.classList.add('eklendi');
            setTimeout(() => {
                this.innerHTML = orijinalHTML;
                this.classList.remove('eklendi');
            }, 1500);
        });
    }

    // Detay favori butonu
    const detayFavoriBtn = document.getElementById('detayFavoriBtn');
    if (detayFavoriBtn) {
        detayFavoriBtn.addEventListener('click', function () {
            window.toggleFavori(parseInt(this.dataset.id), this);
        });
    }

    // ==========================================
    // BİLDİRİM TOAST
    // ==========================================

    function bildirimGoster(mesaj, tip = '') {
        const toast = document.getElementById('bildirimToast');
        const mesajEl = document.getElementById('bildirimMesaj');
        if (!toast || !mesajEl) return;

        mesajEl.textContent = mesaj;
        toast.className = 'bildirim-toast goster' + (tip ? ' ' + tip : '');

        setTimeout(() => {
            toast.classList.remove('goster');
        }, 2500);
    }

    // ==========================================
    // YARDIMCI FONKSİYONLAR
    // ==========================================

    function formatFiyat(sayi) {
        return parseFloat(sayi).toLocaleString('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // ==========================================
    // SAYFA YÜKLEME — Başlangıç
    // ==========================================

    sepetBadgeGuncelle();
    favoriBadgeGuncelle();
    favoriButonlariGuncelle();
    sepetSidebarGuncelle();

});
