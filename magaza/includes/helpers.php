<?php
/**
 * Resim yolunu oluşturur.
 * Harici URL (https://...) ise aynen döner.
 * Yerel dosya ise /UrunDetay/ prefix'i ekler.
 */
function resim_url($yol) {
    if (empty($yol)) {
        return '/UrunDetay/magaza/assets/img/placeholder.svg';
    }
    // Harici URL kontrolü
    if (strpos($yol, 'http://') === 0 || strpos($yol, 'https://') === 0) {
        return $yol;
    }
    // Yerel dosya
    return '/UrunDetay/' . ltrim($yol, '/');
}

/**
 * Verilen ürün ID'leri için urun_yorumlari tablosundan
 * ortalama puan ve yorum adedini tek sorguda çeker.
 * Dönüş: [urun_id => ['ortalama' => float, 'adet' => int]]
 */
function urun_ortalama_puanlari(PDO $pdo, array $urunIdler): array {
    if (empty($urunIdler)) {
        return [];
    }

    $yerTutucular = implode(',', array_fill(0, count($urunIdler), '?'));
    $stmt = $pdo->prepare("
        SELECT urun_id, AVG(puan) AS ortalama, COUNT(*) AS adet
        FROM urun_yorumlari
        WHERE urun_id IN ($yerTutucular)
        GROUP BY urun_id
    ");
    $stmt->execute($urunIdler);

    $puanMap = [];
    foreach ($stmt->fetchAll() as $satir) {
        $puanMap[$satir['urun_id']] = [
            'ortalama' => round((float)$satir['ortalama'], 1),
            'adet'     => (int)$satir['adet'],
        ];
    }

    return $puanMap;
}

/**
 * Bir ürün listesine ortalama puan/yorum sayısı alanlarını ekler
 * (yildiz_ortalama, yildiz_adet).
 */
function urunlere_puan_ekle(PDO $pdo, array $urunler): array {
    if (empty($urunler)) {
        return $urunler;
    }

    $puanMap = urun_ortalama_puanlari($pdo, array_column($urunler, 'id'));

    foreach ($urunler as &$u) {
        $u['yildiz_ortalama'] = $puanMap[$u['id']]['ortalama'] ?? null;
        $u['yildiz_adet'] = $puanMap[$u['id']]['adet'] ?? 0;
    }
    unset($u);

    return $urunler;
}

/**
 * 5 yıldızlık puan ikonlarının HTML'ini üretir.
 * $deger'e en yakın tam sayıya kadar olan yıldızlar dolu görünür.
 */
function yildizlar_html($deger, int $maxYildiz = 5): string {
    $dolu = (int)round((float)$deger);
    $html = '';
    for ($i = 1; $i <= $maxYildiz; $i++) {
        $html .= '<i class="bi ' . ($i <= $dolu ? 'bi-star-fill' : 'bi-star') . '"></i>';
    }
    return $html;
}

/**
 * Bir ürünün fiyatını gösterim için biçimlendirir.
 * TL fiyatı tanımlıysa TL, değilse USD olarak döner.
 */
function urun_fiyat_goster(array $urun): string {
    $tl = (float)($urun['fiyat_tl'] ?? 0);
    if ($tl > 0) {
        return number_format($tl, 2, ',', '.') . ' TL';
    }
    return '$' . number_format((float)($urun['fiyat_usd'] ?? 0), 2);
}

/**
 * Kategori/fiyat aralığı/sıralama filtrelerine göre ürünleri çeker.
 * index.php'nin ilk yüklemesi ve AJAX filtre endpoint'i (urunler-filtrele.php)
 * tarafından ortak olarak kullanılır.
 */
function urunleri_filtrele(PDO $pdo, ?string $seciliKategori, ?float $fiyatMin, ?float $fiyatMax, string $siralama, ?int $limit = null): array {
    $sql = "SELECT * FROM urunler WHERE durum = 1";
    $params = [];

    if ($seciliKategori) {
        $sql .= " AND kategori = :kategori";
        $params[':kategori'] = $seciliKategori;
    }

    if ($fiyatMin !== null && $fiyatMin > 0) {
        $sql .= " AND (CASE WHEN fiyat_tl > 0 THEN fiyat_tl ELSE fiyat_usd END) >= :fiyat_min";
        $params[':fiyat_min'] = $fiyatMin;
    }

    if ($fiyatMax !== null && $fiyatMax > 0) {
        $sql .= " AND (CASE WHEN fiyat_tl > 0 THEN fiyat_tl ELSE fiyat_usd END) <= :fiyat_max";
        $params[':fiyat_max'] = $fiyatMax;
    }

    switch ($siralama) {
        case 'fiyat_artan':
            $sql .= " ORDER BY (CASE WHEN fiyat_tl > 0 THEN fiyat_tl ELSE fiyat_usd END) ASC";
            break;
        case 'fiyat_azalan':
            $sql .= " ORDER BY (CASE WHEN fiyat_tl > 0 THEN fiyat_tl ELSE fiyat_usd END) DESC";
            break;
        case 'ad_az':
            $sql .= " ORDER BY baslik_tr ASC";
            break;
        default:
            $sql .= " ORDER BY id DESC";
    }

    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * "Customer Say!" bölümü için DummyJSON'dan rastgele ürün yorumları çeker.
 */
function dis_yorumlari_getir(int $adet = 9): array {
    $ch = curl_init("https://dummyjson.com/products?limit=15&select=title,reviews,thumbnail,price");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $yanit = curl_exec($ch);
    curl_close($ch);

    if (!$yanit) {
        return [];
    }

    $veri = json_decode($yanit, true);
    if (empty($veri['products'])) {
        return [];
    }

    $tumYorumlar = [];
    foreach ($veri['products'] as $urun) {
        if (empty($urun['reviews'])) continue;
        foreach ($urun['reviews'] as $yorum) {
            $tumYorumlar[] = [
                'reviewerName' => $yorum['reviewerName'],
                'comment'      => $yorum['comment'],
                'rating'       => (int)round($yorum['rating']),
                'urunAdi'      => $urun['title'],
                'urunResim'    => $urun['thumbnail'],
                'urunFiyat'    => $urun['price'],
            ];
        }
    }

    shuffle($tumYorumlar);
    return array_slice($tumYorumlar, 0, $adet);
}

/**
 * Sepet satırlarını (urun_id, adet, fiyat, paket_id) sepet görünümü için gruplar.
 * paket_id'si olan satırlar tek bir "bundle" satırına birleşir (sabit adetli,
 * hep birlikte silinir); paket_id'si olmayanlar normal "single" satır olarak kalır.
 * Dönüş sırası, satırların ham sorgudaki (eklenme_tarihi DESC) ilk görülme sırasını korur.
 */
function sepeti_grupla(array $satirlar): array {
    $satirlarMap = [];

    foreach ($satirlar as $satir) {
        if (!empty($satir['paket_id'])) {
            $anahtar = 'p' . $satir['paket_id'];
            if (!isset($satirlarMap[$anahtar])) {
                $satirlarMap[$anahtar] = [
                    'line_id'     => $anahtar,
                    'type'        => 'bundle',
                    'paket_id'    => $satir['paket_id'],
                    'adet'        => 1,
                    'fiyat'       => 0.0,
                    'parcaSayisi' => 0,
                    'baslikListe' => [],
                    'resim'       => $satir['resim'],
                ];
            }
            $satirlarMap[$anahtar]['fiyat'] += (float)$satir['fiyat'] * (int)$satir['adet'];
            $satirlarMap[$anahtar]['parcaSayisi'] += (int)$satir['adet'];
            $satirlarMap[$anahtar]['baslikListe'][] = $satir['baslik'];
        } else {
            $anahtar = 'u' . $satir['urun_id'];
            $satirlarMap[$anahtar] = [
                'line_id'     => $anahtar,
                'type'        => 'single',
                'id'          => (int)$satir['urun_id'],
                'adet'        => (int)$satir['adet'],
                'parcaSayisi' => (int)$satir['adet'],
                'fiyat'       => (float)$satir['fiyat'],
                'baslik'      => $satir['baslik'],
                'resim'       => $satir['resim'],
            ];
        }
    }

    $sonuc = [];
    foreach ($satirlarMap as $satir) {
        if ($satir['type'] === 'bundle') {
            $satir['fiyat']  = round($satir['fiyat'], 2);
            $satir['baslik'] = implode(' + ', $satir['baslikListe']);
            unset($satir['baslikListe']);
        }
        $sonuc[] = $satir;
    }

    return $sonuc;
}

/**
 * Kullanıcının sepetinde birden fazla paket (bundle) grubu varsa (normalde
 * asla oluşmaması gereken bir durum, bkz. sepet-islem.php'deki paket_ekle
 * güvenlik kontrolü) sadece ilk oluşturulanı tutar, diğerlerini siler.
 * Sepet her okunduğunda çağrılması güvenlidir (tek paket varken no-op'tur).
 */
function sepet_fazla_paketleri_temizle(PDO $pdo, int $kullaniciId): void {
    $stmt = $pdo->prepare("
        SELECT paket_id, MIN(id) AS ilk_id
        FROM sepet
        WHERE kullanici_id = :kid AND paket_id IS NOT NULL
        GROUP BY paket_id
        ORDER BY ilk_id ASC
    ");
    $stmt->execute([':kid' => $kullaniciId]);
    $paketler = $stmt->fetchAll();

    if (count($paketler) <= 1) {
        return;
    }

    $silinecekIdler = array_column(array_slice($paketler, 1), 'paket_id');
    $yerTutucular = implode(',', array_fill(0, count($silinecekIdler), '?'));

    $pdo->prepare("DELETE FROM sepet WHERE kullanici_id = ? AND paket_id IN ($yerTutucular)")
        ->execute(array_merge([$kullaniciId], $silinecekIdler));
}
