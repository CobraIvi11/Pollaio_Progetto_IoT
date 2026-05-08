<?php

$url = $_GET['url'] ?? '';

$url = trim($url, '/');

switch ($url) {

    case '':
    case 'login':
        require 'pages/login.php';
        break;

    case 'home':
        require 'pages/home.php';
        break;

    default:
        echo "
        <h1>404</h1>
        <p>Pagina non trovata</p>
        ";
        break;
}