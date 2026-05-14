<?php
$routes = [
    ''          => 'pages/login.php',
    'login'     => 'pages/login.php',
    'home'      => 'pages/home.php',
    'register'  => 'pages/register.php',
];

// Con Herd/Nginx leggiamo l'URL direttamente dalla richiesta del server
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = trim($url, '/');

if (array_key_exists($url, $routes)) {
    require $routes[$url];
} else {
    // Gestione errore 404
    http_response_code(404);
    echo "<h1>404</h1><p>Pagina non trovata: /{$url}</p>";
}