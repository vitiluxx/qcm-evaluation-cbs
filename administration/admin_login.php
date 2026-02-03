<?php
// Vue de connexion admin. Rendue via allMethod::affichePageAdminLogin()
// Hypothèses: _config.php est déjà chargé, $_SESSION['csrf_token'] défini, et $messages est fourni par le contrôleur.
?>
<?php include_once(ADMIN_ROOT.'openContenuPrincipale.php'); ?>

<style>
/* Style épuré Orange/Blanc/Noir, cohérent avec l'admin */
.login-wrapper {
  max-width: 420px;
  margin: 60px auto;
  background: #fff;
  border: 1px solid #e6e9ee;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  padding: 24px;
}
.login-title { margin:0 0 10px 0; font-size: 1.4rem; font-weight:700; color:#111827; }
.login-sub { margin:0 0 18px 0; color:#6b7280; }
.form-group { margin-bottom: 14px; }
.form-group label { display:block; margin-bottom:6px; color:#374151; font-weight:600; }
.form-control { width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; }
.btn-primary { background:#f97316; color:#111827; border:none; padding:10px 14px; border-radius:8px; cursor:pointer; font-weight:700; width:100%; }
.btn-primary:hover { background:#ea580c; color:#fff; }
.alert { padding:10px 12px; border-radius:8px; margin-bottom:10px; font-size:.95rem; }
.alert-danger { background:#fee2e2; color:#7f1d1d; border:1px solid #fecaca; }
.alert-success { background:#dcfce7; color:#14532d; border:1px solid #bbf7d0; }
.small-muted { color:#6b7280; font-size:.9rem; }
.center { text-align:center; }
</style>

<div class="login-wrapper">
  <h2 class="login-title">Connexion administrateur</h2>
  <p class="login-sub">Saisissez vos identifiants pour accéder au tableau de bord.</p>

  <?php if (!empty($messages) && is_array($messages)): ?>
    <?php foreach ($messages as $m): ?>
      <div class="alert alert-<?= htmlspecialchars($m['type'] ?? 'danger') ?>">
        <?= htmlspecialchars($m['text'] ?? '') ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <div class="form-group">
      <label for="username">Nom d'utilisateur</label>
      <input type="text" id="username" name="username" class="form-control" required>
    </div>
    <div class="form-group">
      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" class="form-control" required>
    </div>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <button type="submit" class="btn-primary">Se connecter</button>
  </form>

  <p class="small-muted center" style="margin-top:12px;">Accès réservé à l'équipe d'administration.</p>
</div>

<?php include_once(ADMIN_ROOT.'closeContenuPrincipale.php'); ?>
