# Warehouse / Restaurant Yönetim Sistemi — Özellik Sunumu (TR)

## 1) Genel Bakış

- Bu platform; **stok, ürün kataloğu, satın alma, depo yönetimi, restoran menüsü ve siparişleri, muhasebe** gibi operasyonları tek panelde toplar.
- **Rol/Yetki** yapısı sayesinde her kullanıcı yalnızca yetkili olduğu ekranları görür ve işlem yapar.
- Arayüz; **mobil uyumlu**, hızlı ve iş akışlarını kısaltmaya odaklıdır.

---

## 2) Hızlı İşlemler (Quick Actions)

- **Stok Giriş (Input)**: Hızlı giriş kaydı oluşturma
- **Stok Çıkış (Output)**: Hızlı çıkış kaydı oluşturma
- **Sipariş Alma (Take Order)**: Restoran için manuel sipariş oluşturma

---

## 3) Envanter / Katalog Modülü

### Ürünler

- Ürün listeleme, detay görüntüleme
- Ürün bazlı stok/denge görüntüleme (depo bazlı)
- Ürün arama ve filtreleme

### Kategoriler

- Ürün kategorisi yönetimi

### Birimler (Units)

- Birim tanımlama (örn. adet, kg, litre)
- Ürünlerle ilişkilendirme

### Tedarikçiler (Suppliers)

- Tedarikçi kayıtları, listeleme ve yönetim

### Depolar (Warehouses)

- Depo tanımlama ve yönetim

---

## 4) Stok Hareketleri (Movements)

- Hareket tipleri:
    - **Giriş (in)**
    - **Çıkış (out)**
    - **Transfer (transfer)**
    - **Düzeltme (adjustment)**
- Çok satırlı giriş (birden fazla ürün/kalemle tek işlem)
- Tarih/filtre/arama ve dışa aktarma gibi operasyonel kolaylıklar

---

## 5) Satın Alma (Purchase Orders)

- Satın alma siparişi oluşturma ve yönetme
- Sipariş detayları ve kalemleri
- Süreçte teslim alma/işleme alma gibi operasyonel adımlar (yetkiye bağlı)

---

## 6) Görev Yönetimi (Tasks)

- Operasyonel görevleri takip etmeye yönelik görev ekranları
- Durum bazlı görselleştirme ve iş takibi

---

## 7) Aktivite Kayıtları (Activity Logs)

- Sistem üzerinde yapılan önemli işlemlerin kaydı
- İzlenebilirlik ve denetim kolaylığı

---

## 8) Muhasebe (Accounting)

- Gelir/Gider kayıtları
- Tarih aralığına göre filtreleme
- Hızlı işlem ekleme (cüzdan giriş/çıkış gibi)
- Rapor/çıktı (export) akışı

---

## 9) Restoran Modülü

### Restoran Menüsü (Restaurant Menu)

- Menü kategori ve ürünlerinin listelenmesi/yönetimi
- Menü arama

### Restoran Siparişleri (Restaurant Orders)

- Sipariş listeleme ve detay akışı
- Duruma göre filtreleme

### Manuel Sipariş Oluşturma (Take Order)

- Masa/ürün seçimi üzerinden hızlı sipariş akışı

---

## 10) Yönetim & Yetkilendirme

- **Admin kullanıcı yönetimi**
- **Roller ve izinler (Permissions/Roles)**
- Yetkiye göre menülerin ve aksiyonların görünmesi

---

## 11) UI/UX ve Teknik Notlar

- Tutarlı “cam/şeffaf” tasarım dili (Cards, Tables, Inputs)
- Mobil cihazlarda menü ve sayfa deneyimi
- Tablo ve listelerde kullanılabilirlik: arama, filtre, sayfalama
- Çok dilli kullanım (TR/EN) altyapısı

---

## 12) Yakında: Yapay Zekâ (AI) Ekleyeceğiz

Bu projeye, operasyonu hızlandıracak ve hataları azaltacak **AI özellikleri** eklemeyi planlıyoruz:

- **Akıllı arama & doğal dil sorgu**  
  “Geçen ay en çok çıkan ürünler” gibi soruları anlayıp ilgili listeyi getirme.

- **Talep tahmini & stok uyarıları**  
  Satış/hareket geçmişine göre kritik stok seviyesini öngörme, öneri üretme.

- **Otomatik satın alma önerileri**  
  Minimum stok, tedarik süresi ve tüketim hızına göre “ne kadar alınmalı?” önerisi.

- **Anomali tespiti**  
  Olağandışı stok çıkışları, hatalı birim fiyat/kalem girişleri için uyarı.

- **Fatura/fiş okuma (OCR) ile veri girişi**  
  Satın alma faturalarından kalemleri otomatik çıkarıp taslak kayıt oluşturma.

---

## 13) Sonuç

- Platform; depo + restoran operasyonlarını tek merkezden yönetmek için tasarlandı.
- Yetkilendirme, raporlama ve kullanıcı deneyimi odaklı ilerleniyor.
- Bir sonraki adım: AI destekli otomasyonlarla daha hızlı ve daha az hatalı operasyon.
