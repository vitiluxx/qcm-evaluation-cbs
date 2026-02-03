# Jeux Club Informatique (JCI)

Application web d'évaluation par QCM. Les utilisateurs saisissent leur nom, tirent des questions au hasard et y répondent. 
Côté admin, un tableau de bord centralise la gestion des QCM et l'historique des évaluations.

## Démarrage rapide
- Prérequis: XAMPP (Apache + MySQL), PHP 8+, MySQL.
- Config BD: éditer `connexionBd.php` (host, port, dbname, user, pass).
- Base de données: créer les tables nécessaires (requettes sql dans `recherches/sql_bd_text.txt`). 
- Démarrer Apache et MySQL (XAMPP), puis ouvrir: `http://localhost/jci/`

## Navigation (URLs principales)
- Public (alias sans `.php`)
  - `http://localhost/jci/accueil` → page d’accueil
  - `http://localhost/jci/nom_utilisateur` → saisir le nom du joueur
  - `http://localhost/jci/qcm?id_utilisateur=<ID>` → tirage + animation
  - `http://localhost/jci/mainQcm?id=<ID_QUESTION>&id_utilisateur=<ID>` → affichage du QCM
  - `http://localhost/jci/jeux` → page de lancement
- Administration (protégé)
  - `http://localhost/jci/connexion_admin` → connexion admin
  - `http://localhost/jci/tableau_de_bord` → tableau de bord (centralise tout)
  - `http://localhost/jci/gestion_qcm` → gestion des QCM (liste, suppression)
  - `http://localhost/jci/modifier_qcm?id=<ID_QUESTION>` → édition d’un QCM
  - `http://localhost/jci/export_evaluations?...[filtres]` → export CSV des évaluations
  - `http://localhost/jci/export_user_session?id_utilisateur=<ID>&...[filtres]` → export CSV détaillé par utilisateur (si activé)
  - `http://localhost/jci/reinitialiser_historique` (POST CSRF) → réinitialisation globale de l'historique

Remarque: l’application fonctionne principalement avec les alias (sans `.php`). Les routes techniques (par ex. `export_evaluations`, `eval_log`) sont servies sans entête/pied HTML (mode API) pour éviter du HTML dans les exports.

## Fonctionnalités clés
- Côté Joueur
  - Saisie du nom → tirage → animation → QCM.
  - Bouton « Passer à la question suivante » activé uniquement après bonne réponse.
  - Anti-répétition: durant une séance, une question tirée n’est pas re-tirée immédiatement avant validation (exclusions en session).
  - Multi-utilisateurs: chaque joueur possède son propre historique; le tirage exclut les questions déjà RÉUSSIES par ce joueur (basé sur `evaluations`).
  - Fin de séance: si un joueur a répondu à toutes les questions, la plateforme l’informe et bloque de nouveaux tirages.
- Côté Admin (Tableau de bord)
  - Statistiques: total d’évaluations, moyenne et max de tentatives.
  - Résumé par utilisateur: dernière date et nombre de questions répondues; clic sur un nom → filtre l’historique détaillé.
  - Historique filtrable par utilisateur et plage de dates, pagination.
  - Export CSV des évaluations selon les filtres (sans BOM UTF‑8 pour éviter `ï»¿Date`).
  - Reset global de l’historique (action POST CSRF) supprimant `evaluations` (et `evaluation_attempts` si présent).
  - Gestion des QCM (liste, modification, suppression, création via `publier_qcm`).

## Authentification admin
- Table `admins` requise. Pour créer un mot de passe:
  - Exemple de hash dans `recherches/sql_bd_text.txt` (section hash/password) ou via `recherches/hashpwdadmin.php`.
  - Insérer ensuite `username` et `password_hash` en base.

## Journalisation des évaluations
- Lorsqu’un joueur répond correctement, l’app enregistre: `id_utilisateur`, `id_solo`, `attempts` (nb de tentatives avant réussite), `created_at`.
- Optionnel (si activé): journal par tentative dans `evaluation_attempts` via l’endpoint `attempt_log`.
- Visible et exportable depuis `tableau_de_bord`.

## Sécurité
- CSRF activé sur les actions sensibles (login, suppression, reset historique, logs).
- Accès direct bloqué sur `administration/` et `view/` via `.htaccess`.
- Routes alias via `routeur.class.php`.

## Dépannage
- Erreur MySQL (HY000/2002): vérifier que MySQL est démarré (port par défaut 3306) et les identifiants de `connexionBd.php`.
- Accès admin: créer au moins un compte dans `admins` et se connecter via `connexion_admin`.
- CSV avec `ï»¿Date`: le BOM UTF‑8 a été retiré; si besoin Excel, choisir l’import UTF‑8.

---
Pour les évolutions (export PDF, reset par utilisateur, visualisation tentatives, etc.), ouvrir une issue ou contacter l’auteur.

---

# Architecture et organisation du projet

- **Entrée**: `index.php` + réécritures via `.htaccess` pour servir des alias sans `.php`.
- **Routeur**: `routeur.class.php` mappe les chemins vers un contrôleur (`allMethod`) et une méthode.
- **Contrôleur**: `controller/allMethod.class.php` contient la logique (pages publiques, endpoints API, admin).
- **Modèles**: `model/*.class.php` (`solo`, `formulaire`, `admin`). Accès PDO injecté depuis `connexionBd.php`.
- **Vues**: `view/*.php` (parties publiques) et `administration/*.php` (interfaces admin).
- **Config/BD**: `connexionBd.php` (PDO), `_config.php` (constantes), `recherches/sql_bd_text.txt` (DDL SQL).

Arborescence simplifiée:
- `index.php`, `.htaccess`, `_config.php`, `connexionBd.php`
- `routeur.class.php`
- `controller/allMethod.class.php`
- `model/` (admin.class.php, solo.class.php, ...)
- `view/` (accueil.php, nom_utilisateur.php, solo.php, mainSolo.php, ...)
- `administration/` (dashboard.php, admin_login.php, admin_solo*.php, ...)
- `recherches/` (sql_bd_text.txt, hashpwdadmin.php)

# Base de données (référence)

Voir `recherches/sql_bd_text.txt` pour les requêtes exactes. Tables clés:
- `solo` (banque de questions QCM)
- `utilisateurs` (joueurs)
- `admins` (comptes admin, `password_hash` généré via `password_hash`)
- `evaluations` (succès par question, avec `attempts >= 0`)
- `evaluation_attempts` (optionnel, journal de chaque clic: `attempt_index`, `choice`, `is_correct`)

Indexes recommandés: `(id_utilisateur)`, `(id_solo)`, `(created_at)` sur `evaluations` et `evaluation_attempts`.

# Sécurité et sessions

- **CSRF** sur actions sensibles: login admin, suppression, reset historique, endpoints `eval_log` et `attempt_log`.
- **Admin** protégé par `$_SESSION['admin_id']` (voir `requireAdmin()`).
- **Sessions joueur**: `$_SESSION['eval_session_id']` + exclusions de tirage par utilisateur pour éviter les re‑tirages immédiats.

# Déploiement (guide)

Pré‑requis:
- PHP 8.0+ (pdo_mysql activé), MySQL 5.7+/MariaDB, Apache (mod_rewrite) ou Nginx.

Étapes (Apache/Shared hosting):
1) Cloner/copier le dossier du projet dans le DocumentRoot (ex: `htdocs/jci` ou `public_html/jci`).
2) Configurer la BD dans `connexionBd.php` (host, port, dbname, user, pass).
3) Créer les tables en exécutant le contenu de `recherches/sql_bd_text.txt` (phpMyAdmin/CLI).
4) Vérifier `.htaccess` (mod_rewrite ON). Si besoin, adapter `RewriteBase /jci/`.
5) Créer un compte admin dans `admins` (générer `password_hash` via `recherches/hashpwdadmin.php`).
6) Ouvrir `http://<domaine>/jci/` et tester navigation publique; puis `http://<domaine>/jci/connexion_admin`.

Étapes (Nginx):
- Configurer un `try_files` pour rediriger vers `index.php` (équivalent du rewrite Apache). Exemple: `try_files $uri /index.php?$args;`
- Assurer `fastcgi` vers PHP‑FPM et droits d’accès au dossier.

Production (bonnes pratiques):
- Désactiver l’affichage des erreurs PHP, activer logs.
- Restreindre l’accès direct aux dossiers `administration/` et `view/` (déjà géré par `.htaccess` côté Apache).
- Sauvegardes régulières de la BD; suivre `sql_bd_text.txt` pour les évolutions de schéma.

# Guide de prise en main du micro‑framework (maison)

Principe: un routeur central mappe un alias d’URL à une méthode du contrôleur unique `allMethod`.

1) Ajouter une route
- Fichier: `routeur.class.php`
- Ajouter dans `$route` une entrée:
  - Exemple: `"mon_nouvel_ecran" => ["controller" => "allMethod", "method" => "affichePageMonNouvelEcran"],`
- L’alias sera accessible via `http://localhost/jci/mon_nouvel_ecran`.

2) Créer la méthode contrôleur
- Fichier: `controller/allMethod.class.php`
- Ajouter:
  - `public function affichePageMonNouvelEcran() { $this->ensureDb(); include(VIEW_ROOT.'mon_nouvel_ecran.php'); }`
- Pour une page admin: protéger par `$this->requireAdmin();` avant `ensureDb()`.
- Pour un endpoint (API/CSV): définir les entêtes (ex: `Content-Type: application/json` ou CSV) et `exit;` en fin.

3) Créer la vue
- Fichier: `view/mon_nouvel_ecran.php` (ou `administration/mon_nouvel_ecran.php` pour l’admin).
- Réutiliser les includes d’en‑tête/pied si nécessaire.

4) Accès aux données
- Via modèles dans `model/` (ex: créer `monmodele.class.php`), injecter `$pdo` depuis `$GLOBALS['connexionBd']` après `ensureDb()`.
- Écrire des méthodes de lecture/écriture sécurisées (requêtes préparées, validations).

5) Bonnes pratiques
- Toujours générer/valider CSRF pour les POST sensibles.
- Ne pas exposer d’HTML dans les endpoints d’export ou APIs (utiliser `exit;`).
- Journaliser côté admin ce qui est utile (ex: exports, reset historique).

# Scénarios de développement courants

- Ajouter une nouvelle page publique:
  - Route → méthode `allMethod` → vue `view/*.php` → (optionnel) modèle `model/*.php`.
- Ajouter une page d’administration:
  - Route protégée → `requireAdmin()` → vue dans `administration/`.
- Ajouter un endpoint CSV/JSON:
  - Route → méthode contrôleur → entêtes adaptés → itérer sur les données du modèle.
- Étendre le dashboard:
  - Ajouter méthodes dans `adminModel` (filtres, pagination) → consommer depuis `administration/dashboard.php`.

# Tests rapides (check‑list)

- Public: `nom_utilisateur` → `qcm` → `mainQcm` → validation et passage à la question suivante.
- Anti double‑clic: bouton TIRER et choix QCM correctement désactivés au bon moment.
- Admin: connexion, dashboard, filtres, pagination, export CSV global et détaillé.
- BD: enregistrements dans `evaluations` (y compris `attempts = 0`) et (si activé) `evaluation_attempts`.

# FAQ courte

- Problème d’URL/alias qui ne passent pas: vérifier `.htaccess` (Apache) ou `try_files` (Nginx), et les routes dans `routeur.class.php`.
- CSV encodage: fichiers sans BOM; importer en UTF‑8 dans Excel/LibreOffice.
- Erreurs MySQL: confirmer `connexionBd.php` et que MySQL est démarré.

