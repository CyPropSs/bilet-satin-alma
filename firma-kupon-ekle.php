<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = trim($_POST['code']);
    $discount = (float)$_POST['discount'];
    $usage_limit = (int)$_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];
    
    $errors = [];
    
    if (empty($code)) {
        $errors[] = "Kupon kodu gereklidir!";
    } elseif (strlen($code) < 3 || strlen($code) > 20) {
        $errors[] = "Kupon kodu 3-20 karakter arasında olmalıdır!";
    } elseif (!preg_match('/^[A-Z0-9]+$/', $code)) {
        $errors[] = "Kupon kodu sadece büyük harf ve rakamlardan oluşmalıdır!";
    }
    
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
            $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                $errors[] = "Bu kupon kodu zaten kullanılıyor!";
            }
        } catch (PDOException $e) {
            $errors[] = "Kupon kontrolü sırasında hata oluştu: " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $kupon_id = generateUUID();
            
            $stmt = $db->prepare("
                INSERT INTO coupons (id, code, discount, company_id, usage_limit, expire_date) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $kupon_id,
                $code,
                $discount,
                $_SESSION['company_id'], // company_id'yi session'dan al
                $usage_limit,
                $expire_date
            ]);
            
            $db->commit();
            
            $_SESSION['success'] = "Kupon başarıyla oluşturuldu: " . $code;
            header('Location: firma-kupon.php');
            exit();
            
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['error'] = "Kupon oluşturulurken hata oluştu: " . $e->getMessage();
            header('Location: firma-kupon.php');
            exit();
        }
    } else {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: firma-kupon.php');
        exit();
    }
} else {
    header('Location: firma-kupon.php');
    exit();
}
?>