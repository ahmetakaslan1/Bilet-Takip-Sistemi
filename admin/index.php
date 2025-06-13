<?php
$page_title = "Admin Paneli";
require_once dirname(__DIR__) . '/includes/config.php';
require_once ROOT_PATH . '/includes/functions.php';

// Admin kontrolü
requireAdmin();

// İstatistikleri çek
$total_users = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$stmt->bind_result($total_users);
$stmt->fetch();
$stmt->close();

$total_events = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM events");
$stmt->execute();
$stmt->bind_result($total_events);
$stmt->fetch();
$stmt->close();

$total_tickets = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM tickets");
$stmt->execute();
$stmt->bind_result($total_tickets);
$stmt->fetch();
$stmt->close();

$total_revenue = 0;
$stmt = $conn->prepare("SELECT SUM(e.price) FROM tickets t JOIN events e ON t.event_id = e.id");
$stmt->execute();
$stmt->bind_result($total_revenue);
$stmt->fetch();
$stmt->close();
$total_revenue = $total_revenue ?? 0;

// Son eklenen etkinlikler
$recent_events = [];
$stmt = $conn->prepare("SELECT id, title, date FROM events ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$stmt->bind_result($id, $title, $date);
while($stmt->fetch()){
    $recent_events[] = ['id' => $id, 'title' => $title, 'date' => $date];
}
$stmt->close();

// Son kayıt olan kullanıcılar
$recent_users = [];
$stmt = $conn->prepare("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$stmt->bind_result($id, $name, $email, $created_at);
while($stmt->fetch()){
    $recent_users[] = ['id' => $id, 'name' => $name, 'email' => $email, 'created_at' => $created_at];
}
$stmt->close();

// Toplam destek talebi sayısı
$stmt = $conn->prepare("SELECT COUNT(*) FROM support_tickets");
$stmt->execute();
$stmt->bind_result($total_support_tickets);
$stmt->fetch();
$stmt->close();

// Açık destek talebi sayısı
$stmt = $conn->prepare("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'");
$stmt->execute();
$stmt->bind_result($open_support_tickets);
$stmt->fetch();
$stmt->close();

// Yaklaşan etkinlikler
$upcoming_events = [];
$stmt = $conn->prepare("SELECT id, title, location, date, capacity, (SELECT COUNT(*) FROM tickets t WHERE t.event_id = events.id) as sold_tickets FROM events WHERE date > NOW() ORDER BY date ASC LIMIT 5");
$stmt->execute();
$stmt->bind_result($id, $title, $location, $date, $capacity, $sold_tickets);

while ($stmt->fetch()) {
    $upcoming_events[] = [
        'id' => $id,
        'title' => $title,
        'location' => $location,
        'date' => $date,
        'capacity' => $capacity,
        'sold_tickets' => $sold_tickets
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Etkinlik Bilet Sistemi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Çıkış Yap</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <?php include '../includes/admin_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="admin-content">
                    <h2 class="mb-4">Dashboard</h2>
                    
                    <!-- İstatistikler -->
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Toplam Kullanıcı</h6>
                                            <h2 class="mb-0"><?php echo $total_users; ?></h2>
                                        </div>
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Toplam Etkinlik</h6>
                                            <h2 class="mb-0"><?php echo $total_events; ?></h2>
                                        </div>
                                        <i class="fas fa-calendar-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Toplam Bilet</h6>
                                            <h2 class="mb-0"><?php echo $total_tickets; ?></h2>
                                        </div>
                                        <i class="fas fa-ticket-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Destek Talepleri</h6>
                                            <h2 class="mb-0"><?php echo $total_support_tickets; ?></h2>
                                        </div>
                                        <i class="fas fa-life-ring fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Son Kullanıcılar -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Son Kaydolan Kullanıcılar</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Ad Soyad</th>
                                                    <th>E-posta</th>
                                                    <th>Kayıt Tarihi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_users as $user): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer text-center">
                                        <a href="<?php echo BASE_URL; ?>admin/users.php" class="btn btn-outline-primary btn-sm">Tüm Kullanıcıları Gör</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Yaklaşan Etkinlikler -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Yaklaşan Etkinlikler</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Başlık</th>
                                                    <th>Konum</th>
                                                    <th>Tarih</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($upcoming_events) > 0): ?>
                                                    <?php foreach ($upcoming_events as $event): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                                                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                                                            <td><?php echo formatDate($event['date']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">Yaklaşan etkinlik bulunmuyor.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <a href="<?php echo BASE_URL; ?>admin/events.php" class="btn btn-outline-primary btn-sm">Tüm Etkinlikleri Gör</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Etkinlik Bilet Sistemi - Admin Paneli</p>
            </div>
        </div>
    </footer>

    <!-- jQuery ve Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 