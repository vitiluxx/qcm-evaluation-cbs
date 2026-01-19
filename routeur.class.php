<?php

    include(CONTROLLER_ROOT."allMethod.class.php");
    
    class Routeur 
    {
        private $requette;
        private $route = [
                          "formulaire.php" =>          ["controller" => "allMethod", "method" => "affichePageFormulaire"],
                          "accueil.php" =>             ["controller" => "allMethod", "method" => "affichePageAccueil" ], 
                          "filiere.php" =>             ["controller" => "allMethod", "method" => "affichePageFiliere"], 
                          "solo.php" =>                ["controller" => "allMethod", "method" => "affichePageSolo"],                    
                          "mainSolo.php" =>            ["controller" => "allMethod", "method" => "affichePageMainSolo"],                      
                          "dashboard.php" =>           ["controller" => "allMethod", "method" => "affichePageDashboard"],                      
                          "nom_utilisateur.php" =>    ["controller" => "allMethod", "method" => "affichePageNomUtilisateur"],
                          "admin_solo.php" =>          ["controller" => "allMethod", "method" => "affichePageAdminSoloList"],
                          "admin_solo_edit.php" =>     ["controller" => "allMethod", "method" => "affichePageAdminSoloEdit"],
                          "admin_login.php" =>         ["controller" => "allMethod", "method" => "affichePageAdminLogin"],
                          "logout.php" =>              ["controller" => "allMethod", "method" => "affichePageLogout"],
                          "eval_log.php" =>            ["controller" => "allMethod", "method" => "affichePageEvalLog"],
                          "export_evaluations.php" =>  ["controller" => "allMethod", "method" => "affichePageExportEvaluations"],
                          "attempt_log.php" =>        ["controller" => "allMethod", "method" => "affichePageAttemptLog"],
                          "export_user_session.php" => ["controller" => "allMethod", "method" => "affichePageExportUserSession"],
                          "reinitialiser_historique.php" => ["controller" => "allMethod", "method" => "affichePageResetHistorique"],
                          "qcm.php" =>                 ["controller" => "allMethod", "method" => "affichePageSolo"],
                          "mainQcm.php" =>             ["controller" => "allMethod", "method" => "affichePageMainSolo"],
                          "admin_qcm.php" =>           ["controller" => "allMethod", "method" => "affichePageAdminSoloList"],
                          "admin_qcm_edit.php" =>      ["controller" => "allMethod", "method" => "affichePageAdminSoloEdit"],

                          "formulaire" =>              ["controller" => "allMethod", "method" => "affichePageFormulaire"],
                          "accueil" =>                 ["controller" => "allMethod", "method" => "affichePageAccueil" ], 
                          "filiere" =>                 ["controller" => "allMethod", "method" => "affichePageFiliere"], 
                          "solo" =>                    ["controller" => "allMethod", "method" => "affichePageSolo"],                    
                          "dashboard" =>               ["controller" => "allMethod", "method" => "affichePageDashboard"], 
                          "admin_solo" =>              ["controller" => "allMethod", "method" => "affichePageAdminSoloList"],
                          "admin_solo_edit" =>         ["controller" => "allMethod", "method" => "affichePageAdminSoloEdit"],
                          "admin_login" =>             ["controller" => "allMethod", "method" => "affichePageAdminLogin"],
                          "logout" =>                  ["controller" => "allMethod", "method" => "affichePageLogout"],
                          "eval_log" =>                ["controller" => "allMethod", "method" => "affichePageEvalLog"],
                          "export_evaluations" =>      ["controller" => "allMethod", "method" => "affichePageExportEvaluations"],
                          "attempt_log" =>             ["controller" => "allMethod", "method" => "affichePageAttemptLog"],
                          "export_user_session" =>     ["controller" => "allMethod", "method" => "affichePageExportUserSession"],
                          "reinitialiser_historique" => ["controller" => "allMethod", "method" => "affichePageResetHistorique"],
                          "nom_utilisateur" =>         ["controller" => "allMethod", "method" => "affichePageNomUtilisateur"],
                          "qcm" =>                      ["controller" => "allMethod", "method" => "affichePageSolo"],
                          "mainQcm" =>                 ["controller" => "allMethod", "method" => "affichePageMainSolo"],
                          "admin_qcm" =>               ["controller" => "allMethod", "method" => "affichePageAdminSoloList"],
                          "admin_qcm_edit" =>          ["controller" => "allMethod", "method" => "affichePageAdminSoloEdit"],

                          // Alias FR supplémentaires
                          "tableau_de_bord" =>         ["controller" => "allMethod", "method" => "affichePageDashboard"],
                          "publier_qcm" =>             ["controller" => "allMethod", "method" => "affichePageFormulaire"],
                          "gestion_qcm" =>             ["controller" => "allMethod", "method" => "affichePageAdminSoloList"],
                          "modifier_qcm" =>            ["controller" => "allMethod", "method" => "affichePageAdminSoloEdit"],
                          "connexion_admin" =>         ["controller" => "allMethod", "method" => "affichePageAdminLogin"],
                          "deconnexion" =>             ["controller" => "allMethod", "method" => "affichePageLogout"],
                          "journal_tentative" =>       ["controller" => "allMethod", "method" => "affichePageAttemptLog"],
                          "export_evaluations_utilisateur" => ["controller" => "allMethod", "method" => "affichePageExportUserSession"],
                        
                        ];
    
    
        public function __construct($requette)
        {
            
            $this->requette = $requette;
            
        }
    
    
        public function runderController()
        {
            $requette = $this->requette;
            
            if(key_exists($requette, $this->route))
            {
                $controller = $this->route[$requette]["controller"]; //on recupere la requette + le controller
                $method = $this->route[$requette]["method"]; // de meme on recupere la requette + la method adequoite
        
                $controllerDemander = new $controller();
                $controllerDemander->$method();
            }
            else{
                echo "ERREUR 404 PAGE NON TROUVER SUR NOTRE SITE";
            }
        }
    }

?>