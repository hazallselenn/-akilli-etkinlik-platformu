<?php 
include 'inc/config.php';

// Kullanıcının ilgi alanlarını çek
$ilgi_alanlari = [];
if(isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT ilgi_alanlari FROM kullanicilar WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if($user && $user['ilgi_alanlari']) {
        // JSON formatındaki ilgi alanlarını diziye çevir
        $ilgi_alanlari = json_decode($user['ilgi_alanlari'], true);
    }
}

// Önerilen etkinlikleri çek
$onerilen_etkinlikler = [];
if(!empty($ilgi_alanlari)) {
    $placeholders = str_repeat('?,', count($ilgi_alanlari) - 1) . '?';
    $sql = "SELECT * FROM etkinlikler WHERE kategori IN ($placeholders) AND tarih >= CURDATE() AND durum = 1 ORDER BY tarih LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute($ilgi_alanlari);
    $onerilen_etkinlikler = $stmt->fetchAll();
}

// Popüler etkinlikleri çek
$stmt = $db->prepare("SELECT e.*, COUNT(k.id) as katilimci_sayisi 
                     FROM etkinlikler e 
                     LEFT JOIN katilimcilar k ON e.id = k.etkinlik_id 
                     WHERE e.tarih >= CURDATE() AND e.durum = 1
                     GROUP BY e.id 
                     ORDER BY katilimci_sayisi DESC 
                     LIMIT 5");
$stmt->execute();
$populer_etkinlikler = $stmt->fetchAll();

// AJAX araması için
if(isset($_POST['aranan'])) {
    $aranan = '%' . $_POST['aranan'] . '%';
    $stmt = $db->prepare("SELECT * FROM etkinlikler WHERE (etkinlik_adi LIKE ? OR aciklama LIKE ?) AND tarih >= CURDATE() AND durum = 1 LIMIT 10");
    $stmt->execute([$aranan, $aranan]);
    $sonuclar = $stmt->fetchAll();
    
    $html = '';
    foreach($sonuclar as $sonuc) {
        $html .= '<div class="search-result" onclick="window.location.href=\'etkinlik.php?id=' . $sonuc['id'] . '\'">';
        $html .= '<h3>' . htmlspecialchars($sonuc['etkinlik_adi']) . '</h3>';
        $html .= '<p>' . htmlspecialchars($sonuc['aciklama']) . '</p>';
        $html .= '<p class="event-details">';
        $html .= '<i class="fas fa-calendar"></i> ' . date('d.m.Y', strtotime($sonuc['tarih'])) . ' ';
        $html .= '<i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($sonuc['konum']);
        $html .= '</p></div>';
    }
    
    echo $html;
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Akıllı Etkinlik Planlama Platformu</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyACNdPibs__Qkt4iL_DFFqz17aWITD3Hg8&libraries=places"></script>
</head>

<body>
    <!-- Navbar -->
    <header class="main-header">
        <?php include 'inc/header.php'; ?>
    </header>

    <!-- Tanıtım Bölümü -->
    <section class="intro-section">
        <div class="intro-overlay">
            <h1>AKILLI ETKİNLİK PLANLAMA PLATFORMU</h1>
            <p>Etkinlikleriniz için doğru insanları, uygun zamanları ve ideal mekanları bir araya getiren, kişisel tercihlerinize göre öneriler sunan ve gerçek zamanlı etkileşim sağlayan akıllı bir platform. Planlamayı kolaylaştırın, katılımı artırın, etkinliklerinizi benzersiz bir deneyime dönüştürün!</p>
        </div>
    </section>

    <!-- Arama Bölümü -->
    <section class="search-section">
        <div class="search-container">
            <input type="text" id="etkinlik-ara" placeholder="Etkinlik ara..." class="search-input">
            <button class="search-button"><i class="fas fa-search"></i></button>
        </div>
    </section>

    <!-- Arama Sonuçları -->
    <section id="search-results" class="search-results hidden">
        <div id="results-container" class="results-container">
            <!-- JavaScript ile doldurulacak -->
        </div>
    </section>

    <!-- Önerilen Etkinlikler -->
    <section class="recommended-events">
        <h2>Önerilen Etkinlikler</h2>
        <div class="event-cards">
            <?php if(empty($onerilen_etkinlikler)): ?>
                <p class="no-events">İlgi alanlarınıza uygun etkinlik bulunamadı.</p>
            <?php else: ?>
                <?php foreach($onerilen_etkinlikler as $etkinlik): ?>
                <div class="event-card">
                    <h3><?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?></h3>
                    <p class="event-date"><i class="far fa-calendar"></i> <?php echo date('d.m.Y', strtotime($etkinlik['tarih'])); ?></p>
                    <p class="event-time"><i class="far fa-clock"></i> <?php echo $etkinlik['saat']; ?></p>
                    <p class="event-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($etkinlik['konum']); ?></p>
                    <div class="event-map" id="map_<?php echo $etkinlik['id']; ?>" style="height: 200px; width: 100%; margin: 10px 0;"></div>
                    <p class="event-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($etkinlik['kategori']); ?></p>
                    <p class="event-description"><?php echo htmlspecialchars($etkinlik['aciklama']); ?></p>
                    <a href="etkinlik.php?id=<?php echo $etkinlik['id']; ?>" class="btn-join">İncele</a>
                </div>
                <script>
                    var map_<?php echo $etkinlik['id']; ?> = new google.maps.Map(document.getElementById('map_<?php echo $etkinlik['id']; ?>'), {
                        center: {lat: <?php echo $etkinlik['lat']; ?>, lng: <?php echo $etkinlik['lng']; ?>},
                        zoom: 15
                    });
                    new google.maps.Marker({
                        position: {lat: <?php echo $etkinlik['lat']; ?>, lng: <?php echo $etkinlik['lng']; ?>},
                        map: map_<?php echo $etkinlik['id']; ?>,
                        title: '<?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?>'
                    });
                </script>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Popüler Etkinlikler -->
    <section class="popular-events">
        <h2>Popüler Etkinlikler</h2>
        <div class="event-cards">
            <?php foreach($populer_etkinlikler as $etkinlik): ?>
            <div class="event-card">
                <h3><?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?></h3>
                <p class="event-date"><i class="far fa-calendar"></i> <?php echo date('d.m.Y', strtotime($etkinlik['tarih'])); ?></p>
                <p class="event-time"><i class="far fa-clock"></i> <?php echo $etkinlik['saat']; ?></p>
                <p class="event-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($etkinlik['konum']); ?></p>
                <div class="event-map" id="map_pop_<?php echo $etkinlik['id']; ?>" style="height: 200px; width: 100%; margin: 10px 0;"></div>
                <p class="event-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($etkinlik['kategori']); ?></p>
                <p class="event-description"><?php echo htmlspecialchars($etkinlik['aciklama']); ?></p>
                <p class="event-participants"><i class="fas fa-users"></i> <?php echo $etkinlik['katilimci_sayisi']; ?> katılımcı</p>
                <a href="etkinlik.php?id=<?php echo $etkinlik['id']; ?>" class="btn-join">İncele</a>
            </div>
            <script>
                var map_pop_<?php echo $etkinlik['id']; ?> = new google.maps.Map(document.getElementById('map_pop_<?php echo $etkinlik['id']; ?>'), {
                    center: {lat: <?php echo $etkinlik['lat']; ?>, lng: <?php echo $etkinlik['lng']; ?>},
                    zoom: 15
                });
                new google.maps.Marker({
                    position: {lat: <?php echo $etkinlik['lat']; ?>, lng: <?php echo $etkinlik['lng']; ?>},
                    map: map_pop_<?php echo $etkinlik['id']; ?>,
                    title: '<?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?>'
                });
            </script>
            <?php endforeach; ?>
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
    $(document).ready(function() {
        $('#etkinlik-ara').on('keyup', function() {
            var aranan = $(this).val();
            if(aranan.length > 2) {
                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: {aranan: aranan},
                    success: function(response) {
                        $('#search-results').removeClass('hidden');
                        $('#results-container').html(response);
                    }
                });
            } else {
                $('#search-results').addClass('hidden');
            }
        });
    });
    </script>

</body>
</html>