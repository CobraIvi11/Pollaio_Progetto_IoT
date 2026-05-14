<head>
    <title>Login</title>
    <link rel="stylesheet" href="/Pollaio_Progetto_Iot_WebApp/style/style.css">
</head>

<body>

<head>
    <title>Login</title>
    <link rel="stylesheet" href="style/style.css">
</head>

<body>

<form id="login-form" class="auth-form" action="/login" method="POST">
    <div class="logo-container">
        <img src="/img/Logo.png" alt="Logo" class="brand-logo">
    </div>

    <h2 class="title">Login</h2>

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
