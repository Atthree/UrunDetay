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
            paketOzetiCiz();
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

    function sepetPaketSil(paketId) {
        sepetIstek('islem=paket_sil&paket_id=' + encodeURIComponent(paketId));
    }

    function sepetBadgeGuncelle() {
        const sepet = sepetGetir();
        const toplamAdet = sepet.reduce((t, u) => t + parseInt(u.parcaSayisi ?? u.adet, 10), 0);

        const badge = document.getElementById('sepetBadge');
        if (badge) {
            badge.textContent = toplamAdet;
            badge.classList.toggle('aktif', toplamAdet > 0);
        }

        const mobilBadge = document.getElementById('mobilSepetBadge');
        if (mobilBadge) {
            mobilBadge.textContent = toplamAdet;
            mobilBadge.classList.toggle('aktif', toplamAdet > 0);
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

        if (u.type === 'bundle') {
            html += `
                <div class="sepet-urun sepet-urun-paket">
                    <img src="${u.resim}" alt="${u.baslik}" draggable="false">
                    <div class="sepet-urun-bilgi">
                        <div class="sepet-paket-rozet"><i class="bi bi-box-seam"></i> Paket</div>
                        <div class="ad">${u.baslik}</div>
                        <div class="fiyat">$${araToplam.toFixed(2)}</div>
                    </div>
                    <button class="sepet-urun-sil" onclick="window.sepetPaketSil('${u.paket_id}')">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            `;
            return;
        }

        html += `
            <div class="sepet-urun">
                <img src="${u.resim}" alt="${u.baslik}" draggable="false">
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
    window.sepetPaketSil = function(paketId) { sepetPaketSil(paketId); };

    // ==========================================
    // FAVORİ YÖNETİMİ (veritabanı - AJAX)
    // ==========================================

    function favoriBadgeDegistir(delta) {
        const badge = document.getElementById('favoriBadge');
        const mobilBadge = document.getElementById('mobilFavoriBadge');
        if (!badge && !mobilBadge) return;

        const mevcutSayi = parseInt((badge || mobilBadge).textContent || '0', 10);
        const yeniSayi = Math.max(0, mevcutSayi + delta);

        if (badge) {
            badge.textContent = yeniSayi;
            badge.classList.toggle('aktif', yeniSayi > 0);
        }
        if (mobilBadge) {
            mobilBadge.textContent = yeniSayi;
            mobilBadge.classList.toggle('aktif', yeniSayi > 0);
        }
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

    window.hizliSepeteEkle = function (id, btn) {
        sepeteEkle(id, 1);
        if (btn) {
            const orijinalIcon = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            btn.classList.add('eklendi');
            setTimeout(() => {
                btn.innerHTML = orijinalIcon;
                btn.classList.remove('eklendi');
            }, 1200);
        }
    };

    // ==========================================
    // SEPET SİDEBAR (Bootstrap Offcanvas)
    // ==========================================

    const sepetSidebarEl = document.getElementById('sepetSidebar');

    function sepetAc() {
        if (sepetSidebarEl) bootstrap.Offcanvas.getOrCreateInstance(sepetSidebarEl).show();
    }

    document.getElementById('mobilSepetAcBtn')?.addEventListener('click', sepetAc);

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
                                    <img src="${u.ana_resim || ''}" alt="" draggable="false">
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
    // FİLTRE SIDEBAR — AJAX (kategori/fiyat/sıralama/temizle)
    // ==========================================

    const filtreSidebarEl = document.getElementById('filtreSidebar');
    const urunGridAlaniEl = document.getElementById('urunGridAlani');
    const urunGridIcerikEl = document.getElementById('urunGridIcerik');
    const urunlerBaslikMetinEl = document.getElementById('urunlerBaslikMetin');
    const urunlerSayacMetinEl = document.getElementById('urunlerSayacMetin');
    const fiyatMinInput = document.getElementById('fiyatMinInput');
    const fiyatMaxInput = document.getElementById('fiyatMaxInput');
    const fiyatTemizleLink = document.getElementById('fiyatTemizleLink');
    const siralamaSelect = document.querySelector('#siralamaForm .filtre-siralama');

    if (filtreSidebarEl && urunGridIcerikEl) {

        function filtreninMevcutDurumu() {
            const aktifLink = filtreSidebarEl.querySelector('.filtre-liste a.aktif');
            return {
                kategori: aktifLink?.dataset.kategori || null,
                tumu: !!(aktifLink && aktifLink.hasAttribute('data-tumu')),
                fiyat_min: fiyatMinInput?.value || '',
                fiyat_max: fiyatMaxInput?.value || '',
                siralama: siralamaSelect?.value || 'yeni',
            };
        }

        function sorguOlustur(durum) {
            const q = new URLSearchParams();
            if (durum.kategori) q.set('kategori', durum.kategori);
            else if (durum.tumu) q.set('tumu', '1');
            if (durum.fiyat_min) q.set('fiyat_min', durum.fiyat_min);
            if (durum.fiyat_max) q.set('fiyat_max', durum.fiyat_max);
            if (durum.siralama && durum.siralama !== 'yeni') q.set('siralama', durum.siralama);
            return q;
        }

        function filtreUygula(durum, urlGuncelle = true) {
            const q = sorguOlustur(durum);

            urunGridAlaniEl?.classList.add('yukleniyor');

            fetch('/UrunDetay/magaza/urunler-filtrele.php?' + q.toString())
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.basarili) return;

                    urunGridIcerikEl.innerHTML = data.html;

                    if (urunlerBaslikMetinEl) urunlerBaslikMetinEl.textContent = data.baslik;
                    if (urunlerSayacMetinEl) urunlerSayacMetinEl.textContent = '(' + data.toplamUrun + ' ürün)';

                    filtreSidebarEl.querySelectorAll('.filtre-liste a').forEach(a => {
                        const buLinkTumu = a.hasAttribute('data-tumu');
                        const aktifMi = data.seciliKategori
                            ? a.dataset.kategori === data.seciliKategori
                            : (data.tumuAktif ? buLinkTumu : false);
                        a.classList.toggle('aktif', aktifMi);
                    });

                    if (fiyatMinInput) fiyatMinInput.value = data.fiyatMin ?? '';
                    if (fiyatMaxInput) fiyatMaxInput.value = data.fiyatMax ?? '';
                    if (fiyatTemizleLink) {
                        fiyatTemizleLink.style.display = (data.fiyatMin || data.fiyatMax) ? '' : 'none';
                    }
                    if (siralamaSelect) siralamaSelect.value = data.siralama;

                    if (urlGuncelle) {
                        const urlQuery = sorguOlustur({
                            kategori: data.seciliKategori,
                            tumu: data.tumuAktif,
                            fiyat_min: data.fiyatMin,
                            fiyat_max: data.fiyatMax,
                            siralama: data.siralama,
                        });
                        const yeniUrl = 'index.php' + (urlQuery.toString() ? '?' + urlQuery.toString() : '') + '#urunler';
                        history.pushState(null, '', yeniUrl);
                    }

                    return gorselleriOnYukle(urunGridIcerikEl);
                })
                .catch(() => {
                    bildirimGoster('Ürünler yüklenemedi, tekrar deneyin.');
                })
                .finally(() => {
                    urunGridAlaniEl?.classList.remove('yukleniyor');
                });
        }

        filtreSidebarEl.querySelectorAll('.filtre-liste a').forEach(a => {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                const durum = filtreninMevcutDurumu();
                filtreUygula({
                    kategori: this.dataset.kategori || null,
                    tumu: this.hasAttribute('data-tumu'),
                    fiyat_min: durum.fiyat_min,
                    fiyat_max: durum.fiyat_max,
                    siralama: durum.siralama,
                });
            });
        });

        document.getElementById('fiyatForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            filtreUygula(filtreninMevcutDurumu());
        });

        fiyatTemizleLink?.addEventListener('click', function (e) {
            e.preventDefault();
            filtreUygula({ ...filtreninMevcutDurumu(), fiyat_min: '', fiyat_max: '' });
        });

        siralamaSelect?.addEventListener('change', function () {
            filtreUygula({ ...filtreninMevcutDurumu(), siralama: this.value });
        });

        document.getElementById('filtreTemizleBtn')?.addEventListener('click', function (e) {
            e.preventDefault();
            filtreUygula({ kategori: null, tumu: true, fiyat_min: '', fiyat_max: '', siralama: 'yeni' });
        });

        window.addEventListener('popstate', function () {
            if (!location.pathname.endsWith('/index.php')) return;
            const q = new URLSearchParams(location.search);
            filtreUygula({
                kategori: q.get('kategori'),
                tumu: q.has('tumu'),
                fiyat_min: q.get('fiyat_min') || '',
                fiyat_max: q.get('fiyat_max') || '',
                siralama: q.get('siralama') || 'yeni',
            }, false);
        });
    }

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

    // Bir konteynerdeki ürün görsellerini (arka plan resmi olarak ayarlanmış
    // .urun-resim elemanları) önceden yükler; hepsi yüklenince (veya hata
    // verince) çözülen bir Promise döner. Yükleniyor/şeffaflık durumunun
    // sadece veri gelene kadar değil, görseller gerçekten hazır olana kadar
    // sürmesi için kullanılır.
    function gorselleriOnYukle(konteyner) {
        if (!konteyner) return Promise.resolve();

        const yuklemeler = Array.from(konteyner.querySelectorAll('.urun-resim')).map(el => {
            const eslesme = el.style.backgroundImage.match(/url\(["']?(.*?)["']?\)/);
            if (!eslesme) return Promise.resolve();

            return new Promise(resolve => {
                const img = new Image();
                img.onload = resolve;
                img.onerror = resolve;
                img.src = eslesme[1];
            });
        });

        return Promise.all(yuklemeler);
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
            <div class="col-6 col-md-3 kategori-urun-slayt">
                <a href="urun.php?id=${urun.id}" class="urun-kart">
                    <button class="favori-btn" data-id="${urun.id}"
                            onclick="event.preventDefault();event.stopPropagation();toggleFavori(${urun.id}, this);"
                            title="Favorilere Ekle">
                        <i class="bi bi-heart"></i>
                    </button>
                    <button class="hizli-sepet-btn" data-id="${urun.id}"
                            onclick="event.preventDefault();event.stopPropagation();window.hizliSepeteEkle(${urun.id}, this);"
                            title="Sepete Ekle">
                        <i class="bi bi-bag-plus"></i>
                    </button>
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

        // Kategori değişince carousel'i başa sar (bkz. istek: sekme değişince
        // sayfa/nokta durumu sıfırlanmalı, önceki kategorinin scroll konumu kalmamalı)
        grid.scrollLeft = 0;

        const gorBtn = document.getElementById('kategoriTumunuGorBtn');
        if (gorBtn) {
            gorBtn.href = '/UrunDetay/magaza/index.php?kategori=' + encodeURIComponent(kategori) + '#urunler';
            gorBtn.textContent = 'Tüm ' + kategori.charAt(0).toUpperCase() + kategori.slice(1) + ' Ürünlerini Gör';
        }

        kategoriUrunNoktalariGuncelle();
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
                    return gorselleriOnYukle(document.getElementById('kategoriUrunGrid'));
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
    // YATAY CAROUSEL'LER İÇİN ELLE DOKUNMA (TOUCH) SÜRÜKLEME
    // Bu grid'lerde overflow-x:auto + scroll-snap-type native touch-scroll
    // sağlıyor GİBİ görünse de, bazı tarayıcı/emülatör kombinasyonlarında
    // (özellikle Chrome DevTools'un fare tabanlı dokunmatik simülasyonunda)
    // native yatay pan hiç tetiklenmeyebiliyor. Bu yüzden yatay yönü JS ile
    // elle üstleniyoruz; dikey yön hiç ellenmiyor (preventDefault çağrılmıyor)
    // ki sayfa scroll'u bu değişiklikten etkilenmesin. touch-action:pan-y
    // (CSS) ile birlikte çalışır: dikeyi tarayıcı native yönetir, yatayı biz.
    // ==========================================

    function dokunmaylaYatayKaydir(grid) {
        if (!grid) return;

        let basX = 0;
        let basY = 0;
        let baslangicScrollLeft = 0;
        let yatayKaydirma = false;
        let yonKararVerildi = false;

        grid.addEventListener('touchstart', function (e) {
            if (e.touches.length !== 1) return;
            basX = e.touches[0].clientX;
            basY = e.touches[0].clientY;
            baslangicScrollLeft = grid.scrollLeft;
            yatayKaydirma = false;
            yonKararVerildi = false;
        }, { passive: true });

        grid.addEventListener('touchmove', function (e) {
            if (e.touches.length !== 1) return;
            const dx = e.touches[0].clientX - basX;
            const dy = e.touches[0].clientY - basY;

            if (!yonKararVerildi) {
                // Kararsız bölge: kullanıcı henüz net bir yöne hareket
                // etmedi, hiçbir şey yapma (ne kaydır ne preventDefault).
                if (Math.abs(dx) < 6 && Math.abs(dy) < 6) return;
                yatayKaydirma = Math.abs(dx) > Math.abs(dy);
                yonKararVerildi = true;
            }

            if (yatayKaydirma) {
                // Yatay hareket baskın: sayfanın dikey scroll'a kaymasını
                // engelle, carousel'i parmakla birlikte kaydır.
                e.preventDefault();
                grid.scrollLeft = baslangicScrollLeft - dx;
            }
            // Dikey hareket baskınsa dokunma — preventDefault çağrılmadığı
            // için tarayıcı sayfayı normal şekilde dikey kaydırır.
        }, { passive: false });
    }

    // ==========================================
    // KATEGORİ VİTRİNLERİ — OK BUTONLARI + NOKTA (DOT) SAYFALAMA
    // ==========================================

    const kategoriVitrinGrid = document.getElementById('kategoriVitrinGrid');
    const kategoriVitrinNoktalar = document.getElementById('kategoriVitrinNoktalar');
    const kategoriVitrinOkSol = document.getElementById('kategoriVitrinOkSol');
    const kategoriVitrinOkSag = document.getElementById('kategoriVitrinOkSag');

    if (kategoriVitrinGrid) {
        dokunmaylaYatayKaydir(kategoriVitrinGrid);
        const kartlar = kategoriVitrinGrid.children;

        function kategoriVitrinBosluk() {
            const stil = getComputedStyle(kategoriVitrinGrid);
            return parseFloat(stil.columnGap || stil.gap) || 0;
        }

        function kategoriVitrinKaydir(yon) {
            if (kartlar.length === 0) return;
            const kartGenisligi = kartlar[0].offsetWidth + kategoriVitrinBosluk();
            kategoriVitrinGrid.scrollBy({ left: yon * kartGenisligi, behavior: 'smooth' });
        }

        function kategoriVitrinOkDurumGuncelle() {
            const maxScroll = kategoriVitrinGrid.scrollWidth - kategoriVitrinGrid.clientWidth;
            if (kategoriVitrinOkSol) kategoriVitrinOkSol.disabled = kategoriVitrinGrid.scrollLeft <= 4;
            if (kategoriVitrinOkSag) kategoriVitrinOkSag.disabled = kategoriVitrinGrid.scrollLeft >= maxScroll - 4;
        }

        if (kategoriVitrinOkSol) kategoriVitrinOkSol.addEventListener('click', () => kategoriVitrinKaydir(-1));
        if (kategoriVitrinOkSag) kategoriVitrinOkSag.addEventListener('click', () => kategoriVitrinKaydir(1));

        kategoriVitrinOkDurumGuncelle();
        window.addEventListener('resize', kategoriVitrinOkDurumGuncelle);

        if (kategoriVitrinNoktalar) {
            for (let i = 0; i < kartlar.length; i++) {
                const nokta = document.createElement('span');
                nokta.className = 'nokta' + (i === 0 ? ' aktif' : '');
                kategoriVitrinNoktalar.appendChild(nokta);
            }

            const noktaElemanlari = kategoriVitrinNoktalar.querySelectorAll('.nokta');

            kategoriVitrinGrid.addEventListener('scroll', function () {
                const kartGenisligi = kartlar[0].offsetWidth + kategoriVitrinBosluk();
                const maxIndex = kartlar.length - 1;
                const aktifIndex = Math.min(maxIndex, Math.max(0, Math.round(kategoriVitrinGrid.scrollLeft / kartGenisligi)));

                noktaElemanlari.forEach((n, i) => {
                    n.classList.toggle('aktif', i === aktifIndex);
                });
            });
        }

        kategoriVitrinGrid.addEventListener('scroll', kategoriVitrinOkDurumGuncelle);
    }

    // ==========================================
    // ÖNE ÇIKAN KATEGORİLER — SAYFA (PAGE) BAZLI NOKTA (DOT) SAYFALAMA
    // Kart başına değil, aynı anda görünen kart GRUBU (sayfa) başına bir
    // nokta gösterilir; noktalar tıklanabilir, kategori değişince veya
    // ekran boyutu değişince (sayfa başına kart sayısı değiştiğinden)
    // yeniden hesaplanır.
    // ==========================================

    function kategoriUrunNoktalariGuncelle() {
        const grid = document.getElementById('kategoriUrunGrid');
        const noktaAlani = document.getElementById('kategoriUrunNoktalar');
        if (!grid || !noktaAlani || window.innerWidth > 1199) {
            if (noktaAlani) noktaAlani.innerHTML = '';
            grid.onscroll = null;
            return;
        }

        const kartlar = grid.children;
        noktaAlani.innerHTML = '';
        grid.onscroll = null;

        if (kartlar.length === 0) return;

        const stil = getComputedStyle(grid);
        const bosluk = parseFloat(stil.columnGap || stil.gap) || 0;
        const kartGenisligi = kartlar[0].offsetWidth + bosluk;
        const sayfaBasinaKart = Math.max(1, Math.round(grid.clientWidth / kartGenisligi));
        const toplamSayfa = Math.max(1, Math.ceil(kartlar.length / sayfaBasinaKart));
        const sayfaGenisligi = sayfaBasinaKart * kartGenisligi;

        for (let i = 0; i < toplamSayfa; i++) {
            const nokta = document.createElement('span');
            nokta.className = 'nokta' + (i === 0 ? ' aktif' : '');
            nokta.addEventListener('click', function () {
                grid.scrollTo({ left: i * sayfaGenisligi, behavior: 'smooth' });
            });
            noktaAlani.appendChild(nokta);
        }

        const noktaElemanlari = noktaAlani.querySelectorAll('.nokta');

        grid.onscroll = function () {
            const aktifSayfa = Math.min(toplamSayfa - 1, Math.max(0, Math.round(grid.scrollLeft / sayfaGenisligi)));
            noktaElemanlari.forEach((n, i) => n.classList.toggle('aktif', i === aktifSayfa));
        };
    }

    window.addEventListener('resize', kategoriUrunNoktalariGuncelle);

    kategoriUrunNoktalariGuncelle();
    dokunmaylaYatayKaydir(document.getElementById('kategoriUrunGrid'));

    // ==========================================
    // 3'LÜ TANITIM BANNER — MOBİL NOKTA (DOT) SAYFALAMA
    // ==========================================

    function tanitimBannerNoktalariGuncelle() {
        const grid = document.querySelector('.tanitim-banner-grid');
        const noktaAlani = document.getElementById('tanitimBannerNoktalar');
        if (!grid || !noktaAlani || window.innerWidth > 1023) {
            if (noktaAlani) noktaAlani.innerHTML = '';
            if (grid) grid.onscroll = null;
            return;
        }

        const bannerlar = grid.children;
        noktaAlani.innerHTML = '';
        grid.onscroll = null;

        if (bannerlar.length === 0) return;

        const stil = getComputedStyle(grid);
        const bosluk = parseFloat(stil.columnGap || stil.gap) || 0;
        const bannerGenisligi = bannerlar[0].offsetWidth + bosluk;
        const sayfaBasinaKart = Math.max(1, Math.round(grid.clientWidth / bannerGenisligi));
        const toplamSayfa = Math.max(1, Math.ceil(bannerlar.length / sayfaBasinaKart));
        const sayfaGenisligi = sayfaBasinaKart * bannerGenisligi;

        for (let i = 0; i < toplamSayfa; i++) {
            const nokta = document.createElement('span');
            nokta.className = 'nokta' + (i === 0 ? ' aktif' : '');
            nokta.addEventListener('click', function () {
                grid.scrollTo({ left: i * sayfaGenisligi, behavior: 'smooth' });
            });
            noktaAlani.appendChild(nokta);
        }

        const noktaElemanlari = noktaAlani.querySelectorAll('.nokta');

        grid.onscroll = function () {
            const aktifSayfa = Math.min(toplamSayfa - 1, Math.max(0, Math.round(grid.scrollLeft / sayfaGenisligi)));
            noktaElemanlari.forEach((n, i) => n.classList.toggle('aktif', i === aktifSayfa));
        };
    }

    tanitimBannerNoktalariGuncelle();
    window.addEventListener('resize', tanitimBannerNoktalariGuncelle);

    // ==========================================
    // MÜŞTERİ YORUMLARI — NOKTA (DOT) SAYFALAMA
    // ==========================================

    function yorumNoktalariGuncelle() {
        const grid = document.getElementById('yorumCarousel');
        const noktaAlani = document.getElementById('yorumNoktalar');
        if (!grid || !noktaAlani || window.innerWidth > 1023) {
            if (noktaAlani) noktaAlani.innerHTML = '';
            if (grid) grid.onscroll = null;
            return;
        }

        const kartlar = grid.children;
        noktaAlani.innerHTML = '';
        grid.onscroll = null;

        if (kartlar.length === 0) return;

        const stil = getComputedStyle(grid);
        const bosluk = parseFloat(stil.columnGap || stil.gap) || 0;
        const kartGenisligi = kartlar[0].offsetWidth + bosluk;
        const sayfaBasinaKart = Math.max(1, Math.round(grid.clientWidth / kartGenisligi));
        const toplamSayfa = Math.max(1, Math.ceil(kartlar.length / sayfaBasinaKart));
        const sayfaGenisligi = sayfaBasinaKart * kartGenisligi;

        for (let i = 0; i < toplamSayfa; i++) {
            const nokta = document.createElement('span');
            nokta.className = 'nokta' + (i === 0 ? ' aktif' : '');
            nokta.addEventListener('click', function () {
                grid.scrollTo({ left: i * sayfaGenisligi, behavior: 'smooth' });
            });
            noktaAlani.appendChild(nokta);
        }

        const noktaElemanlari = noktaAlani.querySelectorAll('.nokta');

        grid.onscroll = function () {
            const aktifSayfa = Math.min(toplamSayfa - 1, Math.max(0, Math.round(grid.scrollLeft / sayfaGenisligi)));
            noktaElemanlari.forEach((n, i) => n.classList.toggle('aktif', i === aktifSayfa));
        };
    }

    yorumNoktalariGuncelle();
    window.addEventListener('resize', yorumNoktalariGuncelle);

    // ==========================================
    // MÜŞTERİ YORUMLARI — MASAÜSTÜ OK BUTONLARI (SAYFA BAZLI KAYDIRMA)
    // ==========================================

    (function () {
        const grid = document.getElementById('yorumCarousel');
        const okSol = document.getElementById('yorumCarouselOkSol');
        const okSag = document.getElementById('yorumCarouselOkSag');
        if (!grid || !okSol || !okSag) return;

        function durumGuncelle() {
            const maxScroll = grid.scrollWidth - grid.clientWidth;
            okSol.disabled = grid.scrollLeft <= 4;
            okSag.disabled = grid.scrollLeft >= maxScroll - 4;
        }

        okSol.addEventListener('click', () => grid.scrollBy({ left: -grid.clientWidth, behavior: 'smooth' }));
        okSag.addEventListener('click', () => grid.scrollBy({ left: grid.clientWidth, behavior: 'smooth' }));

        grid.addEventListener('scroll', durumGuncelle);
        window.addEventListener('resize', durumGuncelle);
        durumGuncelle();
    })();

    // ==========================================
    // İNDİRİM KARTLARI — MOBİL NOKTA (DOT) SAYFALAMA
    // ==========================================

    function indirimKartNoktalariGuncelle() {
        const grid = document.querySelector('.promo-banners-grid');
        const noktaAlani = document.getElementById('indirimKartNoktalar');
        if (!grid || !noktaAlani || window.innerWidth > 1023) {
            if (noktaAlani) noktaAlani.innerHTML = '';
            if (grid) grid.onscroll = null;
            return;
        }

        const kartlar = grid.children;
        noktaAlani.innerHTML = '';
        grid.onscroll = null;

        if (kartlar.length === 0) return;

        const stil = getComputedStyle(grid);
        const bosluk = parseFloat(stil.columnGap || stil.gap) || 0;
        const kartGenisligi = kartlar[0].offsetWidth + bosluk;
        const sayfaBasinaKart = Math.max(1, Math.round(grid.clientWidth / kartGenisligi));
        const toplamSayfa = Math.max(1, Math.ceil(kartlar.length / sayfaBasinaKart));
        const sayfaGenisligi = sayfaBasinaKart * kartGenisligi;

        for (let i = 0; i < toplamSayfa; i++) {
            const nokta = document.createElement('span');
            nokta.className = 'nokta' + (i === 0 ? ' aktif' : '');
            nokta.addEventListener('click', function () {
                grid.scrollTo({ left: i * sayfaGenisligi, behavior: 'smooth' });
            });
            noktaAlani.appendChild(nokta);
        }

        const noktaElemanlari = noktaAlani.querySelectorAll('.nokta');

        grid.onscroll = function () {
            const aktifSayfa = Math.min(toplamSayfa - 1, Math.max(0, Math.round(grid.scrollLeft / sayfaGenisligi)));
            noktaElemanlari.forEach((n, i) => n.classList.toggle('aktif', i === aktifSayfa));
        };
    }

    indirimKartNoktalariGuncelle();
    window.addEventListener('resize', indirimKartNoktalariGuncelle);

    // ==========================================
    // ÖZELLİK KARTLARI (NEDEN BİZİ SEÇMELİSİNİZ) — MOBİL NOKTA (DOT) SAYFALAMA
    // ==========================================

    function ozellikKartNoktalariGuncelle() {
        const grid = document.querySelector('.why-choose-us-grid');
        const noktaAlani = document.getElementById('ozellikKartNoktalar');
        if (!grid || !noktaAlani || window.innerWidth > 1023) {
            if (noktaAlani) noktaAlani.innerHTML = '';
            if (grid) grid.onscroll = null;
            return;
        }

        const kartlar = grid.children;
        noktaAlani.innerHTML = '';
        grid.onscroll = null;

        if (kartlar.length === 0) return;

        const stil = getComputedStyle(grid);
        const bosluk = parseFloat(stil.columnGap || stil.gap) || 0;
        const kartGenisligi = kartlar[0].offsetWidth + bosluk;
        const sayfaBasinaKart = Math.max(1, Math.round(grid.clientWidth / kartGenisligi));
        const toplamSayfa = Math.max(1, Math.ceil(kartlar.length / sayfaBasinaKart));
        const sayfaGenisligi = sayfaBasinaKart * kartGenisligi;

        for (let i = 0; i < toplamSayfa; i++) {
            const nokta = document.createElement('span');
            nokta.className = 'nokta' + (i === 0 ? ' aktif' : '');
            nokta.addEventListener('click', function () {
                grid.scrollTo({ left: i * sayfaGenisligi, behavior: 'smooth' });
            });
            noktaAlani.appendChild(nokta);
        }

        const noktaElemanlari = noktaAlani.querySelectorAll('.nokta');

        grid.onscroll = function () {
            const aktifSayfa = Math.min(toplamSayfa - 1, Math.max(0, Math.round(grid.scrollLeft / sayfaGenisligi)));
            noktaElemanlari.forEach((n, i) => n.classList.toggle('aktif', i === aktifSayfa));
        };
    }

    ozellikKartNoktalariGuncelle();
    window.addEventListener('resize', ozellikKartNoktalariGuncelle);

    // ==========================================
    // FOOTER ACCORDION (sadece mobilde anlamlı, masaüstünde CSS zaten
    // hepsini açık gösteriyor, bu JS orada zararsız çalışır)
    // ==========================================

    document.querySelectorAll('.footer-accordion-baslik').forEach(baslik => {
        baslik.addEventListener('click', function () {
            const item = this.closest('.footer-accordion-item');
            if (!item) return;
            item.classList.toggle('acik');
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

    // Sepette zaten "bundle" tipi bir satır varsa yeni paket oluşturulamaz —
    // güvenlik açısından asıl kısıt sepet-islem.php'de (sunucu tarafında);
    // bu sadece arayüzü buna göre pasifleştirmek için kullanılır.
    function aktifPaketVarMi() {
        return sepetGetir().some(u => u.type === 'bundle');
    }

    function paketOzetiCiz() {
        const liste = document.getElementById('paketOzetListe');
        const araToplamEl = document.getElementById('paketAraToplam');
        const indirimSatiri = document.getElementById('paketIndirimSatiri');
        const indirimTutariEl = document.getElementById('paketIndirimTutari');
        const toplamEl = document.getElementById('paketToplam');
        const sepeteEkleBtn = document.getElementById('paketSepeteEkleBtn');

        if (!liste) return;

        const paketIskeletSatiri = `
            <div class="paket-iskelet-satir">
                <div class="paket-iskelet-daire"></div>
                <div class="paket-iskelet-cubuklar">
                    <div class="paket-iskelet-cubuk genis"></div>
                    <div class="paket-iskelet-cubuk dar"></div>
                </div>
            </div>
        `;

        if (paketSepeti.length === 0) {
            liste.innerHTML = paketIskeletSatiri.repeat(3);
        } else {
            liste.innerHTML = paketSepeti.map(u => `
                <div class="paket-ozet-urun">
                    <img src="${u.resim}" alt="${u.baslik}" draggable="false">
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

        const toplamSatiri = toplamEl.closest('.paket-ozet-satir');

        if (indirimUygulaniyor) {
            indirimSatiri.style.display = 'flex';
            indirimTutariEl.textContent = '-$' + indirimTutari.toFixed(2);
            if (toplamSatiri) toplamSatiri.style.display = 'flex';
        } else {
            indirimSatiri.style.display = 'none';
            if (toplamSatiri) toplamSatiri.style.display = 'none';
        }

        const aktifPaketVar = aktifPaketVarMi();

        sepeteEkleBtn.disabled = paketSepeti.length === 0 || aktifPaketVar;

        // Kaldır butonlarını bağla
        liste.querySelectorAll('.cikar').forEach(btn => {
            btn.addEventListener('click', function () {
                paketUrunToggle(parseInt(this.dataset.id));
            });
        });

        // Limit dolunca VEYA sepette zaten aktif bir paket varken,
        // seçili olmayan kartların butonlarını pasifleştir
        const limitDoldu = paketSepeti.length >= PAKET_MAX_URUN;
        document.querySelectorAll('.paket-urun-kart').forEach(kart => {
            const btn = kart.querySelector('.paket-ekle-btn');
            const seciliMi = btn.classList.contains('secili');
            const pasifOlmali = aktifPaketVar || (limitDoldu && !seciliMi);
            btn.disabled = pasifOlmali;
            btn.style.opacity = pasifOlmali ? '0.4' : '1';
            btn.style.cursor = pasifOlmali ? 'not-allowed' : 'pointer';
        });
    }

    // ==========================================
    // PAKET İÇERİĞİ — MOBİL AÇMA/KAPAMA (ACCORDION)
    // ==========================================

    const paketOzetToggle = document.getElementById('paketOzetToggle');
    const paketOzetPaneli = document.getElementById('paketOzet');

    if (paketOzetToggle && paketOzetPaneli) {
        paketOzetToggle.addEventListener('click', function () {
            const acikMi = paketOzetPaneli.classList.toggle('acik');
            paketOzetToggle.classList.toggle('acik', acikMi);
            paketOzetToggle.innerHTML = acikMi
                ? '<i class="bi bi-dash-lg"></i>'
                : '<i class="bi bi-plus-lg"></i>';
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
            if (aktifPaketVarMi()) {
                bildirimGoster('Zaten aktif bir paketiniz var. Yeni paket oluşturmak için önce mevcut paketi sepetten kaldırın.');
                return;
            }

            if (paketSepeti.length !== PAKET_MIN_URUN) return;

            const govde = 'islem=paket_ekle&' + paketSepeti.map(u => 'urun_idler[]=' + encodeURIComponent(u.id)).join('&');

            sepetIstek(govde).then(function (data) {
                if (!data) return;
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
