<?php

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2018 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
 *
 * This program includes a MERGED version of PHP QR Code library
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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

    // html head
    if (!isset($_SESSION["head"])) {
      // falls session variable noch nicht existiert
      $head = $this->model->getHead();	// ba_title, ba_description, ba_author aus tabelle ba_base, feed_title aus ba_options_str
      $_SESSION["head"] = $head;	// in SESSION speichern
    } // neue session variable
    else {
      // alte session variable
      $head = $_SESSION["head"];	// aus SESSION lesen
    }

    $view->setContent("hd_description", $head["hd_description"]);
    $view->setContent("hd_author", $head["hd_author"]);
    $view->setContent("feed_title", $head["feed_title"]);

    // menue im nav-tag
    if (!isset($_SESSION["menue"])) {
      // falls session variable noch nicht existiert
      $menue = $this->model->getMenue();	// daten für menue aus dem model
      $_SESSION["menue"] = $menue;	// in SESSION speichern
    } // neue session variable
    else {
      // alte session variable
      $menue = $_SESSION["menue"];	// aus SESSION lesen
    }

    $view->setContent("menue", $menue["menue"]);

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
      $view->setContent("hd_title", $head["hd_title"]." blog (comment)");

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

    $view->setContent("error", $head["error"].$menue["error"].$login["error"].$ret["error"].$footer["error"]);

    return $view->parseTemplate();	// ausgabe geändertes template
  }

}

?>
