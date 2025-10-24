<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    
    if (strtotime($departure_time) <= time()) {
        $errors[] = "Kalkış zamanı gelecekte bir tarih olmalıdır!";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $sefer_id = generateUUID();
            
            $stmt = $db->prepare("
                INSERT INTO trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $sefer_id,
                $_SESSION['company_id'],
                $departure_city,
                $destination_city,
                $departure_time,
                $arrival_time,
                $price,
                $capacity
            ]);
            
            $db->commit();
            
            $_SESSION['success'] = "Sefer başarıyla eklendi!";
            header('Location: firma-paneli.php');
            exit();
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Sefer eklenirken hata oluştu: " . $e->getMessage();
        }
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: firma-paneli.php');
    exit();
}
?>