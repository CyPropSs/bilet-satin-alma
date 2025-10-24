<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header('Location: login.php');
    exit();
}

$sefer_id = $_GET['id'] ?? 0;

if (!$sefer_id) {
    $_SESSION['error'] = "Geçersiz sefer ID!";
    header('Location: firma-paneli.php');
    exit();
}

try {
    $check_stmt = $db->prepare("
        SELECT t.id 
        FROM trips t
        LEFT JOIN tickets tk ON t.id = tk.trip_id AND tk.status = 'active'
        WHERE t.id = ? AND t.company_id = ?
        GROUP BY t.id
        HAVING COUNT(tk.id) = 0
    ");
    $check_stmt->execute([$sefer_id, $_SESSION['company_id']]);
    $sefer = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sefer) {
        $check_active = $db->prepare("SELECT COUNT(*) as ticket_count FROM tickets WHERE trip_id = ? AND status = 'active'");
        $check_active->execute([$sefer_id]);
        $ticket_count = $check_active->fetch(PDO::FETCH_ASSOC)['ticket_count'];
        
        if ($ticket_count > 0) {
            $_SESSION['error'] = "Bu sefer için aktif biletler bulunuyor. Önce biletleri iptal edin!";
        } else {
            $_SESSION['error'] = "Sefer bulunamadı veya silme yetkiniz yok!";
        }
        
        header('Location: firma-paneli.php');
        exit();
    }
    
    $db->beginTransaction();
    
    $delete_seats_stmt = $db->prepare("
        DELETE FROM booked_seats 
        WHERE ticket_id IN (SELECT id FROM tickets WHERE trip_id = ?)
    ");
    $delete_seats_stmt->execute([$sefer_id]);
    
    $delete_tickets_stmt = $db->prepare("DELETE FROM tickets WHERE trip_id = ?");
    $delete_tickets_stmt->execute([$sefer_id]);
    
    $delete_trip_stmt = $db->prepare("DELETE FROM trips WHERE id = ?");
    $delete_trip_stmt->execute([$sefer_id]);
    
    $db->commit();
    
    $_SESSION['success'] = "Sefer başarıyla silindi!";
    
} catch (PDOException $e) {
    $db->rollBack();
    $_SESSION['error'] = "Sefer silinirken hata oluştu: " . $e->getMessage();
}

header('Location: firma-paneli.php');
exit();
?>