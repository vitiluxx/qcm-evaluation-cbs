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
}

?>
