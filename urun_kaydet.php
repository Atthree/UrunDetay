<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/guvenlik.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product.php');
    exit;
}

try {
    // ---------- 0) NEGATİF DEĞER KONTROLÜ ----------
    $negatifOlamazAlanlar = [
        'miktar' => 'Miktar',
        'fiyat_tl' => 'Satış Fiyatı (TL)',
        'fiyat_usd' => 'Satış Fiyatı ($)',
        'fiyat_eur' => 'Satış Fiyatı (€)',
        'fiyat2_tl' => '2. Satış Fiyatı',
        'siralama' => 'Sıralama',
        'anasayfada_goster' => 'Anasayfada Göster',
        'garanti_suresi' => 'Garanti Süresi',
    ];

    $hatalar = [];
    foreach ($negatifOlamazAlanlar as $alan => $etiket) {
        if (isset($_POST[$alan]) && $_POST[$alan] !== '' && (float)$_POST[$alan] < 0) {
            $hatalar[] = $etiket;
        }
    }

    // İndirim satırlarındaki öncelik ve tutar alanları da negatif olamaz
    foreach (['indirim_oncelik', 'indirim_tl', 'indirim_usd', 'indirim_eur'] as $dizi) {
        if (!empty($_POST[$dizi])) {
            foreach ($_POST[$dizi] as $deger) {
                if ($deger !== '' && (float)$deger < 0) {
                    $hatalar[] = 'İndirim satırındaki bir değer';
                    break;
                }
            }
        }
    }

    if (!empty($_POST['indirim_baslangic'])) {
        foreach ($_POST['indirim_baslangic'] as $i => $baslangic) {
            $bitis = $_POST['indirim_bitis'][$i] ?? '';
            if ($baslangic !== '' && $bitis !== '' && strtotime($bitis) < strtotime($baslangic)) {
                $hatalar[] = 'İndirim satırındaki Bitiş Tarihi, Başlangıç Tarihinden önce olamaz';
                break;
            }
        }
    }

    if (!empty($hatalar)) {
        die('Hata: Şu alanlara negatif (eksi) değer girilemez: ' . implode(', ', array_unique($hatalar)) .
            '. Lütfen <a href="javascript:history.back()">geri dönüp</a> düzeltin.');
    }

    $pdo->beginTransaction();

    // ---------- 1) ANA RESMİ YÜKLE (varsa) ----------
    $anaResimYolu = null;
    if (!empty($_FILES['ana_resim']['name']) && $_FILES['ana_resim']['error'] === UPLOAD_ERR_OK) {
        $klasor = __DIR__ . '/uploads/products/';
        $dosyaAdi = uniqid('urun_') . '_' . basename($_FILES['ana_resim']['name']);
        move_uploaded_file($_FILES['ana_resim']['tmp_name'], $klasor . $dosyaAdi);
        $anaResimYolu = 'uploads/products/' . $dosyaAdi;
    }

    // Düzenleme modunda mıyız (urun_id gönderilmiş mi) yoksa yeni kayıt mı?
    $duzenlemeModu = !empty($_POST['urun_id']);

    // Ana resim: yeni dosya yüklenmediyse ve düzenleme modundaysak eski resmi koru
    if ($anaResimYolu === null && $duzenlemeModu) {
        $anaResimYolu = $_POST['ana_resim_mevcut'] ?: null;
    }

    $parametreler = [
        ':baslik_tr' => $_POST['baslik_tr'] ?? '',
        ':ek_bilgi_baslik_tr' => $_POST['ek_bilgi_baslik_tr'] ?? null,
        ':ek_bilgi_aciklama_tr' => $_POST['ek_bilgi_aciklama_tr'] ?? null,
        ':meta_title_tr' => $_POST['meta_title_tr'] ?? null,
        ':meta_keywords_tr' => $_POST['meta_keywords_tr'] ?? null,
        ':meta_description_tr' => $_POST['meta_description_tr'] ?? null,
        ':seo_adresi_tr' => $_POST['seo_adresi_tr'] ?? null,
        ':aciklama_tr' => guvenli_html($_POST['aciklama_tr'] ?? ''),
        ':video_embed_tr' => $_POST['video_embed_tr'] ?? null,
        ':urun_kodu' => $_POST['urun_kodu'] ?? '',
        ':miktar' => $_POST['miktar'] ?? 0,
        ':birim' => $_POST['birim'] ?? 'Adet',
        ':sepet_indirim' => $_POST['sepet_indirim'] ?? 0,
        ':vergi_orani' => $_POST['vergi_orani'] ?? 18,
        ':fiyat_tl' => $_POST['fiyat_tl'] ?? 0,
        ':fiyat_usd' => $_POST['fiyat_usd'] ?? 0,
        ':fiyat_eur' => $_POST['fiyat_eur'] ?? 0,
        ':fiyat2_tl' => $_POST['fiyat2_tl'] ?? 0,
        ':stoktan_dus' => $_POST['stoktan_dus'] ?? 1,
        ':durum' => $_POST['durum'] ?? 1,
        ':ozellik_bolumu' => $_POST['ozellik_bolumu'] ?? 1,
        ':gecerlilik_suresi' => $_POST['gecerlilik_suresi'] ?? null,
        ':siralama' => $_POST['siralama'] ?? 0,
        ':anasayfada_goster' => $_POST['anasayfada_goster'] ?? 0,
        ':yeni_urun' => $_POST['yeni_urun'] ?? 1,
        ':taksit' => $_POST['taksit'] ?? 1,
        ':garanti_suresi' => $_POST['garanti_suresi'] ?: null,
        ':ana_resim' => $anaResimYolu,
    ];

    if ($duzenlemeModu) {
        // ---------- 2a) VAR OLAN ÜRÜNÜ GÜNCELLE ----------
        $urunId = (int)$_POST['urun_id'];
        $parametreler[':id'] = $urunId;

        $sql = "UPDATE urunler SET
            baslik_tr = :baslik_tr, ek_bilgi_baslik_tr = :ek_bilgi_baslik_tr, ek_bilgi_aciklama_tr = :ek_bilgi_aciklama_tr,
            meta_title_tr = :meta_title_tr, meta_keywords_tr = :meta_keywords_tr, meta_description_tr = :meta_description_tr,
            seo_adresi_tr = :seo_adresi_tr, aciklama_tr = :aciklama_tr, video_embed_tr = :video_embed_tr,
            urun_kodu = :urun_kodu, miktar = :miktar, birim = :birim, sepet_indirim = :sepet_indirim, vergi_orani = :vergi_orani,
            fiyat_tl = :fiyat_tl, fiyat_usd = :fiyat_usd, fiyat_eur = :fiyat_eur, fiyat2_tl = :fiyat2_tl,
            stoktan_dus = :stoktan_dus, durum = :durum, ozellik_bolumu = :ozellik_bolumu, gecerlilik_suresi = :gecerlilik_suresi,
            siralama = :siralama, anasayfada_goster = :anasayfada_goster, yeni_urun = :yeni_urun, taksit = :taksit, garanti_suresi = :garanti_suresi,
            ana_resim = :ana_resim
            WHERE id = :id";

        $pdo->prepare($sql)->execute($parametreler);

        // İndirimleri güncellerken en basit yöntem: eskilerini silip yeniden ekle
        $pdo->prepare("DELETE FROM urun_indirimler WHERE urun_id = :id")->execute([':id' => $urunId]);

    } else {
        // ---------- 2b) YENİ ÜRÜN EKLE ----------
        $sql = "INSERT INTO urunler (
            baslik_tr, ek_bilgi_baslik_tr, ek_bilgi_aciklama_tr,
            meta_title_tr, meta_keywords_tr, meta_description_tr,
            seo_adresi_tr, aciklama_tr, video_embed_tr,
            urun_kodu, miktar, birim, sepet_indirim, vergi_orani,
            fiyat_tl, fiyat_usd, fiyat_eur, fiyat2_tl,
            stoktan_dus, durum, ozellik_bolumu, gecerlilik_suresi,
            siralama, anasayfada_goster, yeni_urun, taksit, garanti_suresi,
            ana_resim
        ) VALUES (
            :baslik_tr, :ek_bilgi_baslik_tr, :ek_bilgi_aciklama_tr,
            :meta_title_tr, :meta_keywords_tr, :meta_description_tr,
            :seo_adresi_tr, :aciklama_tr, :video_embed_tr,
            :urun_kodu, :miktar, :birim, :sepet_indirim, :vergi_orani,
            :fiyat_tl, :fiyat_usd, :fiyat_eur, :fiyat2_tl,
            :stoktan_dus, :durum, :ozellik_bolumu, :gecerlilik_suresi,
            :siralama, :anasayfada_goster, :yeni_urun, :taksit, :garanti_suresi,
            :ana_resim
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametreler);
        $urunId = $pdo->lastInsertId();
    }

    // ---------- 3) EK RESİMLERİ KAYDET ----------
    if (!empty($_FILES['ek_resimler']['name'][0])) {
        $klasor = __DIR__ . '/uploads/products/';
        $stmtResim = $pdo->prepare(
            "INSERT INTO urun_resimler (urun_id, resim_yolu, sira) VALUES (:urun_id, :resim_yolu, :sira)"
        );

        foreach ($_FILES['ek_resimler']['name'] as $i => $ad) {
            if ($_FILES['ek_resimler']['error'][$i] === UPLOAD_ERR_OK) {
                $dosyaAdi = uniqid('urun_ek_') . '_' . basename($ad);
                move_uploaded_file($_FILES['ek_resimler']['tmp_name'][$i], $klasor . $dosyaAdi);

                $stmtResim->execute([
                    ':urun_id' => $urunId,
                    ':resim_yolu' => 'uploads/products/' . $dosyaAdi,
                    ':sira' => $i,
                ]);
            }
        }
    }

    // ---------- 4) İNDİRİMLERİ KAYDET ----------
    if (!empty($_POST['indirim_musteri_grubu'])) {
        $stmtIndirim = $pdo->prepare(
            "INSERT INTO urun_indirimler (
                urun_id, musteri_grubu, oncelik,
                indirim_tl, indirim_tip_tl,
                indirim_usd, indirim_tip_usd,
                indirim_eur, indirim_tip_eur,
                baslangic_tarihi, bitis_tarihi
            ) VALUES (
                :urun_id, :musteri_grubu, :oncelik,
                :indirim_tl, :indirim_tip_tl,
                :indirim_usd, :indirim_tip_usd,
                :indirim_eur, :indirim_tip_eur,
                :baslangic_tarihi, :bitis_tarihi
            )"
        );

        foreach ($_POST['indirim_musteri_grubu'] as $i => $grup) {
            if ($grup === '') continue; // boş satırları atla

            $stmtIndirim->execute([
                ':urun_id' => $urunId,
                ':musteri_grubu' => $grup,
                ':oncelik' => $_POST['indirim_oncelik'][$i] ?? 0,
                ':indirim_tl' => $_POST['indirim_tl'][$i] ?? 0,
                ':indirim_tip_tl' => $_POST['indirim_tip_tl'][$i] ?? 'Fiyat',
                ':indirim_usd' => $_POST['indirim_usd'][$i] ?? 0,
                ':indirim_tip_usd' => $_POST['indirim_tip_usd'][$i] ?? 'Fiyat',
                ':indirim_eur' => $_POST['indirim_eur'][$i] ?? 0,
                ':indirim_tip_eur' => $_POST['indirim_tip_eur'][$i] ?? 'Fiyat',
                ':baslangic_tarihi' => $_POST['indirim_baslangic'][$i] ?: null,
                ':bitis_tarihi' => $_POST['indirim_bitis'][$i] ?: null,
            ]);
        }
    }

    $pdo->commit();

    if ($duzenlemeModu) {
        header('Location: product.php?id=' . $urunId . '&basarili=1');
    } else {
        header('Location: product.php?basarili=1');
    }
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die('Kayıt sırasında hata oluştu: ' . $e->getMessage());
}
