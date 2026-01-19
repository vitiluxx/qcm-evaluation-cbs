# \# Jeux Club Informatique (JCI)

# 

# Application web d'évaluation par QCM. Les utilisateurs saisissent leur nom, tirent des questions au hasard et y répondent. 

# Côté admin, un tableau de bord centralise la gestion des QCM et l'historique des évaluations.

# 

# \## Démarrage rapide

# \- Prérequis: XAMPP (Apache + MySQL), PHP 8+, MySQL.

# \- Config BD: éditer `connexionBd.php` (host, port, dbname, user, pass).

# \- Base de données: créer les tables nécessaires (requettes sql dans `recherches/sql\_bd\_text.txt`). Assurez-vous d'avoir:

# &nbsp; - Table `solo` (questions QCM)

# &nbsp; - Table `utilisateurs`

# &nbsp; - Table `admins` (login admin; stocke `username`, `password\_hash`)

# &nbsp; - Table `evaluations` (historique d'évaluation)

# &nbsp; - Optionnel: Table `evaluation\_attempts` (journal par tentative si activé)

# \- Démarrer Apache et MySQL (XAMPP), puis ouvrir: `http://localhost/jci/`

# 

# \## Navigation (URLs principales)

# \- Public (alias sans `.php`)

# &nbsp; - `http://localhost/jci/accueil` → page d’accueil

# &nbsp; - `http://localhost/jci/nom\_utilisateur` → saisir le nom du joueur

# &nbsp; - `http://localhost/jci/qcm?id\_utilisateur=<ID>` → tirage + animation

# &nbsp; - `http://localhost/jci/mainQcm?id=<ID\_QUESTION>\&id\_utilisateur=<ID>` → affichage du QCM

# &nbsp; - `http://localhost/jci/jeux` → page de lancement

# \- Administration (protégé)

# &nbsp; - `http://localhost/jci/connexion\_admin` → connexion admin

# &nbsp; - `http://localhost/jci/tableau\_de\_bord` → tableau de bord (centralise tout)

# &nbsp; - `http://localhost/jci/gestion\_qcm` → gestion des QCM (liste, suppression)

# &nbsp; - `http://localhost/jci/modifier\_qcm?id=<ID\_QUESTION>` → édition d’un QCM

# &nbsp; - `http://localhost/jci/export\_evaluations?...\[filtres]` → export CSV des évaluations

# &nbsp; - `http://localhost/jci/export\_user\_session?id\_utilisateur=<ID>\&...\[filtres]` → export CSV détaillé par utilisateur (si activé)

# &nbsp; - `http://localhost/jci/reinitialiser\_historique` (POST CSRF) → réinitialisation globale de l'historique

# 

# Remarque: l’application fonctionne principalement avec les alias (sans `.php`). Les routes techniques (par ex. `export\_evaluations`, `eval\_log`) sont servies sans entête/pied HTML (mode API) pour éviter du HTML dans les exports.

# 

# \## Fonctionnalités clés

# \- Côté Joueur

# &nbsp; - Saisie du nom → tirage → animation → QCM.

# &nbsp; - Bouton « Passer à la question suivante » activé uniquement après bonne réponse.

# &nbsp; - Anti-répétition: durant une séance, une question tirée n’est pas re-tirée immédiatement avant validation (exclusions en session).

# &nbsp; - Multi-utilisateurs: chaque joueur possède son propre historique; le tirage exclut les questions déjà RÉUSSIES par ce joueur (basé sur `evaluations`).

# &nbsp; - Fin de séance: si un joueur a répondu à toutes les questions, la plateforme l’informe et bloque de nouveaux tirages.

# \- Côté Admin (Tableau de bord)

# &nbsp; - Statistiques: total d’évaluations, moyenne et max de tentatives.

# &nbsp; - Résumé par utilisateur: dernière date et nombre de questions répondues; clic sur un nom → filtre l’historique détaillé.

# &nbsp; - Historique filtrable par utilisateur et plage de dates, pagination.

# &nbsp; - Export CSV des évaluations selon les filtres (sans BOM UTF‑8 pour éviter `ï»¿Date`).

# &nbsp; - Reset global de l’historique (action POST CSRF) supprimant `evaluations` (et `evaluation\_attempts` si présent).

# &nbsp; - Gestion des QCM (liste, modification, suppression, création via `publier\_qcm`).

# 

# \## Authentification admin

# \- Table `admins` requise. Pour créer un mot de passe:

# &nbsp; - Exemple de hash dans `recherches/sql\_bd\_text.txt` (section hash/password) ou via `recherches/hashpwdadmin.php`.

# &nbsp; - Insérer ensuite `username` et `password\_hash` en base.

# 

# \## Journalisation des évaluations

# \- Lorsqu’un joueur répond correctement, l’app enregistre: `id\_utilisateur`, `id\_solo`, `attempts` (nb de tentatives avant réussite), `created\_at`.

# \- Optionnel (si activé): journal par tentative dans `evaluation\_attempts` via l’endpoint `attempt\_log`.

# \- Visible et exportable depuis `tableau\_de\_bord`.

# 

# \## Sécurité

# \- CSRF activé sur les actions sensibles (login, suppression, reset historique, logs).

# \- Accès direct bloqué sur `administration/` et `view/` via `.htaccess`.

# \- Routes alias via `routeur.class.php`.

# 

# \## Dépannage

# \- Erreur MySQL (HY000/2002): vérifier que MySQL est démarré (port par défaut 3306) et les identifiants de `connexionBd.php`.

# \- Accès admin: créer au moins un compte dans `admins` et se connecter via `connexion\_admin`.

# \- CSV avec `ï»¿Date`: le BOM UTF‑8 a été retiré; si besoin Excel, choisir l’import UTF‑8.

# 

# ---

# Pour les évolutions (export PDF, reset par utilisateur, visualisation tentatives, etc.), ouvrir une issue ou contacter l’auteur.

# 

# ---

# 

# \# Architecture et organisation du projet

# 

# \- \*\*Entrée\*\*: `index.php` + réécritures via `.htaccess` pour servir des alias sans `.php`.

# \- \*\*Routeur\*\*: `routeur.class.php` mappe les chemins vers un contrôleur (`allMethod`) et une méthode.

# \- \*\*Contrôleur\*\*: `controller/allMethod.class.php` contient la logique (pages publiques, endpoints API, admin).

# \- \*\*Modèles\*\*: `model/\*.class.php` (`solo`, `formulaire`, `admin`). Accès PDO injecté depuis `connexionBd.php`.

# \- \*\*Vues\*\*: `view/\*.php` (parties publiques) et `administration/\*.php` (interfaces admin).

# \- \*\*Config/BD\*\*: `connexionBd.php` (PDO), `\_config.php` (constantes), `recherches/sql\_bd\_text.txt` (DDL SQL).

# 

# Arborescence simplifiée:

# \- `index.php`, `.htaccess`, `\_config.php`, `connexionBd.php`

# \- `routeur.class.php`

# \- `controller/allMethod.class.php`

# \- `model/` (admin.class.php, solo.class.php, ...)

# \- `view/` (accueil.php, nom\_utilisateur.php, solo.php, mainSolo.php, ...)

# \- `administration/` (dashboard.php, admin\_login.php, admin\_solo\*.php, ...)

# \- `recherches/` (sql\_bd\_text.txt, hashpwdadmin.php)

# 

# \# Base de données (référence)

# 

# Voir `recherches/sql\_bd\_text.txt` pour les requêtes exactes. Tables clés:

# \- `solo` (banque de questions QCM)

# \- `utilisateurs` (joueurs)

# \- `admins` (comptes admin, `password\_hash` généré via `password\_hash`)

# \- `evaluations` (succès par question, avec `attempts >= 0`)

# \- `evaluation\_attempts` (optionnel, journal de chaque clic: `attempt\_index`, `choice`, `is\_correct`)

# 

# Indexes recommandés: `(id\_utilisateur)`, `(id\_solo)`, `(created\_at)` sur `evaluations` et `evaluation\_attempts`.

# 

# \# Sécurité et sessions

# 

# \- \*\*CSRF\*\* sur actions sensibles: login admin, suppression, reset historique, endpoints `eval\_log` et `attempt\_log`.

# \- \*\*Admin\*\* protégé par `$\_SESSION\['admin\_id']` (voir `requireAdmin()`).

# \- \*\*Sessions joueur\*\*: `$\_SESSION\['eval\_session\_id']` + exclusions de tirage par utilisateur pour éviter les re‑tirages immédiats.

# 

# \# Déploiement (guide)

# 

# Pré‑requis:

# \- PHP 8.0+ (pdo\_mysql activé), MySQL 5.7+/MariaDB, Apache (mod\_rewrite) ou Nginx.

# 

# Étapes (Apache/Shared hosting):

# 1\) Cloner/copier le dossier du projet dans le DocumentRoot (ex: `htdocs/jci` ou `public\_html/jci`).

# 2\) Configurer la BD dans `connexionBd.php` (host, port, dbname, user, pass).

# 3\) Créer les tables en exécutant le contenu de `recherches/sql\_bd\_text.txt` (phpMyAdmin/CLI).

# 4\) Vérifier `.htaccess` (mod\_rewrite ON). Si besoin, adapter `RewriteBase /jci/`.

# 5\) Créer un compte admin dans `admins` (générer `password\_hash` via `recherches/hashpwdadmin.php`).

# 6\) Ouvrir `http://<domaine>/jci/` et tester navigation publique; puis `http://<domaine>/jci/connexion\_admin`.

# 

# Étapes (Nginx):

# \- Configurer un `try\_files` pour rediriger vers `index.php` (équivalent du rewrite Apache). Exemple: `try\_files $uri /index.php?$args;`

# \- Assurer `fastcgi` vers PHP‑FPM et droits d’accès au dossier.

# 

# Production (bonnes pratiques):

# \- Désactiver l’affichage des erreurs PHP, activer logs.

# \- Restreindre l’accès direct aux dossiers `administration/` et `view/` (déjà géré par `.htaccess` côté Apache).

# \- Sauvegardes régulières de la BD; suivre `sql\_bd\_text.txt` pour les évolutions de schéma.

# 

# \# Guide de prise en main du micro‑framework (maison)

# 

# Principe: un routeur central mappe un alias d’URL à une méthode du contrôleur unique `allMethod`.

# 

# 1\) Ajouter une route

# \- Fichier: `routeur.class.php`

# \- Ajouter dans `$route` une entrée:

# &nbsp; - Exemple: `"mon\_nouvel\_ecran" => \["controller" => "allMethod", "method" => "affichePageMonNouvelEcran"],`

# \- L’alias sera accessible via `http://localhost/jci/mon\_nouvel\_ecran`.

# 

# 2\) Créer la méthode contrôleur

# \- Fichier: `controller/allMethod.class.php`

# \- Ajouter:

# &nbsp; - `public function affichePageMonNouvelEcran() { $this->ensureDb(); include(VIEW\_ROOT.'mon\_nouvel\_ecran.php'); }`

# \- Pour une page admin: protéger par `$this->requireAdmin();` avant `ensureDb()`.

# \- Pour un endpoint (API/CSV): définir les entêtes (ex: `Content-Type: application/json` ou CSV) et `exit;` en fin.

# 

# 3\) Créer la vue

# \- Fichier: `view/mon\_nouvel\_ecran.php` (ou `administration/mon\_nouvel\_ecran.php` pour l’admin).

# \- Réutiliser les includes d’en‑tête/pied si nécessaire.

# 

# 4\) Accès aux données

# \- Via modèles dans `model/` (ex: créer `monmodele.class.php`), injecter `$pdo` depuis `$GLOBALS\['connexionBd']` après `ensureDb()`.

# \- Écrire des méthodes de lecture/écriture sécurisées (requêtes préparées, validations).

# 

# 5\) Bonnes pratiques

# \- Toujours générer/valider CSRF pour les POST sensibles.

# \- Ne pas exposer d’HTML dans les endpoints d’export ou APIs (utiliser `exit;`).

# \- Journaliser côté admin ce qui est utile (ex: exports, reset historique).

# 

# \# Scénarios de développement courants

# 

# \- Ajouter une nouvelle page publique:

# &nbsp; - Route → méthode `allMethod` → vue `view/\*.php` → (optionnel) modèle `model/\*.php`.

# \- Ajouter une page d’administration:

# &nbsp; - Route protégée → `requireAdmin()` → vue dans `administration/`.

# \- Ajouter un endpoint CSV/JSON:

# &nbsp; - Route → méthode contrôleur → entêtes adaptés → itérer sur les données du modèle.

# \- Étendre le dashboard:

# &nbsp; - Ajouter méthodes dans `adminModel` (filtres, pagination) → consommer depuis `administration/dashboard.php`.

# 

# \# Tests rapides (check‑list)

# 

# \- Public: `nom\_utilisateur` → `qcm` → `mainQcm` → validation et passage à la question suivante.

# \- Anti double‑clic: bouton TIRER et choix QCM correctement désactivés au bon moment.

# \- Admin: connexion, dashboard, filtres, pagination, export CSV global et détaillé.

# \- BD: enregistrements dans `evaluations` (y compris `attempts = 0`) et (si activé) `evaluation\_attempts`.

# 

# \# FAQ courte

# 

# \- Problème d’URL/alias qui ne passent pas: vérifier `.htaccess` (Apache) ou `try\_files` (Nginx), et les routes dans `routeur.class.php`.

# \- CSV encodage: fichiers sans BOM; importer en UTF‑8 dans Excel/LibreOffice.

# \- Erreurs MySQL: confirmer `connexionBd.php` et que MySQL est démarré.

# 



