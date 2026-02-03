<?php
// Variables attendues: $sets (liste des sets) et $setQuestions (map set_id => questions)
// Le layout global (entête/pied) est géré par index.php via entetePage.php
include_once(ADMIN_ROOT.'sidebar.php');
include_once(ADMIN_ROOT.'openContenuPrincipale.php');
?>

<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
  <h2 style="margin:0;">Évaluations (Séries)</h2>
  <a href="<?= HOST.'admin_evaluations_edit' ?>" style="display:inline-block; padding:8px 12px; background:#111827; color:#fff; border-radius:6px; text-decoration:none;">Nouvelle évaluation</a>
 </div>
<p style="margin:0 0 16px; color:#555;">Liste des évaluations disponibles. Cliquez sur une ligne pour voir les questions. Utilisez le bouton pour Déployer/Annuler. Une seule évaluation peut être déployée à la fois.</p>

<?php $csrf = $_SESSION['csrf_token'] ?? ''; ?>

<table class="table" style="width:100%; border-collapse:collapse;">
  <thead>
    <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
      <th style="padding:8px;">Titre</th>
      <th style="padding:8px;">Questions</th>
      <th style="padding:8px;">Actif</th>
      <th style="padding:8px;">Déployé</th>
      <th style="padding:8px; text-align:right;">Action</th>
    </tr>
  </thead>
  <tbody>
    <?php
      $anyDeployed = false;
      foreach ($sets as $s) { if ((int)$s['is_deployed'] === 1) { $anyDeployed = true; break; } }
      foreach ($sets as $s):
        $sid = (int)$s['id'];
        $isActive = (int)$s['is_active'] === 1;
        $isDeployed = (int)$s['is_deployed'] === 1;
        $qCount = (int)($s['question_count'] ?? 0);
        // Désactiver le bouton si:
        // - un autre set est déjà déployé et celui-ci ne l'est pas
        // - le set est inactif
        // - le set n'a aucune question
        $shouldDisable = (!$isDeployed && $anyDeployed) || !$isActive || $qCount === 0;
        $disabled = $shouldDisable ? 'disabled' : '';
        $disableTitle = '';
        if (!$isActive) { $disableTitle = 'Activer l\'évaluation avant de déployer.'; }
        elseif ($qCount === 0) { $disableTitle = 'Aucune question associée. Ajoutez des QCM avant de déployer.'; }
        elseif (!$isDeployed && $anyDeployed) { $disableTitle = 'Une autre évaluation est déjà déployée.'; }
    ?>
    <tr class="row-expand" data-target="expand-<?php echo $sid; ?>" style="cursor:pointer; border-bottom:1px solid #f1f5f9;">
      <td style="padding:10px; font-weight:600; color:#111827;">
        <?php echo htmlspecialchars($s['title'] ?? ''); ?>
      </td>
      <td style="padding:10px; color:#374151;">
        <?php echo $qCount; ?>
      </td>
      <td style="padding:10px;">
        <span style="display:inline-block; padding:2px 8px; border-radius:9999px; font-size:12px; <?php echo $isActive?'background:#dcfce7;color:#166534;':'background:#fee2e2;color:#991b1b;'; ?>">
          <?php echo $isActive?'Actif':'Inactif'; ?>
        </span>
      </td>
      <td style="padding:10px;">
        <span style="display:inline-block; padding:2px 8px; border-radius:9999px; font-size:12px; <?php echo $isDeployed?'background:#dbeafe;color:#1e3a8a;':'background:#f3f4f6;color:#374151;'; ?>">
          <?php echo $isDeployed?'Déployé':'—'; ?>
        </span>
      </td>
      <td style="padding:10px; text-align:right; white-space:nowrap;">
        <form method="post" action="<?php echo HOST.'admin_evaluations_delete'; ?>" style="display:inline; margin-right:8px;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="set_id" value="<?php echo $sid; ?>">
          <button type="submit" class="btn" <?php echo $isDeployed ? 'disabled' : ''; ?>
                  onclick="return confirm('Supprimer cette évaluation ? Les associations de questions seront également supprimées.');"
                  title="<?php echo $isDeployed ? 'Impossible de supprimer une évaluation déployée. Annulez d\'abord le déploiement.' : 'Supprimer cette évaluation'; ?>"
                  style="padding:6px 10px; border:1px solid #ef4444; background:#fff; color:#b91c1c; border-radius:6px; cursor:pointer; <?php echo $isDeployed?'opacity:.5;cursor:not-allowed;':''; ?>">
            Supprimer
          </button>
        </form>
        <a href="<?= HOST.'admin_evaluations_edit?id='.$sid ?>" style="margin-right:8px; padding:6px 10px; border:1px solid #e5e7eb; background:#fff; border-radius:6px; text-decoration:none; color:#111827;">Éditer</a>
        <form method="post" action="<?php echo HOST.'admin_evaluations_toggle'; ?>" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="set_id" value="<?php echo $sid; ?>">
          <button type="submit" class="btn" <?php echo $disabled; ?> title="<?php echo htmlspecialchars($disableTitle); ?>"
                  style="padding:6px 10px; border:1px solid #e5e7eb; background:#fff; border-radius:6px; cursor:pointer; <?php echo $disabled?'opacity:.5;cursor:not-allowed;':''; ?>">
            <?php echo $isDeployed? 'Annuler' : 'Déployer'; ?>
          </button>
        </form>
      </td>
    </tr>
    <tr id="expand-<?php echo $sid; ?>" style="display:none; background:#fafafa;">
      <td colspan="5" style="padding:12px 16px;">
        <?php $qs = $setQuestions[$sid] ?? []; ?>
        <?php if (empty($qs)): ?>
          <div style="color:#6b7280;">Aucune question associée à cette évaluation.</div>
        <?php else: ?>
          <ul style="margin:0; padding-left:18px;">
            <?php foreach ($qs as $q): ?>
              <li style="margin:6px 0;">
                <span style="font-weight:600; color:#111827;">Q#<?php echo (int)$q['id_solo']; ?>:</span>
                <span style="color:#374151;"> <?php echo htmlspecialchars($q['question_solo'] ?? ''); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
// Expand/Collapse rows
Array.from(document.querySelectorAll('.row-expand')).forEach(function(tr){
  tr.addEventListener('click', function(e){
    if (e.target && (e.target.tagName === 'BUTTON' || e.target.closest('form'))) return; // éviter click sur bouton
    const id = tr.getAttribute('data-target');
    const row = document.getElementById(id);
    if (row) {
      row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
    }
  });
});
</script>

<?php include_once(ADMIN_ROOT.'closeContenuPrincipale.php'); ?>
