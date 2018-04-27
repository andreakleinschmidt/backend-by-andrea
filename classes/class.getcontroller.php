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
  private $alias;
  private $compage;

  // konstruktor, controller erstellen
  public function __construct() {
    $this->action = !empty($_GET["action"]) ? substr(trim($_GET["action"]), 0, 8) : "default";	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->language = !empty($_GET["lang"]) ? substr(trim($_GET["lang"]), 0, 2) : substr(DEFAULT_LOCALE, 0, 2);	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->gallery = !empty($_GET["gallery"]) ? substr(trim($_GET["gallery"]), 0, 16) : NULL;	// GET gallery auslesen, überflüssige leerzeichen entfernen, zu langen alias abschneiden
    $this->id = !empty($_GET["id"]) ? substr(trim($_GET["id"]), 0, 8) : NULL;	// GET id auslesen, überflüssige leerzeichen entfernen, zu lange photoid abschneiden
    $this->q = !empty($_GET["q"]) ? mb_substr(trim($_GET["q"]), 0, 64, MB_ENCODING) : NULL;	// GET q auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->tag = !empty($_GET["tag"]) ? mb_substr(trim($_GET["tag"]), 0, 32, MB_ENCODING) : NULL;	// GET tag auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->page = !empty($_GET["page"]) ? (is_numeric($_GET["page"]) ? intval($_GET["page"]) : NULL) : NULL;	// GET page auslesen, string in int umwandeln
    $this->year = !empty($_GET["year"]) ? (is_numeric($_GET["year"]) ? intval($_GET["year"]) : NULL) : NULL;	// GET year auslesen, string in int umwandeln
    $this->month = !empty($_GET["month"]) ? (is_numeric($_GET["month"]) ? intval($_GET["month"]) : NULL) : NULL;	// GET month auslesen, string in int umwandeln
    $this->alias = !empty($_GET["alias"]) ? substr(trim($_GET["alias"]), 0, 64) : NULL;	// GET alias auslesen, überflüssige leerzeichen entfernen, zu langen alias abschneiden
    $this->compage = !empty($_GET["compage"]) ? (is_numeric($_GET["compage"]) ? intval($_GET["compage"]) : NULL) : NULL;	// GET comment-page auslesen, string in int umwandeln
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

    if ($this->action == ACTION_BLOG) {
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
    $actions = array(ACTION_HOME, ACTION_PROFILE, ACTION_PHOTOS, ACTION_BLOG);
    if (!in_array($this->action, $actions)) {
      $base = $this->model->getBase("startpage");
      if (!empty($base["startpage"])) {
        $this->action = $base["startpage"];
      }
      else {
        $this->action = ACTION_HOME;	// default
      }
    }

    // switch anweisung, {hd_title} und {content} ersetzen je nach GET-action

    switch($this->action) {

      case ACTION_HOME: {
        $model_home = new Home();	// model erstellen
        $ret = $model_home->getHome();	// daten für home aus dem model
        $view->setContent("hd_title", $head["hd_title"]);	// ." home"
        break;
      }

      case ACTION_PROFILE: {
        $model_profile = new Profile();	// model erstellen
        $ret = $model_profile->getProfile($this->language);	// daten für profile aus dem model
        $view->setContent("hd_title", $head["hd_title"]." profile");
        break;
      }

      case ACTION_PHOTOS: {
        $model_photos = new Photos();	// model erstellen
        $ret = $model_photos->getPhotos($this->gallery, $this->id);	// daten für photos aus dem model
        $view->setContent("hd_title", $head["hd_title"]." photos".$ret["hd_title_ext"]);
        break;
      }

      case ACTION_BLOG: {
        $model_blog = new Blog();	// model erstellen
        $ret = $model_blog->getBlog($this->q, $this->tag, $this->page, $this->year, $this->month, $this->alias, $this->compage);	// daten für blog aus dem model
        $view->setContent("hd_title", $head["hd_title"]." blog".$ret["hd_title_ext"]);
        break;
      }

    } // switch

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
