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
