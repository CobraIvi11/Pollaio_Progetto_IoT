<?php
$routes = [
    ''      => 'pages/login.php',
    'login' => 'pages/login.php',
    'home'  => 'pages/home.php',
    'register'  => 'pages/register.php',
];

$url = $_GET['url'] ?? '';
$url = trim($url, '/');

if (array_key_exists($url, $routes)) {
    require $routes[$url];
} else {
    // Gestione errore 404
    http_response_code(404);
    echo "<h1>404</h1><p>Pagina non trovata</p>";
}