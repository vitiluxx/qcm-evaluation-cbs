<?php
    
    class formulaire
    {
        private $connexionBd;

        public function __construct($connexion) {
            // Si l'appelant passe null par inadvertance, on tente un fallback propre
            if (!($connexion instanceof PDO)) {
                // Essayer de récupérer la connexion globale si disponible
                if (isset($GLOBALS['connexionBd']) && $GLOBALS['connexionBd'] instanceof PDO) {
                    $connexion = $GLOBALS['connexionBd'];
                }
            }
            // Vérification finale
            if (!($connexion instanceof PDO)) {
                throw new RuntimeException('La connexion BD (PDO) est absente dans formulaire::__construct');
            }
            $this->connexionBd = $connexion;
        }

        public function setInsererJeuSolo($question_solo,$reponse_a_solo,$reponse_b_solo,$reponse_c_solo,$reponse_d_solo,$bonne_reponse_solo)
        {
            // Préparer l'insertion du QCM SOLO
            $requette = $this->connexionBd->prepare('INSERT INTO solo(question_solo,reponse_a_solo,reponse_b_solo,reponse_c_solo,reponse_d_solo,bonne_reponse_solo) VALUE (:question_solo,:reponse_a_solo,:reponse_b_solo,:reponse_c_solo,:reponse_d_solo,:bonne_reponse_solo) ');
            $requette->bindParam(':question_solo',  $question_solo, PDO::PARAM_STR);
            $requette->bindParam(':reponse_a_solo', $reponse_a_solo, PDO::PARAM_STR);
            $requette->bindParam(':reponse_b_solo', $reponse_b_solo, PDO::PARAM_STR);
            $requette->bindParam(':reponse_c_solo', $reponse_c_solo, PDO::PARAM_STR);
            $requette->bindParam(':reponse_d_solo', $reponse_d_solo, PDO::PARAM_STR);
            $requette->bindParam(':bonne_reponse_solo', $bonne_reponse_solo, PDO::PARAM_STR);
            $requette->execute();
            return true;
        }
    }

 ?>