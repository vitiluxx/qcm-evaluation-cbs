<?php
    
    class solo
    {
        private $connexionBd;

        public function __construct($connexion) {
            $this->connexionBd = $connexion;
        }

        public function AfficheJeu_Solo($id_solo)
        {
            $requette = $this->connexionBd->prepare('SELECT * FROM solo WHERE id_solo = :id_solo');
            $requette->bindParam(':id_solo', $id_solo, PDO::PARAM_INT);
            $requette->execute();
            $donnees = $requette->fetchAll(PDO::FETCH_OBJ);
            return $donnees;
        }


        public function AfficheBonneReponseJeu_Solo($id_solo)
        {
            $requette = $this->connexionBd->prepare('SELECT bonne_reponse_solo FROM solo WHERE id_solo = :id_solo');
            $requette->bindParam(':id_solo', $id_solo, PDO::PARAM_INT);
            $requette->execute();
            $donnees = $requette->fetchAll(PDO::FETCH_OBJ);
            return $donnees;
        }


        public function AfficheListeDeTirages_Solo()
        {
            $requette = $this->connexionBd->query('SELECT id_solo, tirage_solo FROM solo');
            $donnees = $requette->fetchAll(PDO::FETCH_OBJ);
            return $donnees;
        }

        
        public function setInsererTirageSolo($id_solo, $tirage_solo)
        {
            $requette = $this->connexionBd->prepare('UPDATE solo SET tirage_solo = :tirage WHERE id_solo = :id');
            $requette->bindParam(':tirage', $tirage_solo, PDO::PARAM_INT);
            $requette->bindParam(':id', $id_solo, PDO::PARAM_INT);
            $requette->execute();
        }

        public function setUpdateJeuSolo($libelle,$id_solo)
        {
            $requette = $this->connexionBd->prepare('UPDATE solo SET jeux_solo = :libelle WHERE id_solo = :id_solo');
            $requette->bindParam(':libelle', $libelle, PDO::PARAM_STR);
            $requette->bindParam(':id_solo', $id_solo, PDO::PARAM_INT);
            $requette->execute();
        }

        public function resetTousLesTirages()
        {
            $requette = $this->connexionBd->prepare('UPDATE solo SET tirage_solo = 0');
            $requette->execute();
        }

        // Récupère un id_solo aléatoire non encore répondu par l'utilisateur
        public function getRandomSoloId($id_utilisateur)
        {
            // Sélectionner une question non encore validée par cet utilisateur
            // (exclusion basée sur la table evaluations)
            $sql = 'SELECT s.id_solo
                    FROM solo s
                    WHERE s.id_solo NOT IN (
                        SELECT e.id_solo FROM evaluations e WHERE e.id_utilisateur = :u
                    )
                    ORDER BY RAND() LIMIT 1';
            $stmt = $this->connexionBd->prepare($sql);
            $stmt->bindParam(':u', $id_utilisateur, PDO::PARAM_INT);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : null;
        }

        // Variante: exclure aussi une liste (session) pour éviter les répétitions dans une même séance avant validation
        public function getRandomSoloIdExcluding($id_utilisateur, array $excludeIds)
        {
            $params = [':u' => (int)$id_utilisateur];
            $excludeSql = '';
            if (!empty($excludeIds)) {
                // filtrer sur des entiers
                $clean = array_values(array_filter(array_map('intval', $excludeIds), function($v){ return $v > 0; }));
                if (!empty($clean)) {
                    $placeholders = [];
                    foreach ($clean as $i => $val) {
                        $ph = ":ex$i";
                        $placeholders[] = $ph;
                        $params[$ph] = $val;
                    }
                    $excludeSql = ' AND s.id_solo NOT IN ('.implode(',', $placeholders).')';
                }
            }
            $sql = 'SELECT s.id_solo
                    FROM solo s
                    WHERE s.id_solo NOT IN (
                        SELECT e.id_solo FROM evaluations e WHERE e.id_utilisateur = :u
                    )'. $excludeSql .'
                    ORDER BY RAND() LIMIT 1';
            $stmt = $this->connexionBd->prepare($sql);
            foreach ($params as $k=>$v) {
                $stmt->bindValue($k, $v, is_int($v)? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : null;
        }

        // === Admin CRUD helpers ===
        public function getAll()
        {
            $stmt = $this->connexionBd->query('SELECT id_solo, question_solo FROM solo ORDER BY id_solo DESC');
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }

        public function getById($id_solo)
        {
            $stmt = $this->connexionBd->prepare('SELECT * FROM solo WHERE id_solo = :id');
            $stmt->bindParam(':id', $id_solo, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        }

        public function updateSoloFull($id_solo, $question, $ra, $rb, $rc, $rd, $br)
        {
            $sql = 'UPDATE solo SET question_solo = :q, reponse_a_solo = :ra, reponse_b_solo = :rb, reponse_c_solo = :rc, reponse_d_solo = :rd, bonne_reponse_solo = :br WHERE id_solo = :id';
            $stmt = $this->connexionBd->prepare($sql);
            $stmt->bindParam(':q', $question, PDO::PARAM_STR);
            $stmt->bindParam(':ra', $ra, PDO::PARAM_STR);
            $stmt->bindParam(':rb', $rb, PDO::PARAM_STR);
            $stmt->bindParam(':rc', $rc, PDO::PARAM_STR);
            $stmt->bindParam(':rd', $rd, PDO::PARAM_STR);
            $stmt->bindParam(':br', $br, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id_solo, PDO::PARAM_INT);
            return $stmt->execute();
        }

        public function deleteSolo($id_solo)
        {
            $stmt = $this->connexionBd->prepare('DELETE FROM solo WHERE id_solo = :id');
            $stmt->bindParam(':id', $id_solo, PDO::PARAM_INT);
            return $stmt->execute();
        }

        // Récupérer le nom de l'utilisateur depuis la base
        public function getNomUtilisateur($id_utilisateur)
        {
            $requete = $this->connexionBd->prepare('SELECT nom FROM utilisateurs WHERE id = :id');
            $requete->bindParam(':id', $id_utilisateur, PDO::PARAM_INT);
            $requete->execute();
            $utilisateur = $requete->fetch(PDO::FETCH_OBJ);
            return $utilisateur ? $utilisateur->nom : null;
        }

    }

    ?>