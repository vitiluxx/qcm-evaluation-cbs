<?php
// Le layout global (entête/pied) est géré par index.php via entetePage.php
include_once(ADMIN_ROOT.'sidebar.php');
include_once(ADMIN_ROOT.'openContenuPrincipale.php');

$csrf = $_SESSION['csrf_token'] ?? '';
$setId = isset($id) ? (int)$id : 0;
$title = $set['title'] ?? '';
$isActive = isset($set['is_active']) ? ((int)$set['is_active'] === 1) : true;
?>

<h2 style="margin:0 0 16px;"><?= $setId > 0 ? 'Éditer l\'évaluation' : 'Nouvelle évaluation' ?></h2>

<?php if (!empty($messages)): ?>
  <div style="margin-bottom:12px;">
    <?php foreach ($messages as $m): ?>
      <div style="padding:8px 10px; border-radius:6px; margin:6px 0; <?= $m['type']==='danger'?'background:#fee2e2;color:#991b1b;':($m['type']==='warning'?'background:#fef3c7;color:#92400e;':'background:#dcfce7;color:#166534;') ?>">
        <?= htmlspecialchars($m['text'] ?? '') ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="post" action="">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

  <div style="margin-bottom:12px;">
    <label for="title" style="display:block; font-weight:600; margin-bottom:6px;">Titre</label>
    <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required
           style="width:100%; padding:8px 10px; border:1px solid #e5e7eb; border-radius:6px;">
  </div>

  <div style="margin-bottom:16px;">
    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
      <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
      <span>Activer cette évaluation</span>
    </label>
  </div>

  <div style="margin:18px 0 6px; font-weight:600;">Questions à inclure</div>
  <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px; max-height:420px; overflow:auto;">
    <?php if (empty($allQuestions)): ?>
      <div style="color:#6b7280;">Aucune question disponible. Ajoutez des QCM d'abord.</div>
    <?php else: ?>
      <ul style="list-style:none; margin:0; padding:0;">
        <?php foreach ($allQuestions as $q): $qid = (int)$q->id_solo; ?>
          <li style="padding:6px 4px; border-bottom:1px solid #f3f4f6; display:flex; align-items:flex-start; gap:10px;">
            <input type="checkbox" id="q<?= $qid ?>" name="question_ids[]" value="<?= $qid ?>" <?= isset($assigned[$qid]) ? 'checked' : '' ?>>
            <label for="q<?= $qid ?>" style="flex:1; cursor:pointer;">
              <div style="font-weight:600; color:#111827;">Q#<?= $qid ?></div>
              <div style="color:#374151;"><?= htmlspecialchars($q->question_solo) ?></div>
            </label>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div style="margin-top:16px; display:flex; gap:10px;">
    <button type="submit" class="btn" style="padding:8px 12px; background:#111827; color:#fff; border-radius:6px; border:0; cursor:pointer;">Enregistrer</button>
    <a href="<?= HOST.'admin_evaluations' ?>" style="padding:8px 12px; border:1px solid #e5e7eb; background:#fff; border-radius:6px; text-decoration:none; color:#111827;">Annuler</a>
  </div>
</form>

<?php include_once(ADMIN_ROOT.'closeContenuPrincipale.php'); ?>
