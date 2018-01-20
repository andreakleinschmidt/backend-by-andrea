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
  private $galleryid;
  private $id;
  private $page;

  // konstruktor, controller erstellen
  public function __construct() {
    $this->action = !empty($_GET["action"]) ? substr(trim($_GET["action"]), 0, 8) : "default";	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->galleryid = !empty($_GET["gallery"]) ? (is_numeric($_GET["gallery"]) ? intval($_GET["gallery"]) : NULL) : NULL;	// GET gallery auslesen, string in int umwandeln
    $this->id = !empty($_GET["id"]) ? (is_numeric($_GET["id"]) ? intval($_GET["id"]) : NULL) : NULL;	// GET id auslesen, string in int umwandeln
    $this->page = !empty($_GET["page"]) ? (is_numeric($_GET["page"]) ? intval($_GET["page"]) : NULL) : NULL;	// GET page auslesen, string in int umwandeln
    $this->session = new Session();	// session funktionen
    $this->model = new Model();	// model erstellen
  }

// *****************************************************************************
// *** funktionen ***
// *****************************************************************************

  // zustandsmaschine, aufruf der funktionen, datenabfrage beim model, datenweitergabe zum view
  public function display() {
    $view = new View();	// view erstellen
    $view->setTemplate("backend");	// template "tpl_backend.htm" laden

    $debug_str = "";
    if (DEBUG) { $debug_str .= DEBUG_STR; }

// *****************************************************************************
// *** session ***
// *****************************************************************************

    // panic mode
    if (isset($_SESSION["panic"])) {
      $html_backend_ext = "";
      $errorstring = "";
      if (DEBUG) { $debug_str .= "<br>001 panic\n"; }
    }
    else {
      // no panic , login?

      $debug_str .= $this->session->check_login($login);

      if (!$login) {

// *****************************************************************************
// *** login ***
// *****************************************************************************

        // login form
        $html_backend_ext = $this->model->html_form();

      } // nicht login

      else {
        // login

        unset($_SESSION["auth"]);
        session_regenerate_id();	// immer neue session id

        $ret = $this->session->set_login();	// server session setzen/erweitern (mit uid cookie)
        if (DEBUG) { $debug_str .= $ret; }

// *****************************************************************************
// *** backend GET ***
// *****************************************************************************

        $html_backend_ext = $this->model->html_backend($_SESSION["user_role"], $_SESSION["user_name"]);

        $ret = null;	// ["content","error"]

        // action überprüfen

        $actions = array("home", "profile", "photos", "blog", "comment", "upload", "lang", "admin", "password", "logout");
        if (!in_array($this->action, $actions)) {
          $this->action = "default";
        }

        // switch anweisung, je nach GET-action

        switch($this->action) {

// *****************************************************************************
// *** backend GET home ***
// *****************************************************************************

          case "home": {

            if ($_SESSION["user_role"] >= ROLE_EDITOR) {

              $model_home = new Home();	// model erstellen
              $ret = $model_home->getHome();	// daten für home aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_EDITOR

              break;
          }

// *****************************************************************************
// *** backend GET profile ***
// *****************************************************************************

          case "profile": {

            if ($_SESSION["user_role"] >= ROLE_EDITOR) {

              $model_profile = new Profile();	// model erstellen
              $ret = $model_profile->getProfile();	// daten für profile aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_EDITOR

            break;
          }

// *****************************************************************************
// *** backend GET photos ***
// *****************************************************************************

          case "photos": {

            if ($_SESSION["user_role"] >= ROLE_EDITOR) {

              $model_photos = new Photos();	// model erstellen
              $ret = $model_photos->getPhotos($this->galleryid);	// daten für photos aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_EDITOR

            break;
          }

// *****************************************************************************
// *** backend GET blog ***
// *****************************************************************************

          case "blog": {

            if ($_SESSION["user_role"] >= ROLE_EDITOR) {

              $model_blog = new Blog();	// model erstellen
              $ret = $model_blog->getBlog($this->id, $this->page);	// daten für blog aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_EDITOR

            break;
          }

// *****************************************************************************
// *** backend GET comment ***
// *****************************************************************************

          case "comment": {

            if ($_SESSION["user_role"] >= ROLE_MASTER) {

              $model_comment = new Comment();	// model erstellen
              $ret = $model_comment->getComment($this->id, $this->page);	// daten für comment aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_MASTER

            break;
          }

// *****************************************************************************
// *** backend GET upload ***
// *****************************************************************************

          case "upload": {

            if ($_SESSION["user_role"] >= ROLE_MASTER) {

              $model_upload = new Upload();	// model erstellen
              $ret = $model_upload->getUpload();	// daten für upload aus dem model
              $html_backend_ext .= $ret["content"];

            } // ROLE_MASTER

            break;
          }

// *****************************************************************************
// *** backend GET languages ***
// *****************************************************************************

          case "lang": {

            if ($_SESSION["user_role"] >= ROLE_MASTER) {

              $model_languages = new Languages();	// model erstellen
              $ret = $model_languages->getLanguages();	// daten für anguages aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_MASTER

            break;
          }

// *****************************************************************************
// *** backend GET admin ***
// *****************************************************************************

          case "admin": {

            if ($_SESSION["user_role"] >= ROLE_ADMIN) {

              $model_admin = new Admin();	// model erstellen
              $ret = $model_admin->getAdmin();	// daten für admin aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_ADMIN

            break;
          }

// *****************************************************************************
// *** backend GET password ***
// *****************************************************************************

          case "password": {

            // passwort ändern formular
            // - alt (zur überprüfung)
            // - neu
            // - neu2
            $html_backend_ext .= $this->model->password_form(true, false);	// section_start=true

            $ret = $this->model->getTwofa();	// daten für twofa aus dem model

            // zwei-faktor-authentifizierung formular
            // - shared secret
            // - use_2fa (an/aus)
            $html_backend_ext .= $this->model->twofa_form($ret["base64_secret"], $ret["use_2fa"], false, true);	// section_end=true
            $errorstring = $ret["error"];

            break;
          }

// *****************************************************************************
// *** backend GET logout ***
// *****************************************************************************

          case "logout": {

            $html_backend_ext = "<p>logout</p>\n\n";

            $this->session->del_cookies();	// cookies löschen
            unset($_SESSION["auth"]);
            session_unset();
            session_destroy();

            break;
          }

          default: {
            // version

            $ret = $this->model->getVersion();	// daten für version aus dem model
            $html_backend_ext .= $ret["content"];
            $errorstring = $ret["error"];

          }

        } // switch

      } // else login

    } // no panic

    if (DEBUG) { $debug_str .= DEBUG_STR_END; }

    // setze inhalt, falls string vorhanden, sonst leer
    $view->setContent("content", isset($html_backend_ext) ? $html_backend_ext : "");
    $view->setContent("error", isset($errorstring) ? $errorstring : "");
    $view->setContent("debug", $debug_str);

    return $view->parseTemplate(true);	// ausgabe geändertes template, mit backend flag
  }

}

?>