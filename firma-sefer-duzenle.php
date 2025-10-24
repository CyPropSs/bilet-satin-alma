<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sefer_id = $_POST['sefer_id'];
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = (float)$_POST['price'];
    $capacity = (int)$_POST['capacity'];
    
    $errors = [];
    
    if (empty($departure_city) || empty($destination_city)) {
        $errors[] = "Kalkış ve varış şehirleri gereklidir!";
    }
    
    if (empty($departure_time) || empty($arrival_time)) {
        $errors[] = "Kalkış ve varış zamanları gereklidir!";
    }
    
    if ($price <= 0) {
        $errors[] = "Geçerli bir fiyat giriniz!";
    }
    
    if ($capacity <= 0 || $capacity > 60) {
        $errors[] = "Koltuk kapasitesi 1-60 arasında olmalıdır!";
    }
    
    if (strtotime($departure_time) >= strtotime($arrival_time)) {
        $errors[] = "Varış zamanı kalkış zamanından sonra olmalıdır!";
    }
    
    try {
        $check_stmt = $db->prepare("SELECT id FROM trips WHERE id = ? AND company_id = ?");
        $check_stmt->execute([$sefer_id, $_SESSION['company_id']]);
        
        if (!$check_stmt->fetch()) {
            $errors[] = "Bu seferi düzenleme yetkiniz yok!";
        }
    } catch (PDOException $e) {
        $errors[] = "Yetki kontrolü sırasında hata oluştu!";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                UPDATE trips 
                SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ?, capacity = ?
                WHERE id = ? AND company_id = ?
            ");
            
            $stmt->execute([
                $departure_city,
                $destination_city,
                $departure_time,
                $arrival_time,
                $price,
                $capacity,
                $sefer_id,
                $_SESSION['company_id']
            ]);
            
            $db->commit();
            
            $_SESSION['success'] = "Sefer başarıyla güncellendi!";
            header('Location: firma-paneli.php');
            exit();
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Sefer güncellenirken hata oluştu: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header('Location: firma-paneli.php');
        exit();
    }
}
?>