-- magaza_admin veritabanı tablo yapısı
-- Bu dosya, proje kodundaki (urun_kaydet.php, urun_ice_aktar.php, magaza/*.php)
-- SELECT/INSERT sorgularından çıkarılan sütunlara göre oluşturulmuştur.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `urun_yorumlari`;
DROP TABLE IF EXISTS `sepet`;
DROP TABLE IF EXISTS `favoriler`;
DROP TABLE IF EXISTS `urun_indirimler`;
DROP TABLE IF EXISTS `urun_resimler`;
DROP TABLE IF EXISTS `sifre_sifirlama`;
DROP TABLE IF EXISTS `urunler`;
DROP TABLE IF EXISTS `kullanicilar`;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- urunler (product.php, urun_kaydet.php, urun_ice_aktar.php, magaza/*)
-- ------------------------------------------------------------
CREATE TABLE `urunler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Genel / SEO
    `baslik_tr` VARCHAR(255) NOT NULL,
    `ek_bilgi_baslik_tr` VARCHAR(255) NULL,
    `ek_bilgi_aciklama_tr` VARCHAR(255) NULL,
    `meta_title_tr` VARCHAR(255) NULL,
    `meta_keywords_tr` VARCHAR(255) NULL,
    `meta_description_tr` VARCHAR(255) NULL,
    `seo_adresi_tr` VARCHAR(255) NULL,
    `aciklama_tr` TEXT NULL,
    `video_embed_tr` TEXT NULL,

    -- Kategori (magaza tarafında filtre/arama/benzer ürünler için kullanılır)
    `kategori` VARCHAR(100) NULL,

    -- Detaylar
    `urun_kodu` VARCHAR(100) NOT NULL,
    `miktar` INT NOT NULL DEFAULT 0,
    `birim` VARCHAR(20) NOT NULL DEFAULT 'Adet',
    `sepet_indirim` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `vergi_orani` TINYINT UNSIGNED NOT NULL DEFAULT 18,
    `fiyat_tl` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `fiyat_usd` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `fiyat_eur` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `fiyat2_tl` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `stoktan_dus` TINYINT(1) NOT NULL DEFAULT 1,
    `durum` TINYINT(1) NOT NULL DEFAULT 1,
    `ozellik_bolumu` TINYINT(1) NOT NULL DEFAULT 1,
    `gecerlilik_suresi` DATE NULL,
    `siralama` INT NOT NULL DEFAULT 0,
    `anasayfada_goster` INT NOT NULL DEFAULT 0,
    `yeni_urun` TINYINT(1) NOT NULL DEFAULT 1,
    `taksit` TINYINT(1) NOT NULL DEFAULT 1,
    `garanti_suresi` INT UNSIGNED NULL,

    -- Resim
    `ana_resim` VARCHAR(500) NULL,

    `olusturma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_urun_kodu` (`urun_kodu`),
    KEY `idx_kategori` (`kategori`),
    KEY `idx_durum` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- urun_resimler (urun_kaydet.php, urun.php, urun_sil.php)
-- ------------------------------------------------------------
CREATE TABLE `urun_resimler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `urun_id` INT UNSIGNED NOT NULL,
    `resim_yolu` VARCHAR(500) NOT NULL,
    `sira` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_urun_id` (`urun_id`),
    CONSTRAINT `fk_urun_resimler_urun` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- urun_indirimler (urun_kaydet.php, product.php)
-- ------------------------------------------------------------
CREATE TABLE `urun_indirimler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `urun_id` INT UNSIGNED NOT NULL,
    `musteri_grubu` VARCHAR(100) NOT NULL,
    `oncelik` INT NOT NULL DEFAULT 0,
    `indirim_tl` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `indirim_tip_tl` ENUM('Fiyat','Yüzde') NOT NULL DEFAULT 'Fiyat',
    `indirim_usd` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `indirim_tip_usd` ENUM('Fiyat','Yüzde') NOT NULL DEFAULT 'Fiyat',
    `indirim_eur` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `indirim_tip_eur` ENUM('Fiyat','Yüzde') NOT NULL DEFAULT 'Fiyat',
    `baslangic_tarihi` DATE NULL,
    `bitis_tarihi` DATE NULL,
    PRIMARY KEY (`id`),
    KEY `idx_urun_id` (`urun_id`),
    CONSTRAINT `fk_urun_indirimler_urun` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- kullanicilar (magaza/giris.php, kayit.php, profil.php, auth.php)
-- ------------------------------------------------------------
CREATE TABLE `kullanicilar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ad_soyad` VARCHAR(150) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `sifre` VARCHAR(255) NOT NULL,
    `telefon` VARCHAR(30) NULL,
    `kayit_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- favoriler (magaza/favori-islem.php, favorilerim.php)
-- ------------------------------------------------------------
CREATE TABLE `favoriler` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kullanici_id` INT UNSIGNED NOT NULL,
    `urun_id` INT UNSIGNED NOT NULL,
    `eklenme_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kullanici_urun` (`kullanici_id`, `urun_id`),
    KEY `idx_urun_id` (`urun_id`),
    CONSTRAINT `fk_favoriler_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_favoriler_urun` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- sepet (magaza/sepet-islem.php, includes/header.php)
-- ------------------------------------------------------------
CREATE TABLE `sepet` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kullanici_id` INT UNSIGNED NOT NULL,
    `urun_id` INT UNSIGNED NOT NULL,
    `adet` INT NOT NULL DEFAULT 1,
    `birim_fiyat` DECIMAL(12,2) NULL,
    `paket_id` VARCHAR(32) NULL,
    `eklenme_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kullanici_id` (`kullanici_id`),
    KEY `idx_urun_id` (`urun_id`),
    KEY `idx_paket_id` (`paket_id`),
    CONSTRAINT `fk_sepet_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sepet_urun` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- urun_yorumlari (magaza/yorum-ekle.php, urun-detay-veri.php, index.php)
-- kullanici_id NULL olabilir: dummy/dış yorumlar reviewer_adi ile gösterilir.
-- ------------------------------------------------------------
CREATE TABLE `urun_yorumlari` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `urun_id` INT UNSIGNED NOT NULL,
    `kullanici_id` INT UNSIGNED NULL,
    `reviewer_adi` VARCHAR(150) NULL,
    `puan` TINYINT UNSIGNED NOT NULL,
    `yorum` TEXT NOT NULL,
    `olusturma_tarihi` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_urun_id` (`urun_id`),
    KEY `idx_kullanici_id` (`kullanici_id`),
    CONSTRAINT `fk_yorum_urun` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_yorum_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_puan` CHECK (`puan` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ------------------------------------------------------------
-- sifre_sifirlama (magaza/sifremi-unuttum.php, sifre-sifirla.php)
-- ------------------------------------------------------------
CREATE TABLE `sifre_sifirlama` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(190) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `son_kullanma` DATETIME NOT NULL,
    `kullanildi` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token` (`token`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
