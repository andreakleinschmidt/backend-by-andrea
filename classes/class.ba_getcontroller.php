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
  private $galleryid;
  private $id;
  private $page;

  // konstruktor, controller erstellen
  public function __construct() {
    $this->action = !empty($_GET["action"]) ? substr(trim($_GET["action"]), 0, 8) : "default";	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->galleryid = !empty($_GET["gallery"]) ? (is_numeric($_GET["gallery"]) ? intval($_GET["gallery"]) : NULL) : NULL;	// GET gallery auslesen, string in int umwandeln
    $this->id = !empty($_GET["id"]) ? (is_numeric($_GET["id"]) ? intval($_GET["id"]) : NULL) : NULL;	// GET id auslesen, string in int umwandeln
    $this->page = !empty($_GET["page"]) ? (is_numeric($_GET["page"]) ? intval($_GET["page"]) : NULL) : NULL;	// GET page auslesen, string in int umwandeln
    $this->date = !empty($_GET["date"]) ? substr(trim($_GET["date"]), 0, 10) : NULL;	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
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

        $actions = array(ACTION_BASE, ACTION_HOME, ACTION_PROFILE, ACTION_PHOTOS, ACTION_BLOG, ACTION_COMMENT, ACTION_UPLOAD, ACTION_LANGUAGES, ACTION_ADMIN, ACTION_PASSWORD, ACTION_LOGOUT);
        if (!in_array($this->action, $actions)) {
          $this->action = "default";
        }

        // switch anweisung, je nach GET-action

        switch($this->action) {

// *****************************************************************************
// *** backend GET base ***
// *****************************************************************************

          case ACTION_BASE: {

            if ($_SESSION["user_role"] >= ROLE_EDITOR) {

              $model_base = new Base();	// model erstellen
              $ret = $model_base->getBase();	// daten für basis aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_EDITOR

              break;
          }

// *****************************************************************************
// *** backend GET home ***
// *****************************************************************************

          case ACTION_HOME: {

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

          case ACTION_PROFILE: {

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

          case ACTION_PHOTOS: {

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

          case ACTION_BLOG: {

            if ($_SESSION["user_role"] >= ROLE_EDITOR) {

              $model_blog = new Blog();	// model erstellen
              $ret = $model_blog->getBlog($this->id, $this->page, $this->date);	// daten für blog aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_EDITOR

            break;
          }

// *****************************************************************************
// *** backend GET comment ***
// *****************************************************************************

          case ACTION_COMMENT: {

            if ($_SESSION["user_role"] >= ROLE_MASTER) {

              $model_comment = new Comment();	// model erstellen
              $ret = $model_comment->getComment($this->id, $this->page, $this->date);	// daten für comment aus dem model
              $html_backend_ext .= $ret["content"];
              $errorstring = $ret["error"];

            } // ROLE_MASTER

            break;
          }

// *****************************************************************************
// *** backend GET upload ***
// *****************************************************************************

          case ACTION_UPLOAD: {

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

          case ACTION_LANGUAGES: {

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

          case ACTION_ADMIN: {

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

          case ACTION_PASSWORD: {

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

          case ACTION_LOGOUT: {

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
