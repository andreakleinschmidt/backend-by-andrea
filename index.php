<?php
header("Content-Type: text/html; charset=utf-8");
session_start();	// für suggest_tab

// *****************************************************************************
// * index.php
// * - klassen einbinden
// * - controller aufrufen
// *****************************************************************************

// (require wie include, aber abbruch/fatal error  bei nicht-einbinden)
require_once("classes/define.php");
require_once("classes/class.database.php");
require_once("classes/class.getcontroller.php");
require_once("classes/class.postcontroller.php");
require_once("classes/class.model.php");
require_once("classes/class.model_home.php");
require_once("classes/class.model_profil.php");
require_once("classes/class.model_fotos.php");
require_once("classes/class.model_blog.php");
require_once("classes/class.view.php");

// frontcontroller
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  // GET
  $controller = new GetController();	// controller erstellen
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
  // POST
  $controller = new PostController();	// controller erstellen
}
if (isset($controller)) {
  echo $controller->display();	// inhalt ausgeben
}

?>
