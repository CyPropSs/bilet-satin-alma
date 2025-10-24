<?php
include 'includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız!']);
    exit();
}

$ticket_id = $_GET['id'] ?? 0;

try {
    $db->beginTransaction();
    
    $stmt = $db->prepare("
        SELECT t.*, tr.departure_time, tr.price, tr.departure_city, tr.destination_city
        FROM tickets t 
        LEFT JOIN trips tr ON t.trip_id = tr.id 
        WHERE t.id = ? AND t.user_id = ? AND t.status = 'active'
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    $bilet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bilet) {
        throw new Exception("Bilet bulunamadı veya zaten iptal edilmiş!");
    }
    
    $departure_time = strtotime($bilet['departure_time']);
    $current_time = time();
    $time_diff = $departure_time - $current_time;
    
    if ($time_diff <= 3600) {
        throw new Exception("Kalkış saatine 1 saatten az kaldığı için bilet iptal edilemez!");
    }
    
    $stmt = $db->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$ticket_id]);
    
    $yeni_bakiye = $_SESSION['user_balance'] + $bilet['total_price'];
    $stmt = $db->prepare("UPDATE user SET balance = ? WHERE id = ?");
    $stmt->execute([$yeni_bakiye, $_SESSION['user_id']]);
    
    $_SESSION['user_balance'] = $yeni_bakiye;
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Bilet iptal edildi. ₺' . number_format($bilet['total_price'], 2) . ' bakiyenize iade edildi.',
        'new_balance' => $yeni_bakiye
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>