<?php
// Szablon emaila potwierdzającego płatność
$subject = "Potwierdzenie płatności - PrzyjacielNaGodzinę";

$body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Potwierdzenie płatności</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007cba; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
        .payment-details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>PrzyjacielNaGodzinę</h1>
            <h2>Potwierdzenie płatności</h2>
        </div>
        
        <div class='content'>
            <p>Witaj {user_name},</p>
            <p>Twoja płatność została pomyślnie przetworzona.</p>
            
            <div class='payment-details'>
                <h3>Szczegóły płatności:</h3>
                <p><strong>Usługa:</strong> {payment_description}</p>
                <p><strong>Kwota:</strong> {payment_amount} {payment_currency}</p>
                <p><strong>Data:</strong> {payment_date}</p>
                <p><strong>ID płatności:</strong> {payment_id}</p>
                <p><strong>Metoda płatności:</strong> {payment_method}</p>
            </div>
            
            <p>Twoja usługa została aktywowana i jest już dostępna na Twoim koncie.</p>
            
            <p>Dziękujemy za skorzystanie z PrzyjacielNaGodzinę!</p>
            
            <p>Pozdrawiamy,<br>Zespół PrzyjacielNaGodzinę</p>
        </div>
        
        <div class='footer'>
            <p>© 2024 PrzyjacielNaGodzinę. Wszelkie prawa zastrzeżone.</p>
            <p>Wiadomość wygenerowana automatycznie, prosimy na nią nie odpowiadać.</p>
        </div>
    </div>
</body>
</html>
";

return array('subject' => $subject, 'body' => $body);