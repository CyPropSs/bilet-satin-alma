<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "Tüm alanları doldurun!";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Yeni şifreler eşleşmiyor!";
    }
    
    if (strlen($new_password) < 6) {
        $errors[] = "Yeni şifre en az 6 karakter olmalıdır!";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT password FROM user WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                $errors[] = "Mevcut şifreniz yanlış!";
            }
            
            if (empty($errors)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE user SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                
                $_SESSION['success'] = "Şifreniz başarıyla değiştirildi!";
                header('Location: hesabim.php#sifre');
                exit();
            }
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Şifre değiştirilirken hata oluştu: " . $e->getMessage();
            header('Location: hesabim.php#sifre');
            exit();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header('Location: hesabim.php#sifre');
        exit();
    }
} else {
    header('Location: hesabim.php');
    exit();
}
?>