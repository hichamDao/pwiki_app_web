<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - <?php echo htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: #fff; border-radius: 10px; padding: 32px; width: 100%; max-width: 400px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        h1 { font-size: 22px; margin-bottom: 6px; }
        .subtitle { color: #666; font-size: 14px; margin-bottom: 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password] {
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
    <h1>Inscription</h1>
    <div class="subtitle">Créez votre compte analytics</div>
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form method="post" action="?action=register">
        <input type="hidden" name="csrf" value="<?php echo pkiwi_csrf_token(); ?>">
        <label for="username">Nom d'utilisateur</label>
        <input type="text" id="username" name="username" required minlength="3" pattern="[a-zA-Z0-9_]+" title="Lettres, chiffres et underscores uniquement" autofocus>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Mot de passe (6+ caractères)</label>
        <input type="password" id="password" name="password" required minlength="6">
        <button type="submit">Créer mon compte</button>
    </form>
    <div class="footer">Déjà un compte ? <a href="?action=login">Se connecter</a></div>
</div>
</body>
</html>
