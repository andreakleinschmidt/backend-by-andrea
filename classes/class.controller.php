<?php

// *****************************************************************************
// * controller
// * - GET/POST auslesen
// * - benutzereingabe (request)
// * - datenabfrage beim model
// * - datenweitergabe zum view
// *****************************************************************************

define("STATE_APPROVAL",2);
define("STATE_PUBLISHED",3);
define("MB_ENCODING","UTF-8");

class Controller {

  private $request = null;
  private $method;
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
  public function __construct($request, $method) {
    $this->request = $request;
    $this->method = !empty($method) ? $method : "GET";
    $this->action = !empty($request["action"]) ? substr(trim($request["action"]), 0, 8) : "home";	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->language = !empty($request["lang"]) ? substr(trim($request["lang"]), 0, 4) : "de";	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->gallery = !empty($request["gallery"]) ? substr(trim($request["gallery"]), 0, 16) : NULL;	// GET gallery auslesen, überflüssige leerzeichen entfernen, zu langen alias abschneiden
    $this->id = !empty($request["id"]) ? substr(trim($request["id"]), 0, 8) : NULL;	// GET id auslesen, überflüssige leerzeichen entfernen, zu lange fotoid abschneiden
    $this->q = !empty($request["q"]) ? mb_substr(trim($request["q"]), 0, 64, MB_ENCODING) : NULL;	// GET q auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->tag = !empty($request["tag"]) ? mb_substr(trim($request["tag"]), 0, 32, MB_ENCODING) : NULL;	// GET tag auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->page = !empty($request["page"]) ? (is_numeric($request["page"]) ? intval($request["page"]) : NULL) : NULL;	// GET page auslesen, string in int umwandeln
    $this->year = !empty($request["year"]) ? (is_numeric($request["year"]) ? intval($request["year"]) : NULL) : NULL;	// GET year auslesen, string in int umwandeln
    $this->month = !empty($request["month"]) ? (is_numeric($request["month"]) ? intval($request["month"]) : NULL) : NULL;	// GET month auslesen, string in int umwandeln
    $this->compage = !empty($request["compage"]) ? (is_numeric($request["compage"]) ? intval($request["compage"]) : NULL) : NULL;	// GET comment-page auslesen, string in int umwandeln
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

    $ret = null;	// ["inhalt","error"]

    // GET/POST
    if ($this->method != "POST") {
      // GET

      // action überprüfen
      if ($this->action != "home" AND $this->action != "profil" AND $this->action != "fotos" AND $this->action != "blog") {
        $this->action = "home";
      }

      // language überprüfen
      if ($this->language != "de" AND $this->language != "en") {
        $this->language = "de";
      }

      // switch anweisung, {hd_titel} und {inhalt} ersetzen je nach GET-action

      switch($this->action) {

        case "home": {
          $model_home = new Home();	// model erstellen
          $ret = $model_home->getHome();	// daten für home aus dem model
          //$view->setContent("hd_titel", " home");
          break;
        }

        case "profil": {
          $model_profil = new Profil();	// model erstellen
          $ret = $model_profil->getProfil($this->language);	// daten für profil aus dem model
          $view->setContent("hd_titel", " profile");
          break;
        }

        case "fotos": {
          $model_fotos = new Fotos();	// model erstellen
          $ret = $model_fotos->getFotos($this->gallery, $this->id);	// daten für fotos aus dem model
          $view->setContent("hd_titel", " photos".$ret["hd_titel"]);
          break;
        }

        case "blog": {
          $model_blog = new Blog();	// model erstellen
          $ret = $model_blog->getBlog($this->q, $this->tag, $this->page, $this->year, $this->month, $this->compage);	// daten für blog aus dem model
          $view->setContent("hd_titel", " blog".$ret["hd_titel"]);
          break;
        }

      } // switch

    } // GET

    else {
      // POST

      // POST überprüfen
      if (isset($this->request["comment"])) {
        // comment[name, mail, website, blogid, text]

        $model_blog = new Blog();	// model erstellen
        $comment_array = $this->request["comment"];
        $ret = $model_blog->postComment($comment_array);	// daten für comment in das model
        $view->setContent("hd_titel", " blog (comment)");

      }
      else {
        $errorstring = "<br>POST error\n";
        $ret["inhalt"] = "";
        $ret["error"] = $errorstring;
      }

      // redirect index.php?action=blog#comment
      header("refresh:10;index.php?action=blog#comment");

    } // POST

    $view->setContent("inhalt", $ret["inhalt"]);
    $view->setContent("error", $login["error"].$ret["error"]);

    return $view->parseTemplate();	// ausgabe geändertes template
  }

}

?>
