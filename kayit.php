<?php
include 'inc/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = guvenli_giris($_POST['username']);
    $email = guvenli_giris($_POST['email']);
    $password = password_hash($_POST['new-password'], PASSWORD_DEFAULT);
    $firstName = guvenli_giris($_POST['first-name']); 
    $lastName = guvenli_giris($_POST['last-name']);
    $birthdate = guvenli_giris($_POST['birthdate']);
    $gender = guvenli_giris($_POST['gender']);
    $phone = guvenli_giris($_POST['phone']);
    
    // İlgi alanlarını dizi olarak al ve JSON'a dönüştür
    $interests = isset($_POST['interests']) ? json_encode($_POST['interests']) : null;
    
    // Profil fotoğrafı yükleme dizini kontrolü ve oluşturma
    $target_dir = "uploads/profile_pictures/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["profile-picture"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Kullanıcı adı ve email kontrolü
    $stmt = $db->prepare("SELECT COUNT(*) FROM kullanicilar WHERE kullanici_adi = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $count = $stmt->fetchColumn();
    
    if($count > 0) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'Bu kullanıcı adı veya email zaten kullanımda!'
            });
        </script>";
        exit;
    }
    
    // Dosya yükleme kontrolü
    if (is_uploaded_file($_FILES["profile-picture"]["tmp_name"]) && move_uploaded_file($_FILES["profile-picture"]["tmp_name"], $target_file)) {
        try {
            $db->beginTransaction();

            // Kullanıcı kaydı
            $stmt = $db->prepare("INSERT INTO kullanicilar (kullanici_adi, sifre, email, ad, soyad, dogum_tarihi, cinsiyet, telefon, ilgi_alanlari, profil_fotografi, admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
            
            $stmt->execute([
                $username,
                $password,
                $email,
                $firstName,
                $lastName,
                $birthdate,
                $gender,
                $phone,
                $interests,
                $new_filename
            ]);

            $kullanici_id = $db->lastInsertId();

            // Kayıt bonusu puanı ekleme
            $kayit_bonus_puani = 20;
            $stmt = $db->prepare("INSERT INTO puanlar (kullanici_id, puan, kazanilan_tarih) VALUES (?, ?, NOW())");
            $stmt->execute([$kullanici_id, $kayit_bonus_puani]);

            $db->commit();
            
            header("Location: giris.php?register=success");
            exit();
            
        } catch(PDOException $e) {
            $db->rollBack();
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Kayıt işlemi sırasında bir hata oluştu!'
                });
            </script>";
        }
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'Profil fotoğrafı yüklenirken bir hata oluştu! Lütfen yükleme dizininin yazma izinlerini kontrol edin.'
            });
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="register.css">
    <style>
        .auth-container {
            width: 80%;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .auth-form {
            
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .interests-section {
            grid-column: 1 / -1;
        }
        
        .interests-checkboxes {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .btn-submit {
            grid-column: 1 / -1;
            padding: 1rem;
            font-size: 1.1rem;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        input, select {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
    </style>
</head>

<body>

    <header class="main-header">
        <?php include 'inc/header.php'; ?>
    </header>

    <div class="auth-container register animate__animated animate__fadeIn" style="margin-top: 100px;">
        <h2 class="text-center mb-4"><i class="fas fa-user-plus"></i> Kayıt Ol</h2>
        <form class="auth-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" onsubmit="return validateRegisterForm()">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="first-name">Ad</label>
                <input type="text" id="first-name" name="first-name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="last-name">Soyad</label>
                <input type="text" id="last-name" name="last-name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="birthdate">Doğum Tarihi</label>
                <input type="date" id="birthdate" name="birthdate" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="phone">Telefon Numarası</label>
                <input type="tel" id="phone" name="phone" class="form-control" pattern="[0-9]{10}" placeholder="5XX XXX XX XX" required>
            </div>

            <div class="form-group">
                <label for="gender">Cinsiyet</label>
                <select id="gender" name="gender" class="form-control" required>
                    <option value="">Seçiniz</option>
                    <option value="male">Erkek</option>
                    <option value="female">Kadın</option>
                    <option value="other">Diğer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="profile-picture">Profil Fotoğrafı</label>
                <input type="file" id="profile-picture" name="profile-picture" accept="image/*" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="new-password">Şifre</label>
                <input type="password" id="new-password" name="new-password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm-password">Şifreyi Onayla</label>
                <input type="password" id="confirm-password" name="confirm-password" class="form-control" required>
            </div>

            <div class="interests-section">
                <label>İlgi Alanları (En az 3 seçiniz)</label>
                <div class="interests-checkboxes">
                    <label><input type="checkbox" name="interests[]" value="Spor"> Spor</label>
                    <label><input type="checkbox" name="interests[]" value="Sanat"> Sanat</label>
                    <label><input type="checkbox" name="interests[]" value="Teknoloji"> Teknoloji</label>
                    <label><input type="checkbox" name="interests[]" value="Eğitim"> Eğitim</label>
                    <label><input type="checkbox" name="interests[]" value="Müzik"> Müzik</label>
                    <label><input type="checkbox" name="interests[]" value="Sağlık"> Sağlık</label>
                    <label><input type="checkbox" name="interests[]" value="Gönüllülük"> Gönüllülük</label>
                    <label><input type="checkbox" name="interests[]" value="Doğa"> Doğa</label>
                    <label><input type="checkbox" name="interests[]" value="Yemek"> Yemek</label>
                    <label><input type="checkbox" name="interests[]" value="Eğlence"> Eğlence</label>
                    <label><input type="checkbox" name="interests[]" value="Tiyatro"> Tiyatro</label>
                    <label><input type="checkbox" name="interests[]" value="Sinema"> Sinema</label>
                    <label><input type="checkbox" name="interests[]" value="Moda"> Moda</label>
                    <label><input type="checkbox" name="interests[]" value="Bilim"> Bilim</label>
                    <label><input type="checkbox" name="interests[]" value="Gezi"> Gezi</label>
                    <label><input type="checkbox" name="interests[]" value="Kariyer"> Kariyer</label>
                    <label><input type="checkbox" name="interests[]" value="Networking"> Networking</label>
                    <label><input type="checkbox" name="interests[]" value="Workshop"> Workshop</label>
                </div>
            </div>

            <button type="submit" class="btn-submit">Kayıt Ol</button>
            <p id="register-error-message" class="error-message"></p>
        </form>
        <p class="switch-form text-center mt-3">Zaten hesabınız var mı? <a href="giris.php">Giriş Yap</a></p>
    </div>

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
        function validateRegisterForm() {
            const username = document.getElementById('username').value;
            const firstName = document.getElementById('first-name').value;
            const lastName = document.getElementById('last-name').value;
            const birthdate = document.getElementById('birthdate').value;
            const gender = document.getElementById('gender').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const interests = document.querySelectorAll('input[name="interests[]"]:checked');
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const profilePicture = document.getElementById('profile-picture').value;
            const errorMessage = document.getElementById('register-error-message');

            if (!username || !firstName || !lastName || !birthdate || !gender || !email || !phone || !newPassword || !confirmPassword || !profilePicture) {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Lütfen tüm alanları doldurunuz.'
                });
                return false;
            }

            if (interests.length < 3) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Uyarı!',
                    text: 'Lütfen en az 3 ilgi alanı seçiniz.'
                });
                return false;
            }

            if (newPassword !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Şifreler eşleşmiyor!'
                });
                return false;
            }

            if (newPassword.length < 6) {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Şifre en az 6 karakter olmalıdır.'
                });
                return false;
            }

            return true;
        }
    </script>

</body>

<input type="submit" class="button-register">


</html>