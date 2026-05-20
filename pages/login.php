<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'config.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        try {
            $pdo = Database::getInstance()->getConnection();


            $stmt = $pdo->prepare("SELECT * FROM utenti WHERE email = :email AND password = PASSWORD(:password)");

            $stmt->execute([
                    'email' => $email,
                    'password' => $password
            ]);

            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_nome'] = $user['nome'];
                $_SESSION['user_cognome'] = $user['cognome'];

                header("Location: " . BASE_URL . "/home");
                exit;
            } else {

                $error_message = "Email o Password errata.";
            }
        } catch (PDOException $e) {
            $error_message = "Errore di sistema: " . $e->getMessage();
        }
    } else {
        $error_message = "Tutti i campi sono obbligatori.";
    }
}
?>

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="<?=BASE_URL?>/style/style.css">
</head>
<body>

<form id="login-form" class="auth-form" action="" method="POST">
    <div class="logo-container">
        <img src="<?=BASE_URL?>/img/Logo.png" alt="Logo" class="brand-logo">
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
        Non hai un account? <a href="/register">Registrati qui</a>
    </p>

</form>

</body>
