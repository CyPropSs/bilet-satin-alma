<?php
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "Tüm zorunlu alanları doldurun!";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir email adresi girin!";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Şifreler eşleşmiyor!";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır!";
    }
    
    $stmt = $db->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Bu email adresi zaten kullanılıyor!";
    }
    
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        header('Location: register.php');
        exit();
    }
    
    try {
        $user_id = generateUUID();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO user (id, full_name, email, password, role, balance) 
            VALUES (?, ?, ?, ?, 'user', 800.0)
        ");
        
        $stmt->execute([$user_id, $full_name, $email, $hashed_password]);
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_role'] = 'user';
        $_SESSION['user_balance'] = 800.0;
        $_SESSION['user_email'] = $email;
        
        $_SESSION['register_success'] = "Kayıt başarılı! Hoş geldiniz " . $full_name . "!";
        
        header('Location: index.php');
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['register_errors'] = ["Kayıt sırasında bir hata oluştu: " . $e->getMessage()];
        header('Location: register.php');
        exit();
    }
} else {
    header('Location: register.php');
    exit();
}
?>