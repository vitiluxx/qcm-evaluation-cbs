
<!----------------debut: CORPS DE LA PAGE -------------------------->
<!-- Inclusion du script JS principal -->
<script src="<?=ASSET_HOST?>js.js"></script>

<?php
    // Récupérer la connexion PDO fournie par le contrôleur
    $pdo = $GLOBALS['connexionBd'] ?? null;
    // Inclusion du fichier de classe Solo pour la gestion des jeux
    include_once(MODEL_ROOT."solo.class.php");
    // Si la connexion est indisponible, afficher un message explicite
    if (!($pdo instanceof PDO)) {
        echo '<div style="max-width:700px;margin:40px auto;color:#b91c1c;background:#fee2e2;border:1px solid #fecaca;padding:14px;border-radius:8px;">Erreur de connexion à la base de données. Vérifiez que MySQL est démarré et que les identifiants/port dans connexionBd.php sont corrects.</div>';
        return;
    }
    $objetSolo = new solo($pdo); // Création d'une instance de la classe Solo avec la connexion BD

    $id = $_GET['id']; // Récupération de l'identifiant du jeu depuis l'URL

    // Vérification de la validité de l'identifiant
    if(empty($id) OR $id > 100 OR !(INT)$id){
        echo "DESOLER PAS DE JEUX DISPONIBLE"; // Erreur si ID non valide
    }
    else {
        $jeuDemander = $objetSolo->AfficheJeu_Solo($id); // Récupération des infos du jeu en base

        if(empty($jeuDemander)) {
            // Si aucun jeu trouvé, on vide les variables d'affichage
            $Q = "DESOLER PAS DE JEUX DISPONIBLE POUR CE CHIFFRE";
            $RA = "";
            $RB = "";
            $RC = "";
            $RD = "";
            $BR = "";
        } else {
            // Sinon, on remplit les variables avec les données du jeu
            foreach($jeuDemander as $jeu) {
                $Q = $jeu->question_solo;
                $RA = $jeu->reponse_a_solo;
                $RB = $jeu->reponse_b_solo;
                $RC = $jeu->reponse_c_solo;
                $RD = $jeu->reponse_d_solo;
                $BR = $jeu->bonne_reponse_solo;
            }
        }
    }
?>


<!-- Bloc principal contenant la question et les choix -->
<div class="question">
    <!-- Affichage de la question -->
    <p class="p-question"><?=@$Q;?></p>

    <!-- Bloc contenant les 4 choix de réponse -->
    <div class="choices">
        <div class="top-choices">
            <button class="choice" data-answer=""><?=@$RA;?></button>
            <button class="choice" data-answer=""><?=@$RB;?></button>
        </div>
        <div class="bottom-choices">
            <button class="choice" data-answer=""><?=@$RC;?></button>
            <button class="choice" data-answer=""><?=@$RD;?></button>
        </div>
    </div>

    <!-- Bouton 'suivant' désactivé tant que la bonne réponse n'est pas trouvée -->
    <button id="button-suivant" disabled title="Répondez correctement pour continuer"></button>

    <!-- Zone de feedback (regroupe emoji + message) -->
    <div id="feedback" class="feedback">
        <div id="emojiContainer" class="emoji-container">😢😭😝
            <div id="emojiCaption" class="emoji-caption"></div>
        </div>
        <div id="feedbackText" class="feedback-text"></div>
    </div>
</div>


<style>
/* Feedback bloc sous le QCM */
.feedback { text-align:center; margin-top: 24px; }
/* Emoji de réaction affiché temporairement en cas de mauvaise réponse */
.emoji-container {
    display: none; /* Caché par défaut */
    font-size: 70px;
    position: absolute;
    top: 40%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation: bounce 1s ease-in-out; /* Animation rebond */
}
.emoji-caption { display:none; margin-top: 12px; font-size: 18px; color:#b91c1c; font-weight:600; text-align:center; }
.feedback-text { margin-top: 12px; font-size: 1.05rem; font-weight: 600; }
.feedback-text.error { color: #b91c1c; }
.feedback-text.success { color: #15803d; }

/* Animation de rebond lors de l'apparition de l'emoji */
@keyframes bounce {
    0% { transform: translate(-50%, -50%) scale(0); }
    50% { transform: translate(-50%, -50%) scale(1.2); }
    100% { transform: translate(-50%, -50%) scale(1); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const choices = document.querySelectorAll('.choice'); // Tous les boutons de réponse
    const goodAnswerNonFormater = "<?=$BR;?>"; // Réponse correcte depuis PHP
    const goodAnswer = goodAnswerNonFormater.trim(); // Nettoyage des espaces éventuels
    const emojiContainer = document.getElementById('emojiContainer');
    const feedbackText = document.getElementById('feedbackText');
    const emojiCaption = document.getElementById('emojiCaption');
    const btnSuivant = document.getElementById('button-suivant');
    const idSolo = <?= json_encode((int)($_GET['id'] ?? 0)) ?>;
    const idUtilisateur = <?= json_encode((int)($_GET['id_utilisateur'] ?? 0)) ?>;
    const csrfToken = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
    let attempts = 0; // nombre d'essais (mauvaises réponses) avant de trouver la bonne réponse

    // Écouteurs d'événement sur chaque bouton de choix
    choices.forEach(choice => {
        choice.addEventListener('click', function() {
            if (this.disabled) return; // éviter double-clic
            // Désactiver immédiatement le bouton cliqué pour empêcher tout double-clic
            this.disabled = true;
            this.classList.add('disabled');
            const selectedAnswer = this.textContent.trim(); // Réponse sélectionnée

            // Réinitialiser les états transitoires mais conserver l'historique des mauvaises réponses
            // (ne PAS retirer 'wrong' pour laisser visibles les erreurs précédentes)
            choices.forEach(c => c.classList.remove('selected', 'correct'));

            this.classList.add('selected'); // Marque ce bouton comme sélectionné

            const isCorrect = (selectedAnswer === goodAnswer);
            const attemptIndex = attempts + 1; // index de tentative pour ce clic

            // Journaliser la tentative (correcte ou non)
            if (csrfToken && idUtilisateur && idSolo) {
                fetch('index.php?r=attempt_log', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        csrf_token: csrfToken,
                        id_utilisateur: String(idUtilisateur),
                        id_solo: String(idSolo),
                        attempt_index: String(attemptIndex),
                        choice: selectedAnswer,
                        is_correct: isCorrect ? '1' : '0'
                    }).toString()
                }).catch(()=>{});
            }

            if (isCorrect) {
                this.classList.add('correct'); // Bonne réponse

                // Lancer des confettis en cas de bonne réponse
                confetti({
                    particleCount: 400,
                    spread: 200,
                    origin: { y: 0.5 },
                    shapes: ['circle', 'square', 'rect'],
                    scalar: 1.9,
                    ticks: 300,
                    gravity: 0.5
                });

                // Activer le bouton 'suivant' et changer son libellé
                if (btnSuivant) {
                    // Désactiver tous les autres choix définitivement pour cette question
                    choices.forEach(c => { c.disabled = true; });
                    btnSuivant.disabled = false;
                    btnSuivant.textContent = 'Passer à la question suivante';
                    btnSuivant.title = '';
                    // Message de succès
                    if (feedbackText) {
                        feedbackText.textContent = 'Bonne réponse !';
                        feedbackText.classList.remove('error');
                        feedbackText.classList.add('success');
                    }
                    // Enregistrer l'évaluation côté serveur (tentatives jusqu'à succès)
                    // attempts compte le nombre de mauvaises réponses avant la bonne; on envoie attempts (si 0 => trouvé du premier coup)
                    fetch('index.php?r=eval_log', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            csrf_token: csrfToken,
                            id_utilisateur: String(idUtilisateur),
                            id_solo: String(idSolo),
                            attempts: String(attempts)
                        }).toString()
                    }).catch(()=>{});
                    // Naviguer vers la question suivante au clic (alias 'qcm')
                    btnSuivant.addEventListener('click', function(){
                        // Prévenir le double-clic sur "suivant"
                        btnSuivant.disabled = true;
                        window.location.href = 'index.php?r=qcm&id_utilisateur=' + encodeURIComponent(idUtilisateur);
                    }, { once: true });
                }

            } else {
                this.classList.add('wrong'); // Mauvaise réponse
                attempts += 1; // incrémenter le compteur de mauvaises réponses
                // Ne pas dévoiler la bonne réponse; afficher feedback + emoji
                // Choisir un message court et sympa selon le nombre d'essais
                let msg = '';
                if (attempts === 1) {
                    msg = "Oups, raté ! Réessayez.";
                } else if (attempts === 2) {
                    msg = "Oh non, encore une fausse… Vous y êtes presque, essayez encore !";
                } else {
                    msg = "Courage ! Cette fois, c’est la bonne…";
                }
                if (feedbackText) {
                    feedbackText.textContent = msg;
                    feedbackText.classList.remove('success');
                    feedbackText.classList.add('error');
                }
                // Afficher temporairement l'emoji de tristesse
                emojiContainer.style.display = 'block';
                if (emojiCaption) {
                    emojiCaption.textContent = msg;
                    emojiCaption.style.display = 'block';
                }
                setTimeout(() => {
                    emojiContainer.style.display = 'none';
                    if (emojiCaption) {
                        emojiCaption.style.display = 'none';
                    }
                }, 1500); // Emoji visible pendant 2 secondes

                // Réactiver uniquement les autres choix non encore cliqués après un court délai
                setTimeout(() => {
                    choices.forEach(c => {
                        if (!c.classList.contains('disabled')) {
                            c.disabled = false;
                        }
                    });
                }, 200);
            }
        });
    });
});
</script>

<!-- Inclusion de la bibliothèque de confettis depuis CDN -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.4.0/dist/confetti.browser.min.js"></script>
