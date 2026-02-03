<?php
// Ensure config is available
if (!defined('ADMIN_ROOT')) {
    include_once(dirname(__DIR__).'/_config.php');
}
include_once(ADMIN_ROOT.'sidebar.php');
include_once(ADMIN_ROOT.'openContenuPrincipale.php');
// Charger la connexion BD de façon robuste
$pdo = $GLOBALS['connexionBd'] ?? null;
if (!($pdo instanceof PDO)) {
    include_once(ROOT.'connexionBd.php');
    if (isset($connexionBd) && $connexionBd instanceof PDO) {
        $pdo = $connexionBd;
        $GLOBALS['connexionBd'] = $pdo; // promouvoir globalement si nécessaire
    }
}
include_once(MODEL_ROOT.'solo.class.php');

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$soloModel = new solo($pdo);

// Handle deletion (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo '<div class="alert alert-danger">Jeton CSRF invalide.</div>';
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $soloModel->deleteSolo($id);
            echo '<div class="alert alert-success">Question supprimée.</div>';
        }
    }
}

$rows = $soloModel->getAll();
?>
<style>
.table { width:100%; border-collapse: collapse; }
.table th, .table td { border:1px solid #e5e7eb; padding:10px; text-align:left; }
.table th { background:#f8fafc; }
.actions a, .actions button { margin-right:8px; }
.searchbar { margin: 0 0 14px 0; display:flex; gap:10px; align-items:center; }
.searchbar input[type="text"]{ padding:8px 10px; width: 260px; border:1px solid #e5e7eb; border-radius:6px; }
.searchbar select{ padding:8px 10px; border:1px solid #e5e7eb; border-radius:6px; }
.pagination { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
.pagination button{ padding:6px 10px; border:1px solid #e5e7eb; background:#fff; border-radius:6px; cursor:pointer; }
.pagination button.active{ background:#f97316; color:#111827; border-color:#f97316; }
</style>
<div class="container-fluid">
    <h2 class="mb-3">Gestion QCM</h2>

    <div class="searchbar">
        <!-- Lien vers le formulaire de création -->
        <a class="btn btn-primary" href="<?=HOST?>formulaire">Publier un nouveau QCM</a>
        <!-- Zone de recherche locale (client-side) -->
        <input type="text" id="searchInput" placeholder="Rechercher une question... (client)">
        <!-- Taille de page -->
        <label for="perPage">Par page:</label>
        <select id="perPage">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>

    <table class="table" id="soloTable">
        <thead>
            <tr>
                <th style="width:80px;">ID</th>
                <th>Question</th>
                <th style="width:220px;">Actions</th>
            </tr>
        </thead>
        <tbody><!-- Les lignes sont rendues côté serveur et paginées côté client -->
        <?php if (empty($rows)): ?>
            <tr><td colspan="3">Aucune question enregistrée.</td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr>
                <td><?= (int)$r->id_solo ?></td>
                <td><?= htmlspecialchars(mb_strimwidth($r->question_solo, 0, 140, '...')) ?></td>
                <td class="actions">
                    <a class="btn btn-sm btn-secondary" href="<?=HOST?>admin_qcm_edit?id=<?= (int)$r->id_solo ?>">Modifier</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette question ?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r->id_solo ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <!-- Pagination client-side -->
    <div class="pagination" id="pagination"></div>
</div>

<?php include_once(ADMIN_ROOT.'closeContenuPrincipale.php'); ?>

<script>
// --- Pagination + Recherche côté client (sans lib externe) ---
(function(){
  const table = document.getElementById('soloTable');
  const tbody = table.querySelector('tbody');
  const rows = Array.from(tbody.querySelectorAll('tr'));
  const searchInput = document.getElementById('searchInput');
  const perPageSelect = document.getElementById('perPage');
  const pagination = document.getElementById('pagination');

  let currentPage = 1;
  let perPage = parseInt(perPageSelect.value, 10) || 10;
  let filtered = rows.slice();

  function render() {
    // Filtrer selon la recherche
    const q = (searchInput.value || '').toLowerCase();
    filtered = rows.filter(tr => {
      const text = tr.cells[1]?.innerText.toLowerCase() || '';
      const id = tr.cells[0]?.innerText.toLowerCase() || '';
      return text.includes(q) || id.includes(q);
    });
    // Pagination
    const total = filtered.length;
    const pages = Math.max(1, Math.ceil(total / perPage));
    if (currentPage > pages) currentPage = pages;
    const start = (currentPage - 1) * perPage;
    const end = start + perPage;

    // Affichage des lignes
    tbody.innerHTML = '';
    filtered.slice(start, end).forEach(tr => tbody.appendChild(tr));

    // Contrôles de pagination
    pagination.innerHTML = '';
    for (let p = 1; p <= pages; p++) {
      const btn = document.createElement('button');
      btn.textContent = p;
      if (p === currentPage) btn.classList.add('active');
      btn.addEventListener('click', () => { currentPage = p; render(); });
      pagination.appendChild(btn);
    }
  }

  searchInput.addEventListener('input', () => { currentPage = 1; render(); });
  perPageSelect.addEventListener('change', () => { perPage = parseInt(perPageSelect.value,10)||10; currentPage = 1; render(); });

  // Initial render
  render();
})();
</script>
