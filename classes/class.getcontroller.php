<?php

// *****************************************************************************
// * getcontroller
// * - GET auslesen
// * - benutzereingabe (request)
// * - datenabfrage beim model
// * - datenweitergabe zum view
// *****************************************************************************

class GetController {

  private $action;
  private $language;
  private $gallery;
  private $id;
  private $q;
  private $tag;
  private $page;
  private $year;
  private $month;
  private $compage;

  // konstruktor, controller erstellen
  public function __construct() {
    $this->action = !empty($_GET["action"]) ? substr(trim($_GET["action"]), 0, 8) : "home";	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->language = !empty($_GET["lang"]) ? substr(trim($_GET["lang"]), 0, 4) : "de";	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->gallery = !empty($_GET["gallery"]) ? substr(trim($_GET["gallery"]), 0, 16) : NULL;	// GET gallery auslesen, überflüssige leerzeichen entfernen, zu langen alias abschneiden
    $this->id = !empty($_GET["id"]) ? substr(trim($_GET["id"]), 0, 8) : NULL;	// GET id auslesen, überflüssige leerzeichen entfernen, zu lange photoid abschneiden
    $this->q = !empty($_GET["q"]) ? mb_substr(trim($_GET["q"]), 0, 64, MB_ENCODING) : NULL;	// GET q auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->tag = !empty($_GET["tag"]) ? mb_substr(trim($_GET["tag"]), 0, 32, MB_ENCODING) : NULL;	// GET tag auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->page = !empty($_GET["page"]) ? (is_numeric($_GET["page"]) ? intval($_GET["page"]) : NULL) : NULL;	// GET page auslesen, string in int umwandeln
    $this->year = !empty($_GET["year"]) ? (is_numeric($_GET["year"]) ? intval($_GET["year"]) : NULL) : NULL;	// GET year auslesen, string in int umwandeln
    $this->month = !empty($_GET["month"]) ? (is_numeric($_GET["month"]) ? intval($_GET["month"]) : NULL) : NULL;	// GET month auslesen, string in int umwandeln
    $this->compage = !empty($_GET["compage"]) ? (is_numeric($_GET["compage"]) ? intval($_GET["compage"]) : NULL) : NULL;	// GET comment-page auslesen, string in int umwandeln
    $this->model = new Model();	// model erstellen
  }

  // zustandsmaschine, aufruf der funktionen, datenabfrage beim model, datenweitergabe zum view
  public function display() {
    $view = new View();	// view erstellen
    $view->setTemplate("default");	// template "tpl_default.htm" laden

    if ($this->action == "blog") {
      if (!isset($_SESSION["blogroll"])) {
        // falls session variable noch nicht existiert
        $ret_array = $this->model->blogroll();	// login erweitern mit blogroll
        $_SESSION["blogroll"] = $ret_array;	// in SESSION speichern
      } // neue session variable
      else {
        // alte session variable
        $ret_array = $_SESSION["blogroll"];	// aus SESSION lesen
      }
    }
    else {
      if (!isset($_SESSION["blogbox"])) {
        // falls session variable noch nicht existiert
        $ret_array = $this->model->blogbox();	// login erweitern mit blogbox
        $_SESSION["blogbox"] = $ret_array;	// in SESSION speichern
      } // neue session variable
      else {
        // alte session variable
        $ret_array = $_SESSION["blogbox"];	// aus SESSION lesen
      }
    }

    $login = $this->model->getLogin($ret_array);	// daten für login aus dem model, mit login erweiterung als parameter
    $view->setContent("login", $login["login"]);

    $ret = null;	// ["content","error"]

    // action überprüfen
    $actions = array("home", "profile", "photos", "blog");
    if (!in_array($this->action, $actions)) {
      $this->action = "home";
    }

    // language überprüfen
    if ($this->language != "de" and $this->language != "en") {
      $this->language = "de";
    }

    // switch anweisung, {hd_title_ext} und {content} ersetzen je nach GET-action

    switch($this->action) {

      case "home": {
        $model_home = new Home();	// model erstellen
        $ret = $model_home->getHome();	// daten für home aus dem model
        //$view->setContent("hd_title_ext", " home");
        break;
      }

      case "profile": {
        $model_profile = new Profile();	// model erstellen
        $ret = $model_profile->getProfile($this->language);	// daten für profile aus dem model
        $view->setContent("hd_title_ext", " profile");
        break;
      }

      case "photos": {
        $model_photos = new Photos();	// model erstellen
        $ret = $model_photos->getPhotos($this->gallery, $this->id);	// daten für photos aus dem model
        $view->setContent("hd_title_ext", " photos".$ret["hd_title_ext"]);
        break;
      }

      case "blog": {
        $model_blog = new Blog();	// model erstellen
        $ret = $model_blog->getBlog($this->q, $this->tag, $this->page, $this->year, $this->month, $this->compage);	// daten für blog aus dem model
        $view->setContent("hd_title_ext", " blog".$ret["hd_title_ext"]);
        break;
      }

    } // switch

    $view->setContent("content", $ret["content"]);
    $view->setContent("error", $login["error"].$ret["error"]);

    return $view->parseTemplate();	// ausgabe geändertes template
  }

}

?>
