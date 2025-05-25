<?php 
include 'inc/config.php';

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = guvenli_giris($_POST['username']);
    $password = $_POST['password'];
    $captcha = $_POST['captcha'];
    $captcha_session = $_SESSION['captcha'];
    
    // Captcha kontrolü
    if($captcha != $captcha_session) {
        $error = "Doğrulama kodu hatalı!";
    } else {
        // Kullanıcıyı veritabanında kontrol et
        $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && sifre_kontrol($password, $user['sifre'])) {
            // Giriş başarılı
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['kullanici_adi'];
            
            // Admin kontrolü
            if($user['admin'] == 1) {
                $_SESSION['admin_id'] = $user['id'];
            }
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Kullanıcı adı veya şifre hatalı!";
        }
    }
}

// Rastgele captcha kodu oluştur
$captcha = rand(1000,9999);
$_SESSION['captcha'] = $captcha;

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="user_login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <style>
        .captcha-container {
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .captcha-code {
            background: #f0f0f0;
            padding: 10px;
            font-size: 20px;
            font-family: 'Courier New', monospace;
            letter-spacing: 5px;
            border-radius: 5px;
            user-select: none;
        }
        .refresh-captcha {
            cursor: pointer;
            color: #666;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <header class="main-header">
        <?php
        include 'inc/header.php';
        ?>
    </header>
    <?php if(isset($_GET['register']) && $_GET['register'] == 'success'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: 'Kayıt işleminiz başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.',
                confirmButtonText: 'Tamam',
                customClass: {
                    popup: 'animate__animated animate__fadeInDown'
                }
            });
        </script>
    <?php endif; ?>

    <!-- Giriş Yap Bölümü -->
    <div class="auth-container">
        <h2><i class="fas fa-user"></i> Kullanıcı Girişi</h2>
        <p class="login-description">Lütfen kullanıcı hesabınıza giriş yapın.</p>
        <?php if(isset($error)): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: '<?php echo guvenli_cikti($error); ?>',
                    confirmButtonText: 'Tamam',
                    customClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    }
                });
            </script>
        <?php endif; ?>
        <form class="auth-form" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" onsubmit="return validateForm()">
            <label for="username">Kullanıcı Adı</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Şifre</label>
            <input type="password" id="password" name="password" required>

            <div class="captcha-container">
                <span class="captcha-code"><?php echo $captcha; ?></span>
                <input type="text" id="captcha" name="captcha" placeholder="Doğrulama kodunu girin" required>
            </div>

            <button type="submit" class="btn-submit">Kullanıcı Girişi</button>
            <p class="switch-form">Hesabınız yok mu? <a href="kayit.php">Kayıt Ol</a></p>
            <p class="switch-form"><a href="sifremi_unuttum.php">Şifrenizi mi unuttunuz?</a></p>
        </form>
    </div>

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

    <!-- Doğrulama JavaScript Kodu -->
    <script>
        function validateForm() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const captcha = document.getElementById('captcha').value;

            if (username === "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Uyarı!',
                    text: 'Kullanıcı adı alanı boş bırakılamaz.',
                    confirmButtonText: 'Tamam',
                    customClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    }
                });
                return false;
            }

            if (password === "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Uyarı!',
                    text: 'Şifre alanı boş bırakılamaz.',
                    confirmButtonText: 'Tamam',
                    customClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    }
                });
                return false;
            }

            if (password.length < 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Uyarı!',
                    text: 'Şifre en az 6 karakter olmalıdır.',
                    confirmButtonText: 'Tamam',
                    customClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    }
                });
                return false;
            }

            if (captcha === "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Uyarı!',
                    text: 'Doğrulama kodu boş bırakılamaz.',
                    confirmButtonText: 'Tamam',
                    customClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    }
                });
                return false;
            }

            return true;
        }

        function refreshCaptcha() {
            fetch('refresh_captcha.php')
                .then(response => response.text())
                .then(captcha => {
                    document.querySelector('.captcha-code').textContent = captcha;
                });
        }
    </script>

</body>

</html>