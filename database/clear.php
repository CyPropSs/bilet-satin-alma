<?php
echo "<h2>🗑️ Database Tam Temizleme</h2>";

try {
    $db = new PDO('sqlite:database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Veritabanı bağlantısı kuruldu<br>";
    
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "ℹ️ Veritabanında tablo bulunamadı<br>";
    } else {
        echo "<h3>📋 Silinecek Tablolar:</h3>";
        echo "<ul>";
        foreach($tables as $table) {
            echo "<li>❌ " . $table . "</li>";
        }
        echo "</ul>";
        
        $db->exec("PRAGMA foreign_keys = OFF");
        
        $deleted_tables = 0;
        foreach($tables as $table) {
            try {
                $db->exec("DROP TABLE IF EXISTS " . $table);
                echo "✅ " . $table . " tablosu silindi<br>";
                $deleted_tables++;
            } catch (PDOException $e) {
                echo "❌ " . $table . " silinirken hata: " . $e->getMessage() . "<br>";
            }
        }
        
        $db->exec("PRAGMA foreign_keys = ON");
        
        echo "<h3 style='color: green;'>🎉 Temizleme Tamamlandı!</h3>";
        echo "<p><strong>" . $deleted_tables . " tablo silindi.</strong></p>";
    }
    
    echo "<br><div style='margin-top: 20px;'>";
    echo "<a href='auto_setup.php' class='btn btn-success btn-lg'>🔄 Veritabanını Yeniden Kur</a> ";
    echo "<a href='../index.php' class='btn btn-primary btn-lg'>🏠 Ana Sayfa</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='color: red;'><h3>❌ Hata:</h3>";
    echo "Mesaj: " . $e->getMessage() . "<br>";
    echo "Dosya: " . $e->getFile() . "<br>";
    echo "Satır: " . $e->getLine() . "</div>";
}
?>