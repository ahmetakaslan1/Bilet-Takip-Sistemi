    </div> <!-- .container -->

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-ticket-alt me-2"></i>Etkinlik Bilet Sistemi</h5>
                    <p>Konumunuza en yakın etkinlikleri keşfedin ve biletlerinizi kolayca alın.</p>
                </div>
                <div class="col-md-3">
                    <h5>Hızlı Linkler</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Ana Sayfa</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="my_tickets.php" class="text-white">Biletlerim</a></li>
                            <li><a href="support.php" class="text-white">Destek</a></li>
                            <li><a href="logout.php" class="text-white">Çıkış Yap</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="text-white">Giriş Yap</a></li>
                            <li><a href="register.php" class="text-white">Kayıt Ol</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>İletişim</h5>
                    <address>
                        <i class="fas fa-map-marker-alt me-2"></i> İstanbul, Türkiye<br>
                        <i class="fas fa-envelope me-2"></i> info@etkinlikbilet.com<br>
                        <i class="fas fa-phone me-2"></i> +90 555 123 4567
                    </address>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Etkinlik Bilet Sistemi. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <!-- jQuery ve Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html> 