<?php
header("Content-Type: text/html; charset=utf-8");
session_start();

// *****************************************************************************
// * backend(index).php
// * - klassen einbinden
// * - controller aufrufen
// *****************************************************************************

// (require wie include, aber abbruch/fatal error  bei nicht-einbinden)
require_once("version.php");
require_once("classes/define_backend.php");
require_once("classes/class.database.php");
require_once("classes/class.ba_session.php");
require_once("classes/class.ba_getcontroller.php");
require_once("classes/class.ba_postcontroller.php");
require_once("classes/class.ba_model.php");	// login, password, logout
require_once("classes/class.ba_model_home.php");
require_once("classes/class.ba_model_profile.php");
require_once("classes/class.ba_model_photos.php");
require_once("classes/class.ba_model_blog.php");
require_once("classes/class.ba_model_comment.php");
require_once("classes/class.ba_model_upload.php");
require_once("classes/class.ba_model_languages.php");
require_once("classes/class.ba_model_admin.php");
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
