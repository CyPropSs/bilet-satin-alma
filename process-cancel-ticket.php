<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$bilet_id = $_GET['id'] ?? 0;

if (empty($bilet_id)) {
    $_SESSION['error'] = "Bilet ID'si belirtilmedi!";
    header('Location: biletlerim.php');
    exit();
}

try {
    $stmt = $db->prepare("
        SELECT 
            t.*,
            tr.departure_time,
            tr.price as sefer_fiyati,
            u.balance as user_balance
        FROM tickets t
        LEFT JOIN trips tr ON t.trip_id = tr.id
        LEFT JOIN user u ON t.user_id = u.id
        WHERE t.id = ? AND t.user_id = ? AND t.status = 'active'
    ");
    $stmt->execute([$bilet_id, $_SESSION['user_id']]);
    $bilet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bilet) {
        $_SESSION['error'] = "Bilet bulunamadı veya iptal edilemez!";
        header('Location: biletlerim.php');
        exit();
    }
    
    $departure_time = strtotime($bilet['departure_time']);
    $current_time = time();
    $time_diff = $departure_time - $current_time;
    
    if ($time_diff <= 3600) { // 1 saatten az kalmışsa
        $_SESSION['error'] = "Bilet iptali için son 1 saat içindesiniz. İptal yapılamaz!";
        header('Location: biletlerim.php');
        exit();
    }
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("DELETE FROM booked_seats WHERE ticket_id = ?");
    $stmt->execute([$bilet_id]);
    
    $stmt = $db->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->execute([$bilet_id]);
    
    $yeni_bakiye = $_SESSION['user_balance'] + $bilet['total_price'];
    $stmt = $db->prepare("UPDATE user SET balance = ? WHERE id = ?");
    $stmt->execute([$yeni_bakiye, $_SESSION['user_id']]);
    
    $db->commit();
    
    $_SESSION['user_balance'] = $yeni_bakiye;
    
    $_SESSION['success'] = "Bilet başarıyla iptal edildi! ₺" . number_format($bilet['total_price'], 2) . " bakiyenize iade edildi.";
    
    header('Location: biletlerim.php');
    exit();
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    $_SESSION['error'] = "Bilet iptal işlemi sırasında bir hata oluştu: " . $e->getMessage();
    header('Location: biletlerim.php');
    exit();
}
?>