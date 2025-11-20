<?php
if (!defined('ABSPATH')) exit;

$payment_type = sanitize_text_field($_GET['type'] ?? '');
$listing_id = isset($_GET['listing_id']) ? intval($_GET['listing_id']) : null;

$prices = array(
    'premium_19' => 19.00,
    'premium_49' => 49.00,
    'premium_99' => 99.00,
    'featured_24' => 9.00,
    'featured_72' => 29.00,
    'featured_168' => 49.00,
    'verification' => 29.00
);

$descriptions = array(
    'premium_19' => 'Subskrypcja Premium - 19 zł/miesiąc',
    'premium_49' => 'Subskrypcja Premium - 49 zł/miesiąc',
    'premium_99' => 'Subskrypcja Premium - 99 zł/miesiąc',
    'featured_24' => 'Wyróżnienie ogłoszenia - 24 godziny',
    'featured_72' => 'Wyróżnienie ogłoszenia - 72 godziny',
    'featured_168' => 'Wyróżnienie ogłoszenia - 7 dni',
    'verification' => 'Weryfikacja profilu - badge zweryfikowany'
);

if (!array_key_exists($payment_type, $prices)) {
    echo '<div class="png-container"><div class="png-alert png-alert-error">Nieprawidłowy typ płatności.</div></div>';
    return;
}

$amount = $prices[$payment_type];
$description = $descriptions[$payment_type];

// Check if we're in demo mode
$options = get_option('png_options');
$demo_mode = empty($options['paypal_client_id']) && empty($options['stripe_secret_key']);
?>

<div class="png-container">
    <div class="png-payment-checkout">
        <h1>Płatność</h1>
        
        <?php if ($demo_mode): ?>
            <div class="png-alert png-alert-info">
                <strong>Tryb demonstracyjny:</strong> Płatności są symulowane. Żadne prawdziwe płatności nie są przetwarzane.
            </div>
        <?php endif; ?>
        
        <div class="png-payment-summary">
            <h3>Podsumowanie</h3>
            <div class="png-payment-item">
                <strong>Usługa:</strong> <?php echo esc_html($description); ?>
            </div>
            <div class="png-payment-item">
                <strong>Kwota:</strong> <span class="png-price"><?php echo number_format($amount, 2); ?> PLN</span>
            </div>
        </div>
        
        <div class="png-payment-methods">
            <h3>Wybierz metodę płatności</h3>
            
            <div class="png-payment-tabs">
                <button class="png-payment-tab active" data-provider="paypal">
                    <img src="<?php echo PNG_PLUGIN_URL; ?>assets/images/paypal-logo.png" alt="PayPal" height="20" onerror="this.style.display='none'">
                    PayPal
                </button>
                <button class="png-payment-tab" data-provider="stripe">
                    <img src="<?php echo PNG_PLUGIN_URL; ?>assets/images/stripe-logo.png" alt="Stripe" height="20" onerror="this.style.display='none'">
                    Karta płatnicza
                </button>
                <?php if ($demo_mode): ?>
                <button class="png-payment-tab" data-provider="demo">
                    <span class="dashicons dashicons-admin-tools"></span>
                    Tryb demo
                </button>
                <?php endif; ?>
            </div>
            
            <!-- PayPal Payment -->
            <div class="png-payment-content active" id="paypal-content">
                <div id="paypal-button-container"></div>
                <div id="paypal-messages"></div>
                
                <?php if ($demo_mode): ?>
                    <div class="png-demo-payment">
                        <p><strong>Tryb demo PayPal:</strong> Kliknij przycisk poniżej aby zasymulować płatność.</p>
                        <button type="button" id="png-demo-paypal" class="png-btn">Symuluj płatność PayPal</button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Stripe Payment -->
            <div class="png-payment-content" id="stripe-content">
                <form id="stripe-payment-form">
                    <div class="png-form-group">
                        <label for="card-element">Dane karty płatniczej</label>
                        <div id="card-element" class="png-stripe-card">
                            <div class="png-demo-card-info">
                                <p><strong>Tryb demonstracyjny:</strong> Wprowadź dowolne dane karty.</p>
                                <div class="png-demo-card-data">
                                    <div><strong>Numer karty:</strong> 4242 4242 4242 4242</div>
                                    <div><strong>Data ważności:</strong> Dowolna przyszła data</div>
                                    <div><strong>CVC:</strong> 123</div>
                                </div>
                            </div>
                        </div>
                        <div id="card-errors" class="png-stripe-errors"></div>
                    </div>
                    
                    <button type="submit" class="png-btn png-btn-large" id="stripe-submit">
                        Zapłać <?php echo number_format($amount, 2); ?> PLN
                    </button>
                </form>
                
                <?php if ($demo_mode): ?>
                    <div class="png-demo-payment">
                        <button type="button" id="png-demo-stripe" class="png-btn png-btn-secondary">Szybka symulacja Stripe</button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Demo Payment -->
            <?php if ($demo_mode): ?>
            <div class="png-payment-content" id="demo-content">
                <div class="png-demo-payment-full">
                    <div class="png-demo-icon">🔧</div>
                    <h3>Tryb Demonstracyjny</h3>
                    <p>System płatności jest w trybie demonstracyjnym. Możesz przetestować cały proces płatności bez rzeczywistego pobierania opłat.</p>
                    
                    <div class="png-demo-features">
                        <div class="png-demo-feature">
                            <span class="dashicons dashicons-yes"></span>
                            <span>Pełna symulacja procesu płatności</span>
                        </div>
                        <div class="png-demo-feature">
                            <span class="dashicons dashicons-yes"></span>
                            <span>Aktywacja usług po "płatności"</span>
                        </div>
                        <div class="png-demo-feature">
                            <span class="dashicons dashicons-yes"></span>
                            <span>Email potwierdzający</span>
                        </div>
                    </div>
                    
                    <button type="button" id="png-demo-complete" class="png-btn png-btn-large">
                        Przeprowadź symulację płatności
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="png-payment-security">
            <p>🔒 Płatności obsługiwane przez bezpieczne systemy PayPal i Stripe</p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var currentProvider = 'paypal';
    var paymentData = {
        type: '<?php echo $payment_type; ?>',
        amount: <?php echo $amount; ?>,
        currency: 'PLN',
        listing_id: <?php echo $listing_id ?: 'null'; ?>
    };
    
    // Tab switching
    $('.png-payment-tab').on('click', function() {
        $('.png-payment-tab').removeClass('active');
        $('.png-payment-content').removeClass('active');
        
        $(this).addClass('active');
        currentProvider = $(this).data('provider');
        $('#' + currentProvider + '-content').addClass('active');
    });
    
    // Demo PayPal payment
    $('#png-demo-paypal').on('click', function() {
        processDemoPayment('paypal');
    });
    
    // Demo Stripe payment
    $('#png-demo-stripe').on('click', function() {
        processDemoPayment('stripe');
    });
    
    // Demo complete payment
    $('#png-demo-complete').on('click', function() {
        processDemoPayment('demo');
    });
    
    // Stripe form submission
    $('#stripe-payment-form').on('submit', function(e) {
        e.preventDefault();
        processDemoPayment('stripe');
    });
    
    function processDemoPayment(provider) {
        var $button = $('#' + provider + '-content .png-btn');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('Przetwarzanie...');
        
        // Create payment
        $.post(png_ajax.ajax_url, {
            action: 'png_create_payment',
            nonce: png_ajax.nonce,
            provider: provider,
            payment_type: paymentData.type,
            amount: paymentData.amount,
            currency: paymentData.currency,
            listing_id: paymentData.listing_id
        }, function(response) {
            if (response.success) {
                if (response.data.demo_mode) {
                    // Demo mode - redirect immediately
                    window.location.href = response.data.redirect_url;
                } else {
                    // Real payment flow would continue here
                    alert('W rzeczywistej implementacji tutaj nastąpiłoby przekierowanie do systemu płatności.');
                }
            } else {
                alert('Błąd: ' + response.data);
                $button.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            alert('Wystąpił błąd podczas przetwarzania płatności.');
            $button.prop('disabled', false).text(originalText);
        });
    }
    
    // Initialize PayPal button for demo
    function initPayPal() {
        if (typeof paypal === 'undefined') {
            // Load PayPal SDK only if not in demo mode
            if (!<?php echo $demo_mode ? 'true' : 'false'; ?>) {
                var script = document.createElement('script');
                script.src = 'https://www.paypal.com/sdk/js?client-id=<?php echo esc_js($options["paypal_client_id"] ?? ""); ?>&currency=PLN';
                script.onload = function() {
                    // Real PayPal integration would go here
                };
                document.head.appendChild(script);
            }
        }
    }
    
    initPayPal();
});
</script>

<style>
.png-payment-checkout {
    max-width: 600px;
    margin: 0 auto;
}

.png-payment-summary {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.png-payment-item {
    margin: 10px 0;
    display: flex;
    justify-content: space-between;
}

.png-price {
    font-size: 24px;
    font-weight: bold;
    color: #007cba;
}

.png-payment-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.png-payment-tab {
    flex: 1;
    padding: 15px;
    border: 2px solid #ddd;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    font-weight: bold;
    transition: all 0.3s ease;
    min-width: 120px;
}

.png-payment-tab.active {
    border-color: #007cba;
    background: #007cba;
    color: white;
}

.png-payment-content {
    display: none;
}

.png-payment-content.active {
    display: block;
}

.png-stripe-card {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    margin-bottom: 15px;
}

.png-demo-card-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #007cba;
}

.png-demo-card-data {
    margin-top: 10px;
    font-family: monospace;
    background: white;
    padding: 10px;
    border-radius: 4px;
}

.png-stripe-errors {
    color: #dc3545;
    font-size: 14px;
    margin-top: 10px;
}

.png-btn-large {
    padding: 15px 30px;
    font-size: 18px;
    width: 100%;
}

.png-payment-security {
    text-align: center;
    margin-top: 20px;
    padding: 15px;
    background: #f0f8ff;
    border-radius: 8px;
    color: #666;
}

.png-demo-payment {
    margin-top: 20px;
    padding: 15px;
    background: #fff3cd;
    border-radius: 8px;
    border-left: 4px solid #ffc107;
}

.png-demo-payment-full {
    text-align: center;
    padding: 30px;
}

.png-demo-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.png-demo-features {
    text-align: left;
    max-width: 300px;
    margin: 20px auto;
}

.png-demo-feature {
    display: flex;
    align-items: center;
    margin: 10px 0;
    color: #666;
}

.png-demo-feature .dashicons {
    color: #28a745;
    margin-right: 10px;
}

.png-alert-info {
    background: #d1ecf1;
    border-left: 4px solid #17a2b8;
    color: #0c5460;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}
</style>