<?php
/**
 * Etkinlik Bilet Sistemi
 * Veritabanı Kurulum Dosyası
 * 
 * Bu dosya veritabanı tablolarını oluşturmak için kullanılır.
 * Kurulumdan sonra bu dosyayı silin veya erişimi engelleyin.
 */

// Güvenlik için kurulum şifresi (gerçek ortamda daha güçlü bir şifre kullanın)
$setup_password = "kurulum123";

// Hata gösterimi açık
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kurulum şifresi kontrol
$password_verified = false;
$message = '';

if (isset($_POST['setup_password']) && $_POST['setup_password'] === $setup_password) {
    $password_verified = true;
    
    // Veritabanı bağlantısı
    require_once 'includes/config.php';
    
    // Veritabanı tablolarını oluştur
    $tables = [
        // users tablosu
        "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('user', 'admin') DEFAULT 'user',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        // events tablosu
        "CREATE TABLE IF NOT EXISTS `events` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `title` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `location` VARCHAR(200) NOT NULL,
            `date` DATETIME NOT NULL,
            `capacity` INT NOT NULL,
            `price` DECIMAL(10,2) NOT NULL,
            `latitude` DECIMAL(10,8),
            `longitude` DECIMAL(11,8),
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        // tickets tablosu
        "CREATE TABLE IF NOT EXISTS `tickets` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `event_id` INT NOT NULL,
            `token` VARCHAR(32) NOT NULL,
            `purchase_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `status` ENUM('active', 'used', 'cancelled') DEFAULT 'active',
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        // user_locations tablosu
        "CREATE TABLE IF NOT EXISTS `user_locations` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `user_id` INT NOT NULL UNIQUE,
            `latitude` DECIMAL(10,8) NOT NULL,
            `longitude` DECIMAL(11,8) NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        // support_tickets tablosu
        "CREATE TABLE IF NOT EXISTS `support_tickets` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `subject` VARCHAR(200) NOT NULL,
            `message` TEXT NOT NULL,
            `status` ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        // support_replies tablosu
        "CREATE TABLE IF NOT EXISTS `support_replies` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `ticket_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `message` TEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];
    
    // Tabloları oluştur
    $success = true;
    $error_messages = [];
    
    foreach ($tables as $sql) {
        if (!$conn->query($sql)) {
            $success = false;
            $error_messages[] = "Hata (" . $conn->errno . "): " . $conn->error . "<br>" . $sql;
        }
    }
    
    // Admin hesabı oluştur
    if ($success) {
        // Önce admin hesabının olup olmadığını kontrol et
        $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 0) {
            // Admin yoksa oluştur
            $admin_name = "Admin";
            $admin_email = "admin@etkinlikbilet.com";
            $admin_password = password_hash("admin123", PASSWORD_DEFAULT); // Gerçek ortamda daha güçlü şifre kullanın
            $admin_role = "admin";
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $admin_name, $admin_email, $admin_password, $admin_role);
            
            if (!$stmt->execute()) {
                $success = false;
                $error_messages[] = "Admin hesabı oluşturulurken hata: " . $stmt->error;
            }
        }
        $stmt->close();
    }
    
    // Örnek etkinlikler ekle
    if ($success) {
        $sample_events = [
            [
                'title' => 'Rock Konseri',
                'description' => 'Şehrin en popüler rock gruplarının sahne alacağı muhteşem bir gece!',
                'location' => 'Harbiye Açık Hava Tiyatrosu, İstanbul',
                'date' => date('Y-m-d H:i:s', strtotime('+15 day')),
                'capacity' => 500,
                'price' => 150.00,
                'latitude' => 41.0463,
                'longitude' => 28.9881
            ],
            [
                'title' => 'Teknoloji Konferansı',
                'description' => 'Yazılım ve teknoloji dünyasındaki son gelişmelerin konuşulacağı konferans.',
                'location' => 'Bilişim Vadisi, Kocaeli',
                'date' => date('Y-m-d H:i:s', strtotime('+30 day')),
                'capacity' => 300,
                'price' => 75.00,
                'latitude' => 40.7860,
                'longitude' => 29.4319
            ],
            [
                'title' => 'Tiyatro Gösterisi',
                'description' => 'Ünlü oyuncuların sahne alacağı komedi türünde bir tiyatro gösterisi.',
                'location' => 'Zorlu PSM, İstanbul',
                'date' => date('Y-m-d H:i:s', strtotime('+45 day')),
                'capacity' => 250,
                'price' => 100.00,
                'latitude' => 41.0668,
                'longitude' => 29.0096
            ]
        ];
        
        $stmt = $conn->prepare("SELECT id FROM events LIMIT 1");
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO events (title, description, location, date, capacity, price, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($sample_events as $event) {
                $stmt->bind_param("ssssiddd", 
                    $event['title'], 
                    $event['description'], 
                    $event['location'], 
                    $event['date'], 
                    $event['capacity'], 
                    $event['price'], 
                    $event['latitude'], 
                    $event['longitude']
                );
                
                if (!$stmt->execute()) {
                    $success = false;
                    $error_messages[] = "Örnek etkinlik eklenirken hata: " . $stmt->error;
                    break;
                }
            }
            $stmt->close();
        }
    }
    
    // Sonucu göster
    if ($success) {
        $message = '<div class="alert alert-success">Veritabanı kurulumu başarıyla tamamlandı! Admin girişi için:<br>E-posta: admin@etkinlikbilet.com<br>Şifre: admin123</div>';
    } else {
        $message = '<div class="alert alert-danger">Kurulum sırasında hatalar oluştu:<br>' . implode('<br>', $error_messages) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlik Bilet Sistemi - Veritabanı Kurulumu</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .setup-container {
            max-width: 650px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #3498db;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <h1 class="text-center">Etkinlik Bilet Sistemi Kurulumu</h1>
            
            <?php if (!$password_verified): ?>
                <div class="alert alert-warning">
                    <strong>Uyarı:</strong> Bu sayfa veritabanı tablolarını ve örnek verileri oluşturmak için kullanılır. 
                    Kurulum tamamlandıktan sonra güvenlik için bu dosyayı silmeniz önerilir.
                </div>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="setup_password" class="form-label">Kurulum Şifresi:</label>
                        <input type="password" class="form-control" id="setup_password" name="setup_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Kurulumu Başlat</button>
                </form>
            <?php else: ?>
                <?php echo $message; ?>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary">Ana Sayfaya Git</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 