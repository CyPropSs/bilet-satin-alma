<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = (float)$_POST['amount'];
    
    $errors = [];
    
    if (empty($amount) || $amount <= 0) {
        $errors[] = "Geçerli bir tutar girin!";
    }
    
    if ($amount < 10) {
        $errors[] = "Minimum yükleme tutarı ₺10'dur!";
    }
    
    if ($amount > 1000) {
        $errors[] = "Maksimum yükleme tutarı ₺1000'dir!";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT balance FROM user WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $current_balance = $stmt->fetch(PDO::FETCH_COLUMN);
            
            $new_balance = $current_balance + $amount;
            
            $stmt = $db->prepare("UPDATE user SET balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, $_SESSION['user_id']]);
            
            $_SESSION['user_balance'] = $new_balance;
            
            $_SESSION['success'] = "₺" . number_format($amount, 2) . " bakiyenize yüklendi! Yeni bakiyeniz: ₺" . number_format($new_balance, 2);
            header('Location: hesabim.php#bakiye');
            exit();
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Bakiye yüklenirken hata oluştu: " . $e->getMessage();
            header('Location: hesabim.php#bakiye');
            exit();
        }
    } else {
        $_SESSION['form_errors'] = $errors;
        header('Location: hesabim.php#bakiye');
        exit();
    }
} else {
    header('Location: hesabim.php');
    exit();
}
?>