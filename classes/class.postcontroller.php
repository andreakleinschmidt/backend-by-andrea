<?php

// *****************************************************************************
// * postcontroller
// * - POST auslesen
// * - benutzereingabe (request)
// * - datenabfrage beim model
// * - datenweitergabe zum view
// *****************************************************************************

class PostController {

  // konstruktor, controller erstellen
  public function __construct() {
    $this->model = new Model();	// model erstellen
  }

  // zustandsmaschine, aufruf der funktionen, datenabfrage beim model, datenweitergabe zum view
  public function display() {
    $view = new View();	// view erstellen
    $view->setTemplate("default");	// template "tpl_default.htm" laden

    $login = $this->model->getLogin();	// daten für login aus dem model
    $view->setContent("login", $login["login"]);

    $ret = null;	// ["content","error"]

    // POST überprüfen
    if (isset($_POST["comment"])) {
      // comment[name, mail, website, blogid, text]

      $model_blog = new Blog();	// model erstellen
      $comment_array = $_POST["comment"];

      // überflüssige leerzeichen entfernen, str zu int
      $comment_name = trim($comment_array["name"]);
      $comment_mail = trim($comment_array["mail"]);
      $comment_website = trim($comment_array["website"]);
      $comment_blogid = intval($comment_array["blogid"]);
      $comment_text = trim($comment_array["text"]);

      // zeichen limit
      if (mb_strlen($comment_name, MB_ENCODING) > MAXLEN_COMMENTNAME) {
        $comment_name = mb_substr($comment_name, 0, MAXLEN_COMMENTNAME, MB_ENCODING);
      }
      if (strlen($comment_mail) > MAXLEN_COMMENTMAIL) {
        $comment_mail = substr($comment_mail, 0, MAXLEN_COMMENTMAIL);
      }
      if (mb_strlen($comment_website, MB_ENCODING) > MAXLEN_COMMENTURL) {
        $comment_website = mb_substr($comment_website, 0, MAXLEN_COMMENTURL, MB_ENCODING);
      }
      if (mb_strlen($comment_text, MB_ENCODING) > MAXLEN_COMMENTTEXT) {
        $comment_text = mb_substr($comment_text, 0, MAXLEN_COMMENTTEXT, MB_ENCODING);
      }

      $ret = $model_blog->postComment($comment_name, $comment_mail, $comment_website, $comment_blogid, $comment_text);	// daten für comment in das model
      $view->setContent("hd_title_ext", " blog (comment)");

    } // comment[]

    else {
      $errorstring = "<p>POST error</p>\n\n";
      $ret["content"] = "";
      $ret["error"] = $errorstring;
    }

    // redirect index.php?action=blog#comment
    header("refresh:10;index.php?action=blog#comment");

    $view->setContent("content", $ret["content"]);

    if (!isset($_SESSION["footer"])) {
      // falls session variable noch nicht existiert
      $footer = $this->model->getFooter();	// daten für footer aus dem model
      $_SESSION["footer"] = $footer;	// in SESSION speichern
    } // neue session variable
    else {
      // alte session variable
      $footer = $_SESSION["footer"];	// aus SESSION lesen
    }

    $view->setContent("footer", $footer["footer"]);

    $view->setContent("error", $login["error"].$ret["error"].$footer["error"]);

    return $view->parseTemplate();	// ausgabe geändertes template
  }

}

?>
