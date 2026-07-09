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
$urunBulunamadi = 0;
$hataMesaji = null;

try {
    // Aynı DummyJSON ürün listesi (urun_ice_aktar.php ile aynı kaynak) —
    // her ürünün "reviews" dizisi varsayılan olarak yanıtta gelir.
    $json = api_getir('https://dummyjson.com/products?limit=100');
    $veri = json_decode($json, true);

    if (!isset($veri['products'])) {
        throw new Exception('API beklenmeyen bir yanıt döndü.');
    }

    // sku (urun_kodu) üzerinden yerel ürünü bulacak sorgu
    $stmtUrunBul = $pdo->prepare("SELECT id FROM urunler WHERE urun_kodu = :kodu");

    // Aynı yorum zaten eklenmiş mi diye kontrol edecek sorgu (urun_id + yazan + yorum metni eşleşmesi)
    $stmtVarMi = $pdo->prepare(
        "SELECT id FROM urun_yorumlari WHERE urun_id = :urun_id AND reviewer_adi = :reviewer_adi AND yorum = :yorum"
    );

    // Yeni yorum ekleyecek sorgu (kullanici_id NULL: bu gerçek bir kayıtlı kullanıcı değil, dış/örnek yorum)
    $stmtEkle = $pdo->prepare(
        "INSERT INTO urun_yorumlari (urun_id, kullanici_id, reviewer_adi, puan, yorum) VALUES (:urun_id, NULL, :reviewer_adi, :puan, :yorum)"
    );

    foreach ($veri['products'] as $u) {
        if (empty($u['sku']) || empty($u['reviews']) || !is_array($u['reviews'])) {
            continue;
        }

        $stmtUrunBul->execute([':kodu' => $u['sku']]);
        $urun = $stmtUrunBul->fetch();

        if (!$urun) {
            $urunBulunamadi++;
            continue;
        }

        $urunId = $urun['id'];

        foreach ($u['reviews'] as $r) {
            $reviewerAdi = $r['reviewerName'] ?? 'Müşteri';
            $yorumMetni = guvenli_html($r['comment'] ?? '');
            $puan = max(1, min(5, (int)round($r['rating'] ?? 0)));

            if ($yorumMetni === '') {
                continue;
            }

            $stmtVarMi->execute([
                ':urun_id'      => $urunId,
                ':reviewer_adi' => $reviewerAdi,
                ':yorum'        => $yorumMetni,
            ]);
            if ($stmtVarMi->fetch()) {
                $atlanan++;
                continue;
            }

            $stmtEkle->execute([
                ':urun_id'      => $urunId,
                ':reviewer_adi' => $reviewerAdi,
                ':puan'         => $puan,
                ':yorum'        => $yorumMetni,
            ]);

            $eklenen++;
        }
    }

} catch (Exception $e) {
    $hataMesaji = $e->getMessage();
}

$parametreler = 'yorum_ice_aktarildi=1&eklenen=' . $eklenen . '&atlanan=' . $atlanan . '&bulunamadi=' . $urunBulunamadi;
if ($hataMesaji) {
    $parametreler .= '&hata=' . urlencode($hataMesaji);
}

header('Location: urunler.php?' . $parametreler);
exit;
