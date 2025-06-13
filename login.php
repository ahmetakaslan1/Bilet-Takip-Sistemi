<?php
$page_title = "Giriş Yap";
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

// Form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Validasyon
    if (empty($email) || empty($password)) {
        $error = "E-posta ve şifre alanlarını doldurunuz.";
    } else {
        // Kullanıcıyı veritabanında ara
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($id, $name, $email_db, $password_hash, $role);
        $stmt->fetch();
        
        // E-posta adresi bulunursa ve şifre doğruysa
        if ($id && password_verify($password, $password_hash)) {
            // Oturum bilgilerini ayarla
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email_db;
            $_SESSION['user_role'] = $role;
            
            // Başarılı mesajı göster ve yönlendir
            $_SESSION['success'] = "Giriş başarılı! Hoş geldiniz, $name.";
            
            // Kullanıcı rolüne göre yönlendirme
            if ($role == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "E-posta adresi veya şifre hatalı.";
        }
        
        $stmt->close();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-container">
            <h2 class="text-center mb-4">Giriş Yap</h2>
            
            <?php if ($error): ?>
                <?php echo showError($error); ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <?php echo showSuccess($success); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['info'])): ?>
                <?php echo showInfo($_SESSION['info']); ?>
                <?php unset($_SESSION['info']); ?>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">E-posta</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Beni hatırla</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
                <p><a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Şifremi Unuttum</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Şifremi Unuttum Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="forgotPasswordModalLabel">Şifremi Unuttum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="forgotPasswordForm">
                    <div class="mb-3">
                        <label for="forgot_email" class="form-label">E-posta Adresiniz</label>
                        <input type="email" class="form-control" id="forgot_email" name="forgot_email" required>
                    </div>
                    <div class="alert alert-info">
                        Şifre sıfırlama bağlantısı e-posta adresinize gönderilecektir.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" id="sendResetLink">Şifre Sıfırlama Bağlantısı Gönder</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sendResetLinkBtn = document.getElementById('sendResetLink');
    if (sendResetLinkBtn) {
        sendResetLinkBtn.addEventListener('click', function() {
            const email = document.getElementById('forgot_email').value;
            if (!email) {
                alert('Lütfen e-posta adresinizi giriniz.');
                return;
            }
            
            // Burada normalde bir AJAX isteği ile şifre sıfırlama e-postası gönderilir
            // Şu an sadece bir bilgi mesajı gösteriyoruz
            alert('Şifre sıfırlama fonksiyonu şu an için devre dışıdır. Lütfen daha sonra tekrar deneyiniz.');
            
            // Modalı kapat
            const forgotPasswordModal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
            forgotPasswordModal.hide();
        });
    }
});
</script> 