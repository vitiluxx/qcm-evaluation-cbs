<?php
session_start();
include_once("_config.php");
include("routeur.class.php");

// Déterminer la route demandée le plus tôt possible
$requette = isset($_GET['r']) ? $_GET['r'] : '';
if (empty($requette)) {
    $requette = "accueil.php";
}

// Routes sans layout (API/exports): éviter d'inclure l'entête et le pied HTML
$noLayoutRoutes = [
    'export_evaluations', 'export_evaluations.php',
    'eval_log', 'eval_log.php'
];

$useLayout = !in_array($requette, $noLayoutRoutes, true);

if ($useLayout) {
    // Entête HTML
    require_once(VIEW_ROOT."entetePage.php");
}

// Dispatcher
$runderController = new Routeur($requette);
$runderController->runderController();

if ($useLayout) {
    // Pied de page HTML
    require_once(VIEW_ROOT."piedPage.php");
}

?>