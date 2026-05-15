<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$db_name = "pollaio_iot";
$username = "root";
$password_db = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM utenti WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if ($password === $user['password']) {

                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_nome'] = $user['nome'];
                    $_SESSION['user_cognome'] = $user['cognome'];

                    header("Location: /Pollaio_Progetto_IoT_WebApp/home");
                    exit;
                } else {
                    $error_message = "Password errata.";
                }
            } else {
                $error_message = "Nessun utente trovato con questa email.";
            }
        } catch (PDOException $e) {
            $error_message = "Errore di sistema: " . $e->getMessage();
        }
    } else {
        $error_message = "Tutti i campi sono obbligatori.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="/Pollaio_Progetto_IoT_WebApp/style/style.css">
</head>
<body>

<form id="login-form" class="auth-form" action="" method="POST">
    <div class="logo-container">
        <img src="/Pollaio_Progetto_IoT_WebApp/img/Logo.png" alt="Logo" class="brand-logo">
    </div>

    <h2 class="title">Login</h2>

    <?php if (!empty($error_message)): ?>
        <div style="color: #ff4d4d; text-align: center; margin-bottom: 15px; font-weight: bold;">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <label class="label-text">Email</label>
    <input type="email" class="input-field" name="email" required>

    <label class="label-text">Password</label>
    <input type="password" class="input-field" name="password" required>

    <input type="submit" class="btn-submit" value="Accedi">

    <p class="auth-footer">
        Non hai un account? <a href="/Pollaio_Progetto_IoT_WebApp/register">Registrati qui</a>
    </p>

</form>

</body>
</html>