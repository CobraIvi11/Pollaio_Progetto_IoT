<?php
$routes = [
    ''              => 'pages/login.php',
    'login'         => 'pages/login.php',
    'home'          => 'pages/home.php',
    'register'      => 'pages/register.php',
    'statistiche'   => 'pages/statistiche.php',
    'orari_mangime' => 'pages/orari_mangime.php',
    'imposta_orari' => 'pages/imposta_orari.php',
    'imposta_pin'   => 'pages/imposta_pin.php',
];

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$subfolder = '/' . basename(__DIR__);

if (strpos($url, $subfolder) === 0) {
    $url = substr($url, strlen($subfolder));
}

$url = trim($url, '/');

if (array_key_exists($url, $routes)) {
    if (file_exists($routes[$url])) {
        require $routes[$url];
    } else {
        http_response_code(500);
        echo "<h1>500 Errore Interno</h1><p>Il file di destinazione '{$routes[$url]}' non è stato trovato.</p>";
    }
} else {
    http_response_code(404);
    echo "<h1>404</h1><p>Pagina non trovata: /{$url}</p>";
}
?>