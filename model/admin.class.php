<?php

class adminModel
{
    private $pdo;

    public function __construct($connexion)
    {
        if (!($connexion instanceof PDO)) {
            if (isset($GLOBALS['connexionBd']) && $GLOBALS['connexionBd'] instanceof PDO) {
                $connexion = $GLOBALS['connexionBd'];
            }
        }
        if (!($connexion instanceof PDO)) {
            throw new RuntimeException('PDO manquant dans adminModel::__construct');
        }
        $this->pdo = $connexion;
    }

    // Retourne quelques statistiques globales sur les évaluations
    public function getEvaluationStats()
    {
        $sql = 'SELECT COUNT(*) AS total, AVG(attempts) AS avg_attempts, MAX(attempts) AS max_attempts FROM evaluations';
        $row = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        return [
            'total' => (int)($row['total'] ?? 0),
            'avg_attempts' => (float)($row['avg_attempts'] ?? 0),
            'max_attempts' => (int)($row['max_attempts'] ?? 0),
        ];
    }

    // Liste paginée des évaluations avec filtres facultatifs
    public function listEvaluations($filters = [], $limit = 20, $offset = 0)
    {
        $where = [];
        $params = [];
        if (!empty($filters['id_utilisateur'])) {
            $where[] = 'e.id_utilisateur = :u';
            $params[':u'] = (int)$filters['id_utilisateur'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'e.created_at >= :d1';
            $params[':d1'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'e.created_at <= :d2';
            $params[':d2'] = $filters['date_to'] . ' 23:59:59';
        }
        $sql = 'SELECT e.created_at, e.id_solo, e.attempts, u.nom AS utilisateur, s.question_solo
                FROM evaluations e
                LEFT JOIN utilisateurs u ON u.id = e.id_utilisateur
                LEFT JOIN solo s ON s.id_solo = e.id_solo';
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY e.created_at DESC LIMIT :lim OFFSET :off';
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $st->bindValue(':off', (int)$offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countEvaluations($filters = [])
    {
        $where = [];
        $params = [];
        if (!empty($filters['id_utilisateur'])) {
            $where[] = 'e.id_utilisateur = :u';
            $params[':u'] = (int)$filters['id_utilisateur'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'e.created_at >= :d1';
            $params[':d1'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'e.created_at <= :d2';
            $params[':d2'] = $filters['date_to'] . ' 23:59:59';
        }
        $sql = 'SELECT COUNT(*) AS c FROM evaluations e';
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->execute();
        return (int)$st->fetchColumn();
    }

    // Résumé par utilisateur: dernière évaluation et nombre total de réponses (questions répondues)
    public function getUserSummary($filters = [])
    {
        $where = [];
        $params = [];
        if (!empty($filters['date_from'])) {
            $where[] = 'e.created_at >= :d1';
            $params[':d1'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'e.created_at <= :d2';
            $params[':d2'] = $filters['date_to'] . ' 23:59:59';
        }
        $sql = 'SELECT 
                    u.id AS id_utilisateur,
                    u.nom AS utilisateur,
                    MAX(e.created_at) AS last_date,
                    COUNT(e.id_solo) AS questions_repondues
                FROM evaluations e
                INNER JOIN utilisateurs u ON u.id = e.id_utilisateur';
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' GROUP BY u.id, u.nom ORDER BY last_date DESC';
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) { $st->bindValue($k, $v); }
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pour listes déroulantes d’utilisateurs
    public function listUtilisateurs()
    {
        $st = $this->pdo->query('SELECT id, nom FROM utilisateurs ORDER BY nom ASC');
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Export CSV brut (renvoie un tableau d'enregistrements)
    public function getExportData($filters = [])
    {
        $where = [];
        $params = [];
        if (!empty($filters['id_utilisateur'])) {
            $where[] = 'e.id_utilisateur = :u';
            $params[':u'] = (int)$filters['id_utilisateur'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'e.created_at >= :d1';
            $params[':d1'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'e.created_at <= :d2';
            $params[':d2'] = $filters['date_to'] . ' 23:59:59';
        }
        $sql = 'SELECT 
                    e.created_at,
                    e.id_utilisateur,
                    u.nom AS utilisateur,
                    e.id_solo,
                    s.question_solo,
                    e.attempts
                FROM evaluations e
                LEFT JOIN utilisateurs u ON u.id = e.id_utilisateur
                LEFT JOIN solo s ON s.id_solo = e.id_solo';
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY e.created_at DESC';
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Export détaillé des tentatives par utilisateur (pour export_user_session)
    public function getUserSessionExport($filters = [])
    {
        $where = [];
        $params = [];
        if (!empty($filters['id_utilisateur'])) {
            $where[] = 'ea.id_utilisateur = :u';
            $params[':u'] = (int)$filters['id_utilisateur'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'ea.created_at >= :d1';
            $params[':d1'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'ea.created_at <= :d2';
            $params[':d2'] = $filters['date_to'] . ' 23:59:59';
        }
        $sql = 'SELECT 
                    ea.created_at,
                    ea.session_id,
                    ea.id_utilisateur,
                    ea.id_solo,
                    s.question_solo,
                    ea.attempt_index,
                    ea.choice,
                    ea.is_correct
                FROM evaluation_attempts ea
                LEFT JOIN utilisateurs u ON u.id = ea.id_utilisateur
                LEFT JOIN solo s ON s.id_solo = ea.id_solo';
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY ea.created_at ASC';
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) { $st->bindValue($k, $v); }
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===================== Evaluation Sets (Series) =====================
    public function listEvaluationSets()
    {
        $sql = 'SELECT es.id, es.title, es.is_active, es.is_deployed, es.created_at,
                       (SELECT COUNT(*) FROM evaluation_set_questions eq WHERE eq.set_id = es.id) AS question_count
                FROM evaluation_sets es
                ORDER BY es.created_at DESC';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listQuestionsForSet(int $setId)
    {
        $sql = 'SELECT s.id_solo, s.question_solo, s.reponse_a_solo, s.reponse_b_solo, s.reponse_c_solo, s.reponse_d_solo, s.bonne_reponse_solo
                FROM evaluation_set_questions eq
                INNER JOIN solo s ON s.id_solo = eq.id_solo
                WHERE eq.set_id = :sid
                ORDER BY COALESCE(eq.position, 999999), s.id_solo ASC';
        $st = $this->pdo->prepare($sql);
        $st->bindValue(':sid', $setId, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeployedSet(): ?array
    {
        $row = $this->pdo->query('SELECT id, title FROM evaluation_sets WHERE is_active = 1 AND is_deployed = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function toggleDeploy(int $setId): bool
    {
        // Si déjà déployé => annuler; sinon => déployer uniquement celui-ci (s'il est actif et possède des questions)
        $this->pdo->beginTransaction();
        try {
            // Verrouiller la ligne du set ciblé et vérifier l'état
            $st = $this->pdo->prepare('SELECT id, is_active, is_deployed FROM evaluation_sets WHERE id = :id FOR UPDATE');
            $st->bindValue(':id', $setId, PDO::PARAM_INT);
            $st->execute();
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) { $this->pdo->rollBack(); return false; }

            $isActive = (int)($row['is_active'] ?? 0) === 1;
            $isDeployed = (int)($row['is_deployed'] ?? 0) === 1;

            if ($isDeployed) {
                // Annuler le déploiement
                $st2 = $this->pdo->prepare('UPDATE evaluation_sets SET is_deployed = 0 WHERE id = :id');
                $st2->bindValue(':id', $setId, PDO::PARAM_INT);
                $st2->execute();
                $this->pdo->commit();
                return true;
            }

            // Pour déployer: le set doit être actif et avoir au moins une question
            if (!$isActive) { $this->pdo->rollBack(); return false; }

            $countSt = $this->pdo->prepare('SELECT COUNT(*) FROM evaluation_set_questions WHERE set_id = :sid');
            $countSt->bindValue(':sid', $setId, PDO::PARAM_INT);
            $countSt->execute();
            $qCount = (int)$countSt->fetchColumn();
            if ($qCount <= 0) { $this->pdo->rollBack(); return false; }

            // Rendre unique: tout remettre à 0 puis activer celui-ci
            $this->pdo->exec('UPDATE evaluation_sets SET is_deployed = 0');
            $st3 = $this->pdo->prepare('UPDATE evaluation_sets SET is_deployed = 1 WHERE id = :id AND is_active = 1');
            $st3->bindValue(':id', $setId, PDO::PARAM_INT);
            $st3->execute();

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            return false;
        }
    }

    public function deleteEvaluationSet(int $setId): bool
    {
        // Refuser la suppression si le set est déployé
        $st = $this->pdo->prepare('SELECT is_deployed FROM evaluation_sets WHERE id = :id');
        $st->bindValue(':id', $setId, PDO::PARAM_INT);
        $st->execute();
        $isDeployed = (int)($st->fetchColumn() ?: 0) === 1;
        if ($isDeployed) { return false; }

        $this->pdo->beginTransaction();
        try {
            // Supprimer les associations de questions
            $st1 = $this->pdo->prepare('DELETE FROM evaluation_set_questions WHERE set_id = :sid');
            $st1->bindValue(':sid', $setId, PDO::PARAM_INT);
            $st1->execute();

            // Supprimer le set
            $st2 = $this->pdo->prepare('DELETE FROM evaluation_sets WHERE id = :sid');
            $st2->bindValue(':sid', $setId, PDO::PARAM_INT);
            $st2->execute();

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            return false;
        }
    }

    public function getEvaluationSet(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT id, title, is_active, is_deployed, created_at FROM evaluation_sets WHERE id = :id LIMIT 1');
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createEvaluationSet(string $title, int $isActive = 1): ?int
    {
        $st = $this->pdo->prepare('INSERT INTO evaluation_sets (title, is_active, is_deployed, created_at) VALUES (:t, :a, 0, NOW())');
        $st->bindValue(':t', $title, PDO::PARAM_STR);
        $st->bindValue(':a', $isActive, PDO::PARAM_INT);
        if ($st->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
        return null;
    }

    public function updateEvaluationSet(int $id, string $title, int $isActive = 1): bool
    {
        $st = $this->pdo->prepare('UPDATE evaluation_sets SET title = :t, is_active = :a WHERE id = :id');
        $st->bindValue(':t', $title, PDO::PARAM_STR);
        $st->bindValue(':a', $isActive, PDO::PARAM_INT);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        return $st->execute();
    }

    public function replaceSetQuestions(int $setId, array $questionIds): bool
    {
        $this->pdo->beginTransaction();
        try {
            $stDel = $this->pdo->prepare('DELETE FROM evaluation_set_questions WHERE set_id = :sid');
            $stDel->bindValue(':sid', $setId, PDO::PARAM_INT);
            $stDel->execute();

            if (!empty($questionIds)) {
                $stIns = $this->pdo->prepare('INSERT INTO evaluation_set_questions (set_id, id_solo, position) VALUES (:sid, :qid, :pos)');
                $pos = 1;
                foreach ($questionIds as $qid) {
                    $qid = (int)$qid;
                    if ($qid <= 0) continue;
                    $stIns->bindValue(':sid', $setId, PDO::PARAM_INT);
                    $stIns->bindValue(':qid', $qid, PDO::PARAM_INT);
                    $stIns->bindValue(':pos', $pos, PDO::PARAM_INT);
                    $stIns->execute();
                    $pos++;
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            return false;
        }
    }
}

?>
