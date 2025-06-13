<?php
$page_title = "Etkinlik Yönetimi";
require_once dirname(__DIR__) . '/includes/config.php';
require_once ROOT_PATH . '/includes/functions.php';

// Admin kontrolü
requireAdmin();

// İşlemler
$message = '';
$error = '';

// Başarı mesajını session'dan al (yönlendirme sonrası için)
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Etkinlik silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $event_id = intval($_GET['delete']);
    
    // İşlemlerin tutarlılığını sağlamak için transaction başlatalım.
    // Yani, ya hem biletler hem etkinlik silinir, ya da hiçbiri silinmez.
    $conn->begin_transaction();

    try {
        // 1. Adım: Önce bu etkinliğe ait olan tüm biletleri veritabanından sil.
        $stmt_tickets = $conn->prepare("DELETE FROM tickets WHERE event_id = ?");
        $stmt_tickets->bind_param("i", $event_id);
        $stmt_tickets->execute();
        $stmt_tickets->close();
        
        // 2. Adım: Biletler silindikten sonra etkinliğin kendisini sil.
        $stmt_event = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt_event->bind_param("i", $event_id);
        $stmt_event->execute();
        
        // Silme işleminin başarılı olup olmadığını kontrol et
        if ($stmt_event->affected_rows > 0) {
            $message = "Etkinlik ve ilgili tüm biletler başarıyla silindi.";
        } else {
            // Bu durum, sayfa yenilendiğinde veya geçersiz ID girildiğinde yaşanabilir.
            $error = "Silinecek etkinlik bulunamadı.";
        }
        $stmt_event->close();
        
        // Tüm işlemler başarılıysa, veritabanındaki değişiklikleri onayla.
        $conn->commit();
        
    } catch (mysqli_sql_exception $exception) {
        // Eğer herhangi bir adımda hata olursa, tüm işlemleri geri al.
        $conn->rollback();
        $error = "Veritabanı hatası nedeniyle etkinlik silinemedi: " . $exception->getMessage();
    }
}

// Etkinlik ekleme/güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $date = sanitize($_POST['date']);
    $capacity = intval($_POST['capacity']);
    $price = floatval($_POST['price']);
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    
    // Validasyon
    if (empty($title) || empty($location) || empty($date) || empty($capacity) || $price < 0 || $latitude === null || $longitude === null) {
        $error = "Lütfen tüm zorunlu (*) alanları doldurun.";
    } elseif ($capacity <= 0) {
        $error = "Kapasite sıfırdan büyük olmalıdır.";
    } else {
        // Etkinlik ID kontrol et (güncelleme mi ekleme mi)
        if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
            // Güncelleme
            $event_id = intval($_POST['event_id']);
            
            $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, location = ?, date = ?, capacity = ?, price = ?, latitude = ?, longitude = ? WHERE id = ?");
            $stmt->bind_param("ssssiddii", $title, $description, $location, $date, $capacity, $price, $latitude, $longitude, $event_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Etkinlik başarıyla güncellendi.";
            } else {
                $error = "Etkinlik güncellenirken bir hata oluştu: " . $stmt->error;
            }
        } else {
            // Yeni etkinlik ekleme
            $stmt = $conn->prepare("INSERT INTO events (title, description, location, date, capacity, price, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiddd", $title, $description, $location, $date, $capacity, $price, $latitude, $longitude);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Etkinlik başarıyla eklendi.";
            } else {
                $error = "Etkinlik eklenirken bir hata oluştu: " . $stmt->error;
            }
        }
        $stmt->close();
        
        // Eğer hata yoksa, sayfayı yeniden yönlendir (PRG Pattern)
        if (empty($error)) {
            header("Location: " . BASE_URL . "admin/events.php");
            exit;
        }
    }
}

// Etkinlikleri getir
$events = [];
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) FROM events WHERE title LIKE ?";
$sql = "SELECT e.*, (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id) as sold_tickets 
        FROM events e 
        WHERE e.title LIKE ? 
        ORDER BY e.date DESC 
        LIMIT ? OFFSET ?";
$search_param = "%" . $search_query . "%";

// Toplam etkinlik sayısını al (sayfalama için)
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("s", $search_param);
$stmt->execute();
$stmt->bind_result($total_events);
$stmt->fetch();
$stmt->close();
$total_pages = ceil($total_events / $limit);

// Etkinlik listesini al
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $search_param, $limit, $offset);
$stmt->execute();

// bind_result için değişkenler oluşturalım.
// e.* kullandığımız için tüm sütunları eklememiz gerekiyor.
$stmt->bind_result($id, $title, $description, $location, $date, $capacity, $price, $latitude, $longitude, $created_at, $updated_at, $sold_tickets);

while ($stmt->fetch()) {
    $events[] = [
        'id' => $id,
        'title' => $title,
        'description' => $description,
        'location' => $location,
        'date' => $date,
        'capacity' => $capacity,
        'price' => $price,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'created_at' => $created_at,
        'updated_at' => $updated_at,
        'sold_tickets' => $sold_tickets
    ];
}
$stmt->close();

// Düzenlenecek etkinlik
$edit_event = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $event_id = intval($_GET['edit']);
    
    $stmt = $conn->prepare("SELECT id, title, description, location, date, capacity, price, latitude, longitude FROM events WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($id, $title, $description, $location, $date, $capacity, $price, $latitude, $longitude);
    
    if ($stmt->fetch()) {
        $edit_event = [
            'id' => $id, 'title' => $title, 'description' => $description, 'location' => $location, 
            'date' => $date, 'capacity' => $capacity, 'price' => $price, 
            'latitude' => $latitude, 'longitude' => $longitude
        ];
    }
    $stmt->close();
}
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Etkinlik Yönetimi</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal">
                            <i class="fas fa-plus me-2"></i> Yeni Etkinlik Ekle
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <!-- Etkinlikler Tablosu -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Başlık</th>
                                            <th>Konum</th>
                                            <th>Tarih</th>
                                            <th>Kapasite</th>
                                            <th>Fiyat</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($events) > 0): ?>
                                            <?php foreach ($events as $event): ?>
                                                <tr>
                                                    <td><?php echo $event['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                                    <td><?php echo formatDate($event['date']); ?></td>
                                                    <td><?php echo $event['capacity']; ?></td>
                                                    <td><?php echo formatPrice($event['price']); ?></td>
                                                    <td>
                                                        <a href="<?php echo BASE_URL; ?>admin/events.php?edit=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="<?php echo BASE_URL; ?>admin/events.php?delete=<?php echo $event['id']; ?>" class="btn btn-sm btn-danger btn-delete" onclick="return confirm('Bu etkinliği silmek istediğinizden emin misiniz?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <a href="<?php echo BASE_URL; ?>event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Henüz etkinlik bulunmamaktadır.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Etkinlik Ekleme/Düzenleme Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">
                        <?php echo $edit_event ? 'Etkinlik Düzenle' : 'Yeni Etkinlik Ekle'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="<?php echo BASE_URL; ?>admin/events.php">
                        <?php if ($edit_event): ?>
                            <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Başlık*</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo $edit_event ? htmlspecialchars($edit_event['title']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Konum*</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?php echo $edit_event ? htmlspecialchars($edit_event['location']) : ''; ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date" class="form-label">Tarih ve Saat*</label>
                                <input type="datetime-local" class="form-control" id="date" name="date" 
                                       value="<?php echo $edit_event ? date('Y-m-d\TH:i', strtotime($edit_event['date'])) : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label">Kapasite*</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" min="1" 
                                       value="<?php echo $edit_event ? $edit_event['capacity'] : '100'; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Fiyat (₺)*</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" 
                                   value="<?php echo $edit_event ? $edit_event['price'] : '0.00'; ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="latitude" class="form-label">Enlem*</label>
                                <input type="text" class="form-control" id="latitude" name="latitude" 
                                       value="<?php echo $edit_event && $edit_event['latitude'] ? $edit_event['latitude'] : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="longitude" class="form-label">Boylam*</label>
                                <input type="text" class="form-control" id="longitude" name="longitude" 
                                       value="<?php echo $edit_event && $edit_event['longitude'] ? $edit_event['longitude'] : ''; ?>" required>
                            </div>
                            
                            <div class="col-12">
                                <a href="https://www.latlong.net/" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-external-link-alt me-1"></i> Konum bilgisi için latlong.net adresini kullanabilirsiniz.
                                </a>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_event ? 'Güncelle' : 'Ekle'; ?>
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        </div>
                    </form>
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
    
    <script>
    $(document).ready(function() {
        // Düzenleme modalını otomatik aç
        <?php if ($edit_event): ?>
        var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        eventModal.show();
        <?php endif; ?>
    });
    </script>
</body>
</html> 