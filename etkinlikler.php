<?php 
include 'inc/config.php';

// Zaman çakışması kontrolü fonksiyonu
function zamanCakismasiKontrol($user_id, $etkinlik_id, $db) {
    // Katılmak istenen etkinliğin zamanlarını al
    $sql = "SELECT tarih, saat FROM etkinlikler WHERE id = :etkinlik_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':etkinlik_id', $etkinlik_id);
    $stmt->execute();
    $yeni_etkinlik = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kullanıcının mevcut etkinliklerini al
    $sql = "SELECT e.tarih, e.saat, e.etkinlik_adi 
            FROM etkinlikler e
            INNER JOIN katilimcilar k ON e.id = k.etkinlik_id
            WHERE k.kullanici_id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $mevcut_etkinlikler = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cakismalar = array();
    foreach ($mevcut_etkinlikler as $etkinlik) {
        if ($yeni_etkinlik['tarih'] == $etkinlik['tarih'] && 
            $yeni_etkinlik['saat'] == $etkinlik['saat']) {
            $cakismalar[] = $etkinlik;
        }
    }

    return $cakismalar;
}

// Alternatif etkinlik önerileri fonksiyonu
function alternatifEtkinlikOner($user_id, $etkinlik_kategori, $db) {
    $sql = "SELECT * FROM etkinlikler e 
            WHERE e.kategori = :kategori 
            AND e.id NOT IN (
                SELECT etkinlik_id FROM katilimcilar WHERE kullanici_id = :user_id
            )
            AND e.durum = 1
            ORDER BY e.tarih ASC LIMIT 3";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':kategori', $etkinlik_kategori);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Kullanıcı giriş kontrolü
if(!isset($_SESSION['user_id'])) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Etkinliğe katılmak için giriş yapmalısınız!'
        }).then(() => {
            window.location.href = 'login.php';
        });
    </script>";
    exit();
}

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Etkinlikler - Akıllı Etkinlik Planlama Platformu</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyACNdPibs__Qkt4iL_DFFqz17aWITD3Hg8&libraries=places"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
</head>

<body>

<?php

// Etkinliğe katılma işlemi
if(isset($_GET['katil']) && isset($_GET['etkinlik_id'])) {
    $etkinlik_id = $_GET['etkinlik_id'];
    $user_id = $_SESSION['user_id'];

    // Önce kullanıcının bu etkinliğe zaten katılıp katılmadığını kontrol et
    $kontrol_sql = "SELECT * FROM katilimcilar WHERE etkinlik_id = :etkinlik_id AND kullanici_id = :user_id";
    $kontrol_stmt = $db->prepare($kontrol_sql);
    $kontrol_stmt->bindParam(':etkinlik_id', $etkinlik_id);
    $kontrol_stmt->bindParam(':user_id', $user_id);
    $kontrol_stmt->execute();
    
    if($kontrol_stmt->rowCount() > 0) {
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Uyarı!',
                text: 'Bu etkinliğe zaten katılmışsınız!'
            });
        </script>";
        exit();
    }

    // Zaman çakışması kontrolü
    $cakismalar = zamanCakismasiKontrol($user_id, $etkinlik_id, $db);

    if(empty($cakismalar)) {
        // Çakışma yoksa etkinliğe katıl
        $sql = "INSERT INTO katilimcilar (etkinlik_id, kullanici_id) VALUES (:etkinlik_id, :user_id)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':etkinlik_id', $etkinlik_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if($stmt->execute()) {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı!',
                    text: 'Etkinliğe başarıyla katıldınız!'
                }).then(() => {
                    window.location.href = 'etkinlikler.php';
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Etkinliğe katılırken bir hata oluştu!'
                });
            </script>";
        }
    } else {
        // Çakışma varsa alternatif etkinlikler öner
        $etkinlik_sql = "SELECT kategori FROM etkinlikler WHERE id = :etkinlik_id";
        $stmt = $db->prepare($etkinlik_sql);
        $stmt->bindParam(':etkinlik_id', $etkinlik_id);
        $stmt->execute();
        $etkinlik = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $alternatifler = alternatifEtkinlikOner($user_id, $etkinlik['kategori'], $db);
        
        $cakismaMetni = "Bu etkinliğe katılamazsınız. Aşağıdaki etkinliklerle çakışma var:\n\n";
        foreach($cakismalar as $cakisma) {
            $cakismaMetni .= "- {$cakisma['etkinlik_adi']} ({$cakisma['baslangic_zamani']} - {$cakisma['bitis_zamani']})\n";
        }
        
        if(!empty($alternatifler)) {
            $cakismaMetni .= "\nÖnerilen alternatif etkinlikler:\n";
            foreach($alternatifler as $alternatif) {
                $cakismaMetni .= "- {$alternatif['etkinlik_adi']} ({$alternatif['tarih']} {$alternatif['saat']})\n";
            }
        }
        
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Çakışma!',
                text: `$cakismaMetni`
            });
        </script>";
    }
}

// Etkinlik ekleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $etkinlik_adi = $_POST['event-name'];
    $tarih = $_POST['event-date'];
    $saat = $_POST['event-time']; 
    $aciklama = $_POST['event-description'];
    $konum = $_POST['event-location'];
    $kategori = $_POST['event-category'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $durum = 0; // Onay bekliyor durumu
    $olusturan_id = $_SESSION['user_id']; // Oturum açmış kullanıcının ID'si
    
    // Etkinlik süresi varsayılan olarak 2 saat
    $baslangic_zamani = $tarih . ' ' . $saat;
    $bitis_zamani = date('Y-m-d H:i:s', strtotime($baslangic_zamani . ' +2 hours'));
    
    $sql = "INSERT INTO etkinlikler (etkinlik_adi, tarih, saat, aciklama, konum, kategori, lat, lng, durum, baslangic_zamani, bitis_zamani, olusturan_id) 
            VALUES (:etkinlik_adi, :tarih, :saat, :aciklama, :konum, :kategori, :lat, :lng, :durum, :baslangic_zamani, :bitis_zamani, :olusturan_id)";
            
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':etkinlik_adi', $etkinlik_adi);
    $stmt->bindParam(':tarih', $tarih);
    $stmt->bindParam(':saat', $saat);
    $stmt->bindParam(':aciklama', $aciklama);
    $stmt->bindParam(':konum', $konum);
    $stmt->bindParam(':kategori', $kategori);
    $stmt->bindParam(':lat', $lat);
    $stmt->bindParam(':lng', $lng);
    $stmt->bindParam(':durum', $durum);
    $stmt->bindParam(':baslangic_zamani', $baslangic_zamani);
    $stmt->bindParam(':bitis_zamani', $bitis_zamani);
    $stmt->bindParam(':olusturan_id', $olusturan_id);
    
    if ($stmt->execute()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı!',
                    text: 'Etkinlik başarıyla eklendi! Admin onayından sonra yayınlanacaktır.'
                }).then(() => {
                    window.location.href = 'etkinlikler.php';
                });
            });
        </script>";
        exit();
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Etkinlik eklenirken bir hata oluştu!'
                });
            });
        </script>";
    }
}

?>

    <!-- Navbar -->
    <header class="main-header">
        <?php include 'inc/header.php'; ?>
    </header>

    <?php

// Etkinlik ekleme işlemi
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $etkinlik_adi = $_POST['event-name'];
        $tarih = $_POST['event-date'];
        $saat = $_POST['event-time']; 
        $aciklama = $_POST['event-description'];
        $konum = $_POST['event-location'];
        $kategori = $_POST['event-category'];
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];
        $durum = 0; // Onay bekliyor durumu
        // Etkinlik süresi varsayılan olarak 2 saat
        $baslangic_zamani = $tarih . ' ' . $saat;
        $bitis_zamani = date('Y-m-d H:i:s', strtotime($baslangic_zamani . ' +2 hours'));
        
        $sql = "INSERT INTO etkinlikler (etkinlik_adi, tarih, saat, aciklama, konum, kategori, lat, lng, durum, baslangic_zamani, bitis_zamani) 
                VALUES (:etkinlik_adi, :tarih, :saat, :aciklama, :konum, :kategori, :lat, :lng, :durum, :baslangic_zamani, :bitis_zamani)";
                
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':etkinlik_adi', $etkinlik_adi);
        $stmt->bindParam(':tarih', $tarih);
        $stmt->bindParam(':saat', $saat);
        $stmt->bindParam(':aciklama', $aciklama);
        $stmt->bindParam(':konum', $konum);
        $stmt->bindParam(':kategori', $kategori);
        $stmt->bindParam(':lat', $lat);
        $stmt->bindParam(':lng', $lng);
        $stmt->bindParam(':durum', $durum);
        $stmt->bindParam(':baslangic_zamani', $baslangic_zamani);
        $stmt->bindParam(':bitis_zamani', $bitis_zamani);
        
        if ($stmt->execute()) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Etkinlik başarıyla eklendi! Admin onayından sonra yayınlanacaktır.'
                    }).then(() => {
                        window.location.href = 'etkinlikler.php';
                    });
                });
            </script>";
            exit();
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'Etkinlik eklenirken bir hata oluştu!'
                    });
                });
            </script>";
        }
    }

try {
    // Etkinlikleri veritabanından çek
    $sql = "SELECT * FROM etkinlikler ORDER BY tarih DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'Veritabanı hatası: " . $e->getMessage() . "'
            });
        });
    </script>";
}

?>
<?php if(isset($_SESSION['user_id'])) { ?>
    <!-- Etkinlik Ekleme Bölümü -->
    <section class="add-event">
        <h2>Yeni Etkinlik Ekle</h2>
        <form class="event-form" method="POST" action="">
            <label for="event-name">Etkinlik Adı:</label>
            <input type="text" id="event-name" name="event-name" required>

            <label for="event-date">Tarih:</label>
            <input type="date" id="event-date" name="event-date" required>

            <label for="event-time">Saat:</label>
            <input type="time" id="event-time" name="event-time" required>

            <label for="event-description">Açıklama:</label>
            <textarea id="event-description" name="event-description" rows="4" required></textarea>

            <label for="event-location">Konum:</label>
            <input type="text" id="event-location" name="event-location" required>
            <div id="map" style="height: 300px; width: 100%; margin: 10px 0;"></div>
            <input type="hidden" id="lat" name="lat">
            <input type="hidden" id="lng" name="lng">

            <label for="event-category">Kategori:</label>
            <select id="event-category" name="event-category" required>
                <option value="">Kategori Seçin</option>
                <option value="Spor">Spor</option>
                <option value="Sanat">Sanat</option>
                <option value="Teknoloji">Teknoloji</option>
                <option value="Eğitim">Eğitim</option>
                <option value="Müzik">Müzik</option>
                <option value="Sağlık">Sağlık</option>
                <option value="Gönüllülük">Gönüllülük</option>
                <option value="Doğa">Doğa</option>
                <option value="Yemek">Yemek</option>
                <option value="Eğlence">Eğlence</option>
                <option value="Diğer">Diğer</option>
            </select>

            <button type="submit" class="btn-submit">Etkinliği Ekle</button>
        </form>
    </section>
    <?php } ?>

    <!-- Tüm Etkinlikler Listesi -->
    <section class="all-events">
        <h2>Tüm Etkinlikler</h2>
        <div class="event-cards">
            <?php
            if (count($result) > 0) {
                foreach($result as $row) {
                    if($row['durum'] == 1) { // Sadece onaylanmış etkinlikleri göster
                        echo '<div class="event-card">';
                        echo '<h3>' . htmlspecialchars($row['etkinlik_adi']) . '</h3>';
                        echo '<p class="event-date"><i class="far fa-calendar"></i> ' . date('d.m.Y', strtotime($row['tarih'])) . '</p>';
                        echo '<p class="event-time"><i class="far fa-clock"></i> ' . $row['saat'] . '</p>';
                        echo '<p class="event-location"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['konum']) . '</p>';
                        echo '<div class="event-map" id="map_'.$row['id'].'" style="height: 200px; width: 100%; margin: 10px 0;"></div>';
                        echo '<p class="event-category"><i class="fas fa-tag"></i> ' . htmlspecialchars($row['kategori']) . '</p>';
                        echo '<p class="event-description">' . htmlspecialchars($row['aciklama']) . '</p>';
                        if(isset($_SESSION['user_id'])) {
                            echo '<button onclick="katilEtkinlik('.$row['id'].')" class="btn-join">Katıl</button>';
                        }
                        echo '<a href="etkinlik.php?id=' . $row['id'] . '" class="btn-details">İncele</a>';
                        echo '</div>';
                        
                        // Her etkinlik için harita oluştur
                        echo "<script>
                            var map_".$row['id']." = new google.maps.Map(document.getElementById('map_".$row['id']."'), {
                                center: {lat: ".$row['lat'].", lng: ".$row['lng']."},
                                zoom: 15
                            });
                            new google.maps.Marker({
                                position: {lat: ".$row['lat'].", lng: ".$row['lng']."},
                                map: map_".$row['id'].",
                                title: '".htmlspecialchars($row['etkinlik_adi'])."'
                            });
                        </script>";
                    }
                }
            } else {
                echo '<p class="no-events">Henüz etkinlik bulunmamaktadır.</p>';
            }
            ?>
        </div>
    </section>

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

    <script>
    // Google Maps Autocomplete ve harita işlemleri
    function initMap() {
        var map = new google.maps.Map(document.getElementById('map'), {
            center: {lat: 41.0082, lng: 28.9784}, // İstanbul merkezi
            zoom: 13
        });

        var input = document.getElementById('event-location');
        var autocomplete = new google.maps.places.Autocomplete(input);
        var marker = new google.maps.Marker({
            map: map
        });

        autocomplete.bindTo('bounds', map);
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (!place.geometry) {
                return;
            }

            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);
            }

            marker.setPosition(place.geometry.location);
            document.getElementById('lat').value = place.geometry.location.lat();
            document.getElementById('lng').value = place.geometry.location.lng();
        });
    }

    // Sayfa yüklendiğinde haritayı başlat
    google.maps.event.addDomListener(window, 'load', initMap);

    function katilEtkinlik(etkinlikId) {
        window.location.href = 'etkinlikler.php?katil=1&etkinlik_id=' + etkinlikId;
    }
    </script>

</body>
</html>