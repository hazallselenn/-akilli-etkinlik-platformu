<?php
include 'inc/config.php';

// URL'den etkinlik ID'sini al
$etkinlik_id = isset($_GET['id']) ? $_GET['id'] : null;

// Etkinliğe katılma işlemi
if(isset($_POST['katil']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Daha önce katılıp katılmadığını kontrol et
    $kontrol_sql = "SELECT id FROM katilimcilar WHERE etkinlik_id = :etkinlik_id AND kullanici_id = :kullanici_id";
    $kontrol_stmt = $db->prepare($kontrol_sql);
    $kontrol_stmt->execute(['etkinlik_id' => $etkinlik_id, 'kullanici_id' => $user_id]);
    
    if($kontrol_stmt->rowCount() == 0) {
        // Katılımcılar tablosuna ekle
        $katil_sql = "INSERT INTO katilimcilar (etkinlik_id, kullanici_id) VALUES (:etkinlik_id, :kullanici_id)";
        $katil_stmt = $db->prepare($katil_sql);
        $katil_stmt->execute(['etkinlik_id' => $etkinlik_id, 'kullanici_id' => $user_id]);
        
        if($katil_stmt->rowCount() > 0) {
            // İlk katılım kontrolü
            $ilk_katilim_sql = "SELECT COUNT(*) FROM katilimcilar WHERE kullanici_id = :kullanici_id";
            $ilk_katilim_stmt = $db->prepare($ilk_katilim_sql);
            $ilk_katilim_stmt->execute(['kullanici_id' => $user_id]);
            $katilim_sayisi = $ilk_katilim_stmt->fetchColumn();

            // Puan hesaplama
            $puan = 10; // Normal katılım puanı
            if($katilim_sayisi == 1) {
                $puan += 20; // İlk katılım bonusu
            }

            // Puanı kaydet
            $puan_sql = "INSERT INTO puanlar (kullanici_id, puan, kazanilan_tarih) VALUES (:kullanici_id, :puan, NOW())";
            $puan_stmt = $db->prepare($puan_sql);
            $puan_stmt->execute([
                'kullanici_id' => $user_id,
                'puan' => $puan
            ]);

            // Bildirim ekle
            $bildirim_mesaji = "Tebrikler! Etkinliğe katılarak " . $puan . " puan kazandınız!";
            $bildirim_sql = "INSERT INTO bildirimler (kullanici_id, mesaj, icon, tarih) VALUES (:kullanici_id, :mesaj, :icon, NOW())";
            $bildirim_stmt = $db->prepare($bildirim_sql);
            $bildirim_stmt->execute([
                'kullanici_id' => $user_id,
                'mesaj' => $bildirim_mesaji,
                'icon' => 'fa-trophy'
            ]);

            $mesaj = "Etkinliğe başarıyla katıldınız! " . $puan . " puan kazandınız!";
        } else {
            $hata = "Katılım sırasında bir hata oluştu.";
        }
    } else {
        $hata = "Bu etkinliğe zaten katılmışsınız.";
    }
}

if ($etkinlik_id) {
    // Etkinlik detaylarını getir
    $sql = "SELECT * FROM etkinlikler WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute(['id' => $etkinlik_id]);
    $etkinlik = $stmt->fetch(PDO::FETCH_ASSOC);

    // Katılımcı sayısını getir
    $sql_katilimci = "SELECT COUNT(*) as katilimci_sayisi FROM katilimcilar WHERE etkinlik_id = :etkinlik_id";
    $stmt_katilimci = $db->prepare($sql_katilimci);
    $stmt_katilimci->execute(['etkinlik_id' => $etkinlik_id]);
    $katilimci_sayisi = $stmt_katilimci->fetch(PDO::FETCH_ASSOC)['katilimci_sayisi'];

    if(isset($_SESSION['user_id'])) {
        // Kullanıcının toplam puanını getir
        $puan_sql = "SELECT SUM(puan) as toplam_puan FROM puanlar WHERE kullanici_id = :kullanici_id";
        $puan_stmt = $db->prepare($puan_sql);
        $puan_stmt->execute(['kullanici_id' => $_SESSION['user_id']]);
        $toplam_puan = $puan_stmt->fetch(PDO::FETCH_ASSOC)['toplam_puan'];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Etkinlik Detayları - <?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?></title>
    <link rel="stylesheet" href="event.css">
</head>
<body>

    <!-- Navbar -->
    <header class="main-header">
        <?php include 'inc/header.php'; ?>
    </header>

    <?php if ($etkinlik): ?>
    <!-- Etkinlik Detayları ve Harita -->
    <div class="content-container">
        <!-- Etkinlik Bilgileri -->
        <div class="event-details-container">
            <?php if(isset($mesaj)): ?>
                <div class="alert alert-success"><?php echo $mesaj; ?></div>
            <?php endif; ?>
            <?php if(isset($hata)): ?>
                <div class="alert alert-danger"><?php echo $hata; ?></div>
            <?php endif; ?>
            
            <h2><?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?></h2>
            <p><strong>Tarih:</strong> <?php echo date('d F Y', strtotime($etkinlik['tarih'])); ?></p>
            <p><strong>Saat:</strong> <?php echo date('H:i', strtotime($etkinlik['saat'])); ?></p>
            <p><strong>Süre:</strong> <?php echo $etkinlik['etkinlik_suresi']; ?> dakika</p>
            <p><strong>Kategori:</strong> <?php echo htmlspecialchars($etkinlik['kategori']); ?></p>
            <p><strong>Açıklama:</strong> <?php echo nl2br(htmlspecialchars($etkinlik['aciklama'])); ?></p>
            <p><strong>Konum:</strong> <?php echo htmlspecialchars($etkinlik['konum']); ?></p>
            <p><strong>Katılımcı Sayısı:</strong> <?php echo $katilimci_sayisi; ?></p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <p><strong>Toplam Puanınız:</strong> <?php echo $toplam_puan ?? 0; ?></p>
                <form method="POST">
                    <button type="submit" name="katil" class="btn-join">Etkinliğe Katıl</button>
                </form>
            <?php else: ?>
                <p>Etkinliğe katılmak için lütfen <a href="giris.php">giriş yapın</a></p>
            <?php endif; ?>
        </div>

        <!-- Harita ve Rota Önerileri -->
        <div class="map-and-routes-container">
            <h3>Harita</h3>
            <div id="map" class="map-container"></div>
            <input type="hidden" id="eventLat" value="<?php echo htmlspecialchars($etkinlik['lat']); ?>">
            <input type="hidden" id="eventLng" value="<?php echo htmlspecialchars($etkinlik['lng']); ?>">
            <h3>Uygun Rota Önerisi</h3>
            <p id="route-suggestion">Konumunuzu paylaşarak size özel rota alabilirsiniz.</p>
            <button class="btn-route" onclick="getRoute()">Rota Oluştur</button>
        </div>
    </div>
    <?php else: ?>
        <div class="content-container">
            <p>Etkinlik bulunamadı.</p>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section about">
                <h3>Hakkımızda</h3>
                <p>Akıllı Etkinlik Planlama Platformu, kullanıcıların etkinlikleri kolayca keşfetmelerini ve katılmalarını sağlayan bir platformdur.</p>
            </div>

            <div class="footer-section social">
                <h3>Sosyal Medya</h3>
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2024 Akıllı Etkinlik Planlama Platformu. Tüm hakları saklıdır.
        </div>
    </footer>

    <!-- Google Maps API ve JavaScript -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCY0IXagPsFMKdfRwYBEJJQYcJsCehB1dw&libraries=places" async defer></script>
    <script>
        let map;
        let directionsService;
        let directionsRenderer;
        let eventMarker;

        // Sayfa yüklendiğinde haritayı başlat
        window.onload = function() {
            initMap();
        };

        function initMap() {
            // Harita servislerini başlat
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer();
            
            // Etkinlik koordinatlarını al
            const eventLat = parseFloat(document.getElementById('eventLat').value);
            const eventLng = parseFloat(document.getElementById('eventLng').value);
            const eventLocation = { lat: eventLat, lng: eventLng };

            // Haritayı oluştur
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: eventLocation
            });

            // Etkinlik konumuna marker ekle
            eventMarker = new google.maps.Marker({
                position: eventLocation,
                map: map,
                title: 'Etkinlik Konumu'
            });

            // Rota göstericiyi haritaya bağla
            directionsRenderer.setMap(map);
        }

        function getRoute() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        // Rota hesaplama
                        const request = {
                            origin: userLocation,
                            destination: eventMarker.getPosition(),
                            travelMode: google.maps.TravelMode.DRIVING
                        };

                        directionsService.route(request, (result, status) => {
                            if (status === 'OK') {
                                directionsRenderer.setDirections(result);
                                const route = result.routes[0].legs[0];
                                document.getElementById('route-suggestion').innerHTML = 
                                    `Mesafe: ${route.distance.text}<br>Tahmini Süre: ${route.duration.text}`;
                            } else {
                                document.getElementById('route-suggestion').innerHTML = 
                                    'Rota hesaplanamadı. Lütfen tekrar deneyin.';
                            }
                        });
                    },
                    () => {
                        document.getElementById('route-suggestion').innerHTML = 
                            'Konum erişimi reddedildi. Rota oluşturmak için konum izni vermeniz gerekmektedir.';
                    }
                );
            } else {
                document.getElementById('route-suggestion').innerHTML = 
                    'Tarayıcınız konum hizmetlerini desteklemiyor.';
            }
        }
    </script>
</body>
</html>
