<?php

// *****************************************************************************
// * atom controller
// * - datenabfrage beim model
// * - datenweitergabe zum view
// *****************************************************************************

class Controller {

  // konstruktor, controller erstellen
  public function __construct() {
    $this->model = new Model();	// model erstellen
  }

  // datenabfrage beim model, datenweitergabe zum view
  public function display() {
    $view = new View();	// view erstellen
    $view->setTemplate("atom");	// template "tpl_atom.xml" laden

    $ret = $this->model->getFeed();

    $view->setContent("feed", $ret["feed"]);
    $view->setContent("error", $ret["error"]);

    return $view->parseTemplate();	// ausgabe geändertes template
  }

}

?>
