<?php
include 'inc/config.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit;
}

// Kullanıcı bilgilerini veritabanından çek
$kullanici_id = $_SESSION['user_id'];
$sql = "SELECT k.*, 
        GROUP_CONCAT(DISTINCT e.etkinlik_adi) as katildigi_etkinlikler,
        COUNT(DISTINCT p.id) as toplam_puan
        FROM kullanicilar k
        LEFT JOIN katilimcilar ka ON k.id = ka.kullanici_id
        LEFT JOIN etkinlikler e ON ka.etkinlik_id = e.id 
        LEFT JOIN puanlar p ON k.id = p.kullanici_id
        WHERE k.id = ?
        GROUP BY k.id";
$stmt = $db->prepare($sql);
$stmt->execute([$kullanici_id]);
$kullanici = $stmt->fetch(PDO::FETCH_ASSOC);

// İlgi alanlarını güncelle
if (isset($_POST['guncelle_ilgi_alanlari'])) {
    $ilgi_alanlari = isset($_POST['interests']) ? json_encode($_POST['interests']) : '[]';

    $sql = "UPDATE kullanicilar SET ilgi_alanlari = ? WHERE id = ?";
    $stmt = $db->prepare($sql);

    if ($stmt->execute([$ilgi_alanlari, $kullanici_id])) {
        echo json_encode(['status' => 'success', 'message' => 'İlgi alanları başarıyla güncellendi']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Güncelleme sırasında hata oluştu']);
        exit;
    }
}

// Şifre güncelleme
if (isset($_POST['sifre_guncelle'])) {
    $yeni_sifre = $_POST['yeni_sifre'];
    $hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);

    $sql = "UPDATE kullanicilar SET sifre = ? WHERE id = ?";
    $stmt = $db->prepare($sql);

    if ($stmt->execute([$hash, $kullanici_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Şifre başarıyla güncellendi']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Şifre güncellenirken hata oluştu']);
    }
    exit;
}

// Katıldığı etkinlikleri getir
$sql = "SELECT e.* FROM etkinlikler e 
        INNER JOIN katilimcilar k ON e.id = k.etkinlik_id 
        WHERE k.kullanici_id = ?
        ORDER BY e.tarih DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$kullanici_id]);
$katildigi_etkinlikler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Puanları getir
$sql = "SELECT * FROM puanlar WHERE kullanici_id = ? ORDER BY kazanilan_tarih DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$kullanici_id]);
$puanlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <title>Kullanıcı Profili</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="user_profile.css">
</head>

<body>

    <!-- Navbar -->
    <header class="main-header">
        <?php include 'inc/header.php'; ?>
    </header>

    <!-- Ana Container -->
    <div class="main-container" style="margin-top: 100px;">

        <!-- Yan Menü -->
        <nav class="sidebar">
            <ul>
                <li data-section="profile-update" class="active">Profil Bilgilerimi Güncelle</li>
                <li data-section="password-reset">Şifre Sıfırla</li>
                <li data-section="interest-update">İlgi Alanı Güncelle</li>
                <li data-section="notifications">Bildirimlerim</li>
                <li data-section="past-events">Geçmişte Katıldığım Etkinlikler</li>
                <li data-section="my-events">Oluşturduğum Etkinlikler</li>
                <li data-section="points">Puanlarım ve Başarılarım</li>
                <li data-section="feedback">Geri Bildirim Gönder</li>
            </ul>
        </nav>


        <?php
        // Profil güncelleme işlemi
        if (isset($_POST['guncelle'])) {
            $ad = guvenli_giris($_POST['ad']);
            $soyad = guvenli_giris($_POST['soyad']);
            $email = guvenli_giris($_POST['email']);
            $telefon = guvenli_giris($_POST['telefon']);
            $dogum_tarihi = guvenli_giris($_POST['dogum_tarihi']);
            $cinsiyet = guvenli_giris($_POST['cinsiyet']);

            $guncelleme_basarili = true;
            $hata_mesaji = '';

            // Profil fotoğrafı yükleme
            if (isset($_FILES['profil_fotografi']) && $_FILES['profil_fotografi']['error'] == 0) {
                $hedef_klasor = "uploads/profile_pictures/";

                if (!file_exists($hedef_klasor)) {
                    mkdir($hedef_klasor, 0777, true);
                }

                $dosya_uzantisi = strtolower(pathinfo($_FILES['profil_fotografi']['name'], PATHINFO_EXTENSION));
                $izin_verilen_uzantilar = array('jpg', 'jpeg', 'png', 'gif');

                if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
                    $dosya_adi = uniqid() . '.' . $dosya_uzantisi;
                    $hedef_dosya = $hedef_klasor . $dosya_adi;

                    if (move_uploaded_file($_FILES['profil_fotografi']['tmp_name'], $hedef_dosya)) {
                        // Eski fotoğrafı sil
                        if (!empty($kullanici['profil_fotografi']) && $kullanici['profil_fotografi'] != 'default.jpg') {
                            $eski_foto = $hedef_klasor . $kullanici['profil_fotografi'];
                            if (file_exists($eski_foto)) {
                                unlink($eski_foto);
                            }
                        }

                        $sql = "UPDATE kullanicilar SET profil_fotografi = ? WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        if (!$stmt->execute([$dosya_adi, $kullanici_id])) {
                            $guncelleme_basarili = false;
                            $hata_mesaji = 'Profil fotoğrafı güncellenirken hata oluştu.';
                        }
                    } else {
                        $guncelleme_basarili = false;
                        $hata_mesaji = 'Dosya yüklenirken hata oluştu.';
                    }
                } else {
                    $guncelleme_basarili = false;
                    $hata_mesaji = 'Geçersiz dosya formatı. Sadece JPG, JPEG, PNG ve GIF dosyaları kabul edilir.';
                }
            }

            // Diğer bilgileri güncelle
            if ($guncelleme_basarili) {
                $sql = "UPDATE kullanicilar SET 
                ad = ?, 
                soyad = ?, 
                email = ?, 
                telefon = ?, 
                dogum_tarihi = ?, 
                cinsiyet = ? 
                WHERE id = ?";

                $stmt = $db->prepare($sql);

                if ($stmt->execute([$ad, $soyad, $email, $telefon, $dogum_tarihi, $cinsiyet, $kullanici_id])) {
                    // Kullanıcı bilgilerini yeniden çek
                    $sql = "SELECT * FROM kullanicilar WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$kullanici_id]);
                    $kullanici = $stmt->fetch(PDO::FETCH_ASSOC);

                    echo "<script>
                Swal.fire({
                    title: 'Başarılı!',
                    text: 'Profil başarıyla güncellendi',
                    icon: 'success',
                    confirmButtonText: 'Tamam'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'profilim.php';
                    }
                });
            </script>";
                } else {
                    echo "<script>
                Swal.fire({
                    title: 'Hata!',
                    text: 'Güncelleme sırasında bir hata oluştu',
                    icon: 'error',
                    confirmButtonText: 'Tamam'
                });
            </script>";
                }
            } else {
                echo "<script>
            Swal.fire({
                title: 'Hata!',
                text: '" . $hata_mesaji . "',
                icon: 'error',
                confirmButtonText: 'Tamam'
            });
        </script>";
            }
        }

        ?>
        <!-- İçerik Bölümleri -->
        <section class="content">

            <!-- Oluşturduğum Etkinlikler Bölümü -->
            <?php
            // Etkinlik silme işlemi
            if (isset($_POST['etkinlik_sil'])) {
                $silinecek_id = $_POST['etkinlik_id'];

                try {
                    // Önce ilişkili mesajları sil
                    $stmt = $db->prepare("DELETE FROM mesajlar WHERE etkinlik_id = ?");
                    $stmt->execute([$silinecek_id]);

                    // Sonra etkinliği sil
                    $stmt = $db->prepare("DELETE FROM etkinlikler WHERE id = ? AND olusturan_id = ?");
                    if ($stmt->execute([$silinecek_id, $kullanici_id])) {
                        echo "<script>
                            Swal.fire({
                                title: 'Başarılı!',
                                text: 'Etkinlik başarıyla silindi',
                                icon: 'success',
                                confirmButtonText: 'Tamam'
                            });
                        </script>";
                    } else {
                        echo "<script>
                            Swal.fire({
                                title: 'Hata!', 
                                text: 'Etkinlik silinirken bir hata oluştu',
                                icon: 'error',
                                confirmButtonText: 'Tamam'
                            });
                        </script>";
                    }
                } catch(PDOException $e) {
                    echo "<script>
                        Swal.fire({
                            title: 'Hata!',
                            text: 'Etkinlik silinirken bir hata oluştu: " . $e->getMessage() . "',
                            icon: 'error',
                            confirmButtonText: 'Tamam'
                        });
                    </script>";
                }
            }

            // Etkinlik güncelleme işlemi  
            if (isset($_POST['etkinlik_guncelle'])) {
                $guncellenecek_id = $_POST['etkinlik_id'];
                $etkinlik_adi = guvenli_giris($_POST['etkinlik_adi']);
                $aciklama = guvenli_giris($_POST['aciklama']);
                $tarih = guvenli_giris($_POST['tarih']);
                $konum = guvenli_giris($_POST['konum']);

                $stmt = $db->prepare("UPDATE etkinlikler SET etkinlik_adi = ?, aciklama = ?, tarih = ?, konum = ? WHERE id = ? AND olusturan_id = ?");
                if ($stmt->execute([$etkinlik_adi, $aciklama, $tarih, $konum, $guncellenecek_id, $kullanici_id])) {
                    echo "<script>
                        Swal.fire({
                            title: 'Başarılı!',
                            text: 'Etkinlik başarıyla güncellendi',
                            icon: 'success',
                            confirmButtonText: 'Tamam'
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            title: 'Hata!',
                            text: 'Etkinlik güncellenirken bir hata oluştu',
                            icon: 'error',
                            confirmButtonText: 'Tamam'
                        });
                    </script>";
                }
            }
            ?>

            <div id="my-events" class="section">
                <h2>Oluşturduğum Etkinlikler</h2>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <style>
                .modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.7);
                }

                .modal-content {
                    background-color: #fff;
                    margin: 5% auto;
                    padding: 30px;
                    border-radius: 15px;
                    width: 90%;
                    max-width: 800px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                    animation: modalOpen 0.3s ease-out;
                }

                @keyframes modalOpen {
                    from {transform: scale(0.7); opacity: 0;}
                    to {transform: scale(1); opacity: 1;}
                }

                .modal-header {
                    border-bottom: 2px solid #f0f0f0;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }

                .modal-header h5 {
                    font-size: 24px;
                    color: #333;
                    margin: 0;
                }

                .close {
                    position: absolute;
                    right: 25px;
                    top: 25px;
                    font-size: 35px;
                    transition: all 0.2s;
                }

                .close:hover {
                    color: #e74c3c;
                    transform: scale(1.1);
                }

                .form-group {
                    margin-bottom: 25px;
                }

                .form-group label {
                    display: block;
                    font-size: 16px;
                    color: #555;
                    margin-bottom: 10px;
                    font-weight: 500;
                }

                .form-control {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #ddd;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: all 0.3s;
                }

                .form-control:focus {
                    border-color: #3498db;
                    box-shadow: 0 0 8px rgba(52,152,219,0.3);
                    outline: none;
                }

                textarea.form-control {
                    min-height: 120px;
                    resize: vertical;
                }

                .modal-footer {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 2px solid #f0f0f0;
                }

                .btn-primary, .btn-secondary {
                    padding: 12px 25px;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 500;
                    transition: all 0.3s;
                }

                .btn-primary {
                    background-color: #3498db;
                    color: white;
                    border: none;
                }

                .btn-primary:hover {
                    background-color: #2980b9;
                    transform: translateY(-2px);
                }

                .btn-secondary {
                    background-color: #95a5a6;
                    color: white;
                    margin-right: 15px;
                    border: none;
                }

                .btn-secondary:hover {
                    background-color: #7f8c8d;
                    transform: translateY(-2px);
                }
                </style>

                <script>
                function openModal(id) {
                    document.getElementById('modal_' + id).style.display = 'block';
                    document.body.style.overflow = 'hidden';
                }

                function closeModal(id) {
                    document.getElementById('modal_' + id).style.display = 'none';
                    document.body.style.overflow = 'auto';
                }

                window.onclick = function(event) {
                    if (event.target.classList.contains('modal')) {
                        event.target.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                }
                </script>

                <div class="my-events-container">
                    <?php
                    $stmt = $db->prepare("SELECT * FROM etkinlikler WHERE olusturan_id = ? ORDER BY tarih DESC");
                    $stmt->execute([$kullanici_id]);
                    $etkinliklerim = $stmt->fetchAll();

                    if (count($etkinliklerim) > 0) {
                        foreach ($etkinliklerim as $etkinlik) {
                    ?>
                            <div class="event-card">
                                <h3><?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?></h3>
                                <p><?php echo htmlspecialchars($etkinlik['aciklama']); ?></p>
                                <div class="event-details">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($etkinlik['tarih'])); ?></span>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($etkinlik['konum']); ?></span>
                                </div>
                                <div class="event-actions">
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="etkinlik_id" value="<?php echo $etkinlik['id']; ?>">
                                        <button type="button" class="edit-btn" onclick="openModal(<?php echo $etkinlik['id']; ?>)">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </button>
                                        <button type="submit" name="etkinlik_sil" class="delete-btn" onclick="return confirm('Bu etkinliği silmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-trash"></i> Sil
                                        </button>
                                    </form>
                                </div>

                            </div>
                    <?php
                        }
                    } else {
                        echo '<p class="no-events">Henüz etkinlik oluşturmadınız.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Profil Bilgilerimi Güncelle -->
            <div id="profile-update" class="section active">
                <h2>Profil Bilgilerimi Güncelle</h2>
                <form id="profile-update-form" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Kullanıcı Adı:</label>
                        <input type="text" value="<?php echo guvenli_cikti($kullanici['kullanici_adi']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Ad:</label>
                        <input type="text" name="ad" value="<?php echo guvenli_cikti($kullanici['ad']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Soyad:</label>
                        <input type="text" name="soyad" value="<?php echo guvenli_cikti($kullanici['soyad']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Doğum Tarihi:</label>
                        <input type="date" name="dogum_tarihi" value="<?php echo $kullanici['dogum_tarihi']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Cinsiyet:</label>
                        <select name="cinsiyet" required>
                            <option value="Erkek" <?php echo ($kullanici['cinsiyet'] == 'Erkek') ? 'selected' : ''; ?>>Erkek</option>
                            <option value="Kadın" <?php echo ($kullanici['cinsiyet'] == 'Kadın') ? 'selected' : ''; ?>>Kadın</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>E-posta Adresi:</label>
                        <input type="email" name="email" value="<?php echo guvenli_cikti($kullanici['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Telefon Numarası:</label>
                        <input type="tel" name="telefon" value="<?php echo guvenli_cikti($kullanici['telefon']); ?>" required>
                    </div>
                    <div class="form-group photo-group">
                        <label>Profil Fotoğrafı:</label>
                        <div class="photo-display">
                            <img src="uploads/profile_pictures/<?php echo $kullanici['profil_fotografi'] ? guvenli_cikti($kullanici['profil_fotografi']) : 'default.jpg'; ?>" alt="Profil Fotoğrafı" id="profile-photo-preview">
                            <input type="file" name="profil_fotografi" id="profile-photo-input" accept="image/*" onchange="updatePhotoPreview(event)">
                            <button type="button" class="edit-photo-btn" onclick="document.getElementById('profile-photo-input').click()">Fotoğraf Seç</button>
                        </div>
                    </div>
                    <button type="submit" name="guncelle" class="save-btn">Kaydet</button>
                </form>
            </div>

            <!-- Diğer İçerik Bölümleri -->
            <div id="password-reset" class="section">
                <h2><i class="fas fa-lock"></i> Yeni Şifre Belirle</h2>
                <form id="reset-password-form">
                    <div class="form-group">
                        <label for="new-password">Yeni Şifre</label>
                        <input type="password" id="new-password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Şifreyi Onayla</label>
                        <input type="password" id="confirm-password" required>
                    </div>
                    <button type="submit" class="reset-btn">Şifreyi Sıfırla</button>
                </form>
            </div>

            <div id="interest-update" class="section">
                <h2>İlgi Alanı Güncelle</h2>
                <form id="interest-update-form">
                    <p class="section-description">Mevcut ilgi alanlarınız:</p>
                    <div class="current-interests">
                        <?php
                        $ilgi_alanlari = json_decode($kullanici['ilgi_alanlari'], true) ?? [];
                        foreach ($ilgi_alanlari as $ilgi) {
                            echo '<span class="interest-tag">' . guvenli_cikti($ilgi) . '</span>';
                        }
                        ?>
                    </div>

                    <p class="section-description">İlgi alanlarınızı güncellemek için seçim yapın:</p>
                    <div class="interest-select">
                        <select name="interests[]" id="interests" multiple>
                            <?php
                            $tum_ilgi_alanlari = [
                                'Spor',
                                'Sanat',
                                'Teknoloji',
                                'Eğitim',
                                'Müzik',
                                'Sağlık',
                                'Gönüllülük',
                                'Doğa',
                                'Yemek',
                                'Eğlence',
                                'Tiyatro',
                                'Sinema',
                                'Moda',
                                'Bilim',
                                'Gezi',
                                'Kariyer',
                                'Networking',
                                'Workshop'
                            ];

                            foreach ($tum_ilgi_alanlari as $ilgi) {
                                $selected = in_array($ilgi, $ilgi_alanlari) ? 'selected' : '';
                                echo '<option value="' . $ilgi . '" ' . $selected . '>' . $ilgi . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="save-btn">Güncelle</button>
                </form>
            </div>

            <?php
            // Etkinlikten çıkma işlemi
            if (isset($_POST['etkinlikten_cik']) && isset($_POST['etkinlik_id'])) {
                $etkinlik_id = intval($_POST['etkinlik_id']);

                // Katılımcılar tablosundan kaydı sil
                $sil_sql = "DELETE FROM katilimcilar WHERE etkinlik_id = :etkinlik_id AND kullanici_id = :kullanici_id";
                $sil_stmt = $db->prepare($sil_sql);
                $sil_stmt->execute([
                    'etkinlik_id' => $etkinlik_id,
                    'kullanici_id' => $_SESSION['user_id']
                ]);

                if ($sil_stmt->rowCount() > 0) {
                    // Puanları güncelle
                    $puan_sil_sql = "DELETE FROM puanlar WHERE kullanici_id = :kullanici_id AND kazanilan_tarih = (
                        SELECT kazanilan_tarih FROM puanlar 
                        WHERE kullanici_id = :kullanici_id 
                        ORDER BY kazanilan_tarih DESC 
                        LIMIT 1
                    )";
                    $puan_sil_stmt = $db->prepare($puan_sil_sql);
                    $puan_sil_stmt->execute(['kullanici_id' => $_SESSION['user_id']]);

                    header("Location: profilim.php");
                    exit;
                }
            }
            ?>
            <div id="past-events" class="section">
                <h2>Geçmişte Katıldığım Etkinlikler</h2>
                <div class="event-history">
                    <?php foreach ($katildigi_etkinlikler as $etkinlik): ?>
                        <div class="event-card">
                            <h3><?php echo guvenli_cikti($etkinlik['etkinlik_adi']); ?></h3>
                            <p><?php echo guvenli_cikti($etkinlik['aciklama']); ?></p>
                            <div class="event-details">
                                <span><i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($etkinlik['tarih'])); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($etkinlik['saat'])); ?></span>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo guvenli_cikti($etkinlik['konum']); ?></span>
                            </div>
                            <?php
                            $etkinlik_tarihi = strtotime($etkinlik['tarih'] . ' ' . $etkinlik['saat']);
                            if ($etkinlik_tarihi > time()): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="etkinlik_id" value="<?php echo $etkinlik['id']; ?>">
                                    <style>
                                        .btn-leave {
                                            background-color: #e74c3c;
                                            color: white;
                                            border: none;
                                            padding: 8px 16px;
                                            border-radius: 4px;
                                            cursor: pointer;
                                            font-size: 14px;
                                            transition: background-color 0.3s;
                                        }

                                        .btn-leave:hover {
                                            background-color: #c0392b;
                                        }
                                    </style>
                                    <button type="submit" name="etkinlikten_cik" class="btn-leave" onclick="return confirm('Bu etkinlikten çıkmak istediğinize emin misiniz?');">
                                        Etkinlikten Çık
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="points" class="section">
                <h2>Puanlarım ve Başarılarım</h2>
                <div class="points-achievements">
                    <div class="total-points">
                        <h3>Toplam Puan</h3>
                        <p class="points" style="font-size: 48px; font-weight: bold; color: #2ecc71;"><?php echo array_sum(array_column($puanlar, 'puan')); ?></p>
                    </div>
                    <div class="points-history">
                        <h3>Puan Geçmişi</h3>
                        <?php foreach ($puanlar as $puan): ?>
                            <div class="point-entry">
                                <span class="point" style="font-size: 24px; font-weight: bold; color: #27ae60;">+<?php echo $puan['puan']; ?></span>
                                <span class="date"><?php echo date('d.m.Y H:i', strtotime($puan['kazanilan_tarih'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php
            // Geri bildirim tablosunu oluşturmak için SQL kodu:
            /*
            CREATE TABLE geri_bildirimler (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kullanici_id INT NOT NULL,
                feedback_type VARCHAR(50) NOT NULL,
                subject VARCHAR(255) NOT NULL, 
                message TEXT NOT NULL,
                rating INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id)
            );
            */

            // Geri bildirim gönderme işlemi
            if (isset($_POST['geri_bildirim_gonder'])) {
                $feedback_type = guvenli_giris($_POST['feedback_type']);
                $subject = guvenli_giris($_POST['subject']);
                $message = guvenli_giris($_POST['message']);
                $rating = intval($_POST['rating']);

                $sql = "INSERT INTO geri_bildirimler (kullanici_id, feedback_type, subject, message, rating) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);

                if ($stmt->execute([$kullanici_id, $feedback_type, $subject, $message, $rating])) {
                    echo "<script>
                        Swal.fire({
                            title: 'Başarılı!',
                            text: 'Geri bildiriminiz başarıyla kaydedildi',
                            icon: 'success',
                            confirmButtonText: 'Tamam'
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            title: 'Hata!',
                            text: 'Geri bildirim kaydedilirken hata oluştu',
                            icon: 'error',
                            confirmButtonText: 'Tamam'
                        });
                    </script>";
                }
            }
            ?>

            <div id="feedback" class="section">
                <h2 class="feedback-title">Geri Bildirim Gönder</h2>
                <form id="feedback-form" class="feedback-form" method="POST">
                    <div class="feedback-form-group">
                        <label for="feedback-type">Geri Bildirim Türü:</label>
                        <select id="feedback-type" name="feedback_type" required>
                            <option value="">Seçiniz</option>
                            <option value="oneri">Öneri</option>
                            <option value="sikayet">Şikayet</option>
                            <option value="tesekkur">Teşekkür</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                    <div class="feedback-form-group">
                        <label for="subject">Konu:</label>
                        <input type="text" id="subject" name="subject" class="feedback-input" placeholder="Konu başlığı giriniz" required>
                    </div>
                    <div class="feedback-form-group">
                        <label for="message">Mesajınız:</label>
                        <textarea id="message" name="message" class="feedback-textarea" rows="5" placeholder="Mesajınızı buraya yazınız..." required></textarea>
                    </div>
                    <div class="feedback-form-group">
                        <label for="rating">Değerlendirme:</label>
                        <div class="rating">
                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5">★</label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4">★</label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3">★</label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2">★</label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1">★</label>
                        </div>
                    </div>
                    <button type="submit" name="geri_bildirim_gonder" class="feedback-submit-btn">
                        <i class="fas fa-paper-plane"></i> Gönder
                    </button>
                </form>
            </div>

            <style>
                .feedback-title {
                    color: #2c3e50;
                    text-align: center;
                    margin-bottom: 30px;
                    font-size: 24px;
                }

                .feedback-form {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 30px;
                    background: #fff;
                    border-radius: 10px;
                    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
                }

                .feedback-form-group {
                    margin-bottom: 20px;
                }

                .feedback-form-group label {
                    display: block;
                    margin-bottom: 8px;
                    color: #34495e;
                    font-weight: 500;
                }

                .feedback-input,
                .feedback-textarea,
                #feedback-type {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #e0e0e0;
                    border-radius: 5px;
                    font-size: 14px;
                    transition: border-color 0.3s ease;
                }

                .feedback-input:focus,
                .feedback-textarea:focus,
                #feedback-type:focus {
                    border-color: #3498db;
                    outline: none;
                }

                .rating {
                    display: flex;
                    flex-direction: row-reverse;
                    justify-content: flex-end;
                }

                .rating input {
                    display: none;
                }

                .rating label {
                    cursor: pointer;
                    font-size: 30px;
                    color: #ddd;
                    padding: 5px;
                }

                .rating input:checked~label {
                    color: #ffd700;
                }

                .rating label:hover,
                .rating label:hover~label {
                    color: #ffd700;
                }

                .feedback-submit-btn {
                    width: 100%;
                    padding: 12px;
                    background: #3498db;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }

                .feedback-submit-btn:hover {
                    background: #2980b9;
                }

                .feedback-submit-btn i {
                    margin-right: 8px;
                }

                /* Profil fotoğrafı stil güncellemeleri */
                .photo-group {
                    text-align: center;
                }

                .photo-display {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 10px;
                }

                #profile-photo-preview {
                    width: 150px;
                    height: 150px;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 3px solid #3498db;
                }

                #profile-photo-input {
                    display: none;
                }

                .edit-photo-btn {
                    background: #3498db;
                    color: white;
                    padding: 8px 16px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }

                .edit-photo-btn:hover {
                    background: #2980b9;
                }

                .save-btn {
                    width: 100%;
                    padding: 12px;
                    background: #2ecc71;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                    margin-top: 20px;
                }

                .save-btn:hover {
                    background: #27ae60;
                }
            </style>
        </section>
    </div>

    <?php
                    $stmt = $db->prepare("SELECT * FROM etkinlikler WHERE olusturan_id = ? ORDER BY tarih DESC");
                    $stmt->execute([$kullanici_id]);
                    $etkinliklerim = $stmt->fetchAll();

                    if (count($etkinliklerim) > 0) {
                        foreach ($etkinliklerim as $etkinlik) {
                    ?>

                                <!-- Modal -->
                                <div id="modal_<?php echo $etkinlik['id']; ?>" class="modal">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5>Etkinlik Düzenle</h5>
                                            <span class="close" onclick="closeModal(<?php echo $etkinlik['id']; ?>)">&times;</span>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post">
                                                <input type="hidden" name="etkinlik_id" value="<?php echo $etkinlik['id']; ?>">
                                                <div class="form-group">
                                                    <label>Etkinlik Adı</label>
                                                    <input type="text" class="form-control" name="etkinlik_adi" value="<?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Açıklama</label>
                                                    <textarea class="form-control" name="aciklama" rows="3" required><?php echo htmlspecialchars($etkinlik['aciklama']); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Tarih</label>
                                                    <input type="date" class="form-control" name="tarih" value="<?php echo $etkinlik['tarih']; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Konum</label>
                                                    <input type="text" class="form-control" name="konum" value="<?php echo htmlspecialchars($etkinlik['konum']); ?>" required>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn-secondary" onclick="closeModal(<?php echo $etkinlik['id']; ?>)">İptal</button>
                                                    <button type="submit" name="etkinlik_guncelle" class="btn-primary">Güncelle</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
<?php } } ?>
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
        // Menü geçişleri için
        document.querySelectorAll('.sidebar li').forEach(item => {
            item.addEventListener('click', function() {
                // Aktif menü öğesini güncelle
                document.querySelectorAll('.sidebar li').forEach(li => li.classList.remove('active'));
                this.classList.add('active');

                // İlgili bölümü göster
                const sectionId = this.getAttribute('data-section');
                document.querySelectorAll('.section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(sectionId).classList.add('active');
            });
        });

        document.getElementById('interest-update-form').addEventListener('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this);
            formData.append('guncelle_ilgi_alanlari', '1');

            fetch('profilim.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Başarılı!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Tamam'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Hata!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'Tamam'
                        });
                    }
                });
        });

        document.getElementById('reset-password-form').addEventListener('submit', function(e) {
            e.preventDefault();

            let newPassword = document.getElementById('new-password').value;
            let confirmPassword = document.getElementById('confirm-password').value;

            if (newPassword !== confirmPassword) {
                Swal.fire({
                    title: 'Hata!',
                    text: 'Şifreler eşleşmiyor!',
                    icon: 'error',
                    confirmButtonText: 'Tamam'
                });
                return;
            }

            fetch('profilim.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `sifre_guncelle=1&yeni_sifre=${newPassword}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Başarılı!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Tamam'
                        });
                        document.getElementById('new-password').value = '';
                        document.getElementById('confirm-password').value = '';
                    } else {
                        Swal.fire({
                            title: 'Hata!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'Tamam'
                        });
                    }
                });
        });

        function updatePhotoPreview(event) {
            if (event.target.files && event.target.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-photo-preview').src = e.target.result;
                }
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
</body>

</html>