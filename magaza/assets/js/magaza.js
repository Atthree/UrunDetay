/**
 * Mağazam — Ana JavaScript
 * Sepet, Favoriler, Arama, Mega Menü, Filtre Sidebar
 */

document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // SEPET YÖNETİMİ (localStorage)
    // ==========================================


    let sepetVerisi = window.SEPET_BASLANGIC || [];

    function sepetGetir() {
        return sepetVerisi;
    }

    function sepetIstek(govde) {
        return fetch('/UrunDetay/magaza/sepet-islem.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: govde
        }).then(r => {
            if (r.status === 401) {
                window.location.href = '/UrunDetay/magaza/giris.php';
                return null;
            }
            return r.json();
        }).then(data => {
            if (!data || !data.basarili) return null;
            sepetVerisi = data.sepet;
            sepetBadgeGuncelle();
            sepetSidebarGuncelle();
            return data;
        }).catch(() => {
            bildirimGoster('Bir hata oluştu, tekrar deneyin.');
            return null;
        });
    }

    function sepeteEkle(id, adet) {
        sepetIstek('islem=ekle&urun_id=' + encodeURIComponent(id) + '&adet=' + encodeURIComponent(adet))
            .then(data => {
                if (data) bildirimGoster('Ürün sepete eklendi!', 'basarili');
            });
    }

    function sepettenSil(id) {
        sepetIstek('islem=sil&urun_id=' + encodeURIComponent(id));
    }

    function sepetAdetGuncelle(id, delta) {
        sepetIstek('islem=adet_guncelle&urun_id=' + encodeURIComponent(id) + '&delta=' + encodeURIComponent(delta));
    }

    function sepetBadgeGuncelle() {
        const sepet = sepetGetir();
        const toplamAdet = sepet.reduce((t, u) => t + parseInt(u.adet, 10), 0);
        const badge = document.getElementById('sepetBadge');
        if (badge) {
            badge.textContent = toplamAdet;
            badge.classList.toggle('aktif', toplamAdet > 0);
        }
    }

    function paraBirimiSembolu(pb) {
    return pb === 'USD' ? '$' : 'TL';
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
        const araToplam = parseFloat(u.fiyat) * parseInt(u.adet, 10);
        toplam += araToplam;

        html += `
            <div class="sepet-urun">
                <img src="${u.resim}" alt="${u.baslik}">
                <div class="sepet-urun-bilgi">
                    <div class="ad">${u.baslik}</div>
                    <div class="fiyat">$${araToplam.toFixed(2)}</div>
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
        toplamEl.textContent = '$' + toplam.toFixed(2);
    }
}

    // Global erişim (onclick'ler için)
    window.sepetAdetGuncelle = function(id, delta) { sepetAdetGuncelle(id, delta); };
    window.sepettenSil = function(id) { sepettenSil(id); };

    // ==========================================
    // FAVORİ YÖNETİMİ (veritabanı - AJAX)
    // ==========================================

    function favoriBadgeDegistir(delta) {
        const badge = document.getElementById('favoriBadge');
        if (!badge) return;
        const yeniSayi = Math.max(0, parseInt(badge.textContent || '0') + delta);
        badge.textContent = yeniSayi;
        badge.classList.toggle('aktif', yeniSayi > 0);
    }

    // Global toggleFavori
    window.toggleFavori = function (id, btn) {
        fetch('/UrunDetay/magaza/favori-islem.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'urun_id=' + encodeURIComponent(id)
        })
            .then(r => {
                if (r.status === 401) {
                    window.location.href = '/UrunDetay/magaza/giris.php';
                    return null;
                }
                return r.json();
            })
            .then(data => {
                if (!data || !data.basarili) return;

                const eklendi = data.durum === 'eklendi';

                if (btn) {
                    btn.classList.toggle('aktif', eklendi);
                    const icon = btn.querySelector('i');
                    if (icon) icon.className = eklendi ? 'bi bi-heart-fill' : 'bi bi-heart';
                }

                favoriBadgeDegistir(eklendi ? 1 : -1);
                bildirimGoster(eklendi ? 'Favorilere eklendi!' : 'Favorilerden çıkarıldı', eklendi ? 'basarili' : '');
            })
            .catch(() => {
                bildirimGoster('Bir hata oluştu, tekrar deneyin.');
            });
    };

    // ==========================================
    // SEPET SİDEBAR (Bootstrap Offcanvas)
    // ==========================================

    const sepetSidebarEl = document.getElementById('sepetSidebar');

    function sepetAc() {
        if (sepetSidebarEl) bootstrap.Offcanvas.getOrCreateInstance(sepetSidebarEl).show();
    }

    // ==========================================
    // ARAMA MODAL (Bootstrap Modal)
    // ==========================================

    const aramaModalEl = document.getElementById('aramaModal');
    const aramaInput = document.getElementById('aramaInput');
    const aramaSonuclar = document.getElementById('aramaSonuclar');

    aramaModalEl?.addEventListener('shown.bs.modal', () => aramaInput?.focus());
    aramaModalEl?.addEventListener('hidden.bs.modal', () => {
        if (aramaInput) aramaInput.value = '';
        if (aramaSonuclar) {
            aramaSonuclar.style.display = 'none';
            aramaSonuclar.innerHTML = '';
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
                                        <div class="fiyat">$${parseFloat(u.fiyat_usd).toFixed(2)}</div>
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
    // MEGA MENÜ (Bootstrap Dropdown) — masaüstünde hover ile açılma
    // ==========================================

    document.querySelectorAll('.nav-item.dropdown').forEach(item => {
        const toggleEl = item.querySelector('[data-bs-toggle="dropdown"]');
        if (!toggleEl) return;
        const dropdown = bootstrap.Dropdown.getOrCreateInstance(toggleEl);

        item.addEventListener('mouseenter', () => {
            if (window.innerWidth >= 768) dropdown.show();
        });
        item.addEventListener('mouseleave', () => {
            if (window.innerWidth >= 768) dropdown.hide();
        });
    });

    // Hamburger Menü — ikon değişimi (Bootstrap Offcanvas event'leri)
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const anaNav = document.getElementById('anaNav');

    anaNav?.addEventListener('show.bs.offcanvas', () => {
        const icon = hamburgerBtn?.querySelector('i');
        if (icon) icon.className = 'bi bi-x-lg';
    });
    anaNav?.addEventListener('hidden.bs.offcanvas', () => {
        const icon = hamburgerBtn?.querySelector('i');
        if (icon) icon.className = 'bi bi-list';
    });

    // ==========================================
    // SEPETE EKLE BUTONU (Ürün Detay Sayfası)
    // ==========================================

    const sepeteEkleBtn = document.getElementById('sepeteEkleBtn');
    if (sepeteEkleBtn) {
        sepeteEkleBtn.addEventListener('click', function () {
            const id = parseInt(this.dataset.id);
            const adetInput = document.getElementById('urunAdet');
            const adet = adetInput ? parseInt(adetInput.value) || 1 : 1;

            sepeteEkle(id, adet);

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
    // ÖNE ÇIKAN KATEGORİLER — AJAX (POST) + ÖNBELLEK
    // ==========================================

    const kategoriUrunCache = {};

    if (window.KATEGORI_ILK_VERI && window.KATEGORI_ILK_VERI.kategori) {
        kategoriUrunCache[window.KATEGORI_ILK_VERI.kategori] = window.KATEGORI_ILK_VERI.urunler;
    }

    function yildizHtmlOlustur(ortalama) {
        if (ortalama === null || ortalama === undefined) return '';
        const yuvarlanmis = Math.round(ortalama);
        let html = '<span class="urun-yildizlar">';
        for (let i = 1; i <= 5; i++) {
            html += `<i class="bi ${i <= yuvarlanmis ? 'bi-star-fill' : 'bi-star'}"></i>`;
        }
        html += '</span>';
        return html;
    }

    function kategoriUrunKartiOlustur(urun) {
        const stokYokHtml = urun.miktar === 0 ? '<span class="urun-stok-yok">Stokta Yok</span>' : '';
        const yildizHtml = yildizHtmlOlustur(urun.yildiz_ortalama);
        return `
            <div class="col-6 col-md-3">
                <a href="urun.php?id=${urun.id}" class="urun-kart">
                    <div class="urun-resim-wrap">
                        <div class="urun-resim" style="background-image:url('${urun.ana_resim}')"></div>
                    </div>
                    <div class="urun-bilgi">
                        <span class="urun-baslik">${urun.baslik_tr}</span>
                        ${yildizHtml}
                        <span class="urun-fiyat">$${parseFloat(urun.fiyat_usd).toFixed(2)}</span>
                        ${stokYokHtml}
                    </div>
                </a>
            </div>
        `;
    }

    function kategoriPaneliDoldur(kategori, urunler) {
        const grid = document.getElementById('kategoriUrunGrid');
        if (!grid) return;

        grid.innerHTML = urunler.length === 0
            ? '<p class="text-muted">Bu kategoride henüz ürün yok.</p>'
            : urunler.map(kategoriUrunKartiOlustur).join('');

        const gorBtn = document.getElementById('kategoriTumunuGorBtn');
        if (gorBtn) {
            gorBtn.href = '/UrunDetay/magaza/index.php?kategori=' + encodeURIComponent(kategori) + '#urunler';
            gorBtn.textContent = 'Tüm ' + kategori.charAt(0).toUpperCase() + kategori.slice(1) + ' Ürünlerini Gör';
        }
    }

    document.querySelectorAll('.kategori-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            console.log('TIKLANDI:', this.dataset.kategori);
            const kategori = this.dataset.kategori;

            this.closest('.kategori-tab-bar').querySelectorAll('.kategori-tab').forEach(t => t.classList.remove('aktif'));
            this.classList.add('aktif');

            if (kategoriUrunCache[kategori]) {
                kategoriPaneliDoldur(kategori, kategoriUrunCache[kategori]);
                return;
            }

            const alan = document.querySelector('.kategori-panel-alani');
            if (alan) alan.classList.add('yukleniyor');

            fetch('/UrunDetay/magaza/kategori-urunleri.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'kategori=' + encodeURIComponent(kategori)
            })
                .then(r => r.json())
                .then(data => new Promise(resolve => setTimeout(() => resolve(data), 800))) //yapay gecikme ekledik
                .then(data => {
                    if (!data || !data.basarili) return;
                    kategoriUrunCache[kategori] = data.urunler;
                    kategoriPaneliDoldur(kategori, data.urunler);
                })
                .catch(() => {
                    bildirimGoster('Ürünler yüklenemedi, tekrar deneyin.');
                })
                .finally(() => {
                    if (alan) alan.classList.remove('yukleniyor');
                });
        });
    });

    // ==========================================
    // SAYFA YÜKLEME — Başlangıç
    // ==========================================

    // ==========================================
    // PAKET YAP, %30 KAZAN
    // ==========================================

    let paketSepeti = []; // { id, baslik, fiyat, resim }
    const PAKET_INDIRIM_ORANI = 0.30;
    const PAKET_MIN_URUN = 3;
    const PAKET_MAX_URUN = 3;

    function paketOzetiCiz() {
        const liste = document.getElementById('paketOzetListe');
        const araToplamEl = document.getElementById('paketAraToplam');
        const indirimSatiri = document.getElementById('paketIndirimSatiri');
        const indirimTutariEl = document.getElementById('paketIndirimTutari');
        const toplamEl = document.getElementById('paketToplam');
        const sepeteEkleBtn = document.getElementById('paketSepeteEkleBtn');

        if (!liste) return;

        if (paketSepeti.length === 0) {
            liste.innerHTML = '<p class="text-muted" id="paketBosMesaj">Henüz ürün eklemediniz.</p>';
        } else {
            liste.innerHTML = paketSepeti.map(u => `
                <div class="paket-ozet-urun">
                    <img src="${u.resim}" alt="${u.baslik}">
                    <span class="ad">${u.baslik}</span>
                    <button type="button" class="cikar" data-id="${u.id}"><i class="bi bi-x-lg"></i></button>
                </div>
            `).join('');
        }

        const araToplam = paketSepeti.reduce((t, u) => t + u.fiyat, 0);
        const indirimUygulaniyor = paketSepeti.length >= PAKET_MIN_URUN;
        const indirimTutari = indirimUygulaniyor ? araToplam * PAKET_INDIRIM_ORANI : 0;
        const toplam = araToplam - indirimTutari;

        araToplamEl.textContent = '$' + araToplam.toFixed(2);
        toplamEl.textContent = '$' + toplam.toFixed(2);

        if (indirimUygulaniyor) {
            indirimSatiri.style.display = 'flex';
            indirimTutariEl.textContent = '-$' + indirimTutari.toFixed(2);
        } else {
            indirimSatiri.style.display = 'none';
        }

        sepeteEkleBtn.disabled = paketSepeti.length === 0;

        // Kaldır butonlarını bağla
        liste.querySelectorAll('.cikar').forEach(btn => {
            btn.addEventListener('click', function () {
                paketUrunToggle(parseInt(this.dataset.id));
            });
        });

        // Limit dolunca, seçili olmayan kartların butonlarını pasifleştir
        const limitDoldu = paketSepeti.length >= PAKET_MAX_URUN;
        document.querySelectorAll('.paket-urun-kart').forEach(kart => {
            const btn = kart.querySelector('.paket-ekle-btn');
            const seciliMi = btn.classList.contains('secili');
            btn.disabled = limitDoldu && !seciliMi;
            btn.style.opacity = (limitDoldu && !seciliMi) ? '0.4' : '1';
            btn.style.cursor = (limitDoldu && !seciliMi) ? 'not-allowed' : 'pointer';
        });
    }

    function paketUrunToggle(id) {
        const kart = document.querySelector(`.paket-urun-kart[data-id="${id}"]`);
        const mevcutIndex = paketSepeti.findIndex(u => u.id === id);

        if (mevcutIndex > -1) {
            paketSepeti.splice(mevcutIndex, 1);
            if (kart) {
                kart.querySelector('.paket-ekle-btn').classList.remove('secili');
                kart.querySelector('.paket-ekle-btn').textContent = 'Pakete Ekle';
            }
        } else if (kart) {
            if (paketSepeti.length >= PAKET_MAX_URUN) {
                bildirimGoster('Pakete en fazla ' + PAKET_MAX_URUN + ' ürün ekleyebilirsiniz.');
                return;
            }

            paketSepeti.push({
                id: id,
                baslik: kart.dataset.baslik,
                fiyat: parseFloat(kart.dataset.fiyat),
                resim: kart.dataset.resim
            });
            kart.querySelector('.paket-ekle-btn').classList.add('secili');
            kart.querySelector('.paket-ekle-btn').textContent = 'Pakete Eklendi';
        }

        paketOzetiCiz();
    }

    document.querySelectorAll('.paket-ekle-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const kart = this.closest('.paket-urun-kart');
            paketUrunToggle(parseInt(kart.dataset.id));
        });
    });

    const paketSepeteEkleBtn = document.getElementById('paketSepeteEkleBtn');
    if (paketSepeteEkleBtn) {
        paketSepeteEkleBtn.addEventListener('click', function () {
            if (paketSepeti.length === 0) return;

            const istekler = paketSepeti.map(u =>
                sepetIstek('islem=ekle&urun_id=' + encodeURIComponent(u.id) + '&adet=1')
            );

            Promise.all(istekler).then(() => {
                document.querySelectorAll('.paket-ekle-btn.secili').forEach(btn => {
                    btn.classList.remove('secili');
                    btn.textContent = 'Pakete Ekle';
                });
                paketSepeti = [];
                paketOzetiCiz();
                bildirimGoster('Paket sepete eklendi!', 'basarili');
                sepetAc();
            });
        });
    }

    paketOzetiCiz();
    sepetBadgeGuncelle();
    sepetSidebarGuncelle();

});
