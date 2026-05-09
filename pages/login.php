<head>
    <title>Login</title>
    <link rel="stylesheet" href="/Pollaio_Progetto_Iot_WebApp/style/style.css">
</head>

<body>

<form id="login-form" class="auth-form" action="/Pollaio_Progetto_Iot_WebApp/login" method="POST">
    <div class="logo-container">
        <img src="/Pollaio_Progetto_Iot_WebApp/img/Logo.png" alt="Logo" class="brand-logo">
    </div>

    <h2 class="title">Login</h2>

    <label class="label-text">Email</label>
    <input type="email" class="input-field" name="email" required>

    <label class="label-text">Password</label>
    <input type="password" class="input-field" name="password" required>

    <input type="submit" class="btn-submit" value="Accedi">

</form>

</body>
