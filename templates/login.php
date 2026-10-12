<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: #fff; border-radius: 10px; padding: 32px; width: 100%; max-width: 400px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        h1 { font-size: 22px; margin-bottom: 6px; }
        .subtitle { color: #666; font-size: 14px; margin-bottom: 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password], input[type=url] {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; margin-bottom: 16px;
        }
        input:focus { outline: none; border-color: #2a9d8f; box-shadow: 0 0 0 3px rgba(42,157,143,0.15); }
        button { width: 100%; padding: 12px; background: #2a9d8f; color: #fff; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; }
        button:hover { background: #23867a; }
        .error { background: #fdecea; color: #c0392b; padding: 10px 12px; border-radius: 6px; font-size: 13px; margin-bottom: 16px; }
        .footer { text-align: center; margin-top: 20px; font-size: 14px; color: #666; }
        .footer a { color: #2a9d8f; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <h1>Connexion</h1>
    <div class="subtitle">Accédez à votre tableau de bord analytics</div>
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form method="post" action="?action=login">
        <input type="hidden" name="csrf" value="<?php echo pkiwi_csrf_token(); ?>">
        <label for="login">Nom d'utilisateur ou email</label>
        <input type="text" id="login" name="login" required autofocus>
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Se connecter</button>
    </form>
    <div class="footer">Pas encore de compte ? <a href="?action=register">Créer un compte</a></div>
</div>
</body>
</html>
