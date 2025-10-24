<?php

if (!defined('BILETSAT_CONFIG_LOADED')) {
    define('BILETSAT_CONFIG_LOADED', true);
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!function_exists('generateUUID')) {
        function generateUUID() {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }
    }

    try {
        $db_dir = __DIR__ . '/../database';
        if (!is_dir($db_dir)) {
            mkdir($db_dir, 0777, true);
        }
        
        $db_path = $db_dir . '/database.sqlite';
        
        if (!file_exists($db_path)) {
            touch($db_path);
            chmod($db_path, 0666);
        }
        
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        

        
    } catch(PDOException $e) {
        die("Veritabanı bağlantı hatası: " . $e->getMessage() . " - Dosya: " . $db_path);
    }
}
?>