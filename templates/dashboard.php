<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8'); ?> - Tableau de bord</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f6f8; color: #333; }
        .topbar { background: #fff; border-bottom: 1px solid #e5e5e5; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .topbar .brand { font-size: 17px; font-weight: 700; color: #2a9d8f; }
        .topbar .user { font-size: 13px; color: #666; }
        .topbar .user a { color: #2a9d8f; text-decoration: none; margin-left: 12px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .controls { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; align-items: center; }
        .controls a, .controls button { margin-right: 4px; padding: 6px 12px; background: #fff; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; font-size: 13px; cursor: pointer; }
        .controls a.active { background: #2a9d8f; color: #fff; border-color: #2a9d8f; }
        .controls .spacer { flex: 1; }
        .site-select { padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; max-width: 260px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .card { background: #fff; border-radius: 8px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .card h3 { font-size: 12px; color: #777; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .card .value { font-size: 28px; font-weight: 700; }
        .card .extra { font-size: 12px; color: #888; margin-top: 4px; }
        .section { background: #fff; border-radius: 8px; padding: 18px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .section h2 { font-size: 16px; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .section h2 .btn { font-size: 12px; padding: 5px 10px; background: #2a9d8f; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 8px 10px; color: #777; font-weight: 600; border-bottom: 2px solid #eee; }
        td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; }
        tr:hover td { background: #fafafa; }
        .flag { width: 16px; height: 11px; margin-right: 6px; vertical-align: middle; }
        .empty { color: #999; font-style: italic; padding: 12px 0; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 800px) { .two-col { grid-template-columns: 1fr; } }
        .message { padding: 12px 16px; border-radius: 6px; font-size: 13px; margin-bottom: 16px; }
        .message.success { background: #e6f7f4; color: #1d7a6f; }
        .message.error { background: #fdecea; color: #c0392b; }
        .add-site { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .add-site h2 { font-size: 16px; margin-bottom: 12px; }
        .add-site form { display: flex; gap: 8px; flex-wrap: wrap; }
        .add-site input { padding: 9px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .add-site input[name=name] { flex: 1; min-width: 180px; }
        .add-site input[name=url] { flex: 2; min-width: 240px; }
        .add-site button { padding: 9px 18px; background: #2a9d8f; color: #fff; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .hero { text-align: center; padding: 40px 20px; }
        .hero h2 { font-size: 20px; margin-bottom: 10px; }
        .hero p { color: #666; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="brand"><?php echo htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="user">
        <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
        <a href="?action=tracking_code">Code de suivi</a>
        <a href="?action=logout">Déconnexion</a>
    </div>
</div>

<div class="container">
    <?php if (!empty($_GET['added'])): ?>
        <div class="message success">Website ajouté. Le serveur analytics est prêt à tracker ce site.</div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
        <div class="message success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (empty($userSites)): ?>
        <div class="add-site">
            <h2>Ajoutez votre premier site web</h2>
            <form method="post" action="?action=add_site">
                <input type="hidden" name="csrf" value="<?php echo pkiwi_csrf_token(); ?>">
                <input type="text" name="name" placeholder="Nom du site (ex. Ma boutique)" required>
                <input type="url" name="url" placeholder="Adresse du site (ex. https://monsite.com)" required>
                <button type="submit">Ajouter le site</button>
            </form>
            <p style="margin-top:12px; font-size:13px; color:#666;">
                Votre site est enregistré sur le serveur analytics, puis le traitement des visites y est effectué.
                Les résultats sont affichés ici via les endpoints de l'API analytics.
            </p>
        </div>
        <div class="card hero">
            <h2>Bienvenue sur votre espace analytics</h2>
            <p>Ajoutez l'adresse de votre site web ci-dessus pour commencer à suivre vos visiteurs, leur géolocalisation et leurs recherches.</p>
        </div>
    <?php else: ?>

    <div class="controls">
        <select class="site-select" onchange="if(this.value){location.href='?action=dashboard&site='+this.value}">
            <?php foreach ($userSites as $s): ?>
                <option value="<?php echo (int)$s['id']; ?>" <?php echo $currentSite && (int)$currentSite['id'] === (int)$s['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['name'] . ' — ' . $s['url'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <a href="?period=day&amp;date=today" class="<?php echo $date === 'today' ? 'active' : ''; ?>">Aujourd'hui</a>
        <a href="?period=day&amp;date=yesterday" class="<?php echo $date === 'yesterday' ? 'active' : ''; ?>">Hier</a>
        <a href="?period=week&amp;date=today" class="<?php echo $period === 'week' ? 'active' : ''; ?>">Cette semaine</a>
        <a href="?period=month&amp;date=today" class="<?php echo $period === 'month' ? 'active' : ''; ?>">Ce mois</a>
        <span class="spacer"></span>
        <button onclick="if(confirm('Envoyer une visite de test ?')){fetch('?ajax=test_visit',{method:'POST'}).then(r=>r.json()).then(d=>alert(d.success?'Visite de test envoyée':'Échec'));">Visite de test</button>
        <form method="post" action="?action=delete_site" style="display:inline;" onsubmit="return confirm('Retirer ce site de votre liste ?');">
            <input type="hidden" name="csrf" value="<?php echo pkiwi_csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$currentSite['id']; ?>">
            <button type="submit">Retirer le site</button>
        </form>
    </div>

    <div class="add-site">
        <h2>Ajouter un autre site web</h2>
        <form method="post" action="?action=add_site">
            <input type="hidden" name="csrf" value="<?php echo pkiwi_csrf_token(); ?>">
            <input type="text" name="name" placeholder="Nom du site" required>
            <input type="url" name="url" placeholder="https://autresite.com" required>
            <button type="submit">Ajouter le site</button>
        </form>
    </div>

    <?php
    $s = is_array($dashboard['summary']) ? $dashboard['summary'] : [];
    $nbVisits = isset($s['nb_visits']) ? (int)$s['nb_visits'] : 0;
    $nbUniq = isset($s['nb_uniq_visitors']) ? (int)$s['nb_uniq_visitors'] : 0;
    $nbActions = isset($s['nb_actions']) ? (int)$s['nb_actions'] : 0;
    $bounceRate = isset($s['bounce_rate']) ? $s['bounce_rate'] : '0%';
    $avgTime = isset($s['avg_time_on_site']) ? (int)$s['avg_time_on_site'] : 0;
    ?>
    <div class="grid">
        <div class="card"><h3>Visites</h3><div class="value"><?php echo $nbVisits; ?></div><div class="extra"><?php echo $nbUniq; ?> visiteurs uniques</div></div>
        <div class="card"><h3>Actions</h3><div class="value"><?php echo $nbActions; ?></div><div class="extra"><?php echo isset($s['nb_actions_per_visit']) ? htmlspecialchars($s['nb_actions_per_visit'], ENT_QUOTES, 'UTF-8') : '0'; ?> par visite</div></div>
        <div class="card"><h3>Taux de rebond</h3><div class="value"><?php echo htmlspecialchars($bounceRate, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="card"><h3>Temps moyen</h3><div class="value"><?php echo gmdate('H:i:s', $avgTime); ?></div></div>
        <div class="card"><h3>Pays</h3><div class="value"><?php echo (int)$geoSummary['total_countries']; ?></div><div class="extra"><?php echo (int)$geoSummary['total_visits']; ?> visites</div></div>
    </div>

    <div class="two-col">
        <div class="section">
            <h2>Géolocalisation</h2>
            <?php if (!empty($geoSummary['countries'])): ?>
            <table>
                <thead><tr><th>Pays</th><th>Visites</th><th>Uniques</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($geoSummary['countries'] as $c): ?>
                    <tr>
                        <td>
                            <?php $code = strtolower($c['code']); ?>
                            <?php if ($code && $code !== 'xx' && $code !== 'unknown'): ?>
                                <img class="flag" src="<?php echo htmlspecialchars($matomo->getBaseUrl(), ENT_QUOTES, 'UTF-8'); ?>/plugins/Morpheus/images/flags/<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>.png" alt="" onerror="this.style.display='none'">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td><?php echo (int)($c['visits'] ?? 0); ?></td>
                        <td><?php echo (int)($c['unique_visitors'] ?? 0); ?></td>
                        <td><?php echo (int)($c['actions'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty">Aucune donnée pour cette période.</div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Recherches internes</h2>
            <?php if (!empty($dashboard['search_keywords'])): ?>
            <table>
                <thead><tr><th>Mot-clé</th><th>Visites</th><th>Pages/recherche</th></tr></thead>
                <tbody>
                <?php foreach ($dashboard['search_keywords'] as $kw): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($kw['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)($kw['nb_visits'] ?? 0); ?></td>
                        <td><?php echo isset($kw['nb_pages_per_search']) ? htmlspecialchars($kw['nb_pages_per_search'], ENT_QUOTES, 'UTF-8') : '0'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty">Aucune recherche enregistrée.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="two-col">
        <div class="section">
            <h2>Pages les plus visitées</h2>
            <?php if (!empty($dashboard['top_pages'])): ?>
            <table>
                <thead><tr><th>Page</th><th>Visites</th><th>Clics</th></tr></thead>
                <tbody>
                <?php foreach ($dashboard['top_pages'] as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)($p['nb_visits'] ?? 0); ?></td>
                        <td><?php echo (int)($p['nb_hits'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty">Aucune page vue.</div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Sources de trafic</h2>
            <?php if (!empty($dashboard['referrers'])): ?>
            <table>
                <thead><tr><th>Type</th><th>Visites</th><th>Uniques</th></tr></thead>
                <tbody>
                <?php foreach ($dashboard['referrers'] as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)($r['nb_visits'] ?? 0); ?></td>
                        <td><?php echo (int)($r['nb_uniq_visitors'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty">Aucune source de trafic.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="two-col">
        <div class="section">
            <h2>Appareils</h2>
            <?php if (!empty($dashboard['devices'])): ?>
            <table>
                <thead><tr><th>Type d'appareil</th><th>Visites</th><th>Uniques</th></tr></thead>
                <tbody>
                <?php foreach ($dashboard['devices'] as $d): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)($d['nb_visits'] ?? 0); ?></td>
                        <td><?php echo (int)($d['nb_uniq_visitors'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty">Aucune donnée d'appareil.</div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Navigateurs &amp; OS</h2>
            <?php if (!empty($dashboard['browsers'])): ?>
            <table>
                <thead><tr><th>Navigateur</th><th>Visites</th></tr></thead>
                <tbody>
                <?php foreach ($dashboard['browsers'] as $b): ?>
                    <tr><td><?php echo htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int)($b['nb_visits'] ?? 0); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <?php if (!empty($dashboard['os'])): ?>
            <table>
                <thead><tr><th>Système d'exploitation</th><th>Visites</th></tr></thead>
                <tbody>
                <?php foreach ($dashboard['os'] as $o): ?>
                    <tr><td><?php echo htmlspecialchars($o['label'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int)($o['nb_visits'] ?? 0); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <?php else: ?>
                <div class="empty">Aucune donnée de navigateur.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h2>Visites en direct</h2>
        <?php if (!empty($dashboard['live_visits'])): ?>
        <table>
            <thead><tr><th>Visite</th><th>IP</th><th>Localisation</th><th>Actions</th><th>Recherches</th><th>Source</th><th>Appareil</th></tr></thead>
            <tbody>
            <?php foreach ($dashboard['live_visits'] as $v): ?>
                <tr>
                    <td>#<?php echo (int)$v['id']; ?></td>
                    <td><?php echo htmlspecialchars($v['ip'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($v['country'] . ($v['city'] ? ' / ' . $v['city'] : ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int)$v['actions_count']; ?></td>
                    <td>
                        <?php if (!empty($v['search_keywords'])): ?>
                            <?php echo htmlspecialchars(implode(', ', $v['search_keywords']), ENT_QUOTES, 'UTF-8'); ?>
                        <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($v['referrer_name'] ?: $v['referrer_type'] ?: 'Direct', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($v['device_type'], ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars($v['browser'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty">Aucune visite enregistrée pour cette période.</div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>
</body>
</html>
