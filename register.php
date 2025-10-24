<?php include 'includes/header.php'; include 'includes/config.php';?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="login-container">
                <h2 class="text-center mb-4">Kayıt Ol</h2>
                
                <?php 
                if (isset($_SESSION['register_errors'])) {
                    echo '<div class="alert alert-danger">';
                    foreach ($_SESSION['register_errors'] as $error) {
                        echo '<p class="mb-1">❌ ' . $error . '</p>';
                    }
                    echo '</div>';
                    unset($_SESSION['register_errors']);
                }
                
                if (isset($_SESSION['register_success'])) {
                    echo '<div class="alert alert-success">✅ ' . $_SESSION['register_success'] . '</div>';
                    unset($_SESSION['register_success']);
                }
                ?>
                
                <form action="process-register.php" method="POST" id="registerForm">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Şifre en az 6 karakter olmalıdır.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Şifre Tekrar <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Şifre en az 6 karakter olmalıdır!');
        return false;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Şifreler eşleşmiyor!');
        return false;
    }
});
</script>

<?php include 'includes/footer.php';?>