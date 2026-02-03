<?php
// Inclus via le routeur: _config.php est déjà chargé et la connexion BD assurée par le contrôleur
include_once(ADMIN_ROOT.'sidebar.php');
include_once(ADMIN_ROOT.'openContenuPrincipale.php');
include_once(MODEL_ROOT.'solo.class.php');

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Récupérer le PDO depuis la globale (assuré par le contrôleur)
$pdo = $GLOBALS['connexionBd'] ?? null;
$soloModel = new solo($pdo);

// Validate id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo '<div class="alert alert-danger">Identifiant invalide.</div>';
    include_once(ADMIN_ROOT.'closeContenuPrincipale.php');
    exit;
}

// Handle update
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $messages[] = ['type' => 'danger', 'text' => 'Jeton CSRF invalide.'];
    } else {
        // Basic validation
        $question = trim($_POST['question'] ?? '');
        $ra = trim($_POST['reponse_a'] ?? '');
        $rb = trim($_POST['reponse_b'] ?? '');
        $rc = trim($_POST['reponse_c'] ?? '');
        $rd = trim($_POST['reponse_d'] ?? '');
        $br = trim($_POST['bonne_reponse'] ?? '');

        if ($question === '' || $ra === '' || $rb === '' || $rc === '' || $rd === '' || $br === '') {
            $messages[] = ['type' => 'danger', 'text' => 'Tous les champs sont requis.'];
        } else {
            if ($soloModel->updateSoloFull($id, $question, $ra, $rb, $rc, $rd, $br)) {
                $messages[] = ['type' => 'success', 'text' => 'Question mise à jour avec succès.'];
            } else {
                $messages[] = ['type' => 'danger', 'text' => 'Échec de la mise à jour.'];
            }
        }
    }
}

$row = $soloModel->getById($id);
if (!$row) {
    echo '<div class="alert alert-warning">Question introuvable.</div>';
    include_once(ADMIN_ROOT.'closeContenuPrincipale.php');
    exit;
}
?>
<style>
.form-grid { display:grid; grid-template-columns: 1fr; gap:14px; }
.form-grid textarea { width:100%; min-height:80px; padding:10px; border:1px solid #e5e7eb; border-radius:8px; }
.form-actions { margin-top:12px; }
.alert { padding:10px 14px; border-radius:8px; margin-bottom:12px; }
.alert-success { background:#ecfdf5; color:#065f46; }
.alert-danger  { background:#fef2f2; color:#991b1b; }
.alert-warning { background:#fffbeb; color:#92400e; }
</style>
<div class="container-fluid">
    <h2 class="mb-3">Modifier la question #<?= (int)$id ?></h2>

    <?php foreach ($messages as $m): ?>
        <div class="alert alert-<?= htmlspecialchars($m['type']) ?>"><?= htmlspecialchars($m['text']) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="update">

        <div>
            <label>Question</label>
            <textarea name="question" required><?= htmlspecialchars($row->question_solo) ?></textarea>
        </div>
        <div>
            <label>Réponse A</label>
            <textarea name="reponse_a" required><?= htmlspecialchars($row->reponse_a_solo) ?></textarea>
        </div>
        <div>
            <label>Réponse B</label>
            <textarea name="reponse_b" required><?= htmlspecialchars($row->reponse_b_solo) ?></textarea>
        </div>
        <div>
            <label>Réponse C</label>
            <textarea name="reponse_c" required><?= htmlspecialchars($row->reponse_c_solo) ?></textarea>
        </div>
        <div>
            <label>Réponse D</label>
            <textarea name="reponse_d" required><?= htmlspecialchars($row->reponse_d_solo) ?></textarea>
        </div>
        <div>
            <label>Bonne réponse</label>
            <textarea name="bonne_reponse" required><?= htmlspecialchars($row->bonne_reponse_solo) ?></textarea>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn btn-secondary" href="<?=HOST?>admin_solo">Retour à la liste</a>
        </div>
    </form>
</div>

<?php include_once(ADMIN_ROOT.'closeContenuPrincipale.php'); ?>
