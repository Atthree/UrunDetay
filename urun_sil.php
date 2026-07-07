<?php
require_once __DIR__ . '/config/db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    // Önce ürünün resim yollarını al ki dosyaları da silebilelim
    $stmt = $pdo->prepare("SELECT ana_resim FROM urunler WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $urun = $stmt->fetch();

    if ($urun) {
        // Ana resmi diskten sil
        if (!empty($urun['ana_resim']) && file_exists(__DIR__ . '/' . $urun['ana_resim'])) {
            unlink(__DIR__ . '/' . $urun['ana_resim']);
        }

        // Ek resimleri diskten sil
        $stmtResim = $pdo->prepare("SELECT resim_yolu FROM urun_resimler WHERE urun_id = :id");
        $stmtResim->execute([':id' => $id]);
        foreach ($stmtResim->fetchAll() as $r) {
            if (file_exists(__DIR__ . '/' . $r['resim_yolu'])) {
                unlink(__DIR__ . '/' . $r['resim_yolu']);
            }
        }

        // urunler tablosundan sil (urun_resimler ve urun_indirimler
        // ON DELETE CASCADE sayesinde otomatik silinir)
        $pdo->prepare("DELETE FROM urunler WHERE id = :id")->execute([':id' => $id]);
    }
}

header('Location: urunler.php?silindi=1');
exit;
