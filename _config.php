<?php
    /*** CONFIGURATION */
    ini_set('display_errors','on');
    error_reporting(E_ALL);

    // Build dynamic HOST and ROOT based on the actual folder served by XAMPP
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $scheme = $isHttps ? 'https://' : 'http://';

    // Directory of the running script (URL path)
    $scriptDir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $basePath = ($scriptDir === '' || $scriptDir === '/') ? '/' : $scriptDir.'/';

    // Absolute filesystem path to project root (this file's directory)
    $projectRoot = rtrim(str_replace('\\','/', realpath(__DIR__)), '/').'/';

    define('HOST', $scheme.$host.$basePath);
    define('ROOT', $projectRoot);

    define('MODEL_HOST', HOST.'model/');
    define('MODEL_ROOT', ROOT.'model/');

    define('VIEW_HOST', HOST.'view/');
    define('VIEW_ROOT', ROOT.'view/');

    define('CONTROLLER_HOST', HOST.'controller/');
    define('CONTROLLER_ROOT', ROOT.'controller/');

    define('ASSET_HOST', HOST.'asset/');
    define('ASSET_ROOT', ROOT.'asset/');

    define('ADMIN_HOST', HOST.'administration/');
    define('ADMIN_ROOT', ROOT.'administration/');
    
    // Optional: explicit constant for DB connection file root
    define('CONNEXION_BD_ROOT', ROOT);
    

?>
