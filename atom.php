<?php
header("Content-Type: text/xml; charset=utf-8");

// *****************************************************************************
// * atom.php
// * - klassen einbinden
// * - controller aufrufen
// *****************************************************************************

// (require wie include, aber abbruch/fatal error  bei nicht-einbinden)
require_once("classes/class.database.php");
require_once("classes/class.atom_controller.php");
require_once("classes/class.atom_model.php");
require_once("classes/class.model_blog.php");
require_once("classes/class.atom_view.php");

$controller = new Controller();	// controller erstellen
echo $controller->display();	// inhalt ausgeben

?>
