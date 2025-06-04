<?php
include 'inc/config.php';

// Oturum kontrolü
session_start();
if(!isset($_SESSION['admin_id']) || $_SESSION['admin_id'] != 1) {
    header("Location: giris.php");
    exit();
}

// Site ayarları tablosunu oluştur
try {
    $sql = "CREATE TABLE IF NOT EXISTS site_ayarlari (
        id INT PRIMARY KEY AUTO_INCREMENT,
        site_baslik VARCHAR(255),
        hakkimizda TEXT,
        email VARCHAR(255),
        telefon VARCHAR(50),
        adres TEXT,
        facebook VARCHAR(255),
        twitter VARCHAR(255), 
        instagram VARCHAR(255),
        linkedin VARCHAR(255),
        footer_text TEXT,
        logo_url VARCHAR(255),
        favicon_url VARCHAR(255),
        meta_description TEXT,
        meta_keywords TEXT
    )";
    $db->exec($sql);

    // İlk kaydı ekle (eğer yoksa)
    $check = $db->query("SELECT COUNT(*) FROM site_ayarlari")->fetchColumn();
    if($check == 0) {
        $sql = "INSERT INTO site_ayarlari (id, site_baslik, hakkimizda) VALUES (1, 'Site Başlığı', 'Hakkımızda metni')";
        $db->exec($sql);
    }
} catch(PDOException $e) {
    die("Tablo oluşturma hatası: " . $e->getMessage());
}

// Kullanıcı silme işlemi
if(isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    try {
        $stmt = $db->prepare("DELETE FROM kullanicilar WHERE id = ?");
        $stmt->execute([$user_id]);
        $success = true;
    } catch(PDOException $e) {
        $error = true;
    }
}

// Hakkımızda güncelleme
if(isset($_POST['update_about'])) {
    $about_text = $_POST['about_text'];
    try {
        $stmt = $db->prepare("UPDATE site_ayarlari SET hakkimizda = ? WHERE id = 1");
        $stmt->execute([$about_text]);
        $success = true;
    } catch(PDOException $e) {
        $error = true;
    }
}

// Etkinlik silme
if(isset($_POST['delete_event'])) {
    $event_id = $_POST['event_id'];
    try {
        $stmt = $db->prepare("DELETE FROM etkinlikler WHERE id = ?");
        $stmt->execute([$event_id]);
        $success = true;
    } catch(PDOException $e) {
        $error = true;
    }
}

// Etkinlik durumu güncelleme
if(isset($_POST['update_event_status'])) {
    $event_id = $_POST['event_id'];
    $status = $_POST['update_event_status'];
    try {
        $stmt = $db->prepare("UPDATE etkinlikler SET durum = ? WHERE id = ?");
        $stmt->execute([$status, $event_id]);
        $success = true;
    } catch(PDOException $e) {
        $error = true;
    }
}

// Etkinlik güncelleme
if(isset($_POST['update_event'])) {
    $event_id = $_POST['event_id'];
    $etkinlik_adi = $_POST['etkinlik_adi'];
    $tarih = $_POST['tarih'];
    $konum = $_POST['konum'];
    $aciklama = $_POST['aciklama'];
    
    try {
        $stmt = $db->prepare("UPDATE etkinlikler SET etkinlik_adi = ?, tarih = ?, konum = ?, aciklama = ? WHERE id = ?");
        $stmt->execute([$etkinlik_adi, $tarih, $konum, $aciklama, $event_id]);
        $success = true;
    } catch(PDOException $e) {
        $error = true;
    }
}

// Geri bildirimleri getir
$feedbacks = $db->query("SELECT f.*, k.kullanici_adi, k.email 
                        FROM geri_bildirimler f 
                        INNER JOIN kullanicilar k ON f.kullanici_id = k.id 
                        ORDER BY f.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <style>
        .admin-container {
            width: 95%;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .admin-nav {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .admin-nav button {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            background: #4CAF50;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }

        .admin-nav button:hover {
            background: #45a049;
        }

        .admin-section {
            display: none;
            margin-top: 2rem;
        }

        .admin-section.active {
            display: block;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .data-table th, .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background: #f5f5f5;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 0.5rem;
        }

        .edit-btn {
            background: #2196F3;
            color: white;
        }

        .delete-btn {
            background: #f44336;
            color: white;
        }

        .approve-btn {
            background: #4CAF50;
            color: white;
        }

        .reject-btn {
            background: #f44336;
            color: white;
        }

        .about-editor {
            width: 100%;
            min-height: 200px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            font-size: 2rem;
            color: #4CAF50;
            font-weight: bold;
        }

        .user-details {
            display: none;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .user-details.active {
            display: block;
        }

        .feedback-section {
            margin-top: 2rem;
        }

        .feedback-card {
            background: #f8f9fa;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .feedback-rating {
            color: #f39c12;
        }

        .event-edit-form {
            display: none;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .event-edit-form.active {
            display: block;
        }

        .event-edit-form input, .event-edit-form textarea {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <header class="main-header">
        <?php include 'inc/header.php'; ?>
    </header>

    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-cog"></i> Yönetim Paneli</h1>
            <div>
                <span>Hoş geldiniz, Admin</span>
                <a href="cikis.php" class="action-btn delete-btn">Çıkış Yap</a>
            </div>
        </div>

        <div class="stats-grid">
            <?php
            // İstatistikleri getir
            $user_count = $db->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn();
            $event_count = $db->query("SELECT COUNT(*) FROM etkinlikler")->fetchColumn();
            $active_events = $db->query("SELECT COUNT(*) FROM etkinlikler WHERE durum=1 AND tarih >= CURDATE()")->fetchColumn();
            $feedback_count = $db->query("SELECT COUNT(*) FROM geri_bildirimler")->fetchColumn();
            ?>
            <div class="stat-card">
                <h3>Toplam Kullanıcı</h3>
                <p><?php echo $user_count; ?></p>
            </div>
            <div class="stat-card">
                <h3>Toplam Etkinlik</h3>
                <p><?php echo $event_count; ?></p>
            </div>
            <div class="stat-card">
                <h3>Aktif Etkinlikler</h3>
                <p><?php echo $active_events; ?></p>
            </div>
            <div class="stat-card">
                <h3>Geri Bildirimler</h3>
                <p><?php echo $feedback_count; ?></p>
            </div>
        </div>

        <div class="admin-nav">
            <button onclick="showSection('users')" class="nav-btn active">Kullanıcılar</button>
            <button onclick="showSection('events')" class="nav-btn">Etkinlikler</button>
            <button onclick="showSection('feedbacks')" class="nav-btn">Geri Bildirimler</button>
            <button onclick="showSection('about')" class="nav-btn">Hakkımızda</button>
        </div>

        <!-- Kullanıcılar Bölümü -->
        <div id="users" class="admin-section active">
            <h2>Kullanıcı Yönetimi</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı Adı</th>
                        <th>Email</th>
                        <th>Ad Soyad</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $users = $db->query("SELECT * FROM kullanicilar ORDER BY id DESC")->fetchAll();
                    foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['kullanici_adi']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['ad'] . ' ' . $user['soyad']; ?></td>
                        <td>
                            <button onclick="showUserDetails(<?php echo $user['id']; ?>)" class="action-btn edit-btn">Detay</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="action-btn delete-btn">Sil</button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <div id="user-details-<?php echo $user['id']; ?>" class="user-details">
                                <h3>Kullanıcı Detayları</h3>
                                <p><strong>Telefon:</strong> <?php echo $user['telefon']; ?></p>
                                <p><strong>Adres:</strong> <?php echo $user['konum']; ?></p>
                                <p><strong>Son Görülme:</strong> <?php echo $user['son_gorulme']; ?></p>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Etkinlikler Bölümü -->
        <div id="events" class="admin-section">
            <h2>Etkinlik Yönetimi</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Başlık</th>
                        <th>Tarih</th>
                        <th>Konum</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $events = $db->query("SELECT * FROM etkinlikler ORDER BY tarih DESC")->fetchAll();
                    foreach($events as $event): ?>
                    <tr>
                        <td><?php echo $event['id']; ?></td>
                        <td><?php echo $event['etkinlik_adi']; ?></td>
                        <td><?php echo $event['tarih']; ?></td>
                        <td><?php echo $event['konum']; ?></td>
                        <td>
                            <?php 
                            switch($event['durum']) {
                                case 0:
                                    echo "Onay Bekliyor";
                                    break;
                                case 1:
                                    echo "Onaylandı"; 
                                    break;
                                case 2:
                                    echo "Reddedildi";
                                    break;
                                default:
                                    echo "Bilinmiyor";
                            }
                            ?>
                        </td>
                        <td>
                            <button onclick="showEventEdit(<?php echo $event['id']; ?>)" class="action-btn edit-btn">Güncelle</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" name="update_event_status" value="1" class="action-btn approve-btn">Onayla</button>
                                <button type="submit" name="update_event_status" value="2" class="action-btn reject-btn">Reddet</button>
                                <button type="submit" name="delete_event" class="action-btn delete-btn">Sil</button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <div id="event-edit-<?php echo $event['id']; ?>" class="event-edit-form">
                                <h3>Etkinlik Düzenle</h3>
                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <input type="text" name="etkinlik_adi" value="<?php echo $event['etkinlik_adi']; ?>" placeholder="Etkinlik Adı">
                                    <input type="datetime-local" name="tarih" value="<?php echo date('Y-m-d\TH:i', strtotime($event['tarih'])); ?>">
                                    <input type="text" name="konum" value="<?php echo $event['konum']; ?>" placeholder="Konum">
                                    <textarea name="aciklama" placeholder="Etkinlik Açıklaması" rows="4"><?php echo $event['aciklama']; ?></textarea>
                                    <button type="submit" name="update_event" class="action-btn edit-btn">Güncelle</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Geri Bildirimler Bölümü -->
        <div id="feedbacks" class="admin-section">
            <h2>Geri Bildirim Yönetimi</h2>
            <div class="feedback-section">
                <?php foreach($feedbacks as $feedback): ?>
                <div class="feedback-card">
                    <div class="feedback-header">
                        <strong><?php echo $feedback['kullanici_adi']; ?></strong>
                        <span class="feedback-rating">
                            <?php 
                            for($i = 0; $i < $feedback['rating']; $i++) {
                                echo "★";
                            }
                            ?>
                        </span>
                    </div>
                    <div class="feedback-content">
                        <p><strong>Konu:</strong> <?php echo $feedback['subject']; ?></p>
                        <p><strong>Mesaj:</strong> <?php echo $feedback['message']; ?></p>
                        <p><small>Tarih: <?php echo $feedback['created_at']; ?></small></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Hakkımızda Bölümü -->
        <div id="about" class="admin-section">
            <h2>Hakkımızda Düzenle</h2>
            <form method="POST">
                <?php
                $about = $db->query("SELECT hakkimizda FROM site_ayarlari WHERE id = 1")->fetchColumn();
                ?>
                <textarea name="about_text" class="about-editor"><?php echo $about; ?></textarea>
                <button type="submit" name="update_about" class="action-btn edit-btn">Güncelle</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.admin-section').forEach(section => {
                section.classList.remove('active');
            });
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(sectionId).classList.add('active');
            event.target.classList.add('active');
        }

        function showUserDetails(userId) {
            const detailsDiv = document.getElementById(`user-details-${userId}`);
            document.querySelectorAll('.user-details').forEach(div => {
                if(div !== detailsDiv) {
                    div.classList.remove('active');
                }
            });
            detailsDiv.classList.toggle('active');
        }

        function showEventEdit(eventId) {
            const editForm = document.getElementById(`event-edit-${eventId}`);
            document.querySelectorAll('.event-edit-form').forEach(form => {
                if(form !== editForm) {
                    form.classList.remove('active');
                }
            });
            editForm.classList.toggle('active');
        }

        <?php if(isset($success)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Başarılı!',
            text: 'İşlem başarıyla gerçekleştirildi!'
        });
        <?php endif; ?>

        <?php if(isset($error)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'İşlem sırasında bir hata oluştu!'
        });
        <?php endif; ?>

        // Sayfa yüklendiğinde varsayılan olarak kullanıcılar bölümünü göster
        document.addEventListener('DOMContentLoaded', function() {
            showSection('users');
        });
    </script>

</body>
</html>