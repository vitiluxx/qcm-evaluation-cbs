<!-------------- Inclure la Sidebar -------------->
<?php
// Ensure configuration constants are available even if this file is accessed directly
if (!defined('ADMIN_ROOT')) {
    include_once(dirname(__DIR__).'/_config.php');
}
?>
<?php include(ADMIN_ROOT.'sidebar.php'); ?>
<!--------------------------------------------------------->

<!-------------------------------------------------------------> 
<!-- le TAG(a inclure) d'ouverture du contenu principale -->
<?php include(ADMIN_ROOT.'openContenuPrincipale.php'); ?>
<!--------------------------------------------------------------->

<?php
// Charger la connexion BD de façon robuste (lecture depuis $GLOBALS puis fallback)
$pdo = $GLOBALS['connexionBd'] ?? null;
if (!($pdo instanceof PDO)) {
    include_once(ROOT.'connexionBd.php');
    if (isset($connexionBd) && $connexionBd instanceof PDO) {
        $pdo = $connexionBd;
        $GLOBALS['connexionBd'] = $pdo; // promouvoir globalement
    }
}
// Charger modèle admin pour les évaluations
include_once(MODEL_ROOT.'admin.class.php');
$adminModel = new adminModel($pdo);

// Récupérer quelques métriques clés
$nbSolo = 0;
$nbUtilisateurs = 0;
try {
    $nbSolo = (int)$pdo->query('SELECT COUNT(*) FROM solo')->fetchColumn();
} catch (Throwable $e) {
    $nbSolo = 0;
}

try {
    // Utiliser uniquement la table utilisateurs
    $nbUtilisateurs = (int)$pdo->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
} catch (Throwable $e) {
    $nbUtilisateurs = 0;
}

// Filtres pour l'historique des évaluations
$filters = [];
$selectedUser = isset($_GET['u']) && $_GET['u'] !== '' ? (int)$_GET['u'] : '';
$dateFrom = $_GET['d1'] ?? '';
$dateTo   = $_GET['d2'] ?? '';
if ($selectedUser !== '') { $filters['id_utilisateur'] = $selectedUser; }
if (!empty($dateFrom)) { $filters['date_from'] = $dateFrom; }
if (!empty($dateTo)) { $filters['date_to'] = $dateTo; }

// Pagination
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Données
$evalStats = $adminModel->getEvaluationStats();
$utilisateurs = $adminModel->listUtilisateurs();
$evalCount = $adminModel->countEvaluations($filters);
$evalRows = $adminModel->listEvaluations($filters, $perPage, $offset);
// Résumé par utilisateur (indépendant du filtre utilisateur pour afficher la vue globale)
$summaryFilters = [];
if (!empty($dateFrom)) { $summaryFilters['date_from'] = $dateFrom; }
if (!empty($dateTo)) { $summaryFilters['date_to'] = $dateTo; }
$userSummary = $adminModel->getUserSummary($summaryFilters);
// Liens d'export
$exportQuery = http_build_query([
    'id_utilisateur' => $selectedUser,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
]);
?>
<style>
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 16px;
}
.dashboard-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 18px;
}
.dashboard-card h3 {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
    color: #333;
}
.dashboard-metric {
    font-size: 2rem;
    font-weight: bold;
    color: #0589b1;
}
.links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 16px;
    margin-top: 24px;
}
.link-tile {
    background: #f8fafc;
    border: 1px solid #e6e9ee;
    border-radius: 10px;
    padding: 16px;
}
.link-tile a {
    font-weight: bold;
    color: #0589b1;
}
.muted { color: #667085; font-size: .9rem; }

/* Styles évaluation */
.filters { display:flex; gap:10px; flex-wrap:wrap; align-items:end; margin: 18px 0; }
.filters label { display:block; font-weight:600; color:#374151; font-size:.9rem; }
.filters select, .filters input { padding:8px 10px; border:1px solid #e5e7eb; border-radius:6px; }
.eval-table { width:100%; border-collapse: collapse; margin-top: 8px; }
.eval-table th, .eval-table td { border:1px solid #e5e7eb; padding:8px 10px; text-align:left; }
.eval-table th { background:#f8fafc; }
.export-actions { margin-top: 10px; }
.btn-export { background:#111827; color:#fff; padding:8px 12px; border:none; border-radius:6px; cursor:pointer; }
.btn-primary { background:#f97316; color:#111827; padding:10px 14px; border:none; border-radius:8px; font-weight:700; display:inline-block; text-decoration:none; }
.btn-primary:hover { background:#ea580c; color:#fff; }
.pagination { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
.pagination a { padding:6px 10px; border:1px solid #e5e7eb; background:#fff; border-radius:6px; text-decoration:none; color:#111827; }
.pagination a.active { background:#f97316; color:#111827; border-color:#f97316; }
.summary-table { width:100%; border-collapse: collapse; margin-top: 16px; }
.summary-table th, .summary-table td { border:1px solid #e5e7eb; padding:8px 10px; text-align:left; }
.summary-table th { background:#f8fafc; }
</style>

<div class="container">
    <h2 class="mb-4">Tableau de bord</h2>
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <h3>Les Questions</h3>
            <div class="dashboard-metric"><?php echo (int)$nbSolo; ?></div>
            <div class="muted">Nombre total de QCM disponibles</div>
        </div>

        <div class="dashboard-card">
            <h3>Utilisateurs Enregistrer</h3>
            <div class="dashboard-metric"><?php echo (int)$nbUtilisateurs; ?></div>
            <div class="muted">Noms enregistrés via la page de saisie</div>
        </div>

        <div class="dashboard-card">
            <h3>Évaluations</h3>
            <div class="muted">
                Total validées: <strong><?=(int)$evalStats['total']?></strong><br>
                Moy. tentatives: <strong><?=number_format($evalStats['avg_attempts'],2)?></strong><br>
                Max tentatives: <strong><?=(int)$evalStats['max_attempts']?></strong>
            </div>
        </div>
    </div>

    <!-- <div class="links-grid">
        <div class="link-tile">
            <h4>Publier un nouveau QCM</h4>
            <p class="muted">Créer une nouvelle question SOLO</p>
            <a class="btn-primary" href="<?=HOST?>formulaire">Publier un nouveau QCM</a>
        </div>

        <div class="link-tile">
            <h4>Lancer une partie</h4>
            <p class="muted">Accéder à la sélection et démarrer en SOLO</p>
            <a href="<?=HOST?>jeux">Aller aux jeux</a>
        </div>

        <div class="link-tile">
            <h4>Gestion QCM SOLO</h4>
            <p class="muted">Lister, modifier, supprimer les questions</p>
            <a href="<?=HOST?>admin_solo">Ouvrir la gestion</a>
        </div>

        <div class="link-tile">
            <h4>Utilisateurs</h4>
            <p class="muted">Saisir un nom puis jouer en SOLO</p>
            <a href="<?=HOST?>nom_utilisateur.php">Saisir un nom</a>
        </div>
    </div> -->

    <hr style="margin:24px 0;">
    <h3>Résumé par utilisateur</h3>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Date-heure (dernière)</th>
                <th>Utilisateur</th>
                <th>Nbr questions répondues</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($userSummary)): ?>
                <tr><td colspan="3">Aucun résumé disponible pour cette période.</td></tr>
            <?php else: foreach ($userSummary as $s): $q = http_build_query(['r'=>'dashboard','u'=>$s['id_utilisateur'],'d1'=>$dateFrom,'d2'=>$dateTo]); ?>
                <tr>
                    <td><?= htmlspecialchars($s['last_date'] ?? '') ?></td>
                    <td><a href="?<?= $q ?>"><?= htmlspecialchars($s['utilisateur'] ?? 'N/A') ?></a></td>
                    <td><?= (int)($s['questions_repondues'] ?? 0) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <hr style="margin:24px 0;">
    <h3>Historique des évaluations</h3>
    <form method="get" class="filters">
        <input type="hidden" name="r" value="dashboard">
        <div>
            <label for="u">Utilisateur</label>
            <select id="u" name="u">
                <option value="">Tous</option>
                <?php foreach ($utilisateurs as $u): $sel = ($selectedUser!=='' && (int)$u['id']===$selectedUser)?'selected':''; ?>
                    <option value="<?=$u['id']?>" <?=$sel?>><?=htmlspecialchars($u['nom'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="d1">Du</label>
            <input type="date" id="d1" name="d1" value="<?=htmlspecialchars($dateFrom)?>">
        </div>
        <div>
            <label for="d2">Au</label>
            <input type="date" id="d2" name="d2" value="<?=htmlspecialchars($dateTo)?>">
        </div>
        <div>
            <button type="submit" class="btn-export">Filtrer</button>
        </div>
        <div class="export-actions">
            <a class="btn-export" href="<?=HOST?>export_evaluations?<?=$exportQuery?>">Exporter CSV</a>
        </div>
    </form>

    <table class="eval-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Question (id)</th>
                <th>La Question</th>
                <th>Tentatives avant réussite</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($evalRows)): ?>
            <tr><td colspan="5">Aucune donnée pour ces filtres.</td></tr>
        <?php else: foreach ($evalRows as $row): ?>
            <tr>
                <td><?=htmlspecialchars($row['created_at'])?></td>
                <td><?=htmlspecialchars($row['utilisateur'] ?? 'N/A')?></td>
                <td><?= (int)$row['id_solo'] ?></td>
                <td><?= htmlspecialchars(mb_strimwidth((string)($row['question_solo'] ?? ''), 0, 120, '…')) ?></td>
                <td><?= (int)$row['attempts'] ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php $pages = max(1, (int)ceil($evalCount / $perPage)); if ($pages>1): ?>
        <div class="pagination">
            <?php for($p=1;$p<=$pages;$p++): $q = http_build_query(['r'=>'dashboard','u'=>$selectedUser,'d1'=>$dateFrom,'d2'=>$dateTo,'p'=>$p]); ?>
                <a href="?<?=$q?>" class="<?=$p===$page?'active':''?>"><?=$p?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!----- le TAG a inlure pour de fermeture du contenu principale --------->
<?php include(ADMIN_ROOT.'closeContenuPrincipale.php'); ?>
<!--------------------------------------------------------------->
