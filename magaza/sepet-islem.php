<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/json');

if (!girisYapmisMi()) {
    http_response_code(401);
    echo json_encode(['basarili' => false, 'hata' => 'giris_gerekli']);
    exit;
}

$kullaniciId = $_SESSION['kullanici_id'];
$islem = $_POST['islem'] ?? '';

// "Paket Yap" indirimi — magaza.js'teki PAKET_INDIRIM_ORANI / PAKET_MIN_URUN ile senkron tutulmalı.
// Fiyat indirimi burada, sunucu tarafında hesaplanır; istemciden fiyat kabul edilmez (manipülasyona kapalı).
define('PAKET_INDIRIM_ORANI', 0.30);
define('PAKET_URUN_SAYISI', 3);

if ($islem === 'paket_ekle') {
    // Sepette zaten bir paket varken ikinci bir paket oluşturulamaz — bu kontrol
    // istemcinin (buton disabled durumu, ön kontrol) atlanması ihtimaline karşı
    // asıl güvenlik sınırıdır; istemci fonksiyonu doğrudan çağırsa bile geçersizdir.
    $kontrolPaket = $pdo->prepare("SELECT COUNT(DISTINCT paket_id) FROM sepet WHERE kullanici_id = :kid AND paket_id IS NOT NULL");
    $kontrolPaket->execute([':kid' => $kullaniciId]);
    if ((int)$kontrolPaket->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['basarili' => false, 'hata' => 'paket_zaten_var']);
        exit;
    }

    $urunIdler = array_map('intval', $_POST['urun_idler'] ?? []);
    $urunIdler = array_values(array_unique(array_filter($urunIdler, function ($id) { return $id > 0; })));

    if (count($urunIdler) !== PAKET_URUN_SAYISI) {
        http_response_code(400);
        echo json_encode(['basarili' => false, 'hata' => 'gecersiz_paket']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($urunIdler), '?'));
    $urunStmt = $pdo->prepare("SELECT id, fiyat_usd FROM urunler WHERE id IN ($placeholders) AND durum = 1");
    $urunStmt->execute($urunIdler);
    $urunler = $urunStmt->fetchAll();

    if (count($urunler) !== PAKET_URUN_SAYISI) {
        http_response_code(400);
        echo json_encode(['basarili' => false, 'hata' => 'urun_bulunamadi']);
        exit;
    }

    // Her "Paket Yap" tıklaması kendi bundle instance'ını oluşturur — mevcut
    // sepet satırlarıyla (ne normal ne başka bir paket) asla birleşmez, böylece
    // paket dışına eklenen aynı ürün, paketin indirimli fiyatını miras almaz.
    $paketId = bin2hex(random_bytes(8));

    foreach ($urunler as $u) {
        $indirimliFiyat = round((float)$u['fiyat_usd'] * (1 - PAKET_INDIRIM_ORANI), 2);

        $pdo->prepare("INSERT INTO sepet (kullanici_id, urun_id, adet, birim_fiyat, paket_id) VALUES (:kid, :uid, 1, :fiyat, :pid)")
            ->execute([':kid' => $kullaniciId, ':uid' => $u['id'], ':fiyat' => $indirimliFiyat, ':pid' => $paketId]);
    }
} elseif ($islem === 'paket_sil') {
    $paketId = $_POST['paket_id'] ?? '';

    if ($paketId === '') {
        http_response_code(400);
        echo json_encode(['basarili' => false, 'hata' => 'gecersiz_paket']);
        exit;
    }

    $pdo->prepare("DELETE FROM sepet WHERE kullanici_id = :kid AND paket_id = :pid")
        ->execute([':kid' => $kullaniciId, ':pid' => $paketId]);
} else {
    $urunId = (int)($_POST['urun_id'] ?? 0);

    if ($urunId <= 0) {
        http_response_code(400);
        echo json_encode(['basarili' => false, 'hata' => 'gecersiz_urun']);
        exit;
    }

    // Bu üç işlem yalnızca paket_id'si OLMAYAN (normal, tek ürün) satırları hedefler —
    // bir ürün paket içindeyse, sepette paketten bağımsız ayrı bir satır olarak ele alınır.
    if ($islem === 'ekle') {
        $adet = max(1, (int)($_POST['adet'] ?? 1));

        $kontrol = $pdo->prepare("SELECT id, adet FROM sepet WHERE kullanici_id = :kid AND urun_id = :uid AND paket_id IS NULL");
        $kontrol->execute([':kid' => $kullaniciId, ':uid' => $urunId]);
        $mevcut = $kontrol->fetch();

        if ($mevcut) {
            $guncelle = $pdo->prepare("UPDATE sepet SET adet = :adet WHERE id = :id");
            $guncelle->execute([':adet' => $mevcut['adet'] + $adet, ':id' => $mevcut['id']]);
        } else {
            $ekle = $pdo->prepare("INSERT INTO sepet (kullanici_id, urun_id, adet) VALUES (:kid, :uid, :adet)");
            $ekle->execute([':kid' => $kullaniciId, ':uid' => $urunId, ':adet' => $adet]);
        }
    } elseif ($islem === 'adet_guncelle') {
        $delta = (int)($_POST['delta'] ?? 0);

        $kontrol = $pdo->prepare("SELECT id, adet FROM sepet WHERE kullanici_id = :kid AND urun_id = :uid AND paket_id IS NULL");
        $kontrol->execute([':kid' => $kullaniciId, ':uid' => $urunId]);
        $mevcut = $kontrol->fetch();

        if ($mevcut) {
            $yeniAdet = $mevcut['adet'] + $delta;
            if ($yeniAdet < 1) {
                $pdo->prepare("DELETE FROM sepet WHERE id = :id")->execute([':id' => $mevcut['id']]);
            } else {
                $pdo->prepare("UPDATE sepet SET adet = :adet WHERE id = :id")->execute([':adet' => $yeniAdet, ':id' => $mevcut['id']]);
            }
        }
    } elseif ($islem === 'sil') {
        $pdo->prepare("DELETE FROM sepet WHERE kullanici_id = :kid AND urun_id = :uid AND paket_id IS NULL")
            ->execute([':kid' => $kullaniciId, ':uid' => $urunId]);
    } else {
        http_response_code(400);
        echo json_encode(['basarili' => false, 'hata' => 'gecersiz_islem']);
        exit;
    }
}

// Olası eski/hatalı çoklu paket verisini (bu koddan önce oluşmuş olabilir) düzelt
sepet_fazla_paketleri_temizle($pdo, $kullaniciId);

// Güncel sepeti döndür (paket satırları tek bundle satırına gruplanmış olarak)
$stmt = $pdo->prepare("
    SELECT s.urun_id, s.adet, s.paket_id, u.baslik_tr AS baslik, COALESCE(s.birim_fiyat, u.fiyat_usd) AS fiyat, u.ana_resim AS resim
    FROM sepet s
    INNER JOIN urunler u ON u.id = s.urun_id
    WHERE s.kullanici_id = :kid
    ORDER BY s.eklenme_tarihi DESC
");
$stmt->execute([':kid' => $kullaniciId]);
$satirlar = $stmt->fetchAll();

foreach ($satirlar as &$satir) {
    $satir['resim'] = resim_url($satir['resim']);
}
unset($satir);

$sepet = sepeti_grupla($satirlar);

echo json_encode(['basarili' => true, 'sepet' => $sepet]);