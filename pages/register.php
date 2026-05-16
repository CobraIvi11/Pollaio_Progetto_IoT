<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

$error_message = "";
$registration_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $email = filter_var(trim($_POST['email']));
    $password = trim($_POST['password']);

    if (!empty($nome) && !empty($cognome) && !empty($email) && !empty($password)) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $stmt_check = $pdo->prepare("SELECT id FROM utenti WHERE email = :email");
            $stmt_check->execute(['email' => $email]);

            if ($stmt_check->rowCount() > 0) {
                $error_message = "Questa email è già registrata.";
            } else {
                $sql = "INSERT INTO utenti (nome, cognome, email, password, creato_il, aggiornato_il) 
                        VALUES (:nome, :cognome, :email, PASSWORD(:password), NOW(), NOW())";

                $stmt_insert = $pdo->prepare($sql);
                $stmt_insert->execute([
                        'nome' => $nome,
                        'cognome' => $cognome,
                        'email' => $email,
                        'password' => $password
                ]);

                $registration_success = true;
            }
        } catch (PDOException $e) {
            $error_message = "Errore durante la registrazione: " . $e->getMessage();
        }
    } else {
        $error_message = "Tutti i campi sono obbligatori.";
    }
}
?>

<head>
    <meta charset="UTF-8">
    <title>Registrazione - Pollaio IoT</title>
    <link rel="stylesheet" href="/Pollaio_Progetto_IoT_WebApp/style/style.css">
</head>
<body>

<div class="auth-form">
    <div class="logo-container">
        <img src="/Pollaio_Progetto_IoT_WebApp/img/Logo.png" alt="Logo" class="brand-logo">
    </div>

    <?php if ($registration_success): ?>

        <h2 class="title">ACCOUNT CREATO!</h2>
        <p style="text-align: center; margin-bottom: 20px; color: #2ecc71; font-weight: bold;">
            La registrazione è avvenuta con successo.
        </p>
        <p style="text-align: center; margin-bottom: 30px; color: #555;">
            Adesso puoi utilizzare le tue credenziali per accedere al pannello di controllo.
        </p>
        <a href="/Pollaio_Progetto_IoT_WebApp/login" class="btn-submit" style="text-align: center; display: block; text-decoration: none; line-height: inherit;">
            Torna al Login
        </a>

    <?php else: ?>

        <form action="" method="POST">
            <h2 class="title">REGISTRATI</h2>

            <?php if (!empty($error_message)): ?>
                <div style="color: #ff4d4d; text-align: center; margin-bottom: 15px; font-weight: bold;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="input-group">
                <div class="input-wrapper">
                    <label class="label-text">Nome</label>
                    <input type="text" name="nome" class="input-field" required>
                </div>
                <div class="input-wrapper">
                    <label class="label-text">Cognome</label>
                    <input type="text" name="cognome" class="input-field" required>
                </div>
            </div>

            <label class="label-text">Email</label>
            <input type="email" name="email" class="input-field" required>

            <label class="label-text">Password</label>
            <input type="password" name="password" class="input-field" required>

            <input type="submit" class="btn-submit" value="Registrati">

            <p class="auth-footer">
                Hai già un account? <a href="/Pollaio_Progetto_IoT_WebApp/login">Accedi qui</a>
            </p>
        </form>

    <?php endif; ?>
</div>

</body>
