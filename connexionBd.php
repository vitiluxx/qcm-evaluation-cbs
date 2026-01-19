<?php
    try{
        $serveur = "127.0.0.1";
        $bd = "jci_bd";
        $utilisateur = "root";
        $mdp = "root";
        $encodage = "utf8";

        // Ajout explicite du port MySQL (adaptable si vous utilisez un port non standard, ex: 3307)
        $port = "3306";
        $connexionBd = new PDO ("mysql:host=$serveur;port=$port;dbname=$bd;charset=$encodage", $utilisateur, $mdp, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    catch(Exception $e)
    {
        $erreur =$e->getMessage();
        echo "ERREUR : ".$erreur;
    }
?> 
