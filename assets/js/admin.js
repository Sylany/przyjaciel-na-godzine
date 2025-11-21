jQuery(document).ready(function($) {
    // Podstawowe funkcjonalności admina
    console.log('Przyjaciel na Godzinę - admin loaded');
});/* Styl główny kontenera ustawień */
.pn-admin-wrap {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #ddd;
    margin-top: 20px;
    max-width: 900px;
}

/* Nagłówki */
.pn-admin-wrap h1,
.pn-admin-wrap h2,
.pn-admin-wrap h3 {
    margin-bottom: 15px;
    font-weight: 600;
    color: #222;
}

/* Etykiety pól */
.pn-admin-wrap label {
    font-weight: 500;
    margin-bottom: 5px;
    display: block;
}

/* Pola formularza */
.pn-admin-wrap input[type="text"],
.pn-admin-wrap input[type="email"],
.pn-admin-wrap input[type="password"],
.pn-admin-wrap textarea,
.pn-admin-wrap select {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
    margin-bottom: 15px;
}

/* Tabela */
.pn-admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.pn-admin-table th,
.pn-admin-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.pn-admin-table th {
    font-weight: 600;
    background: #fafafa;
}

/* Przycisk */
.pn-admin-wrap .button-primary {
    background: #0073aa;
    border-color: #006799;
    color: #fff !important;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
}

.pn-admin-wrap .button-primary:hover {
    background: #005e8a;
}

/* Box informacyjny */
.pn-notice {
    background: #e9f7ff;
    border-left: 4px solid #0073aa;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
