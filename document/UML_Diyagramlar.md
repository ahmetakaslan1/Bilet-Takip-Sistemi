# Bilet Sistemi Projesi UML Diyagramları

## Proje Adı: Bilet Sistemi
## Grup Üyesi: [Adınızı ve Öğrenci Numaranızı Buraya Yazın]

## 1. Activity Diyagramı (Bilet Satın Alma Süreci)

```mermaid
graph TD
    A[Başla] --> B[Kullanıcı Girişi]
    B -->|Giriş Başarılı| C[Etkinlik Listesini Görüntüle]
    B -->|Giriş Başarısız| BA[Hata Mesajı Göster]
    BA --> B
    C --> D[Etkinlik Seç]
    D --> E[Etkinlik Detaylarını Görüntüle]
    E --> F{Bilet Var mı?}
    F -->|Evet| G[Bilet Sayfasına Yönlendir]
    F -->|Hayır| H{Kapasite Var mı?}
    H -->|Hayır| I[Tükendi Mesajı Göster]
    H -->|Evet| J[Bilet Satın Al]
    J --> K[Bilet Oluştur]
    K --> L[QR Kod Oluştur]
    L --> M[Bilet Detaylarını Göster]
    M --> N[Bitir]
```

## 2. Sequence Diyagramı (Konum Tabanlı Etkinlik Önerisi)

```mermaid
sequenceDiagram
    participant K as Kullanıcı
    participant W as Web Arayüzü
    participant S as Sunucu
    participant VT as Veritabanı
    
    K->>W: Konum İzni Talep Et
    W->>K: Konum İzni İste
    K->>W: Konum İzni Ver
    W->>S: Konum Bilgisini Gönder
    S->>VT: Konum Bilgisini Kaydet
    S->>VT: Yakındaki Etkinlikleri Sorgula
    VT-->>S: Etkinlik Listesi
    S->>W: Etkinlik Listesini Gönder
    W->>K: En Yakın Etkinlikleri Göster
```

## 3. State (Durum) Diyagramı (Bilet Durumları)

```mermaid
stateDiagram-v2
    [*] --> Oluşturuldu
    Oluşturuldu --> Aktif: Ödeme Tamamlandı
    Aktif --> Kullanıldı: QR Kod Okutuldu
    Aktif --> İptalEdildi: İptal Talebi Onaylandı
    Kullanıldı --> [*]
    İptalEdildi --> [*]
```

## 4. Collaboration Diyagramı (Bilet Doğrulama)

```mermaid
graph TD
    A[Bilet Doğrulama Sistemi] -->|1: Token Sorgula| B[Doğrulama İşlemi]
    B -->|2: Token Bilgisini Oku| C[Veritabanı]
    C -->|3: Token Bilgisi| B
    B -->|4: Kullanıcı Bilgisi Sorgula| C
    C -->|5: Kullanıcı Bilgisi| B
    B -->|6: Etkinlik Bilgisi Sorgula| C
    C -->|7: Etkinlik Bilgisi| B
    B -->|8: Doğrulama Sonucu| A
    A -->|9: Bileti İşaretle| D[Bilet Durum Güncelleme]
    D -->|10: Durum Güncelle| C
```

## 5. Package (Paket) Diyagramı (Sistem Mimarisi)

```mermaid
graph TD
    subgraph Kullanıcı_Arayüzü
        A[Ana Sayfa]
        B[Etkinlik Sayfaları]
        C[Kullanıcı Profili]
        D[Bilet Sayfaları]
    end
    
    subgraph Çekirdek_Sistem
        E[Kullanıcı Yönetimi]
        F[Etkinlik Yönetimi]
        G[Bilet İşlemleri]
        H[Konum Hizmetleri]
    end
    
    subgraph Veritabanı_Katmanı
        I[Kullanıcı Tablosu]
        J[Etkinlik Tablosu]
        K[Bilet Tablosu]
        L[Konum Tablosu]
        M[Destek Tablosu]
    end
    
    subgraph Admin_Paneli
        N[Kullanıcı Yönetimi]
        O[Etkinlik Yönetimi]
        P[Bilet Raporları]
        Q[Destek Talepleri]
    end
    
    Kullanıcı_Arayüzü --> Çekirdek_Sistem
    Çekirdek_Sistem --> Veritabanı_Katmanı
    Admin_Paneli --> Çekirdek_Sistem
```

# Sınıf Diyagramı (Bonus)

```mermaid
classDiagram
    class User {
        +int id
        +string name
        +string email
        +string password
        +string role
        +datetime created_at
        +datetime updated_at
        +register()
        +login()
        +updateProfile()
    }
    
    class Event {
        +int id
        +string title
        +string description
        +string location
        +datetime date
        +int capacity
        +float price
        +float latitude
        +float longitude
        +datetime created_at
        +datetime updated_at
        +create()
        +update()
        +delete()
        +getRemainingCapacity()
    }
    
    class Ticket {
        +int id
        +int user_id
        +int event_id
        +string token
        +datetime purchase_date
        +string status
        +generate()
        +verify()
        +cancel()
    }
    
    class UserLocation {
        +int id
        +int user_id
        +float latitude
        +float longitude
        +datetime created_at
        +datetime updated_at
        +update()
    }
    
    class SupportTicket {
        +int id
        +int user_id
        +string subject
        +string message
        +string status
        +datetime created_at
        +datetime updated_at
        +create()
        +reply()
        +close()
    }
    
    User "1" -- "n" Ticket: satın alır
    User "1" -- "1" UserLocation: sahip olur
    User "1" -- "n" SupportTicket: oluşturur
    Event "1" -- "n" Ticket: barındırır
``` 