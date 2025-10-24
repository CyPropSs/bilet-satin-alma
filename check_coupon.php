<?php
include 'includes/config.php';
header('Content-Type: application/json');

if (!isset($_GET['code']) || !isset($_GET['trip_id'])) {
    echo json_encode(['success' => false, 'message' => 'Kupon kodu ve sefer ID gerekli']);
    exit();
}

$kupon_kodu = trim($_GET['code']);
$trip_id = $_GET['trip_id'];

try {
    $trip_stmt = $db->prepare("SELECT company_id FROM trips WHERE id = ?");
    $trip_stmt->execute([$trip_id]);
    $trip = $trip_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        echo json_encode(['success' => false, 'message' => 'Sefer bulunamadı']);
        exit();
    }
    
    $trip_company_id = $trip['company_id'];
    
    $stmt = $db->prepare("
        SELECT * FROM coupons 
        WHERE code = ? AND expire_date > datetime('now')
        AND (company_id IS NULL OR company_id = ?)
        AND (usage_limit = 0 OR usage_limit > (
            SELECT COUNT(*) FROM user_coupons WHERE coupon_id = coupons.id
        ))
    ");
    $stmt->execute([$kupon_kodu, $trip_company_id]);
    $kupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($kupon) {
        if (isset($_SESSION['user_id'])) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as kullanilmis 
                FROM user_coupons 
                WHERE coupon_id = ? AND user_id = ?
            ");
            $stmt->execute([$kupon['id'], $_SESSION['user_id']]);
            $kullanilmis = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($kullanilmis['kullanilmis'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Bu kuponu daha önce kullandınız!']);
                exit();
            }
        }
        
        echo json_encode([
            'success' => true,
            'discount' => $kupon['discount'],
            'message' => 'Kupon geçerli! %' . $kupon['discount'] . ' indirim uygulanacak.',
            'kupon_id' => $kupon['id']
        ]);
    } else {
        $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->execute([$kupon_kodu]);
        $kupon_var = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($kupon_var) {
            if (strtotime($kupon_var['expire_date']) <= time()) {
                echo json_encode(['success' => false, 'message' => 'Bu kuponun süresi dolmuş!']);
            } else {
                $stmt = $db->prepare("SELECT COUNT(*) as kullanım FROM user_coupons WHERE coupon_id = ?");
                $stmt->execute([$kupon_var['id']]);
                $kullanım = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($kupon_var['usage_limit'] > 0 && $kullanım['kullanım'] >= $kupon_var['usage_limit']) {
                    echo json_encode(['success' => false, 'message' => 'Bu kuponun kullanım limiti dolmuş!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Bu kupon bu firma için geçerli değil!']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Geçersiz kupon kodu!']);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Kupon kontrolü sırasında hata oluştu: ' . $e->getMessage()]);
}
?>