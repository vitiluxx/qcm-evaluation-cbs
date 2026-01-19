<?php
// Script de migration pour créer la table utilisateurs_partie
include_once("connexionBd.php");

try {
    $sql = "CREATE TABLE IF NOT EXISTS utilisateurs_partie (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $connexionBd->exec($sql);
    echo '<h2 style="color:green;">Table <b>utilisateurs_partie</b> créée ou déjà existante !</h2>';
} catch (PDOException $e) {
    echo '<h2 style="color:red;">Erreur lors de la création de la table : ' . $e->getMessage() . '</h2>';
}
?>
<p>Tu peux maintenant supprimer ce fichier <b>migration.php</b> pour la sécurité.</p> 