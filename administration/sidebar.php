<?php
// Ensure configuration constants are available even if this file is accessed directly
if (!defined('ADMIN_ROOT')) {
    include_once(dirname(__DIR__).'/_config.php');
}
?>
<!-- Sidebar -->
<div class="sidebar">
    <a href="<?=HOST;?>tableau_de_bord">
        <i class="fas fa-home"></i>
        <h3 class="text-center mb-4">Dashboard</h3>
    </a>
    <ul class="list-unstyled">
        <!-- <li><a href="<?=HOST;?>dashboard"><i class="fas fa-home"></i> Accueil</a></li> -->
        <?php
            // Highlight active link based on current route
            $r = $_GET['r'] ?? '';
            // Normalize: allow both with and without .php
            $normalize = function($s) { return rtrim($s, '.php'); };
            $current = $normalize($r);
            if ($current === '') { $current = 'dashboard'; }
        ?>
        <li>
            <a href="<?=HOST;?>tableau_de_bord">
                <i class="fas fa-chart-line"></i> ACCUEIL
            </a>
        </li>
        <li>
            <a class="<?= ($current === 'publier_qcm' || $current === 'formulaire' || $current === 'formulaire.php') ? 'active' : '' ?>" href="<?=HOST;?>publier_qcm">
                <i class="fas fa-chart-line"></i> PUBLIER UN QCM
            </a>
        </li>
        <li>
            <a class="<?= ($current === 'gestion_qcm' || $current === 'admin_solo' || $current === 'admin_solo.php') ? 'active' : '' ?>" href="<?=HOST;?>gestion_qcm">
                <i class="fas fa-list"></i> GESTION DES QCM
            </a>
        </li>
        <li>
            <a class="<?= ($current === 'admin_evaluations' || $current === 'admin_evaluations.php' || $current === 'evaluations') ? 'active' : '' ?>" href="<?=HOST;?>admin_evaluations">
                <i class="fas fa-layer-group"></i> ÉVALUATIONS
            </a>
        </li>
    </ul>
    
    <!-- Actions sensibles en bas de la sidebar -->
    <div class="sidebar-actions" style="margin-top:auto; padding: 16px;">
        <div class="sidebar-sep" aria-hidden="true" style="border-top:1px solid rgba(0,0,0,0.08); margin-bottom:12px;"></div>
        <?php if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } ?>
        <form method="post" action="<?=HOST?>reinitialiser_historique" onsubmit="return confirm('Confirmer la réinitialisation de l\'historique global ?');" class="mb-2">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
            <button type="submit" class="action-btn warn" style="display: block; width: 100%; text-align: center; margin-bottom: 10px; padding: 12px 14px; border-radius: 10px; text-decoration: none; font-weight: 700; border: none; cursor: pointer; background:#f97316; color:#111827;">
                <i class="fas fa-undo"></i> Réinitialiser l'historique global
            </button>
        </form>
        <?php if (!empty($_SESSION['admin_id'])): ?>
        <a class="action-btn danger" href="<?=HOST?>deconnexion" onclick="return confirm('Confirmer la déconnexion ?');" style="display: block; width: 100%; text-align: center; margin-bottom: 10px; padding: 12px 14px; border-radius: 10px; text-decoration: none; font-weight: 700; border: none; cursor: pointer; background:#dc2626; color:#fff;">
            <i class="fas fa-sign-out-alt"></i> Se déconnecter
        </a>
        <?php endif; ?>
    </div>
</div>

<?php // La logique de réinitialisation est désormais gérée par le contrôleur via la route reinitialiser_historique ?>
