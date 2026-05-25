<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';

// Solo utenti loggati
if (empty($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin     = trim($_POST['pin']     ?? '');
    $pinConf = trim($_POST['pin_confirm'] ?? '');

    if (!preg_match('/^\d{4}$/', $pin)) {
        $error = 'Il PIN deve essere esattamente 4 cifre numeriche.';
    } elseif ($pin !== $pinConf) {
        $error = 'I due PIN non coincidono.';
    } else {
        try {
            $pdo = Database::getInstance()->getConnection();
            $hash = hash('sha256', $pin);
            $stmt = $pdo->prepare("UPDATE utenti SET pin = :pin WHERE id = :id");
            $stmt->execute(['pin' => $hash, 'id' => $_SESSION['user_id']]);
            $success = 'PIN impostato correttamente! Ora puoi usarlo nell\'app Android.';
        } catch (PDOException $e) {
            $error = 'Errore DB: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Imposta PIN App</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/style/style.css">
    <style>
        .pin-card {
            max-width: 400px;
            margin: 60px auto;
            background: white;
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .pin-card h2 { color: #2d5a27; margin-bottom: 6px; }
        .pin-card p  { color: #888; font-size: 0.9rem; margin-bottom: 24px; }
        .pin-input {
            width: 100%; padding: 14px; font-size: 1.2rem;
            letter-spacing: 0.4em; text-align: center;
            border: 2px solid #e0e8de; border-radius: 12px;
            margin-bottom: 14px; outline: none;
            font-family: 'JetBrains Mono', monospace;
        }
        .pin-input:focus { border-color: #4caf50; }
        .btn-pin {
            width: 100%; padding: 14px;
            background: #2d5a27; color: white;
            border: none; border-radius: 12px;
            font-size: 1rem; font-weight: 600;
            cursor: pointer; margin-top: 8px;
        }
        .btn-pin:hover { background: #1a3d18; }
        .msg-ok  { color: #2d5a27; background: #e8f5e9; padding: 12px; border-radius: 10px; margin-bottom: 16px; }
        .msg-err { color: #c62828; background: #ffebee; padding: 12px; border-radius: 10px; margin-bottom: 16px; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #888; font-size: 0.85rem; }
    </style>
</head>
<body style="background:#f4f7f3;">

<div class="pin-card">
    <h2>📱 PIN App Android</h2>
    <p>Imposta un PIN di 4 cifre per accedere all'app mobile con il tuo account.</p>

    <?php if ($success): ?>
        <div class="msg-ok">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="msg-err">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label style="font-weight:600; color:#2d5a27;">Nuovo PIN</label>
        <input type="password" name="pin" class="pin-input"
               maxlength="4" pattern="\d{4}" inputmode="numeric"
               placeholder="• • • •" required>

        <label style="font-weight:600; color:#2d5a27;">Conferma PIN</label>
        <input type="password" name="pin_confirm" class="pin-input"
               maxlength="4" pattern="\d{4}" inputmode="numeric"
               placeholder="• • • •" required>

        <button type="submit" class="btn-pin">💾 Salva PIN</button>
    </form>

    <a href="<?= BASE_URL ?>/home" class="back-link">← Torna alla dashboard</a>
</div>

</body>
</html>
