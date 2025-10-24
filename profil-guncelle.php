<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Ad soyad alanı boş olamaz!";
    }
    
    if (strlen($full_name) < 2) {
        $errors[] = "Ad soyad en az 2 karakter olmalıdır!";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE user SET full_name = ? WHERE id = ?");
            $stmt->execute([$full_name, $_SESSION['user_id']]);
            
            $_SESSION['user_name'] = $full_name;
            
            $_SESSION['success'] = "Profil bilgileriniz başarıyla güncellendi!";
            header('Location: hesabim.php#profil');
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Profil güncellenirken hata oluştu: " . $e->getMessage();
            header('Location: hesabim.php#profil');
            exit();
        }
    } else {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: hesabim.php#profil');
        exit();
    }
} else {
    header('Location: hesabim.php');
    exit();
}
?>