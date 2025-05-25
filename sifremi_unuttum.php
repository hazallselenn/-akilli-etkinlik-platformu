<?php
include 'inc/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = guvenli_giris($_POST['email']);
    
    // E-posta adresini veritabanında kontrol et
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Benzersiz token oluştur
        $token = bin2hex(random_bytes(32));
        $token_bitis = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Token'ı veritabanına kaydet
        $update = $db->prepare("UPDATE kullanicilar SET sifre_sifirlama_token = ?, token_bitis = ? WHERE email = ?");
        $update->execute([$token, $token_bitis, $email]);
        
        // E-posta gönderme işlemi
        $to = $email;
        $subject = "Şifre Sıfırlama Talebi";
        $reset_link = "http://localhost/sifre_sifirla.php?token=" . $token;
        
        $message = "
        <html>
        <head>
            <title>Şifre Sıfırlama</title>
        </head>
        <body>
            <h2>Şifre Sıfırlama Talebi</h2>
            <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:</p>
            <p><a href='$reset_link'>Şifremi Sıfırla</a></p>
            <p>Bu bağlantı 1 saat süreyle geçerlidir.</p>
            <p>Eğer bu talebi siz yapmadıysanız, bu e-postayı dikkate almayın.</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: noreply@etkinlikplatformu.com' . "\r\n";
        
        if(mail($to, $subject, $message, $headers)) {
            $success = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
        } else {
            $error = "E-posta gönderilirken bir hata oluştu.";
        }
    } else {
        $error = "Bu e-posta adresi sistemde kayıtlı değil.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Şifre Sıfırlama</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="forgot_password.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
</head>
<body>

    <!-- Navbar -->
    <header class="main-header">
        <?php include 'inc/header.php'; ?>
    </header>

    <?php if(isset($success)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: '<?php echo $success; ?>',
                confirmButtonText: 'Tamam'
            });
        </script>
    <?php endif; ?>

    <?php if(isset($error)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: '<?php echo $error; ?>',
                confirmButtonText: 'Tamam'
            });
        </script>
    <?php endif; ?>

    <!-- Şifre Sıfırlama Formu -->
    <div class="auth-container reset">
        <h2><i class="fas fa-key"></i> Şifre Sıfırlama</h2>
        <p class="reset-description">Lütfen kayıtlı e-posta adresinizi girin. Şifre sıfırlama bağlantısını size e-posta ile göndereceğiz.</p>
        <form class="auth-form" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" onsubmit="return validateForm()">
            <label for="email">E-posta</label>
            <input type="email" id="email" name="email" required>
            <button type="submit" class="btn-submit">Sıfırlama Bağlantısını Gönder</button>
            <p class="switch-form">Giriş sayfasına dön? <a href="giris.php">Giriş Yap</a></p>
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

    <!-- JavaScript -->
    <script>
        function validateForm() {
            const email = document.getElementById('email').value;
            
            if (!email) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Uyarı!',
                    text: 'E-posta alanı boş bırakılamaz.',
                    confirmButtonText: 'Tamam'
                });
                return false;
            }

            if (!isValidEmail(email)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Uyarı!',
                    text: 'Lütfen geçerli bir e-posta adresi girin.',
                    confirmButtonText: 'Tamam'
                });
                return false;
            }

            return true;
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>

</body>
</html>
