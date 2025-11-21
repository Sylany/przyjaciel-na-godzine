# Przyjaciel na Godzinę PRO 3.0

Profesjonalna platforma WordPress do znajdowania towarzyszy do wspólnych aktywności z pełnymi funkcjami PRO.

## 📋 Spis treści

- [Funkcje](#funkcje)
- [Wymagania](#wymagania)
- [Instalacja](#instalacja)
- [Konfiguracja](#konfiguracja)
- [Shortcodes](#shortcodes)
- [Struktura plików](#struktura-plików)
- [Funkcje PRO](#funkcje-pro)
- [API](#api)
- [FAQ](#faq)

## ✨ Funkcje

### Podstawowe
- ✅ System ogłoszeń z kategoriami i tagami
- ✅ Profile użytkowników z avatarami
- ✅ System wiadomości prywatnych
- ✅ System ocen i opinii
- ✅ Upload wielu zdjęć do ogłoszeń
- ✅ Ulubione ogłoszenia
- ✅ Zaawansowane wyszukiwanie i filtrowanie
- ✅ Responsywny design
- ✅ System powiadomień (email + in-app)
- ✅ Moderacja treści
- ✅ System raportowania

### PRO Features
- 🌟 **Subskrypcje** - 3 plany: Free, Premium, PRO
- 🌟 **Weryfikacja kont** - Weryfikacja tożsamości z dokumentami
- 🌟 **Analityka** - Zaawansowane statystyki i wykresy
- 🌟 **Kalendarz** - Zarządzanie dostępnością i rezerwacje
- 🌟 **Wyróżnione ogłoszenia** - Lepsze pozycjonowanie
- 🌟 **Boost** - Tymczasowe zwiększenie widoczności
- 🌟 **System poziomów** - Gamifikacja z punktami
- 🌟 **Badges** - Weryfikacja, Premium, Top Rated

### Płatności
- 💳 PayPal - Pełna integracja
- 💳 Stripe - Pełna integracja
- 💳 Automatyczne faktury
- 💳 Zwroty płatności
- 💳 Historia transakcji

## 📦 Wymagania

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+
- mod_rewrite włączony
- Recommended: 256MB+ PHP memory limit

## 🚀 Instalacja

### Metoda 1: Upload przez WordPress Admin

1. Pobierz plik ZIP wtyczki
2. Przejdź do **WordPress Admin > Wtyczki > Dodaj nową**
3. Kliknij **Wyślij wtyczkę** i wybierz plik ZIP
4. Kliknij **Zainstaluj teraz**
5. Po instalacji kliknij **Aktywuj**

### Metoda 2: FTP Upload

1. Wypakuj plik ZIP
2. Upload folderu `przyjaciel-na-godzine` do `/wp-content/plugins/`
3. Przejdź do **WordPress Admin > Wtyczki**
4. Aktywuj **Przyjaciel na Godzinę PRO**

### Po instalacji

Wtyczka automatycznie:
- Utworzy wszystkie wymagane tabele w bazie danych
- Utworzy strony frontendowe z shortcodes
- Ustawi domyślne kategorie
- Utworzy role użytkowników

## ⚙️ Konfiguracja

### 1. Podstawowe ustawienia

Przejdź do **Przyjaciel na Godzinę > Ustawienia**

#### Zakładka "Ogólne"
- Automatyczne zatwierdzanie ogłoszeń
- Wymagana weryfikacja email
- Maksymalna liczba ogłoszeń na użytkownika
- Maksymalna liczba zdjęć
- Wygaśnięcie ogłoszeń (dni)

#### Zakładka "Płatności"
- Waluta (PLN/EUR/USD)
- Ceny dla wyróżnienia, boost, weryfikacji
- **PayPal**: Client ID, Secret, Tryb (sandbox/live)
- **Stripe**: Publishable Key, Secret Key

#### Zakładka "Moderacja"
- Automatyczna moderacja
- Lista zakazanych słów (jedno na linię)
- Maksymalna liczba zgłoszeń przed banem

#### Zakładka "Powiadomienia"
- Email dla nowych wiadomości
- Email dla nowych opinii
- Email dla zatwierdzonych ogłoszeń
- Email dla płatności

### 2. Utworzone strony

Po aktywacji wtyczka automatycznie tworzy:

- `/znajdz-towarzysza` - Lista ogłoszeń
- `/dodaj-ogloszenie` - Formularz dodawania
- `/moje-ogloszenia` - Panel użytkownika
- `/moj-profil` - Profil użytkownika
- `/edytuj-profil` - Edycja profilu
- `/wiadomosci` - System wiadomości
- `/platnosc` - Checkout płatności
- `/ulubione` - Ulubione ogłoszenia
- `/statystyki` - Statystyki (PRO)
- `/subskrypcje` - Plany subskrypcji
- `/weryfikacja` - Weryfikacja konta

## 📌 Shortcodes

### Podstawowe

```php
[png_listings_archive] // Lista wszystkich ogłoszeń
[png_listings_archive category="sport" per_page="12"] // Z parametrami

[png_listing_form] // Formularz dodawania/edycji

[png_my_listings] // Panel użytkownika z jego ogłoszeniami

[png_user_profile] // Profil użytkownika
[png_user_profile user_id="123"] // Profil konkretnego użytkownika

[png_profile_edit] // Formularz edycji profilu

[png_messages] // System wiadomości

[png_search_form] // Formularz wyszukiwania
[png_search_form show_filters="yes"] // Z zaawansowanymi filtrami

[png_favorites] // Ulubione ogłoszenia użytkownika
```

### PRO Shortcodes

```php
[png_user_statistics] // Statystyki użytkownika (wykresy, dane)

[png_subscriptions] // Plany subskrypcji i zarządzanie

[png_verification] // Formularz weryfikacji konta

[png_payment_checkout] // Strona płatności
[png_payment_success] // Potwierdzenie płatności
[png_payment_cancelled] // Anulowana płatność
```

## 📁 Struktura plików

```
przyjaciel-na-godzine/
│
├── przyjaciel-na-godzine.php          # Główny plik wtyczki
├── uninstall.php                       # Deinstalacja
├── README.md                           # Ta dokumentacja
│
├── includes/                           # Core classes
│   ├── class-png-install.php          # Instalacja i setup
│   ├── class-png-post-types.php       # Custom post types
│   ├── class-png-shortcodes.php       # Shortcodes
│   ├── class-png-ajax.php             # AJAX handlers
│   ├── class-png-security.php         # Bezpieczeństwo
│   ├── class-png-listings.php         # Ogłoszenia
│   ├── class-png-users.php            # Użytkownicy
│   ├── class-png-messages.php         # Wiadomości
│   ├── class-png-payments.php         # Płatności
│   ├── class-png-reviews.php          # Opinie
│   ├── class-png-images.php           # Obrazy
│   ├── class-png-notifications.php    # Powiadomienia
│   ├── class-png-statistics.php       # Statystyki
│   ├── class-png-moderation.php       # Moderacja
│   │
│   ├── pro/                            # PRO features
│   │   ├── class-png-subscriptions.php
│   │   ├── class-png-verification.php
│   │   ├── class-png-analytics.php
│   │   └── class-png-calendar.php
│   │
│   └── admin/                          # Admin panel
│       ├── class-png-admin.php
│       ├── class-png-settings.php
│       └── class-png-reports.php
│
├── templates/                          # Frontend templates
│   ├── listings-archive.php
│   ├── listing-form.php
│   ├── my-listings.php
│   ├── user-profile.php
│   ├── profile-edit.php
│   ├── messages.php
│   ├── payment-checkout.php
│   ├── payment-success.php
│   ├── payment-cancelled.php
│   ├── favorites.php
│   ├── user-statistics.php
│   ├── subscriptions.php
│   └── verification-form.php
│
├── assets/                             # Assets
│   ├── css/
│   │   ├── frontend.css               # Główne style
│   │   └── admin.css                  # Style admina
│   │
│   ├── js/
│   │   ├── frontend.js                # Główny JavaScript
│   │   ├── admin.js                   # Admin JavaScript
│   │   ├── messages.js                # Wiadomości
│   │   └── image-upload.js            # Upload obrazów
│   │
│   └── images/
│       └── placeholder.jpg             # Placeholder
│
└── languages/                          # Tłumaczenia
    └── przyjaciel-na-godzine.pot
```

## 🌟 Funkcje PRO

### Subskrypcje

**Free (0 PLN)**
- 5 ogłoszeń
- 3 zdjęcia na ogłoszenie
- Podstawowe funkcje

**Premium Monthly (99 PLN/mies)**
- 50 ogłoszeń
- 10 zdjęć na ogłoszenie
- 3 wyróżnione ogłoszenia/mies
- 5 boost/mies
- Badge weryfikacji
- Wsparcie priorytetowe
- Analityka
- Bez reklam

**Premium Yearly (999 PLN/rok)**
- Wszystko z Monthly
- Oszczędność 17%
- 5 wyróżnień/mies
- 10 boost/mies

**PRO (299 PLN/mies)**
- Nielimitowane ogłoszenia
- 20 zdjęć na ogłoszenie
- Nielimitowane wyróżnienia i boost
- API access
- Custom branding
- Wszystkie funkcje Premium

### Weryfikacja konta

1. Użytkownik przesyła:
   - Zdjęcie dokumentu (dowód/paszport/prawo jazdy)
   - Selfie z dokumentem
2. Admin weryfikuje w panelu
3. Po zatwierdzeniu: Badge + 50 punktów

### System poziomów

- **Poziom 1**: 0 punktów - Nowicjusz
- **Poziom 2**: 100 punktów - Początkujący
- **Poziom 3**: 250 punktów - Doświadczony
- **Poziom 4**: 500 punktów - Zaawansowany
- **Poziom 5**: 1000 punktów - Ekspert
- **Poziom 6**: 2000 punktów - Mistrz
- **Poziom 7**: 5000 punktów - Legenda
- **Poziom 8**: 10000 punktów - Titan
- **Poziom 9**: 20000 punktów - Champion
- **Poziom 10**: 50000 punktów - Grand Master

**Zdobywanie punktów:**
- Utworzenie ogłoszenia: +10
- Otrzymanie opinii: +5
- Zakup subskrypcji: +100
- Weryfikacja konta: +50
- Zalogowanie dzienny: +1

## 🔌 API

### AJAX Endpoints

```javascript
// Toggle favorite
jQuery.ajax({
    url: pngData.ajaxUrl,
    type: 'POST',
    data: {
        action: 'png_toggle_favorite',
        nonce: pngData.nonce,
        listing_id: 123
    }
});

// Send message
jQuery.ajax({
    url: pngData.ajaxUrl,
    type: 'POST',
    data: {
        action: 'png_send_message',
        nonce: pngData.nonce,
        receiver_id: 456,
        message: 'Hello!'
    }
});

// Get analytics
jQuery.ajax({
    url: pngData.ajaxUrl,
    type: 'POST',
    data: {
        action: 'png_get_analytics',
        nonce: pngData.nonce,
        period: 30
    }
});
```

### Hooks

```php
// After listing saved
add_action('png_listing_saved', function($listing_id, $data) {
    // Your code
}, 10, 2);

// After payment completed
add_action('png_payment_completed', function($payment_id, $payment) {
    // Your code
}, 10, 2);

// After user verified
add_action('png_verification_approved', function($verification_id, $user_id) {
    // Your code
}, 10, 2);
```

## ❓ FAQ

### Jak zmienić wygląd?

Możesz nadpisać style w swoim theme:
```css
.png-listing-card { /* Twoje style */ }
```

### Jak dostosować szablon?

Skopiuj template z `/templates/` do `/twoj-theme/png-templates/`

### Czy jest kompatybilne z WooCommerce?

Tak! Wtyczka działa niezależnie.

### Jak eksportować dane?

**WP Admin > Przyjaciel na Godzinę > Eksport**

### Problemy z płatnościami?

1. Sprawdź API keys w ustawieniach
2. Upewnij się, że SSL jest włączony
3. Sprawdź logi w `wp-content/debug.log`

## 📞 Wsparcie

- Email: support@example.com
- Dokumentacja: https://docs.example.com
- Forum: https://forum.example.com

## 📝 Changelog

### 3.0.0 (2025-01-20)
- ✨ Dodano system subskrypcji
- ✨ Dodano weryfikację kont
- ✨ Dodano analitykę PRO
- ✨ Dodano kalendarz i rezerwacje
- ✨ Pełna integracja PayPal
- ✨ Pełna integracja Stripe
- 🔧 Przepisano całą wtyczkę
- 🔧 Ulepszona bezpieczeństwo
- 🔧 Ulepszona wydajność

### 2.0.0
- Pierwsza publiczna wersja

## 📄 Licencja

GPL v2 lub późniejsza

---

**Stworzone z ❤️ dla społeczności WordPress**