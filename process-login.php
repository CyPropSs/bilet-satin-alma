<?php
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Email ve şifre girin!";
        header('Location: login.php');
        exit();
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_balance'] = $user['balance'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['company_id'] = $user['company_id'];
            
            $_SESSION['login_success'] = "Hoş geldiniz, " . $user['full_name'] . "!";
            
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['login_error'] = "Geçersiz email veya şifre!";
            header('Location: login.php');
            exit();
        }
        
    } catch (PDOException $e) {
        $_SESSION['login_error'] = "Giriş sırasında bir hata oluştu!";
        header('Location: login.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>