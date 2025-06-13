<?php
// Mevcut sayfanın adını al
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/index.php">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'events.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/events.php">
                <i class="fas fa-calendar-alt me-2"></i> Etkinlikler
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/users.php">
                <i class="fas fa-users me-2"></i> Kullanıcılar
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'support_tickets.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/support_tickets.php">
                <i class="fas fa-life-ring me-2"></i> Destek Talepleri
            </a>
        </li>
    </ul>
</div> 