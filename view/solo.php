<!-- Affichage du nom de l'utilisateur en haut des la page -->
<div style="text-align:center; margin-top:20px; font-size:1.3em; font-weight:bold; color:#0589b1;">
    M./Mme : <?= htmlspecialchars($nom_utilisateur) ?>
</div>

<!-- Contenu principal de la page (tirage) -->
<main class="main-corpsPageSujetAccueil solo-page">
    <div class="div-contenu_corpsPageSujets" style="text-align:center; margin-top:40px;">
        <!-- Zone d'affichage du nombre animé -->
        <div id="nombreAnime" style="font-size:60px; margin-bottom:20px; color:#333; min-height:72px;"></div>

        <?php if (!empty($tirageMessage)): ?>
            <div style="text-align:center;color:#b91c1c;margin-top:10px;">
                <?= htmlspecialchars($tirageMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Bouton de tirage -->
        <form method="post" id="form-tirer">
            <!-- Bouton rond rouge (styled via css.css) -->
            <button type="submit" name="tirer" id="btn-tirer" class="boutton_rouge"><span style="display:inline-block; max-width:80%; font-size:clamp(16px, 3.2vw, 22px); line-height:1; letter-spacing:0.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight: bold;">TIRER</span></button>
        </form>

        <p class="note-explication">
            Clique sur "TIRER" pour piocher un numéro et démarrer une question.
        </p>
        <p id="redirNote" class="note-explication"></p>
    </div>
</main>

<!-- Script JS: animation visible puis redirection -->
<script>
// Empêcher les doubles soumissions sur le bouton TIRER sans bloquer l'envoi de name="tirer"
document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('form-tirer');
    const btn = document.getElementById('btn-tirer');
    if (form && btn) {
        form.addEventListener('submit', function(){
            // Injecter un champ caché pour garantir la présence de "tirer" même si le bouton est désactivé ensuite
            if (!form.querySelector('input[name="tirer"]')) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'tirer';
                hidden.value = '1';
                form.appendChild(hidden);
            }
            // Désactiver le bouton après avoir ajouté le champ caché
            btn.setAttribute('aria-disabled', 'true');
            btn.classList.add('is-waiting');
            const span = btn.querySelector('span');
            if (span) span.textContent = 'Patientez...';
        }, { once: true });
    }
});

/**
 * Anime l'affichage d'un nombre en comptage (décrémentation ou incrémentation) jusqu'à la valeur finale.
 * nombreFinal: nombre entier à afficher à la fin
 * dureeMs: durée totale de l'animation en millisecondes
 */
function animationChiffre(nombreFinal, dureeMs) {
    const output = document.getElementById('nombreAnime'); // Zone d'affichage
    if (!output) return;

    // Départ aléatoire pour l'effet visuel
    let start = Math.floor(Math.random() * 150) + 1; // 1..150
    let current = start;
    const steps = Math.max(30, Math.floor(dureeMs / 50)); // Nombre d'étapes (~50ms par étape)
    let stepCount = 0;
    const delta = (nombreFinal - start) / steps;

    const timer = setInterval(() => {
        stepCount++;
        current += delta;
        output.textContent = Math.max(1, Math.floor(current));
        if (stepCount >= steps) {
            clearInterval(timer);
            output.textContent = nombreFinal;
        }
    }, Math.max(16, Math.floor(dureeMs / steps)));
}

<?php if (!empty($tirageEffectue) && $idTire !== null): ?>
// Lancer une animation de 3 secondes puis rediriger vers la question tirée
(function(){
    const idTire = <?= (int)$idTire ?>;
    const idUtilisateur = <?= (int)$id_utilisateur ?>;
    // Démarre l'animation visible pendant 3000ms
    animationChiffre(idTire, 3000);
    // Après 3s d'animation, afficher 2s le nombre final puis rediriger (total ~5s)
    setTimeout(function(){
        const note = document.getElementById('redirNote');
        if (note) note.textContent = 'Redirection dans 2 secondes...';
        setTimeout(function(){
            window.location.href = 'index.php?r=mainQcm&id=' + idTire + '&id_utilisateur=' + idUtilisateur;
        }, 2000);
    }, 3000);
})();
<?php endif; ?>
</script>
