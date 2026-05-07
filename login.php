<head>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
<form id="login-form">
    <div class="logo-container">
        <img src="img/Logo.png" alt="Logo" class="brand-logo">
    </div>

    <h2 class="login-title">Login</h2>

    <label for="email" class="label-text">Email:</label>
    <input type="email" id="email" class="input-field" name="email" placeholder="Inserisci la tua email" required>

    <label for="password" class="label-text">Password:</label>
    <input type="password" id="password" class="input-field" name="password" placeholder="Inserisci la password" required>

    <input type="submit" id="login-button" class="btn-submit" value="Accedi">

    <p class="forgot-password">
        <a href="#" class="forgot-link">Password dimenticata?</a>
    </p>
</form>
</body>