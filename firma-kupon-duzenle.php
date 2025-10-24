<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kupon_id = $_POST['kupon_id'];
    $discount = (float)$_POST['discount'];
    $usage_limit = (int)$_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];
    
    $errors = [];
    
    if ($discount <= 0 || $discount > 100) {
        $errors[] = "İndirim oranı 1-100 arasında olmalıdır!";
    }
    
    if ($usage_limit < 0) {
        $errors[] = "Kullanım limiti 0 veya daha büyük olmalıdır!";
    }
    
    if (empty($expire_date)) {
        $errors[] = "Son kullanma tarihi gereklidir!";
    } elseif (strtotime($expire_date) <= time()) {
        $errors[] = "Son kullanma tarihi gelecekte bir tarih olmalıdır!";
    }
    
    if (empty($errors)) {
        try {
            $check_stmt = $db->prepare("SELECT id FROM coupons WHERE id = ? AND company_id = ?");
            $check_stmt->execute([$kupon_id, $_SESSION['company_id']]);
            
            if (!$check_stmt->fetch()) {
                $errors[] = "Bu kuponu düzenleme yetkiniz yok!";
            }
        } catch (PDOException $e) {
            $errors[] = "Yetki kontrolü sırasında hata oluştu!";
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                UPDATE coupons 
                SET discount = ?, usage_limit = ?, expire_date = ?
                WHERE id = ? AND company_id = ?
            ");
            
            $stmt->execute([
                $discount,
                $usage_limit,
                $expire_date,
                $kupon_id,
                $_SESSION['company_id']
            ]);
            
            $db->commit();
            
            $_SESSION['success'] = "Kupon başarıyla güncellendi!";
            header('Location: firma-kupon.php');
            exit();
            
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['error'] = "Kupon güncellenirken hata oluştu: " . $e->getMessage();
            header('Location: firma-kupon.php');
            exit();
        }
    } else {
        $_SESSION['form_errors'] = $errors;
        header('Location: firma-kupon.php');
        exit();
    }
} else {
    header('Location: firma-kupon.php');
    exit();
}
?>