<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header('Location: login.php');
    exit();
}

$kupon_id = $_GET['id'] ?? 0;

if (!$kupon_id) {
    $_SESSION['error'] = "Geçersiz kupon ID!";
    header('Location: firma-kupon.php');
    exit();
}

try {
    $check_stmt = $db->prepare("
        SELECT c.*, COUNT(uc.id) as kullanım_sayisi
        FROM coupons c
        LEFT JOIN user_coupons uc ON c.id = uc.coupon_id
        WHERE c.id = ? AND c.company_id = ?
        GROUP BY c.id
    ");
    $check_stmt->execute([$kupon_id, $_SESSION['company_id']]);
    $kupon = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kupon) {
        $_SESSION['error'] = "Kupon bulunamadı veya silme yetkiniz yok!";
        header('Location: firma-kupon.php');
        exit();
    }
    
    if ($kupon['kullanım_sayisi'] > 0) {
        $_SESSION['error'] = "Bu kupon kullanıldığı için silinemez! Kullanım sayısı: " . $kupon['kullanım_sayisi'];
        header('Location: firma-kupon.php');
        exit();
    }
    
    $db->beginTransaction();
    
    $delete_relations_stmt = $db->prepare("DELETE FROM user_coupons WHERE coupon_id = ?");
    $delete_relations_stmt->execute([$kupon_id]);
    
    $delete_kupon_stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
    $delete_kupon_stmt->execute([$kupon_id]);
    
    $db->commit();
    
    $_SESSION['success'] = "Kupon başarıyla silindi!";
    
} catch (PDOException $e) {
    $db->rollBack();
    $_SESSION['error'] = "Kupon silinirken hata oluştu: " . $e->getMessage();
}

header('Location: firma-kupon.php');
exit();
?>