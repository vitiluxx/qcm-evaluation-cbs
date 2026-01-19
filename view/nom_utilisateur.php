<?php
// Connexion fournie par le contrôleur (ensureDb), lue depuis la superglobale
$pdo = $GLOBALS['connexionBd'] ?? null;

// Traitement du formulaire
if (isset($_POST['nom_utilisateur']) && !empty(trim($_POST['nom_utilisateur']))) {
    $nom = htmlspecialchars(trim($_POST['nom_utilisateur']));
    // Insertion en base (table utilisateurs)
    $requete = $pdo->prepare('INSERT INTO utilisateurs (nom) VALUES (:nom)');
    $requete->bindParam(':nom', $nom, PDO::PARAM_STR);
    $requete->execute();
    $id_utilisateur = $pdo->lastInsertId();
    // Redirection vers la partie SOLO avec l'id utilisateur via le routeur
    header('Location: index.php?r=solo.php&id_utilisateur=' . $id_utilisateur);
    exit();
}
?>
<main class="main-corpsPageSujetAccueil">
    <form method="post" action="" class="form-saisie-nom">
        <h2>Bienvenue !</h2>
        <label for="nom_utilisateur">Veuillez saisir votre nom pour commencer :</label><br>
        <input type="text" id="nom_utilisateur" name="nom_utilisateur" required autofocus><br><br>
        <button type="submit">Commencer</button>
    </form>
</main>
<style>
    .form-saisie-nom {
        max-width: 400px;
        margin: 100px auto;
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        text-align: center;
    }
    .form-saisie-nom input {
        padding: 10px;
        width: 80%;
        border-radius: 5px;
        border: 1px solid #ccc;
    }
    .form-saisie-nom button {
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        background: #0589b1;
        color: #fff;
        font-weight: bold;
        cursor: pointer;
    }
    .form-saisie-nom button:hover {
        background: #02607a;
    }
</style> 