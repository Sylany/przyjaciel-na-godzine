<?php
if (!defined('ABSPATH')) exit;

$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
$user_id = get_current_user_id();

// Jeśli mamy payment_id, możemy pobrać szczegóły płatności
if ($payment_id) {
    global $wpdb;
    $payment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}png_payments WHERE id = %d AND user_id = %d",
        $payment_id, $user_id
    ));
}

// Określ typ usługi na podstawie payment_type
$service_type = '';
$service_details = '';

if (isset($payment)) {
    switch ($payment->payment_type) {
        case 'premium_19':
            $service_type = 'Subskrypcja Premium (19 zł/miesiąc)';
            $service_details = 'Twoje konto zostało aktywowane na 30 dni. Możesz teraz korzystać ze wszystkich funkcji premium.';
            break;
        case 'premium_49':
            $service_type = 'Subskrypcja Premium (49 zł/miesiąc)';
            $service_details = 'Twoje konto zostało aktywowane na 30 dni. Ciesz się rozszerzonymi funkcjami premium.';
            break;
        case 'premium_99':
            $service_type = 'Subskrypcja Premium (99 zł/miesiąc)';
            $service_details = 'Twoje konto zostało aktywowane na 30 dni. Otrzymujesz dostęp do wszystkich funkcji premium.';
            break;
        case 'featured_24':
            $service_type = 'Wyróżnienie ogłoszenia (24 godziny)';
            $service_details = 'Twoje ogłoszenie będzie wyróżnione przez 24 godziny.';
            break;
        case 'featured_72':
            $service_type = 'Wyróżnienie ogłoszenia (72 godziny)';
            $service_details = 'Twoje ogłoszenie będzie wyróżnione przez 72 godziny.';
            break;
        case 'featured_168':
            $service_type = 'Wyróżnienie ogłoszenia (7 dni)';
            $service_details = 'Twoje ogłoszenie będzie wyróżnione przez 7 dni.';
            break;
        case 'verification':
            $service_type = 'Weryfikacja profilu';
            $service_details = 'Twój profil został zweryfikowany. Użytkownicy widzą teraz badge weryfikacji przy Twoim nazwisku.';
            break;
        default:
            $service_type = 'Usługa Premium';
            $service_details = 'Twoja usługa została aktywowana.';
    }
}
?>

<div class="png-container">
    <div class="png-payment-result png-payment-success">
        <div class="png-payment-icon">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                <circle cx="40" cy="40" r="38" fill="#28a745" stroke="#28a745" stroke-width="4"/>
                <path d="M25 40L35 50L55 30" stroke="white" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        
        <h1>Płatność zakończona pomyślnie!</h1>
        <p class="png-payment-message">Dziękujemy za zakup. Twoja usługa została aktywowana.</p>
        
        <?php if (isset($payment)): ?>
            <div class="png-payment-details">
                <h3>Szczegóły transakcji</h3>
                <div class="png-detail-row">
                    <span class="png-detail-label">Numer zamówienia:</span>
                    <span class="png-detail-value">#<?php echo $payment->id; ?></span>
                </div>
                <div class="png-detail-row">
                    <span class="png-detail-label">Usługa:</span>
                    <span class="png-detail-value"><?php echo $service_type; ?></span>
                </div>
                <div class="png-detail-row">
                    <span class="png-detail-label">Kwota:</span>
                    <span class="png-detail-value"><?php echo number_format($payment->amount, 2); ?> <?php echo $payment->currency; ?></span>
                </div>
                <div class="png-detail-row">
                    <span class="png-detail-label">Data:</span>
                    <span class="png-detail-value"><?php echo date('d.m.Y H:i', strtotime($payment->created_at)); ?></span>
                </div>
                <div class="png-detail-row">
                    <span class="png-detail-label">Status:</span>
                    <span class="png-detail-value png-status-completed">Zakończone</span>
                </div>
            </div>
            
            <div class="png-service-info">
                <h3>Aktywowana usługa</h3>
                <p><?php echo $service_details; ?></p>
                
                <?php if (strpos($payment->payment_type, 'premium') !== false): ?>
                    <div class="png-premium-features">
                        <h4>Korzyści z konta Premium:</h4>
                        <ul>
                            <li>✅ Nieograniczona liczba ogłoszeń</li>
                            <li>✅ Wyróżnione pozycjonowanie w wynikach wyszukiwania</li>
                            <li>✅ Zaawansowane statystyki profilu</li>
                            <li>✅ Priorytetowa obsługa wsparcia</li>
                            <li>✅ Brak reklam</li>
                        </ul>
                    </div>
                <?php elseif (strpos($payment->payment_type, 'featured') !== false): ?>
                    <div class="png-featured-info">
                        <h4>Korzyści z wyróżnienia:</h4>
                        <ul>
                            <li>✅ Wyświetlanie na górze listy ogłoszeń</li>
                            <li>✅ Specjalna ramka wyróżniająca ogłoszenie</li>
                            <li>✅ Zwiększona widoczność nawet o 300%</li>
                            <li>✅ Ikona "Wyróżnione" przy ogłoszeniu</li>
                        </ul>
                    </div>
                <?php elseif ($payment->payment_type === 'verification'): ?>
                    <div class="png-verification-info">
                        <h4>Korzyści z weryfikacji:</h4>
                        <ul>
                            <li>✅ Niebieski znaczek weryfikacji przy profilu</li>
                            <li>✅ Zwiększone zaufanie innych użytkowników</li>
                            <li>✅ Wyższa pozycja w wynikach wyszukiwania</li>
                            <li>✅ Priorytet w rekomendacjach</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="png-payment-details">
                <div class="png-detail-row">
                    <span class="png-detail-label">Status płatności:</span>
                    <span class="png-detail-value png-status-completed">Zakończone pomyślnie</span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="png-next-steps">
            <h3>Co dalej?</h3>
            <div class="png-steps-grid">
                <div class="png-step">
                    <div class="png-step-number">1</div>
                    <div class="png-step-content">
                        <h4>Sprawdź swoje konto</h4>
                        <p>Twoja usługa została już aktywowana. Możesz od razu zacząć z niej korzystać.</p>
                    </div>
                </div>
                <div class="png-step">
                    <div class="png-step-number">2</div>
                    <div class="png-step-content">
                        <h4>Dodaj ogłoszenia</h4>
                        <p>Wykorzystaj nowe możliwości i dodaj atrakcyjne ogłoszenia.</p>
                    </div>
                </div>
                <div class="png-step">
                    <div class="png-step-number">3</div>
                    <div class="png-step-content">
                        <h4>Odbierz potwierdzenie</h4>
                        <p>Na Twój email zostało wysłane potwierdzenie transakcji.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="png-payment-actions">
            <a href="<?php echo home_url('/moje-ogloszenia/'); ?>" class="png-btn png-btn-large">
                🎯 Przejdź do moich ogłoszeń
            </a>
            <a href="<?php echo home_url('/lista/'); ?>" class="png-btn png-btn-secondary">
                🔍 Przeglądaj ogłoszenia
            </a>
            <a href="<?php echo home_url('/dodaj-ogloszenie/'); ?>" class="png-btn png-btn-outline">
                ➕ Dodaj nowe ogłoszenie
            </a>
        </div>
        
        <div class="png-support-info">
            <p>Masz pytania dotyczące zakupionej usługi?</p>
            <a href="<?php echo home_url('/kontakt/'); ?>" class="png-support-link">Skontaktuj się z nami</a>
        </div>
    </div>
</div>

<style>
.png-payment-success {
    text-align: center;
    padding: 50px 30px;
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 100%);
    border-radius: 15px;
    border: 1px solid #c8e6c9;
    margin: 20px 0;
}

.png-payment-icon {
    margin-bottom: 30px;
}

.png-payment-icon svg {
    filter: drop-shadow(0 4px 8px rgba(40, 167, 69, 0.3));
}

.png-payment-success h1 {
    color: #2e7d32;
    margin-bottom: 15px;
    font-size: 2.5em;
    font-weight: 700;
}

.png-payment-message {
    font-size: 1.2em;
    color: #388e3c;
    margin-bottom: 40px;
    line-height: 1.6;
}

.png-payment-details {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin: 30px 0;
    text-align: left;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-left: 4px solid #4caf50;
}

.png-payment-details h3 {
    color: #2e7d32;
    margin-top: 0;
    margin-bottom: 20px;
    text-align: center;
    font-size: 1.4em;
}

.png-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e8f5e9;
}

.png-detail-row:last-child {
    border-bottom: none;
}

.png-detail-label {
    font-weight: 600;
    color: #555;
}

.png-detail-value {
    color: #333;
    font-weight: 500;
}

.png-service-info {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin: 25px 0;
    text-align: left;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.png-service-info h3 {
    color: #2e7d32;
    margin-top: 0;
    margin-bottom: 15px;
}

.png-service-info h4 {
    color: #4caf50;
    margin: 20px 0 10px 0;
}

.png-premium-features,
.png-featured-info,
.png-verification-info {
    background: #f1f8e9;
    padding: 20px;
    border-radius: 8px;
    margin-top: 15px;
}

.png-premium-features ul,
.png-featured-info ul,
.png-verification-info ul {
    margin: 0;
    padding-left: 20px;
}

.png-premium-features li,
.png-featured-info li,
.png-verification-info li {
    margin-bottom: 8px;
    line-height: 1.5;
}

.png-next-steps {
    margin: 40px 0;
}

.png-next-steps h3 {
    color: #2e7d32;
    margin-bottom: 25px;
    font-size: 1.5em;
}

.png-steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 25px 0;
}

.png-step {
    background: white;
    padding: 25px 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e8f5e9;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.png-step:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.png-step-number {
    width: 50px;
    height: 50px;
    background: #4caf50;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5em;
    font-weight: bold;
    margin: 0 auto 15px auto;
}

.png-step-content h4 {
    color: #2e7d32;
    margin: 0 0 10px 0;
    font-size: 1.1em;
}

.png-step-content p {
    color: #666;
    line-height: 1.5;
    margin: 0;
}

.png-payment-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    margin: 40px 0 30px 0;
}

.png-btn-large {
    padding: 15px 30px;
    font-size: 1.1em;
    font-weight: 600;
}

.png-btn-outline {
    background: transparent;
    border: 2px solid #4caf50;
    color: #4caf50;
}

.png-btn-outline:hover {
    background: #4caf50;
    color: white;
}

.png-support-info {
    background: #e8f5e9;
    padding: 20px;
    border-radius: 8px;
    margin-top: 30px;
}

.png-support-info p {
    margin: 0 0 10px 0;
    color: #2e7d32;
    font-weight: 500;
}

.png-support-link {
    color: #4caf50;
    text-decoration: none;
    font-weight: 600;
    border-bottom: 1px solid #4caf50;
}

.png-support-link:hover {
    color: #2e7d32;
    border-bottom-color: #2e7d32;
}

/* Responsywność */
@media (max-width: 768px) {
    .png-payment-success {
        padding: 30px 20px;
    }
    
    .png-payment-success h1 {
        font-size: 2em;
    }
    
    .png-detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .png-steps-grid {
        grid-template-columns: 1fr;
    }
    
    .png-payment-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .png-payment-actions .png-btn {
        width: 100%;
        max-width: 300px;
    }
}

@media (max-width: 480px) {
    .png-payment-success {
        padding: 20px 15px;
    }
    
    .png-payment-success h1 {
        font-size: 1.8em;
    }
    
    .png-payment-details,
    .png-service-info {
        padding: 20px 15px;
    }
}
</style>