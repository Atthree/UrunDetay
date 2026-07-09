<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/guvenlik.php';

// cURL ile dış API'den veri çeken küçük yardımcı fonksiyon
function api_getir($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $yanit = curl_exec($ch);
    if ($yanit === false) {
        $hata = curl_error($ch);
        curl_close($ch);
        throw new Exception('API bağlantı hatası: ' . $hata);
    }
    curl_close($ch);
    return $yanit;
}

$eklenen = 0;
$atlanan = 0;
$hataMesaji = null;

try {
    // limit=100 -> tek seferde 100 ürün çeker (DummyJSON'da toplam 194 ürün var)
    $json = api_getir('https://dummyjson.com/products?limit=100');
    $veri = json_decode($json, true);

    if (!isset($veri['products'])) {
        throw new Exception('API beklenmeyen bir yanıt döndü.');
    }

    // Aynı ürün kodu zaten var mı diye kontrol edecek sorgu
    $stmtVarMi = $pdo->prepare("SELECT id FROM urunler WHERE urun_kodu = :kodu");

    // Yeni ürün ekleyecek sorgu
    $stmtEkle = $pdo->prepare("INSERT INTO urunler (
        baslik_tr, aciklama_tr, urun_kodu, kategori, miktar, fiyat_usd,
        vergi_orani, durum, ana_resim, stoktan_dus, ozellik_bolumu,
        yeni_urun, taksit
    ) VALUES (
        :baslik_tr, :aciklama_tr, :urun_kodu, :kategori, :miktar, :fiyat_usd,
        18, 1, :ana_resim, 1, 1, 1, 1
    )");

    // Ürünün "images" dizisindeki ek resimleri urun_resimler tablosuna kaydedecek sorgu
    $stmtResimEkle = $pdo->prepare(
        "INSERT INTO urun_resimler (urun_id, resim_yolu, sira) VALUES (:urun_id, :resim_yolu, :sira)"
    );

    foreach ($veri['products'] as $u) {
        // sku yoksa id'den kendi kodumuzu üretelim
        $kodu = !empty($u['sku']) ? $u['sku'] : ('dummy_' . $u['id']);

        // Bu ürün kodu zaten veritabanında varsa atla (tekrar tekrar eklenmesin)
        $stmtVarMi->execute([':kodu' => $kodu]);
        if ($stmtVarMi->fetch()) {
            $atlanan++;
            continue;
        }

        $stmtEkle->execute([
            ':baslik_tr'   => $u['title'] ?? '',
            ':aciklama_tr' => guvenli_html($u['description'] ?? ''),
            ':urun_kodu'   => $kodu,
            ':kategori'    => $u['category'] ?? null,
            ':miktar'      => (int)($u['stock'] ?? 0),
            ':fiyat_usd'   => (float)($u['price'] ?? 0),
            ':ana_resim'   => $u['thumbnail'] ?? null,
        ]);

        $urunId = $pdo->lastInsertId();

        // DummyJSON'daki "images" dizisini ek resim olarak kaydet (thumbnail ile aynı olan varsa atla)
        if (!empty($u['images']) && is_array($u['images'])) {
            $sira = 0;
            foreach ($u['images'] as $resimUrl) {
                if ($resimUrl === ($u['thumbnail'] ?? null)) {
                    continue;
                }
                $stmtResimEkle->execute([
                    ':urun_id'    => $urunId,
                    ':resim_yolu' => $resimUrl,
                    ':sira'       => $sira,
                ]);
                $sira++;
            }
        }

        $eklenen++;
    }

} catch (Exception $e) {
    $hataMesaji = $e->getMessage();
}

$parametreler = 'ice_aktarildi=1&eklenen=' . $eklenen . '&atlanan=' . $atlanan;
if ($hataMesaji) {
    $parametreler .= '&hata=' . urlencode($hataMesaji);
}

header('Location: urunler.php?' . $parametreler);
exit;