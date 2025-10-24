🎫 Bilet Satın Alma Platformu

Bu proje, PHP ve SQLite kullanılarak geliştirilmiş, çok kullanıcı rollü bir otobüs bileti satın alma platformudur. Ziyaretçiler, yolcular, firma yöneticileri ve sistem yöneticileri için farklı yetkilendirme seviyeleri bulunur.
Proje, modern web teknolojilerini kullanarak dinamik bir yapı oluşturmayı ve temel web güvenlik ilkelerini uygulamayı amaçlar.

🚀 Özellikler
👤 Ziyaretçi (Giriş Yapmamış Kullanıcı)

Kalkış ve varış noktalarına göre sefer arayabilir.

Sefer detaylarını görebilir ancak bilet satın alamaz.

Satın alma butonuna tıkladığında “Lütfen Giriş Yapın” uyarısı alır.

🧍 User (Yolcu)

Kayıt olabilir, giriş yapabilir ve hesabını yönetebilir.

Seferleri listeleyebilir, bilet satın alabilir veya iptal edebilir.

Bilet ücretini sanal kredi ile öder, iptal durumunda ücret iade edilir.

Biletlerini PDF formatında indirebilir.

Kalkış saatine 1 saatten az kala bilet iptali yapılamaz.

🏢 Firma Admin (Firma Yetkilisi)

Kendi firmasına ait seferleri yönetebilir (CRUD işlemleri).

Yeni sefer ekleyebilir, düzenleyebilir veya silebilir.

Firma özelinde indirim kuponları oluşturabilir, düzenleyebilir, silebilir.

🛠️ Admin

Tüm firmaları ve firma yöneticilerini yönetir.

Yeni firma ve “Firma Admin” kullanıcıları oluşturabilir.

Tüm firmalarda geçerli indirim kuponlarını yönetebilir.


| Sayfa / İşlev      | Ziyaretçi | User | Firma Admin | Admin | Açıklama                    |
| ------------------ | --------- | ---- | ----------- | ----- | --------------------------- |
| Ana Sayfa          | ✅         | ✅    | ✅           | ✅     | Sefer arama ve listeleme    |
| Giriş / Kayıt      | ✅         | ✅    | ✅           | ✅     | Kullanıcı işlemleri         |
| Sefer Detayları    | ✅         | ✅    | ✅           | ✅     | Sefer bilgileri             |
| Bilet Satın Alma   | ❌         | ✅    | ❌           | ❌     | Kredi ile satın alma        |
| Bilet İptal Etme   | ❌         | ✅    | ✅           | ❌     | 1 saat kuralı kontrolü      |
| Hesabım / Biletler | ❌         | ✅    | ✅           | ❌     | Profil ve PDF indir         |
| Firma Admin Paneli | ❌         | ❌    | ✅           | ❌     | Firma sefer yönetimi        |
| Admin Paneli       | ❌         | ❌    | ❌           | ✅     | Firma ve kullanıcı yönetimi |



🛠️ Teknolojiler ve Araçlar

Dil: PHP

Veritabanı: SQLite

Arayüz: HTML, CSS (isteğe bağlı: Bootstrap)

PDF Üretimi: PHP tabanlı PDF kütüphanesi (ör. FPDF veya TCPDF)

Paketleme: Docker

🐳 Docker ile Çalıştırma

Projeyi Docker ortamında çalıştırmak için:

# Depoyu klonla
git clone https://github.com/CyPropSs/bilet-satin-alma.git
cd bilet-satin-alma

# Docker image oluştur
docker build -t bilet-satin-alma .

# Container başlat
docker run -p 8080:80 bilet-satin-alma


Uygulamaya tarayıcıdan http://localhost:8080
 adresiyle erişebilirsin.

kullanmadan önce http://localhost:8080/database/setup.php adresine git.

eğer veritabanını temizlemek istersen http://localhost:8080/database/clear.php adresine git.

veritabanında değişiklik yapmak için http://localhost:8080/database/check_all.php adresine git.

