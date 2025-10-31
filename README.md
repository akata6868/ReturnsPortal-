# ReturnsPortal - PlentyMarkets İade Yönetim Sistemi

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PlentyMarkets](https://img.shields.io/badge/PlentyMarkets-7.0+-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)

Profesyonel e-ticaret işletmeleri için kapsamlı iade yönetim portalı. Müşteriler için self-servis iade sistemi ve yöneticiler için güçlü iade yönetim paneli.

## 🌟 Özellikler

### Müşteri Özellikleri
- ✅ **Self-Servis İade Portalı**: Müşteriler sipariş numarası ile kolayca iade talebi oluşturabilir
- 📦 **Ürün Seçimi**: Siparişteki tüm ürünler arasından iade edilecek ürünleri seçme
- 📝 **İade Nedenleri**: Özelleştirilebilir iade nedenleri listesi
- 📸 **Fotoğraf Yükleme**: İade edilecek ürünlerin fotoğraflarını yükleme
- 🔍 **İade Takibi**: Gerçek zamanlı iade durumu takibi
- 📧 **E-posta Bildirimleri**: Otomatik durum değişikliği bildirimleri
- 🏷️ **İade Etiketi**: Yazdırılabilir iade gönderim etiketi oluşturma
- 📱 **Mobil Uyumlu**: Responsive tasarım

### Admin Özellikleri
- 📊 **Güçlü Dashboard**: İade istatistikleri ve metrikleri
- ✅ **İade Onay/Red**: Hızlı iade onay ve red işlemleri
- 📋 **İade Listesi**: Filtrelenebilir ve aranabilir iade listesi
- 💰 **Geri Ödeme Yönetimi**: Otomatik geri ödeme işlemleri
- 📈 **Raporlama**: Detaylı iade raporları ve analizleri
- 🔔 **Bildirimler**: Yeni iade talepleri için e-posta bildirimleri
- 📁 **Dışa Aktarma**: CSV formatında iade verisi dışa aktarma
- ⚙️ **Özelleştirilebilir**: Plugin ayarları ile tam kontrol

### Teknik Özellikler
- 🔌 **PlentyMarkets REST API**: Tam entegrasyon
- 🌍 **Çoklu Dil**: Almanca, İngilizce, Türkçe
- 🎨 **Bootstrap 5**: Modern ve responsive arayüz
- 🔐 **Güvenli**: OAuth 2.0 kimlik doğrulama
- 📦 **Modüler**: Kolay genişletilebilir mimari
- 🚀 **Performanslı**: Optimize edilmiş veritabanı sorguları
- 📝 **Logging**: Kapsamlı log sistemi
- 🔄 **Events**: Event-driven architecture

## 📋 Gereksinimler

- PlentyMarkets 7.0 veya üzeri
- PHP 7.4 veya üzeri
- IO Plugin 5.0 veya üzeri
- Ceres Theme (opsiyonel, kendi theme'inizi de kullanabilirsiniz)

## 🚀 Kurulum

### 1. Plugin Dosyalarını Yükleme

#### GitHub Üzerinden
```bash
# GitHub repository'nizi oluşturun
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/KULLANICI_ADI/ReturnsPortal.git
git push -u origin master
```

#### PlentyMarkets Backend'e Yükleme

1. PlentyMarkets backend'e giriş yapın
2. **Plugins » Plugin overview** menüsüne gidin
3. **Add repository** butonuna tıklayın
4. GitHub repository URL'inizi ekleyin:
   - Public repository için: Sadece URL'i girin
   - Private repository için: GitHub token'ı girin

5. **Pull** butonuna tıklayarak plugin'i çekin
6. **Deploy** butonuna tıklayarak plugin'i deploy edin

### 2. Plugin'i Aktif Etme

1. **System » Client » Select client**
2. **Plugins » Plugin set overview**
3. Plugin set'inizi açın
4. **ReturnsPortal** plugin'ini aktif edin
5. **Save** butonuna tıklayın
6. **Deploy** yapın

### 3. Veritabanı Tablolarını Oluşturma

Plugin ilk deploy edildiğinde otomatik olarak aşağıdaki tablolar oluşturulur:
- `returns` - İade talepleri
- `return_items` - İade edilen ürünler
- `return_status_history` - İade durum geçmişi

### 4. Plugin Ayarları

1. **System » System settings » Services » ReturnsPortal**
2. Aşağıdaki ayarları yapılandırın:

#### Genel Ayarlar
- **Otomatik Onay**: İade taleplerini otomatik onaylama
- **İade Süresi**: İade için geçerli gün sayısı (varsayılan: 14 gün)
- **Fotoğraf Gereksinimi**: İade için fotoğraf zorunlu mu?
- **E-posta Bildirimleri**: Otomatik bildirimler gönder
- **Admin E-posta**: Bildirimler için admin e-posta adresi

#### Kargo Ayarları
- **İade Kargo Yöntemi**: 
  - Müşteri öder
  - Ön ödemeli etiket
  - Ücretsiz iade
- **Varsayılan Depo ID**: İade ürünlerin gideceği depo

#### Geri Ödeme Ayarları
- **Geri Ödeme Yöntemi**:
  - Orijinal ödeme yöntemi
  - Mağaza kredisi
  - Değişim
- **Maksimum İade Ürün Sayısı**: İade başına maksimum ürün

#### İade Nedenleri
İade nedenlerini özelleştirin (varsayılan olarak gelir):
- Yanlış ürün gönderildi
- Ürün arızalı
- Açıklamaya uygun değil
- Fikrimi değiştirdim
- Beden sorunu
- Kalite sorunu
- Geç teslimat
- Diğer

## 🎯 Kullanım

### Müşteri Tarafı

#### İade Talebi Oluşturma

1. Müşteri `/returns` sayfasına gider
2. Sipariş numarasını girer veya "Siparişlerim" sayfasından iade başlatır
3. İade edilecek ürünleri seçer
4. Her ürün için iade nedenini belirtir
5. İsteğe bağlı olarak fotoğraf yükler
6. İletişim bilgilerini girer
7. İade talebini gönderir

#### İade Takibi

1. `/returns/track/{returnId}` sayfasını ziyaret eder
2. veya `/my-returns` sayfasından tüm iadelerini görüntüler
3. Gerçek zamanlı durum güncellemelerini takip eder
4. Onaylandığında iade etiketini yazdırır

### Admin Tarafı

#### İade Yönetimi

1. **Admin Panel** → **Returns Management**
2. Bekleyen iadeleri görüntüle
3. İade detaylarını incele:
   - Sipariş bilgileri
   - Ürün listesi
   - Müşteri notları
   - Fotoğraflar

4. **İade Onaylama**:
   ```
   - İadeyi onayla
   - Admin notu ekle
   - Müşteriye otomatik e-posta gönderilir
   ```

5. **İade Reddetme**:
   ```
   - İadeyi reddet
   - Red nedeni belirt
   - Müşteriye bildirim gönderilir
   ```

6. **İade Alma**:
   ```
   - Ürün teslim alındığında işaretle
   - Ürün kalitesini değerlendir
   - Durum otomatik güncellenir
   ```

7. **Geri Ödeme**:
   ```
   - Geri ödeme yöntemini seç
   - Geri ödeme tutarını belirle
   - Geri ödemeyi işle
   - Müşteriye bildirim gönderilir
   ```

#### Raporlama

**İstatistikler**:
- Toplam iade sayısı
- Bekleyen iadeler
- Onaylanan iadeler
- Tamamlanan iadeler
- Toplam geri ödeme tutarı
- İade oranı

**Dışa Aktarma**:
```bash
# Tüm iadeleri CSV olarak dışa aktar
GET /admin/returns/export?format=csv

# Belirli tarih aralığı
GET /admin/returns/export?dateFrom=2025-01-01&dateTo=2025-12-31&format=csv
```

## 🔧 API Kullanımı

### REST API Endpoints

#### İade Oluşturma
```http
POST /api/v1/returns
Content-Type: application/json
Authorization: Bearer {token}

{
  "order_id": 12345,
  "customer_email": "customer@example.com",
  "customer_name": "John Doe",
  "customer_phone": "+49123456789",
  "return_reason": "wrong_item",
  "customer_notes": "Ürün yanlış gönderildi",
  "items": [
    {
      "order_item_id": 100,
      "item_variation_id": 1000,
      "item_name": "Ürün Adı",
      "quantity": 1,
      "price": 29.99,
      "reason": "wrong_item"
    }
  ]
}
```

#### İade Listesi
```http
GET /api/v1/returns
Authorization: Bearer {token}
```

#### İade Detayı
```http
GET /api/v1/returns/{returnId}
Authorization: Bearer {token}
```

#### İade Durumu Güncelleme
```http
POST /api/v1/returns/{returnId}/status
Authorization: Bearer {token}

{
  "status": "approved",
  "admin_note": "İade onaylandı"
}
```

## 🎨 Özelleştirme

### Twig Template'lerini Özelleştirme

```twig
{# Kendi theme'inizde override edin #}
{% extends "ReturnsPortal::content.return-form" %}

{% block customContent %}
    {# Özel içeriğiniz #}
{% endblock %}
```

### CSS Özelleştirme

```scss
// resources/css/custom-returns.scss

// Kendi renklerinizi tanımlayın
:root {
    --return-primary: #ff6b6b;
    --return-success: #51cf66;
}

// Özel stiller ekleyin
.returns-portal-form {
    .card {
        border-radius: 15px;
    }
}
```

### JavaScript Özelleştirme

```javascript
// resources/js/custom-returns.js

document.addEventListener('DOMContentLoaded', function() {
    // Özel JavaScript kodunuz
});
```

## 📧 E-posta Şablonları

Plugin aşağıdaki e-posta şablonlarını içerir:

1. **İade Talebi Oluşturuldu** (`return_created.twig`)
2. **İade Onaylandı** (`return_approved.twig`)
3. **İade Reddedildi** (`return_rejected.twig`)
4. **İade Alındı** (`return_received.twig`)
5. **Geri Ödeme İşlendi** (`return_refunded.twig`)

E-posta şablonlarını özelleştirmek için:
```
resources/views/emails/
```

## 🌍 Çoklu Dil Desteği

Dil dosyaları:
```
resources/lang/de/messages.properties
resources/lang/en/messages.properties
resources/lang/tr/messages.properties
```

Yeni dil eklemek için:
```properties
# resources/lang/fr/messages.properties
createReturnTitle = "Créer une demande de retour"
orderNumber = "Numéro de commande"
...
```

## 🔍 Sorun Giderme

### İade Talebi Oluşturulamıyor

1. **Log'ları kontrol edin**:
   ```
   System » System settings » Logs
   ```

2. **Plugin aktif mi?**
   ```
   Plugins » Plugin overview
   ```

3. **Veritabanı tabloları oluştu mu?**
   ```sql
   SHOW TABLES LIKE 'ReturnsPortal%';
   ```

### E-posta Gönderilmiyor

1. **E-posta ayarlarını kontrol edin**:
   ```
   System » System settings » Email
   ```

2. **Plugin ayarlarında e-posta bildirimleri aktif mi?**

3. **SMTP ayarları doğru mu?**

### İade Durumu Güncellenmiyor

1. **Event dispatcher çalışıyor mu?**
2. **Log'larda hata var mı?**
3. **Veritabanı bağlantısı aktif mi?**

## 📝 Changelog

### Version 1.0.0 (2025-01-30)
- ✨ İlk sürüm yayınlandı
- ✅ Müşteri self-servis portalı
- ✅ Admin yönetim paneli
- ✅ Çoklu dil desteği (DE, EN, TR)
- ✅ E-posta bildirimleri
- ✅ İade takibi
- ✅ REST API
- ✅ Raporlama ve dışa aktarma

## 🤝 Katkıda Bulunma

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/AmazingFeature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add some AmazingFeature'`)
4. Branch'inizi push edin (`git push origin feature/AmazingFeature`)
5. Pull Request açın

## 📄 Lisans

Bu plugin AGPL-3.0 lisansı ile lisanslanmıştır.

## 💬 Destek

- **Dokümantasyon**: [Link eklenecek]
- **Issues**: GitHub Issues
- **E-posta**: support@example.com
- **Forum**: PlentyMarkets Forum

## 👥 Yazarlar

- **Your Company** - *Initial work*

## 🙏 Teşekkürler

- PlentyMarkets ekibine harika platform için
- Tüm katkıda bulunanlara

---

**Not**: Bu plugin PlentyMarkets marketplace'inde yayınlanmadan önce kapsamlı test edilmelidir.

## 📸 Ekran Görüntüleri

### Müşteri Portalı
![İade Formu](docs/screenshots/return-form.png)
![İade Takibi](docs/screenshots/return-tracking.png)

### Admin Paneli
![Admin Dashboard](docs/screenshots/admin-dashboard.png)
![İade Detayı](docs/screenshots/admin-detail.png)

## 🔗 Bağlantılar

- [PlentyMarkets Developers](https://developers.plentymarkets.com/)
- [PlentyMarkets Marketplace](https://marketplace.plentymarkets.com/)
- [Plugin Documentation](https://developers.plentymarkets.com/en-gb/developers)
