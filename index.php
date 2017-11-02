<?php
header("Content-Type: text/html; charset=utf-8");
session_start();	// für suggest_tab

// *****************************************************************************
// * index.php
// * - klassen einbinden
// * - controller aufrufen
// *****************************************************************************

// (require wie include, aber abbruch/fatal error  bei nicht-einbinden)
require_once("classes/class.controller.php");
require_once("classes/class.database.php");
require_once("classes/class.model.php");
require_once("classes/class.model_home.php");
require_once("classes/class.model_profil.php");
require_once("classes/class.model_fotos.php");
require_once("classes/class.model_blog.php");
require_once("classes/class.view.php");

$request = array_merge($_GET, $_POST);	// $_GET und $_POST zusammenfassen
$method = $_SERVER["REQUEST_METHOD"];	// GET oder POST

$controller = new Controller($request, $method);	// controller erstellen
echo $controller->display();	// inhalt ausgeben

?>
