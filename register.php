<?php
$page_title = "Kayıt Ol";
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Kullanıcı giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

// Form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasyon
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Tüm alanları doldurunuz.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir e-posta adresi giriniz.";
    } elseif ($password != $confirm_password) {
        $error = "Şifreler eşleşmiyor.";
    } elseif (strlen($password) < 6) {
        $error = "Şifre en az 6 karakter olmalıdır.";
    } else {
        // E-posta adresi daha önce kullanılmış mı?
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Bu e-posta adresi zaten kullanılmaktadır.";
        } else {
            // Şifreyi hashle
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Kullanıcıyı kaydet
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                // Kullanıcı ID'sini al
                $user_id = $conn->insert_id;
                
                // Oturum bilgilerini ayarla
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';
                
                // Başarılı mesajı göster ve yönlendir
                $_SESSION['success'] = "Kayıt işleminiz başarıyla tamamlandı! Hoş geldiniz, $name.";
                header("Location: index.php");
                exit;
            } else {
                $error = "Kayıt sırasında bir hata oluştu: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-container">
            <h2 class="text-center mb-4">Hesap Oluştur</h2>
            
            <?php if ($error): ?>
                <?php echo showError($error); ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <?php echo showSuccess($success); ?>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="name" class="form-label">Ad Soyad</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">E-posta</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <small class="text-muted">Şifreniz en az 6 karakter olmalıdır.</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Şifre Tekrar</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Kullanım şartlarını</a> ve <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">gizlilik politikasını</a> kabul ediyorum.
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Kullanım Şartları Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Kullanım Şartları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Hizmet Kullanımı</h5>
                <p>Bu web sitesini kullanarak aşağıdaki şartları kabul etmiş olursunuz. Bu şartlara uymadığınız takdirde siteyi kullanmayı bırakmanız gerekir.</p>
                
                <h5>2. Hesap Güvenliği</h5>
                <p>Hesabınızın güvenliğinden siz sorumlusunuz. Hesabınızla ilgili tüm etkinliklerin sorumluluğu size aittir.</p>
                
                <h5>3. Bilet Satın Alma ve İptal</h5>
                <p>Satın alınan biletler iade edilemez. Etkinlik iptal edilirse, bilet ücretiniz iade edilecektir.</p>
                
                <h5>4. Değişiklikler</h5>
                <p>Bu kullanım şartları herhangi bir zamanda güncellenebilir. Değişiklikler bu sayfada yayınlanacaktır.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- Gizlilik Politikası Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Gizlilik Politikası</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Toplanan Bilgiler</h5>
                <p>Kayıt olurken e-posta adresiniz, adınız ve şifreniz gibi bilgiler toplanır. Ayrıca, konum bazlı öneriler için konum bilginiz kullanılır.</p>
                
                <h5>2. Bilgilerin Kullanımı</h5>
                <p>Toplanan bilgiler size daha iyi hizmet sunmak, size özel etkinlik önerileri göstermek ve hesap güvenliğinizi sağlamak için kullanılır.</p>
                
                <h5>3. Çerezler</h5>
                <p>Bu site, deneyiminizi geliştirmek için çerezleri kullanır. Tarayıcı ayarlarınızdan çerezleri devre dışı bırakabilirsiniz.</p>
                
                <h5>4. Üçüncü Taraflarla Bilgi Paylaşımı</h5>
                <p>Kişisel bilgileriniz, yasal zorunluluklar dışında üçüncü taraflarla paylaşılmaz.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 