<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione - Pollaio IoT</title>
    <link rel="stylesheet" href="/Pollaio_Progetto_IoT_WebApp/style/style.css">
</head>
<body>

<form id="register-form" class="auth-form" action="/Pollaio_Progetto_IoT_WebApp/home" method="POST">
    <div class="logo-container">
        <img src="/Pollaio_Progetto_IoT_WebApp/img/Logo.png" alt="Logo" class="brand-logo">
    </div>

    <h2 class="title">REGISTRATI</h2>
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

</body>
</html>