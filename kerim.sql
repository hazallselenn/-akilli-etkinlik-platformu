-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost
-- Üretim Zamanı: 06 Ara 2024, 01:32:43
-- Sunucu sürümü: 10.4.28-MariaDB
-- PHP Sürümü: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `kerim`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bildirimler`
--

CREATE TABLE `bildirimler` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `mesaj` text NOT NULL,
  `icon` varchar(50) NOT NULL,
  `tarih` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `bildirimler`
--

INSERT INTO `bildirimler` (`id`, `kullanici_id`, `mesaj`, `icon`, `tarih`) VALUES
(1, 1, 'Tebrikler! Etkinliğe katılarak 10 puan kazandınız!', 'fa-trophy', '2024-12-06 03:26:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `etkinlikler`
--

CREATE TABLE `etkinlikler` (
  `id` int(11) NOT NULL,
  `etkinlik_adi` varchar(100) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `tarih` date DEFAULT NULL,
  `saat` time DEFAULT NULL,
  `etkinlik_suresi` int(11) DEFAULT NULL,
  `konum` varchar(100) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `durum` int(11) NOT NULL,
  `lat` text NOT NULL,
  `lng` text NOT NULL,
  `olusturan_id` int(11) NOT NULL,
  `baslangic_zamani` text NOT NULL,
  `bitis_zamani` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `etkinlikler`
--

INSERT INTO `etkinlikler` (`id`, `etkinlik_adi`, `aciklama`, `tarih`, `saat`, `etkinlik_suresi`, `konum`, `kategori`, `durum`, `lat`, `lng`, `olusturan_id`, `baslangic_zamani`, `bitis_zamani`) VALUES
(4, 'İstanbul Maratonu', 'İstanbul\'un tarihi mekanları eşliğinde düzenlenen uluslararası maraton. Hem profesyoneller hem amatör koşucular katılabilir.', '2025-03-03', '08:00:00', NULL, 'Binbirdirek, Sultanahmet Meydanı, Sultan Ahmet Parkı, Fatih/İstanbul, Türkiye', 'Spor', 1, '41.0063286', '28.9757051', 0, '2025-03-03 08:00', '2025-03-03 10:00:00'),
(6, 'Modern Sanat Sergisi', 'Ünlü sanatçıların eserlerinden oluşan eşsiz bir sergi. Sanatseverler için kaçırılmayacak bir fırsat.', '2025-04-10', '18:00:00', NULL, 'Kılıçali Paşa, İstanbul Modern Sanat Müzesi, Tophane İskele Caddesi, Beyoğlu/İstanbul, Türkiye', 'Sanat', 1, '41.02591959999999', '28.9828383', 0, '2025-04-10 18:00', '2025-04-10 20:00:00'),
(7, 'Yapay Zeka Konferansı', 'Yapay zeka alanında uzman konuşmacıların yer alacağı, sektörel gelişmelerin paylaşıldığı bir konferans.', '2025-05-15', '09:30:00', NULL, 'Harbiye, Lütfi Kırdar Uluslararası Kongre ve Sergi Sarayı, Şişli/İstanbul, Türkiye', 'Teknoloji', 1, '41.0473777', '28.9905316', 0, '2025-05-15 09:30', '2025-05-15 11:30:00'),
(8, 'Kişisel Gelişim Semineri', 'Zaman yönetimi, stresle başa çıkma ve motivasyon teknikleri üzerine bir seminer.', '2025-06-20', '14:00:00', NULL, 'Bebek, Boğaziçi Üniversitesi Güney Kampüs, BÜ Güney Kampüsü, Beşiktaş/İstanbul, Türkiye', 'Eğitim', 1, '41.0835911', '29.0519114', 0, '2025-06-20 14:00', '2025-06-20 16:00:00'),
(9, 'Akustik Müzik Gecesi', 'Yerel sanatçıların sahne alacağı, samimi bir akustik müzik gecesi.', '2025-07-07', '20:00:00', NULL, 'Osmanağa, Kadıköy Sahne, Kırtasiyeci Sokak, Kadıköy/İstanbul, Türkiye', 'Müzik', 1, '40.9903127', '29.027577', 0, '2025-07-07 20:00', '2025-07-07 22:00:00'),
(10, 'Sağlıklı Yaşam Atölyesi', 'Diyetisyen ve fitness uzmanlarının katılımıyla sağlıklı yaşam üzerine uygulamalı bir atölye.', '2025-08-18', '10:00:00', NULL, 'Sütlüce, Haliç Kongre Merkezi, Karaağaç Caddesi, Beyoğlu/İstanbul, Türkiye', 'Sağlık', 1, '41.050286', '28.939997', 0, '2025-08-18 10:00', '2025-08-18 12:00:00'),
(11, 'Çocuklar İçin Kitap Toplama Kampanyası', 'Köy okullarına kitap desteği sağlamak amacıyla düzenlenen gönüllü etkinlik.', '2025-09-05', '11:00:00', NULL, 'Gümüşsuyu, Atatürk Kitaplığı, Miralay Şefikbey Sokak, Beyoğlu/İstanbul, Türkiye', 'Gönüllülük', 1, '41.0391249', '28.9895638', 0, '2025-09-05 11:00', '2025-09-05 13:00:00'),
(12, 'Orman Yürüyüşü ve Temizlik Etkinliği', 'Hem doğayla buluşmak hem de çevre temizliği için farkındalık yaratmak adına düzenlenen bir etkinlik.', '2025-10-09', '09:00:00', NULL, 'Mithatpaşa, Belgrad Ormanı, Eyüpsultan/İstanbul, Türkiye', 'Doğa', 1, '41.199376', '28.937086', 0, '2025-10-09 09:00', '2025-10-09 11:00:00'),
(13, 'Geleneksel Yemek Atölyesi', 'Geleneksel Türk mutfağından lezzetlerin yapımını öğrenmek isteyenler için uygulamalı bir atölye.', '2025-11-12', '15:00:00', NULL, 'Bereketzade, Galata Kulesi Sokak, Beyoğlu/İstanbul, Türkiye', 'Yemek', 1, '41.0248407', '28.9733825', 0, '2025-11-12 15:00', '2025-11-12 17:00:00'),
(14, 'Stand-Up Gecesi', 'Ünlü komedyenlerin sahne alacağı, kahkaha dolu bir gece.', '2025-12-25', '21:00:00', NULL, 'Levazım, Zorlu Performans Sanatları Merkezi, Zincirlikuyu/İstanbul, Türkiye', 'Eğlence', 1, '41.0666909', '29.0162415', 0, '2025-12-25 21:00', '2025-12-25 23:00:00'),
(15, 'Boğazda Yüzme Yarışı1', 'İstanbul Boğazı\'nda düzenlenen yüzme yarışı, hem amatör hem profesyonel yüzücülere açık.', '2025-03-15', '15:00:00', NULL, 'Kuruçeşme, Kuruçeşme Parkı, Beşiktaş/İstanbul, Türkiye', 'Spor', 1, '41.0601042', '29.0364918', 0, '2025-03-15 15:00', '2025-03-15 17:00:00'),
(16, 'İstanbul Bisiklet Turu', 'İstanbul\'un tarihi ve doğal güzelliklerini keşfetmek için düzenlenen bisiklet turu.', '2025-03-15', '15:00:00', NULL, 'Aksaray, Yenikapı Etkinlik Alanı, Kennedy Caddesi, Fatih/İstanbul, Türkiye', 'Spor', 1, '40.99962299999999', '28.947722', 0, '2025-03-15 15:00', '2025-03-15 17:00:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `geri_bildirimler`
--

CREATE TABLE `geri_bildirimler` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `feedback_type` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `rating` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `geri_bildirimler`
--

INSERT INTO `geri_bildirimler` (`id`, `kullanici_id`, `feedback_type`, `subject`, `message`, `rating`, `created_at`) VALUES
(1, 1, 'oneri', 'test', 'etsetqetq', 5, '2024-11-26 13:16:15'),
(2, 1, 'oneri', 'test', 'etsetqetq', 5, '2024-11-26 13:16:40');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `katilimcilar`
--

CREATE TABLE `katilimcilar` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `etkinlik_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `katilimcilar`
--

INSERT INTO `katilimcilar` (`id`, `kullanici_id`, `etkinlik_id`) VALUES
(4, 1, 4),
(6, 1, 15),
(8, 2, 15),
(9, 1, 12),
(10, 1, 13),
(11, 1, 11);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL,
  `kullanici_adi` varchar(50) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `konum` varchar(100) DEFAULT NULL,
  `ilgi_alanlari` text DEFAULT NULL,
  `ad` varchar(50) DEFAULT NULL,
  `soyad` varchar(50) DEFAULT NULL,
  `dogum_tarihi` date DEFAULT NULL,
  `cinsiyet` varchar(20) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `profil_fotografi` varchar(255) DEFAULT NULL,
  `admin` int(11) NOT NULL,
  `son_giris` text DEFAULT NULL,
  `son_gorulme` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `kullanici_adi`, `sifre`, `email`, `konum`, `ilgi_alanlari`, `ad`, `soyad`, `dogum_tarihi`, `cinsiyet`, `telefon`, `profil_fotografi`, `admin`, `son_giris`, `son_gorulme`) VALUES
(1, 'kerim', '$2y$10$/IB1x57DP51hSHp4AkPkq.WuBkdvD.6syQNBherLnHiAa2xUjKpuy', 'kerim@kerim.com', NULL, '[\"Spor\",\"Sanat\",\"Teknoloji\"]', 'Kerim', 'Dogan', '2001-08-21', 'Erkek', '5426661155', '6745cbb94f4c9.jpg', 1, NULL, '2024-12-06 03:32:33'),
(2, 'deneme', '$2y$10$F5PbwrD9fWKZIBsKbiaFyOSbpBFSwjAzzgFhOhP1dcznVmx7B/oOi', 'denem1@gmail.com', NULL, '[\"Spor\",\"Sanat\",\"G\\u00f6n\\u00fcll\\u00fcl\\u00fck\"]', 'deneme', 'deneme', '2003-02-22', 'male', '5524547885', '674744a7618ad.png', 0, NULL, '2024-11-27 19:13:53');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `mesajlar`
--

CREATE TABLE `mesajlar` (
  `id` int(11) NOT NULL,
  `etkinlik_id` int(11) NOT NULL,
  `gonderen_id` int(11) NOT NULL,
  `mesaj` text NOT NULL,
  `tarih` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `mesajlar`
--

INSERT INTO `mesajlar` (`id`, `etkinlik_id`, `gonderen_id`, `mesaj`, `tarih`) VALUES
(5, 4, 1, 'asdasdasd', '2024-11-27 18:55:44'),
(6, 4, 1, 'asasasasas', '2024-11-27 18:55:46'),
(7, 15, 2, 'hehehehehe', '2024-11-27 19:13:52'),
(8, 15, 1, 'ohohohoho', '2024-11-27 19:14:08'),
(9, 15, 1, 'asdfasdf', '2024-12-06 03:19:57'),
(10, 15, 1, 'asdfasdf', '2024-12-06 03:19:59'),
(11, 15, 1, 'asdfasdf', '2024-12-06 03:20:04'),
(12, 15, 1, 'asdf', '2024-12-06 03:22:55'),
(13, 15, 1, 'asdf', '2024-12-06 03:23:04'),
(14, 15, 1, 'asdfadsfqweqwe', '2024-12-06 03:23:55');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `puanlar`
--

CREATE TABLE `puanlar` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `puan` int(11) DEFAULT NULL,
  `kazanilan_tarih` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `puanlar`
--

INSERT INTO `puanlar` (`id`, `kullanici_id`, `puan`, `kazanilan_tarih`) VALUES
(1, 1, 30, '2024-11-27 18:17:39'),
(2, 1, 30, '2024-11-27 18:55:14'),
(3, 1, 10, '2024-11-27 19:05:42'),
(4, 1, 10, '2024-11-27 19:08:29'),
(5, 2, 30, '2024-11-27 19:12:19'),
(6, 2, 30, '2024-11-27 19:13:45'),
(7, 1, 10, '2024-12-01 22:18:05'),
(8, 1, 10, '2024-12-06 03:26:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_ayarlari`
--

CREATE TABLE `site_ayarlari` (
  `id` int(11) NOT NULL,
  `site_baslik` varchar(255) DEFAULT NULL,
  `hakkimizda` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `footer_text` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `favicon_url` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `site_ayarlari`
--

INSERT INTO `site_ayarlari` (`id`, `site_baslik`, `hakkimizda`, `email`, `telefon`, `adres`, `facebook`, `twitter`, `instagram`, `linkedin`, `footer_text`, `logo_url`, `favicon_url`, `meta_description`, `meta_keywords`) VALUES
(1, 'Site Başlığı', 'Hakkımızda metniasdfasdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `bildirimler`
--
ALTER TABLE `bildirimler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `etkinlikler`
--
ALTER TABLE `etkinlikler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `geri_bildirimler`
--
ALTER TABLE `geri_bildirimler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `katilimcilar`
--
ALTER TABLE `katilimcilar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`),
  ADD KEY `etkinlik_id` (`etkinlik_id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `mesajlar`
--
ALTER TABLE `mesajlar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `etkinlik_id` (`etkinlik_id`),
  ADD KEY `gonderen_id` (`gonderen_id`);

--
-- Tablo için indeksler `puanlar`
--
ALTER TABLE `puanlar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `site_ayarlari`
--
ALTER TABLE `site_ayarlari`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `bildirimler`
--
ALTER TABLE `bildirimler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `etkinlikler`
--
ALTER TABLE `etkinlikler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Tablo için AUTO_INCREMENT değeri `geri_bildirimler`
--
ALTER TABLE `geri_bildirimler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `katilimcilar`
--
ALTER TABLE `katilimcilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `mesajlar`
--
ALTER TABLE `mesajlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Tablo için AUTO_INCREMENT değeri `puanlar`
--
ALTER TABLE `puanlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `site_ayarlari`
--
ALTER TABLE `site_ayarlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `geri_bildirimler`
--
ALTER TABLE `geri_bildirimler`
  ADD CONSTRAINT `geri_bildirimler_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`);

--
-- Tablo kısıtlamaları `katilimcilar`
--
ALTER TABLE `katilimcilar`
  ADD CONSTRAINT `katilimcilar_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`),
  ADD CONSTRAINT `katilimcilar_ibfk_2` FOREIGN KEY (`etkinlik_id`) REFERENCES `etkinlikler` (`id`);

--
-- Tablo kısıtlamaları `mesajlar`
--
ALTER TABLE `mesajlar`
  ADD CONSTRAINT `mesajlar_ibfk_1` FOREIGN KEY (`etkinlik_id`) REFERENCES `etkinlikler` (`id`),
  ADD CONSTRAINT `mesajlar_ibfk_2` FOREIGN KEY (`gonderen_id`) REFERENCES `kullanicilar` (`id`);

--
-- Tablo kısıtlamaları `puanlar`
--
ALTER TABLE `puanlar`
  ADD CONSTRAINT `puanlar_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
