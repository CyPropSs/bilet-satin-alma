<?php include 'includes/header.php'; include 'includes/config.php';?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="login-container">
                <h2 class="text-center mb-4">Giriş Yap</h2>
                
                <?php 
                if (isset($_SESSION['login_error'])) {
                    echo '<div class="alert alert-danger">❌ ' . $_SESSION['login_error'] . '</div>';
                    unset($_SESSION['login_error']);
                }
                
                if (isset($_SESSION['register_success'])) {
                    echo '<div class="alert alert-success">✅ ' . $_SESSION['register_success'] . '</div>';
                    unset($_SESSION['register_success']);
                }
                
                if (isset($_SESSION['login_success'])) {
                    echo '<div class="alert alert-success">✅ ' . $_SESSION['login_success'] . '</div>';
                    unset($_SESSION['login_success']);
                }
                ?>
                
                <form action="process-login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php';?>