<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de suivi - <?php echo htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f6f8; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .topbar { margin-bottom: 20px; }
        .topbar a { color: #2a9d8f; text-decoration: none; font-size: 14px; }
        h1 { font-size: 22px; margin-bottom: 6px; }
        .subtitle { color: #666; font-size: 14px; margin-bottom: 20px; }
        .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 20px; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 16px; border-radius: 6px; overflow-x: auto; font-size: 13px; line-height: 1.5; }
        .copy-btn { margin-top: 12px; padding: 8px 16px; background: #2a9d8f; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .steps { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .steps h2 { font-size: 16px; margin-bottom: 12px; }
        .steps ol { margin-left: 20px; font-size: 14px; color: #444; }
        .steps li { margin-bottom: 8px; }
        code { background: #eef2f1; padding: 1px 5px; border-radius: 3px; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar"><a href="?action=dashboard">&larr; Retour au tableau de bord</a></div>
    <h1>Code de suivi</h1>
    <div class="subtitle">
        Site : <strong><?php echo htmlspecialchars($currentSite['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
        (<?php echo htmlspecialchars($currentSite['url'], ENT_QUOTES, 'UTF-8'); ?>)
    </div>

    <div class="steps">
        <h2>Comment installer le suivi</h2>
        <ol>
            <li>Copiez le code ci-dessous.</li>
            <li>Collez-le dans le <code>&lt;head&gt;</code> de toutes les pages de votre site, juste avant <code>&lt;/head&gt;</code>.</li>
            <li>Le traitement des visites est effectué sur le serveur analytics — les résultats apparaissent automatiquement dans votre tableau de bord.</li>
            <li>La recherche interne est détectée automatiquement via le paramètre d'URL <code>motCle</code> (ex. <code>?motCle=tomato</code>).</li>
        </ol>
    </div>

    <div class="card" style="margin-top:20px;">
        <pre id="tracking-code"><?php
            echo htmlspecialchars(
                $tracker->getTrackingCodeWithSearch([
                    'site_id' => (string)$matomo->getSiteId(),
                    'search_param' => 'motCle',
                ]),
                ENT_QUOTES,
                'UTF-8'
            );
        ?></pre>
        <button class="copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('tracking-code').textContent)">Copier le code</button>
    </div>
</div>
</body>
</html>
