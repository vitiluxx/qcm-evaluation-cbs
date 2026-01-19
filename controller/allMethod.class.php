<?php 
class allMethod
{
    /**
     * Assure la présence de la connexion PDO ($connexionBd) dans le scope global.
     * À appeler avant toute utilisation de modèles ou de vues dépendantes de la BD.
     */
    private function ensureDb(): void
    {
        // Charge la connexion si absente
        if (!isset($GLOBALS['connexionBd']) || !($GLOBALS['connexionBd'] instanceof PDO)) {
            // Inclure le fichier de connexion dans ce scope (méthode)
            include_once(ROOT.'connexionBd.php');
            // Si la variable locale $connexionBd est créée par l'include, la promouvoir en globale
            if (isset($connexionBd) && $connexionBd instanceof PDO) {
                $GLOBALS['connexionBd'] = $connexionBd;
            }
        }
    }
    
    /*============================================================================================================================ */    
    // Journalisation de CHAQUE tentative (bonne ou mauvaise)
    public function affichePageAttemptLog()
    {
        $this->ensureDb();
        header('Content-Type: application/json');
        $pdo = $GLOBALS['connexionBd'] ?? null;
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok'=>false,'error'=>'Méthode non autorisée']);
                return;
            }
            $token = $_POST['csrf_token'] ?? '';
            if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                http_response_code(400);
                echo json_encode(['ok'=>false,'error'=>'CSRF invalide']);
                return;
            }
            $sessionId = $_SESSION['eval_session_id'] ?? '';
            $idUtilisateur = isset($_POST['id_utilisateur']) ? (int)$_POST['id_utilisateur'] : 0;
            $idSolo = isset($_POST['id_solo']) ? (int)$_POST['id_solo'] : 0;
            $attemptIndex = isset($_POST['attempt_index']) ? (int)$_POST['attempt_index'] : 0;
            $choice = trim((string)($_POST['choice'] ?? ''));
            $isCorrect = isset($_POST['is_correct']) && (int)$_POST['is_correct'] === 1 ? 1 : 0;
            if ($sessionId==='' || $idUtilisateur<=0 || $idSolo<=0 || $attemptIndex<=0 || $choice==='') {
                http_response_code(422);
                echo json_encode(['ok'=>false,'error'=>'Paramètres invalides']);
                return;
            }
            // ATTENTION: nécessite une table `evaluation_attempts` créée manuellement côté BDD
            // Colonnes recommandées: id (AI), session_id (varchar 64), id_utilisateur (int), id_solo (int), attempt_index (int), choice (varchar 255), is_correct (tinyint), created_at (datetime default current_timestamp)
            $sql = 'INSERT INTO evaluation_attempts (session_id, id_utilisateur, id_solo, attempt_index, choice, is_correct, created_at) VALUES (:sid,:u,:s,:ai,:c,:ok,NOW())';
            $st = $pdo->prepare($sql);
            $st->bindParam(':sid', $sessionId, PDO::PARAM_STR);
            $st->bindParam(':u', $idUtilisateur, PDO::PARAM_INT);
            $st->bindParam(':s', $idSolo, PDO::PARAM_INT);
            $st->bindParam(':ai', $attemptIndex, PDO::PARAM_INT);
            $st->bindParam(':c', $choice, PDO::PARAM_STR);
            $st->bindParam(':ok', $isCorrect, PDO::PARAM_INT);
            $st->execute();
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'error'=>'Erreur serveur']);
        }
    }

    /*============================================================================================================================ */    
    // Export CSV détaillé des tentatives pour un utilisateur
    public function affichePageExportUserSession()
    {
        $this->requireAdmin();
        $this->ensureDb();
        $pdo = $GLOBALS['connexionBd'] ?? null;
        include_once(MODEL_ROOT.'admin.class.php');
        $adminModel = new adminModel($pdo);

        $idUser = isset($_GET['id_utilisateur']) ? (int)$_GET['id_utilisateur'] : 0;
        if ($idUser<=0) {
            http_response_code(400);
            echo 'Paramètre id_utilisateur requis';
            return;
        }
        $filters = ['id_utilisateur'=>$idUser];
        if (!empty($_GET['date_from'])) { $filters['date_from'] = $_GET['date_from']; }
        if (!empty($_GET['date_to'])) { $filters['date_to'] = $_GET['date_to']; }

        $rows = $adminModel->getUserSessionExport($filters);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="user_session_'.$idUser.'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Session', 'Utilisateur ID', 'Question ID', 'Question', 'Tentative #', 'Choix', 'Correct'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['created_at'] ?? '',
                $r['session_id'] ?? '',
                isset($r['id_utilisateur']) ? (int)$r['id_utilisateur'] : '',
                isset($r['id_solo']) ? (int)$r['id_solo'] : '',
                isset($r['question_solo']) ? preg_replace("/\s+/u", ' ', (string)$r['question_solo']) : '',
                isset($r['attempt_index']) ? (int)$r['attempt_index'] : '',
                $r['choice'] ?? '',
                isset($r['is_correct']) ? ((int)$r['is_correct']===1?'oui':'non') : ''
            ], ';');
        }
        fclose($out);
        exit;
    }

    /*============================================================================================================================ */    
    // Export CSV des évaluations (filtrable) – centralisé depuis le Dashboard
    public function affichePageExportEvaluations()
    {
        $this->requireAdmin();
        $this->ensureDb();
        $pdo = $GLOBALS['connexionBd'] ?? null;
        include_once(MODEL_ROOT.'admin.class.php');
        $adminModel = new adminModel($pdo);

        // Récupérer filtres via GET
        $filters = [];
        if (isset($_GET['id_utilisateur']) && $_GET['id_utilisateur'] !== '') {
            $filters['id_utilisateur'] = (int)$_GET['id_utilisateur'];
        }
        if (!empty($_GET['date_from'])) { $filters['date_from'] = $_GET['date_from']; }
        if (!empty($_GET['date_to'])) { $filters['date_to'] = $_GET['date_to']; }

        $rows = $adminModel->getExportData($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="evaluations_export.csv"');
        $out = fopen('php://output', 'w');
        // En-têtes lisibles et complets
        fputcsv($out, ['Date', 'ID Utilisateur', 'ID Question', 'Utilisateur', 'Questions', 'Tentatives'], ';');
        foreach ($rows as $r) {
            $created = $r['created_at'] ?? '';
            $uid = isset($r['id_utilisateur']) ? (int)$r['id_utilisateur'] : '';
            $uname = $r['utilisateur'] ?? '';
            $qid = isset($r['id_solo']) ? (int)$r['id_solo'] : '';
            // Nettoyer la question pour le CSV (retirer retours à la ligne)
            $qtext = isset($r['question_solo']) ? preg_replace("/\s+/u", ' ', (string)$r['question_solo']) : '';
            $attempts = isset($r['attempts']) ? (int)$r['attempts'] : '';
            fputcsv($out, [$created, $uid, $qid, $qtext, $uname, $attempts], ';');
        }
        fclose($out);
        exit;
    }

    /** Vérifie si un admin est connecté */
    private function isAdmin(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    /** Redirige vers la page de login si non authentifié */
    private function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            header('Location: '.HOST.'admin_login');
            exit;
        }
    }

    public function affichePageFormulaire()
    {
        require(MODEL_ROOT."formulaire.class.php");
        // Connexion BD sécurisée
        $this->ensureDb();
        // Récupérer explicitement depuis la superglobale pour éviter un scope vide
        $pdo = $GLOBALS['connexionBd'] ?? null;
        if (!($pdo instanceof PDO)) {
            // Fallback ultime si pour une raison quelconque la connexion n'est pas encore dispo
            include_once(ROOT.'connexionBd.php');
            $pdo = $GLOBALS['connexionBd'] ?? null;
        }
        $objet = new formulaire($pdo);
    
        if(isset($_POST["soumettre"]))
        {
            // CSRF verification
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                ?><script>alert('Echec de sécurité: CSRF invalide.');</script><?php
            } else {
            // Trim inputs
            $question_solo = trim((string)$_POST["question"]);
            $reponse_a_solo = trim((string)$_POST["reponse_a"]);
            $reponse_b_solo = trim((string)$_POST["reponse_b"]);
            $reponse_c_solo = trim((string)$_POST["reponse_c"]);
            $reponse_d_solo = trim((string)$_POST["reponse_d"]);
            $bonne_reponse_solo = trim((string)$_POST["bonne_reponse"]);

            // Basic validations
            $fields = [$question_solo, $reponse_a_solo, $reponse_b_solo, $reponse_c_solo, $reponse_d_solo, $bonne_reponse_solo];
            $allFilled = array_reduce($fields, function($c,$v){ return $c && (trim((string)$v) !== ''); }, true);

            // Length validations
            $okLen = (mb_strlen($question_solo) <= 500)
                     && (mb_strlen($reponse_a_solo) <= 200)
                     && (mb_strlen($reponse_b_solo) <= 200)
                     && (mb_strlen($reponse_c_solo) <= 200)
                     && (mb_strlen($reponse_d_solo) <= 200)
                     && (mb_strlen($bonne_reponse_solo) <= 200);

            if($_POST["choix_jeux"] === "solo" && $allFilled && $okLen)
            {
                $testInsertion = $objet->setInsererJeuSolo($question_solo,$reponse_a_solo,$reponse_b_solo,$reponse_c_solo,$reponse_d_solo,$bonne_reponse_solo);
 
                if($testInsertion === true)
                {
                    ?><script>alert("QUIZ SOLO inserer avec succes");</script><?php
                }
                else {
                    ?><script>alert("Erreur: insertion échouée.");</script><?php
                }
            }
            else {
                ?><script>alert("Veuillez remplir tous les champs, respecter les limites (Question ≤ 500, Réponses ≤ 200) et choisir SOLO.");</script><?php
            }
            }
        }

        require(VIEW_ROOT."formulaire.php");
    }

   /*============================================================================================================================ */    

    public function affichePageAccueil()
    {

        require(VIEW_ROOT."accueil.php");
    }

    /*============================================================================================================================ */    
    
    public function affichePageJeux()
    {

        require(VIEW_ROOT."jeu.php");
    }



    /*============================================================================================================================ */    

    public function affichePageFiliere()
    {

        include(VIEW_ROOT."filiere.php");
    }

    /*============================================================================================================================ */    

    public function affichePageSolo()
    {
        // Certaines vues chargent elles-mêmes la connexion, mais on sécurise l'accès
        $this->ensureDb();
        // Initialiser un identifiant de session d'évaluation si absent
        if (empty($_SESSION['eval_session_id'])) {
            $_SESSION['eval_session_id'] = bin2hex(random_bytes(16));
        }

        // Préparer les données nécessaires à la vue
        $pdo = $GLOBALS['connexionBd'] ?? null;
        include_once(MODEL_ROOT.'solo.class.php');
        $soloModel = new solo($pdo);

        // Récupérer/valider l'identifiant utilisateur
        if (!isset($_GET['id_utilisateur']) || !is_numeric($_GET['id_utilisateur']) || (int)$_GET['id_utilisateur'] <= 0) {
            header('Location: '.HOST.'nom_utilisateur');
            exit();
        }
        $id_utilisateur = (int)$_GET['id_utilisateur'];

        // Obtenir le nom de l'utilisateur (rediriger si introuvable)
        $nom_utilisateur = $soloModel->getNomUtilisateur($id_utilisateur);
        if (!$nom_utilisateur) {
            header('Location: '.HOST.'nom_utilisateur');
            exit();
        }

        // Vérifier si l'utilisateur a terminé toutes les questions
        $totalQuestions = 0; $totalRepondu = 0; $sessionTerminee = false;
        try {
            $totalQuestions = (int)$pdo->query('SELECT COUNT(*) FROM solo')->fetchColumn();
            $stC = $pdo->prepare('SELECT COUNT(*) FROM evaluations WHERE id_utilisateur = :u');
            $stC->bindParam(':u', $id_utilisateur, PDO::PARAM_INT);
            $stC->execute();
            $totalRepondu = (int)$stC->fetchColumn();
            $sessionTerminee = ($totalQuestions > 0 && $totalRepondu >= $totalQuestions);
        } catch (Throwable $e) {
            // en cas d'erreur SQL, on considère non terminé pour ne pas bloquer
            $sessionTerminee = false;
        }

        // Liste d'exclusions en session pour éviter de repiocher la même question durant la séance (avant validation)
        if (!isset($_SESSION['tirages_exclus'])) { $_SESSION['tirages_exclus'] = []; }
        if (!isset($_SESSION['tirages_exclus'][$id_utilisateur]) || !is_array($_SESSION['tirages_exclus'][$id_utilisateur])) {
            $_SESSION['tirages_exclus'][$id_utilisateur] = [];
        }

        // Gestion du tirage (POST)
        $tirageEffectue = false;
        $idTire = null;
        if ($sessionTerminee) {
            $tirageMessage = "Séance d'évaluation terminée: vous avez répondu à toutes les questions disponibles.";
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tirer'])) {
            // Essayer en excluant aussi les questions déjà tirées dans cette session
            $exclude = $_SESSION['tirages_exclus'][$id_utilisateur];
            if (method_exists($soloModel, 'getRandomSoloIdExcluding')) {
                $idSolo = $soloModel->getRandomSoloIdExcluding($id_utilisateur, $exclude);
            } else {
                $idSolo = $soloModel->getRandomSoloId($id_utilisateur);
            }
            if ($idSolo !== null) {
                $tirageEffectue = true;
                $idTire = (int)$idSolo;
                // Mémoriser dans l'exclusion de séance pour éviter la répétition immédiate
                if (!in_array($idTire, $_SESSION['tirages_exclus'][$id_utilisateur], true)) {
                    $_SESSION['tirages_exclus'][$id_utilisateur][] = $idTire;
                }
            } else {
                // Plus de question disponible pour cet utilisateur
                $tirageMessage = "Aucune autre question disponible. Séance terminée.";
            }
        }

        include(VIEW_ROOT.'solo.php');
    }

    /*============================================================================================================================ */    
    public function affichePageMainSolo()
    { 
        // Cette vue peut utiliser des modèles; on s'assure de la connexion.
        $this->ensureDb();
        // Générer un CSRF si absent (utilisé pour log d'évaluation)
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        include(VIEW_ROOT."mainSolo.php");
    }
    /*============================================================================================================================ */    
    public function affichePageDashboard()
    { 
        // Le dashboard exécute des requêtes (compteurs) — connexion + auth requises
        $this->requireAdmin();
        $this->ensureDb();
        include(ADMIN_ROOT."dashboard.php");
    }
    
    /*============================================================================================================================ */    
    // Affichage de la page de saisie du nom utilisateur
    public function affichePageNomUtilisateur()
    {
        // La page d'enregistrement utilisateur écrit en BD
        $this->ensureDb();
        require(VIEW_ROOT."nom_utilisateur.php");
    }
    /*============================================================================================================================ */    

    // === Admin SOLO: Liste des questions ===
    public function affichePageAdminSoloList()
    {
        // La liste interagit avec la BD (chargement, suppression)
        $this->requireAdmin();
        $this->ensureDb();
        $pdo = $GLOBALS['connexionBd'] ?? null;
        include_once(MODEL_ROOT.'solo.class.php');
        $soloModel = new solo($pdo);

        // CSRF token
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrf = $_SESSION['csrf_token'];
        $messages = [];

        // Suppression (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $messages[] = ['type' => 'danger', 'text' => 'Jeton CSRF invalide.'];
            } else {
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                if ($id > 0) {
                    $soloModel->deleteSolo($id);
                    $messages[] = ['type' => 'success', 'text' => 'Question supprimée.'];
                }
            }
        }

        $rows = $soloModel->getAll();
        include(ADMIN_ROOT.'admin_solo.php');
    }

    // === Admin SOLO: Edition d'une question ===
    public function affichePageAdminSoloEdit()
    {
        // L'édition lit/écrit en BD
        $this->requireAdmin();
        $this->ensureDb();
        $pdo = $GLOBALS['connexionBd'] ?? null;
        include_once(MODEL_ROOT.'solo.class.php');
        $soloModel = new solo($pdo);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrf = $_SESSION['csrf_token'];

        // Validate id
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $messages = [];
        if ($id <= 0) {
            $messages[] = ['type' => 'danger', 'text' => 'Identifiant invalide.'];
            include(ADMIN_ROOT.'admin_solo_edit.php');
            return;
        }

        // Handle update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $messages[] = ['type' => 'danger', 'text' => 'Jeton CSRF invalide.'];
            } else {
                $question = trim($_POST['question'] ?? '');
                $ra = trim($_POST['reponse_a'] ?? '');
                $rb = trim($_POST['reponse_b'] ?? '');
                $rc = trim($_POST['reponse_c'] ?? '');
                $rd = trim($_POST['reponse_d'] ?? '');
                $br = trim($_POST['bonne_reponse'] ?? '');
                if ($question === '' || $ra === '' || $rb === '' || $rc === '' || $rd === '' || $br === '') {
                    $messages[] = ['type' => 'danger', 'text' => 'Tous les champs sont requis.'];
                } else {
                    if ($soloModel->updateSoloFull($id, $question, $ra, $rb, $rc, $rd, $br)) {
                        $messages[] = ['type' => 'success', 'text' => 'Question mise à jour avec succès.'];
                    } else {
                        $messages[] = ['type' => 'danger', 'text' => 'Échec de la mise à jour.'];
                    }
                }
            }
        }

        $row = $soloModel->getById($id);
        if (!$row) {
            $messages[] = ['type' => 'warning', 'text' => 'Question introuvable.'];
        }
        include(ADMIN_ROOT.'admin_solo_edit.php');
    }

    /*============================================================================================================================ */    
    // Endpoint pour journaliser une évaluation (tentatives jusqu'à bonne réponse)
    public function affichePageEvalLog()
    {
        $this->ensureDb();
        header('Content-Type: application/json');
        $pdo = $GLOBALS['connexionBd'] ?? null;
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok'=>false,'error'=>'Méthode non autorisée']);
                return;
            }
            $token = $_POST['csrf_token'] ?? '';
            if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                http_response_code(400);
                echo json_encode(['ok'=>false,'error'=>'CSRF invalide']);
                return;
            }
            $idUtilisateur = isset($_POST['id_utilisateur']) ? (int)$_POST['id_utilisateur'] : 0;
            $idSolo = isset($_POST['id_solo']) ? (int)$_POST['id_solo'] : 0;
            $attempts = isset($_POST['attempts']) ? (int)$_POST['attempts'] : 0;
            if ($idUtilisateur<=0 || $idSolo<=0 || $attempts<0) {
                http_response_code(422);
                echo json_encode(['ok'=>false,'error'=>'Paramètres invalides']);
                return;
            }
            // Insert dans la table evaluations (à créer côté BDD par l'admin)
            $sql = 'INSERT INTO evaluations (id_utilisateur, id_solo, attempts, created_at) VALUES (:u, :s, :a, NOW())';
            $st = $pdo->prepare($sql);
            $st->bindParam(':u', $idUtilisateur, PDO::PARAM_INT);
            $st->bindParam(':s', $idSolo, PDO::PARAM_INT);
            $st->bindParam(':a', $attempts, PDO::PARAM_INT);
            $st->execute();
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'error'=>'Erreur serveur']);
        }
    }

    /*============================================================================================================================ */    
    // Page de connexion admin
    public function affichePageAdminLogin()
    {
        $this->ensureDb();
        // Générer CSRF si absent
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $messages = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postedToken = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
                $messages[] = ['type' => 'danger', 'text' => 'Jeton CSRF invalide.'];
            } else {
                $username = trim((string)($_POST['username'] ?? ''));
                $password = (string)($_POST['password'] ?? '');
                if ($username === '' || $password === '') {
                    $messages[] = ['type' => 'danger', 'text' => 'Identifiants requis.'];
                } else {
                    // Vérifier l'utilisateur dans la table admins
                    $pdo = $GLOBALS['connexionBd'] ?? null;
                    try {
                        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :u LIMIT 1');
                        $stmt->bindParam(':u', $username, PDO::PARAM_STR);
                        $stmt->execute();
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row && password_verify($password, $row['password_hash'])) {
                            $_SESSION['admin_id'] = (int)$row['id'];
                            header('Location: '.HOST.'dashboard');
                            exit;
                        } else {
                            $messages[] = ['type' => 'danger', 'text' => 'Nom d\'utilisateur ou mot de passe incorrect.'];
                        }
                    } catch (Throwable $e) {
                        $messages[] = ['type' => 'danger', 'text' => 'Erreur serveur: '.$e->getMessage()];
                    }
                }
            }
        }
        // Rendre la vue de login
        include(ADMIN_ROOT.'admin_login.php');
    }

    // Déconnexion
    public function affichePageLogout()
    {
        unset($_SESSION['admin_id']);
        header('Location: '.HOST.'admin_login');
        exit;
    }

    /*============================================================================================================================ */    
    // Action admin: réinitialiser l'historique global des évaluations
    public function affichePageResetHistorique()
    {
        $this->requireAdmin();
        $this->ensureDb();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $posted = $_POST['csrf_token'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf_token'], $posted)) {
            // Mauvaise méthode ou CSRF invalide
            header('Location: '.HOST.'tableau_de_bord');
            exit;
        }
        $pdo = $GLOBALS['connexionBd'] ?? null;
        try {
            // Supprimer l'historique global (si table evaluation_attempts existe, la vider aussi)
            $pdo->exec('DELETE FROM evaluations');
            try { $pdo->exec('DELETE FROM evaluation_attempts'); } catch (Throwable $e) { /* table optionnelle */ }
        } catch (Throwable $e) {
            // On ignore l'erreur et on redirige quand même pour éviter de bloquer l'UI
        }
        header('Location: '.HOST.'tableau_de_bord');
        exit;
    }
}
    

?>