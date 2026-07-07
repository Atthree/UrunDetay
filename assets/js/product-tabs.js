document.addEventListener('DOMContentLoaded', function () {

    // ---------- ZENGİN METİN EDİTÖRÜ (CKEditor) ----------
    if (document.getElementById('aciklama_tr') && typeof CKEDITOR !== 'undefined') {
        CKEDITOR.replace('aciklama_tr', {
            language: 'tr',
            height: 300
        });
    }

    // ---------- ANA RESİM ÖNİZLEME ----------
    const anaResimInput = document.getElementById('anaResimInput');
    const anaResimKutu = document.getElementById('anaResimKutu');
    const anaResimTemizle = document.getElementById('anaResimTemizle');

    if (anaResimInput) {
        anaResimInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const url = URL.createObjectURL(this.files[0]);
                anaResimKutu.innerHTML = '<img src="' + url + '" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">';
            }
        });
    }

    if (anaResimTemizle) {
        anaResimTemizle.addEventListener('click', function () {
            anaResimInput.value = '';
            anaResimKutu.innerHTML = '<i class="bi bi-camera text-secondary" style="font-size:3rem; line-height:120px;"></i>';
        });
    }

    // ---------- EK RESİMLER ----------
    const resimEkleBtn = document.getElementById('resimEkleBtn');
    const ekResimInput = document.getElementById('ekResimInput');
    const ekResimlerAlani = document.getElementById('ekResimlerAlani');

    if (resimEkleBtn) {
        resimEkleBtn.addEventListener('click', function () {
            ekResimInput.click();
        });
    }

    if (ekResimInput) {
        ekResimInput.addEventListener('change', function () {
            Array.from(this.files).forEach(function (file) {
                const url = URL.createObjectURL(file);
                const col = document.createElement('div');
                col.className = 'col-auto';
                col.innerHTML = '<img src="' + url + '" class="ek-resim-onizleme">';
                ekResimlerAlani.appendChild(col);
            });
        });
    }

    // ---------- İNDİRİM SATIRI EKLE / KALDIR ----------
    const indirimEkleBtn = document.getElementById('indirimEkleBtn');
    const indirimTablosu = document.getElementById('indirimTablosu').querySelector('tbody');
    const indirimSablon = document.getElementById('indirimSatirSablonu');

    if (indirimEkleBtn) {
        indirimEkleBtn.addEventListener('click', function () {
            const yeniSatir = indirimSablon.content.cloneNode(true);
            indirimTablosu.appendChild(yeniSatir);
        });
    }

    // Kaldır butonuna tıklandığında (event delegation ile, dinamik satırlar için)
    indirimTablosu.addEventListener('click', function (e) {
        if (e.target.classList.contains('indirim-kaldir')) {
            e.target.closest('tr').remove();
        }
    });

    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', function (e) {
            const satirlar = document.querySelectorAll('#indirimTablosu tbody tr');
            for (const satir of satirlar) {
                const baslangic = satir.querySelector('input[name="indirim_baslangic[]"]');
                const bitis = satir.querySelector('input[name="indirim_bitis[]"]');
                if (baslangic && bitis && baslangic.value && bitis.value) {
                    if (new Date(bitis.value) < new Date(baslangic.value)) {
                        e.preventDefault();
                        alert('İndirim satırında Bitiş Tarihi, Başlangıç Tarihinden önce olamaz.');
                        return;
                    }
                }
            }
        });
    }

});
