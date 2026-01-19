<!-------------- Inclure la Sidebar -------------->
<?php include(ADMIN_ROOT.'sidebar.php'); ?>
<!--------------------------------------------------------->

<!-------------------------------------------------------------> 
<!-- Inclure le TAG d'ouverture du contenu principale -->
<?php include(ADMIN_ROOT.'openContenuPrincipale.php'); ?>
<!--------------------------------------------------------------->
<!----------------debut: CORPS DE LA PAGE -------------------------->
<main id="main-publier-jeu">
    <?php
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrf = $_SESSION['csrf_token'];
    ?>
    <form method="post" class="form-publier-jeu">
        
        <p id="titre-form-publier-jeu">PUBLIER UN QCM</p>
       
        <div class="form-group-publier-jeu">
            <label for="choix_jeux_jeu">Choisir le type de D'EVALUATION: </label>
            <select name="choix_jeux" id="choix_jeux_jeu" class="select-publier-jeu">
                <option value="">Quel est votre choix</option>
                <option value="solo">STANDARD</option>
            </select>
        </div>
        
        <div class="form-group-publier-jeu">
            <label for="question_jeu">LA QUESTION: <small style="color:#667085;">(max 500 caractères)</small></label>
            <textarea id="question_jeu" class="textarea-publier-jeu" name="question" placeholder="Énoncé du QCM" maxlength="500" required></textarea>
        </div>
        
        <div class="form-group-publier-jeu">
            <label for="reponse_a_jeu">REPONSE A: <small style="color:#667085;">(max 200 caractères)</small></label>
            <textarea id="reponse_a_jeu" class="textarea-publier-jeu" name="reponse_a" placeholder="Réponse A" maxlength="200" required></textarea>
        </div>
        
        <div class="form-group-publier-jeu">
            <label for="reponse_b_jeu">REPONSE B: <small style="color:#667085;">(max 200 caractères)</small></label>
            <textarea id="reponse_b_jeu" class="textarea-publier-jeu" name="reponse_b" placeholder="Réponse B" maxlength="200" required></textarea>
        </div>
        
        <div class="form-group-publier-jeu">
            <label for="reponse_c_jeu">REPONSE C: <small style="color:#667085;">(max 200 caractères)</small></label>
            <textarea id="reponse_c_jeu" class="textarea-publier-jeu" name="reponse_c" placeholder="Réponse C" maxlength="200" required></textarea>
        </div>
        
        <div class="form-group-publier-jeu">
            <label for="reponse_d_jeu">REPONSE D: <small style="color:#667085;">(max 200 caractères)</small></label>
            <textarea id="reponse_d_jeu" class="textarea-publier-jeu" name="reponse_d" placeholder="Réponse D" maxlength="200" required></textarea>
        </div>

        <div class="form-group-publier-jeu">
            <label for="bonne_reponse_jeu">Saisir la Bonne réponse du qcm ici: <small style="color:#667085;">(max 200 caractères)</small></label>
            <textarea id="bonne_reponse_jeu" class="textarea-publier-jeu textarea-bonne-reponse" name="bonne_reponse" placeholder="Pour ne pas faire d'erreur, veillez copier-coller ici, tout le texte du champ de la bonne réponse" maxlength="200"></textarea>
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <button type="submit" name="soumettre" class="btn-submit-publier-jeu">ENVOYER</button>
    </form>
</main>
<!----------------fin: CORPS DE LA PAGE ---------------------------->
<!----- Inclure le TAG de fermeture du contenu principale --------->
<?php include(ADMIN_ROOT.'closeContenuPrincipale.php'); ?>
<!--------------------------------------------------------------->