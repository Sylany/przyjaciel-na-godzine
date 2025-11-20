<?php
if (!defined('ABSPATH')) exit;

$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
?>

<div class="png-container">
    <div class="png-payment-result png-payment-cancelled">
        <div class="png-payment-icon">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                <circle cx="40" cy="40" r="38" fill="#dc3545" stroke="#dc3545" stroke-width="4"/>
                <path d="M30 30L50 50M50 30L30 50" stroke="white" stroke-width="6" stroke-linecap="round"/>
            </svg>
        </div>
        
        <h1>Płatność anulowana</h1>
        <p class="png-payment-message">Twoja płatność została anulowana. Możesz spróbować ponownie kiedy tylko będziesz gotowy.</p>
        
        <?php if ($payment_id): ?>
            <div class="png-payment-details">
                <div class="png-detail-row">
                    <span class="png-detail-label">Numer zamówienia:</span>
                    <span class="png-detail-value">#<?php echo $payment_id; ?></span>
                </div>
                <div class="png-detail-row">
                    <span class="png-detail-label">Status:</span>
                    <span class="png-detail-value png-status-failed">Anulowane</span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="png-cancellation-reasons">
            <h3>Dlaczego płatność mogła zostać anulowana?</h3>
            <div class="png-reasons-grid">
                <div class="png-reason">
                    <div class="png-reason-icon">⏱️</div>
                    <div class="png-reason-content">
                        <h4>Przekroczono czas</h4>
                        <p>Sesja płatności wygasła z powodu zbyt długiego czasu oczekiwania.</p>
                    </div>
                </div>
                <div class="png-reason">
                    <div class="png-reason-icon">👤</div>
                    <div class="png-reason-content">
                        <h4>Decyzja użytkownika</h4>
                        <p>Anulowałeś płatność przed jej finalizacją.</p>
                    </div>
                </div>
                <div class="png-reason">
                    <div class="png-reason-icon">🔒</div>
                    <div class="png-reason-content">
                        <h4>Problem techniczny</h4>
                        <p>Wystąpił tymczasowy problem z systemem płatności.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="png-next-steps">
            <h3>Co możesz zrobić teraz?</h3>
            <div class="png-steps-grid">
                <div class="png-step">
                    <div class="png-step-number">1</div>
                    <div class="png-step-content">
                        <h4>Spróbuj ponownie</h4>
                        <p>Wróć do procesu płatności i spróbuj wykonać ją jeszcze raz.</p>
                    </div>
                </div>
                <div class="png-step">
                    <div class="png-step-number">2</div>
                    <div class="png-step-content">
                        <h4>Sprawdź dane</h4>
                        <p>Upewnij się, że wszystkie dane płatności są poprawne.</p>
                    </div>
                </div>
                <div class="png-step">
                    <div class="png-step-number">3</div>
                    <div class="png-step-content">
                        <h4>Skontaktuj się</h4>
                        <p>Jeśli problem się powtarza, skontaktuj się z naszym wsparciem.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="png-payment-actions">
            <a href="javascript:history.back()" class="png-btn png-btn-large">
                🔄 Spróbuj ponownie
            </a>
            <a href="<?php echo home_url('/moje-ogloszenia/'); ?>" class="png-btn png-btn-secondary">
                📋 Wróć do moich ogłoszeń
            </a>
            <a href="<?php echo home_url('/lista/'); ?>" class="png-btn png-btn-outline">
                🔍 Przeglądaj ogłoszenia
            </a>
        </div>
        
        <div class="png-troubleshooting">
            <h3>Masz problem z płatnością?</h3>
            <div class="png-tips">
                <div class="png-tip">
                    <strong>Sprawdź połączenie internetowe</strong>
                    <p>Upewnij się, że masz stabilne połączenie z internetem.</p>
                </div>
                <div class="png-tip">
                    <strong>Zweryfikuj dane karty</strong>
                    <p>Sprawdź poprawność numeru karty, daty ważności i kodu CVV.</p>
                </div>
                <div class="png-tip">
                    <strong>Wyczyść cache przeglądarki</strong>
                    <p>Sometimes browser cache can cause payment issues.</p>
                </div>
                <div class="png-tip">
                    <strong>Spróbuj innej metody</strong>
                    <p>Jeśli masz problem z jedną metodą płatności, wypróbuj inną.</p>
                </div>
            </div>
        </div>
        
        <div class="png-support-info">
            <p>Potrzebujesz pomocy? Jesteśmy tutaj, aby Ci pomóc!</p>
            <div class="png-support-actions">
                <a href="mailto:support@przyjacielnagodzine.pl" class="png-support-link">
                    ✉️ Napisz do nas
                </a>
                <a href="<?php echo home_url('/pomoc/'); ?>" class="png-support-link">
                    ❓ Centrum pomocy
                </a>
            </div>
        </div>
        
        <div class="png-security-notice">
            <div class="png-security-icon">🔒</div>
            <div class="png-security-content">
                <h4>Twoje dane są bezpieczne</h4>
                <p>Wszystkie transakcje są chronione zaawansowanym szyfrowaniem. Twoje dane finansowe nigdy nie są przechowywane na naszych serwerach.</p>
            </div>
        </div>
    </div>
</div>

<style>
.png-payment-cancelled {
    text-align: center;
    padding: 50px 30px;
    background: linear-gradient(135deg, #fff5f5 0%, #ffeaea 100%);
    border-radius: 15px;
    border: 1px solid #f5c6cb;
    margin: 20px 0;
}

.png-payment-icon {
    margin-bottom: 30px;
}

.png-payment-icon svg {
    filter: drop-shadow(0 4px 8px rgba(220, 53, 69, 0.3));
}

.png-payment-cancelled h1 {
    color: #c53030;
    margin-bottom: 15px;
    font-size: 2.5em;
    font-weight: 700;
}

.png-payment-message {
    font-size: 1.2em;
    color: #e53e3e;
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
    border-left: 4px solid #e53e3e;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.png-payment-details h3 {
    color: #c53030;
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
    border-bottom: 1px solid #fed7d7;
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

.png-cancellation-reasons {
    margin: 40px 0;
}

.png-cancellation-reasons h3 {
    color: #c53030;
    margin-bottom: 25px;
    font-size: 1.5em;
}

.png-reasons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 25px 0;
}

.png-reason {
    background: white;
    padding: 25px 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #fed7d7;
    transition: transform 0.3s ease;
}

.png-reason:hover {
    transform: translateY(-3px);
}

.png-reason-icon {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.png-reason-content h4 {
    color: #c53030;
    margin: 0 0 10px 0;
    font-size: 1.1em;
}

.png-reason-content p {
    color: #666;
    line-height: 1.5;
    margin: 0;
}

.png-next-steps {
    margin: 40px 0;
}

.png-next-steps h3 {
    color: #c53030;
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
    border: 1px solid #fed7d7;
}

.png-step-number {
    width: 50px;
    height: 50px;
    background: #e53e3e;
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
    color: #c53030;
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
    border: 2px solid #e53e3e;
    color: #e53e3e;
}

.png-btn-outline:hover {
    background: #e53e3e;
    color: white;
}

.png-troubleshooting {
    background: white;
    padding: 30px;
    border-radius: 10px;
    margin: 30px 0;
    text-align: left;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.png-troubleshooting h3 {
    color: #c53030;
    margin-top: 0;
    margin-bottom: 20px;
    text-align: center;
}

.png-tips {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.png-tip {
    background: #fff5f5;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #e53e3e;
}

.png-tip strong {
    color: #c53030;
    display: block;
    margin-bottom: 8px;
    font-size: 1em;
}

.png-tip p {
    color: #666;
    margin: 0;
    line-height: 1.5;
}

.png-support-info {
    background: #fff5f5;
    padding: 25px;
    border-radius: 10px;
    margin: 30px 0;
    text-align: center;
}

.png-support-info p {
    margin: 0 0 15px 0;
    color: #c53030;
    font-weight: 500;
    font-size: 1.1em;
}

.png-support-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.png-support-link {
    background: white;
    color: #e53e3e;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    border: 1px solid #feb2b2;
    transition: all 0.3s ease;
}

.png-support-link:hover {
    background: #e53e3e;
    color: white;
    transform: translateY(-2px);
}

.png-security-notice {
    display: flex;
    align-items: center;
    gap: 20px;
    background: #f0fff4;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #9ae6b4;
    margin-top: 30px;
    text-align: left;
}

.png-security-icon {
    font-size: 2em;
    flex-shrink: 0;
}

.png-security-content h4 {
    color: #2d3748;
    margin: 0 0 8px 0;
    font-size: 1.1em;
}

.png-security-content p {
    color: #4a5568;
    margin: 0;
    line-height: 1.5;
}

/* Responsywność */
@media (max-width: 768px) {
    .png-payment-cancelled {
        padding: 30px 20px;
    }
    
    .png-payment-cancelled h1 {
        font-size: 2em;
    }
    
    .png-reasons-grid,
    .png-steps-grid,
    .png-tips {
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
    
    .png-support-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .png-support-actions .png-support-link {
        width: 100%;
        max-width: 250px;
        text-align: center;
    }
    
    .png-security-notice {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .png-payment-cancelled {
        padding: 20px 15px;
    }
    
    .png-payment-cancelled h1 {
        font-size: 1.8em;
    }
    
    .png-payment-details,
    .png-troubleshooting,
    .png-support-info {
        padding: 20px 15px;
    }
}
</style>