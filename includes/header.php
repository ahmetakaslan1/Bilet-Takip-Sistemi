<?php
// config.php zaten bu dosyayı çağıran (örn: index.php) tarafından çağrıldığı için
// burada tekrar çağırmak genellikle gereksizdir ve hataya yol açabilir.
// Ancak her ihtimale karşı, eğer doğrudan erişilmeye çalışılırsa diye bir kontrol koyalım.
if (!defined('ROOT_PATH')) {
    // Bu, config.php'nin çağrılmadığı anlamına gelir. Hata ver ve çık.
    // Pratikte bu durumun yaşanmaması gerekir.
    die("Yapılandırma dosyası yüklenemedi. Sistem yöneticisiyle görüşün.");
}
require_once ROOT_PATH . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Etkinlik Bilet Sistemi' : 'Etkinlik Bilet Sistemi'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-ticket-alt me-2"></i>Etkinlik Bilet
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php">Ana Sayfa</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <!-- Admin Menüsü -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>admin/index.php">Admin Paneli</a>
                            </li>
                        <?php else: ?>
                            <!-- Normal Kullanıcı Menüsü -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>my_tickets.php">Biletlerim</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>support.php">Destek</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Çıkış Yap</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Ziyaretçi Menüsü -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">Giriş Yap</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>register.php">Kayıt Ol</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <?php
        // Mesaj gösterme
        if (isset($_SESSION['error'])) {
            echo showError($_SESSION['error']);
            unset($_SESSION['error']);
        }
        
        if (isset($_SESSION['success'])) {
            echo showSuccess($_SESSION['success']);
            unset($_SESSION['success']);
        }
        
        if (isset($_SESSION['info'])) {
            echo showInfo($_SESSION['info']);
            unset($_SESSION['info']);
        }
        ?>
    </div>

 
