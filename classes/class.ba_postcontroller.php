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

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// user/password error - bei POST login
// code error - bei POST login
//
// password error - bei backend POST password
// password new (1) and new (2) not equal - bei backend POST password
// wrong password - bei backend POST password
//
// POST error - bei login ($_POST["unbekannt"])
// POST error - bei backend POST ($_POST["unbekannt"])

class PostController {

  private $user_login;
  private $password;
  private $code;

  // konstruktor, controller erstellen
  public function __construct() {
    $this->user_login = !empty($_POST["user_login"]) ? trim($_POST["user_login"]) : NULL;	// überflüssige leerzeichen entfernen
    $this->password = !empty($_POST["password"]) ? trim($_POST["password"]) : NULL;	// überflüssige leerzeichen entfernen
    $this->code = !empty($_POST["code"]) ? (is_numeric($_POST["code"]) ? intval($_POST["code"]) : NULL) : NULL;	// string in int umwandeln
    $this->session = new Session();	// session funktionen
    $this->model = new Model();	// model erstellen
  }

// *****************************************************************************
// *** funktionen ***
// *****************************************************************************

  // generate random password mit duplikaten (für email und shared secret)
  private function gen_password($len) {
    $char_pool = "abcdefghijklmnopqrstuvwxyz".
                 "ABCDEFGHIJKLMNOPQRSTUVWXYZ".
                 "0123456789".
                 "!$%{}=?+*~#.:-_";
    // htmlspecialchars: "&'<>
    // nicht verwendet: /()[]\'`@|,;

    if ($len > MAXLEN_PASSWORD) {
      $len = MAXLEN_PASSWORD;
    }

    $pwd_str = "";
    for ($i = 0; $i < $len; $i++) {
      $offset = mt_rand(0, strlen($char_pool)-1);	// nur ascii
      $pwd_str .= substr($char_pool, $offset, 1);	// nur ascii
    }

    return $pwd_str;
  }

  // PBKDF2
  private function generate_shared_secret($password) {
    $salt = openssl_random_pseudo_bytes(16);
    $iterations = 1000;
    $keylength = 10;	// 80 bit
    $shared_secret = openssl_pbkdf2($password, $salt, $keylength, $iterations, "sha1");
    return $shared_secret;
  }

  // berechne code (zwei-faktor-authentifizierung)
  private function get_code($secret, $ts, $n=0, $digits=6) {
    $tc = floor($ts/30)+$n;				// n: z.B n-1 -> tc-1
    $tc = chr(0).chr(0).chr(0).chr(0).pack("N*", $tc);	// unsigned long (32 bit), big endian
    $hash = hash_hmac("sha1", $tc, $secret, true);	// raw binary output
    $offset = ord(substr($hash, -1, 1)) & 0x0F;		// least 4 significant bits
    $hotp = substr($hash, $offset, 4);			// 4 bytes ab offset...
    $first = chr(ord(substr($hotp, 0, 1)) & 0x7F);	// MSB verwerfen...
    $hotp = substr_replace($hotp, $first, 0, 1);	// ...unsigned 32 bit
    $hotp = unpack("N", $hotp);				// unsigned long (32 bit), big endian
    $code = reset($hotp) % pow(10, $digits);		// code mit x ziffern
    $code = str_pad($code, $digits, "0", STR_PAD_LEFT);	// von links auffüllen mit 0, falls weniger als x ziffern
    return $code;
  }

  // zustandsmaschine, aufruf der funktionen, datenabfrage beim model, datenweitergabe zum view
  public function display() {
    $view = new View();	// view erstellen
    $view->setTemplate();	// template für view laden (DEFAULT_TEMPLATE)

    $debug_str = "";

// *****************************************************************************
// *** session ***
// *****************************************************************************

    // panic mode
    if (isset($_SESSION["panic"])) {
      $html_backend_ext = "";
      $errorstring = "";
      if (DEBUG) { $debug_str .= "001 panic\n"; }
    }
    else {
      // no panic , login?

      $ret = $this->session->check_login($login);
      if (DEBUG) { $debug_str .= $ret; }

      if (!$login) {

// *****************************************************************************
// *** login ***
// *****************************************************************************

        // password oder (optional) code in POST?

        $login_1 = false;
        $login_2 = false;

        $code = 0;

        // POST überprüfen
        if (isset($this->user_login, $this->password)) {

          if (DEBUG) { $debug_str .= "008 user_login = ".$this->user_login."\n"; }
          if (DEBUG) { $debug_str .= "008 pwd = ".$this->password."\n"; }

          if ($this->user_login != "" and mb_strlen($this->user_login, MB_ENCODING) <= MAXLEN_USER and $this->password != "" and mb_strlen($this->password, MB_ENCODING) <= MAXLEN_PASSWORD) {
            // test auf leere felder

            // vergleich mit datenbank (passwort in datenbank ist blowfish hash mit random salt)
            $ret = $this->model->check_user($this->user_login);

            if ($ret["check"] == true) {

              $user_id = $ret["user_id"];
              $user_role = $ret["user_role"];
              $user_login = $ret["user_login"];
              $user_locale = $ret["user_locale"];
              $password_hash = $ret["password_hash"];
              $use_2fa = $ret["use_2fa"];
              $last_code = $ret["last_code"];
              $base64_secret = $ret["base64_secret"];

              if (DEBUG) { $debug_str .= "010 pwd-hash = ".$password_hash."\n"; }
              if (hash_equals(crypt($this->password, $password_hash), $password_hash)) {
                // passwort stimmt

                $login_1 = true;

                // optional zwei-faktor-authentifizierung
                if ($use_2fa > 0) {

                  $_SESSION["login_last_code"] = $last_code;
                  $_SESSION["login_base64_secret"] = $base64_secret;

                  if (DEBUG) { $debug_str .= "2fa last_code = ".$last_code."\n"; }
                  if (DEBUG) { $debug_str .= "2fa base64_secret = ".$base64_secret."\n"; }

                  // login form 2fa
                  $html_backend_ext = $this->model->html_form_2fa();

                } // $use_2fa
                else {
                  $login_2 = true;
                } // don't $use_2fa

              } // passwort ok

            } // check user ok

            else {
              $_SESSION["panic"] = true;	// user falsch, solange session aktiv, kein neuer versuch möglich, kein dauer-POST
              $this->session->del_cookies();	// cookies löschen
              unset($_SESSION["auth"]);
            }

            $errorstring = $ret["error"];

          } // user/password error

          else {
            $errorstring = "user/password error\n";
          }

          $_SESSION["login_passed"] = $login_1;

        } // password in POST

        // POST überprüfen
        elseif (isset($this->code, $_SESSION["login_passed"], $_SESSION["login_last_code"], $_SESSION["login_base64_secret"])) {

          $login_passed = $_SESSION["login_passed"];
          $last_code= $_SESSION["login_last_code"];
          $base64_secret = $_SESSION["login_base64_secret"];

          unset($_SESSION["login_passed"]);
          unset($_SESSION["login_last_code"]);
          unset($_SESSION["login_base64_secret"]);

          if (DEBUG) { $debug_str .= "2fa first login passed (1) = ".$login_passed."\n"; }
          if (DEBUG) { $debug_str .= "2fa last_code = ".$last_code."\n"; }
          if (DEBUG) { $debug_str .= "2fa base64_secret = ".$base64_secret."\n"; }

          if ($login_passed) {

            if (DEBUG) { $debug_str .= "010 code = ".$this->code."\n"; }

            if ($this->code != "" and strlen($this->code) <= MAXLEN_CODE) {
              // test auf leere felder

              $unix_ts = time();	// totp - time based one time password
              $shared_secret = base64_decode($base64_secret);
              $own_code = $this->get_code($shared_secret, $unix_ts);
              $own_code_n1 = $this->get_code($shared_secret, $unix_ts, -1);
              if (DEBUG) { $debug_str .= "010 own_code = ".$own_code."\n"; }
              if (DEBUG) { $debug_str .= "010 own_code_n1 = ".$own_code_n1."\n"; }
              if (DEBUG) { $debug_str .= "010 last_code = ".$last_code."\n"; }

              // vergleich eingegebenen code mit berechneten code und zuletzt eingegebenen code
              if ((($this->code == $own_code) or ($this->code == $own_code_n1)) and ($this->code != $last_code)) {
                // code stimmt

                $login_2 = true;
                $code = $this->code;
              }

              else {
                $_SESSION["panic"] = true;	// (optional) code falsch, solange session aktiv, kein neuer versuch möglich, kein dauer-POST
                $this->session->del_cookies();	// cookies löschen
                unset($_SESSION["auth"]);
              }

            } // code error

            else {
              $errorstring = "code error\n";
            }

          } // login

        } // code in POST

        else {
          $errorstring = "POST error\n";
        }

        // nach POST überprüfung, auswertung login variablen

        if ($login_1) {
          // password in POST
          $ret = $this->session->set_user_login($user_id, $user_role, $user_login, $user_locale);	// server session erweitern (mit variablen für user login)
          if (DEBUG) { $debug_str .= $ret; }
        }
        elseif ($login_2) {
          // code in POST, 2fa
          $login_1 = $login_passed;
          if (DEBUG) { $debug_str .= "2fa first login passed (2) = ".$login_1."\n"; }
        }
        else {
          $ret = $this->session->unset_user_login();	// 2fa failed, user variablen aus session entfernen
          if (DEBUG) { $debug_str .= $ret; }
        }

        $login = $login_1 & $login_2;
        if ($login) {

          $ret = $this->session->set_login();	// server session setzen/erweitern (mit uid cookie)
          if (DEBUG) { $debug_str .= $ret; }

          $ret = $this->model->timestamp($code);	// login zeitstempel und letzter code
          $errorstring = $ret["error"];

        } // passwort und (optional) code ok

        if (DEBUG) { $debug_str .= "011 login = ".$login."\n"; }

        if ($login) {
          $html_backend_ext = $this->model->html_backend($_SESSION["user_role"], $_SESSION["user_name"]);
        }

      } // nicht login

      else {
        // login

        unset($_SESSION["auth"]);
        session_regenerate_id();	// immer neue session id

        $ret = $this->session->set_login();	// server session setzen/erweitern (mit uid cookie)
        if (DEBUG) { $debug_str .= $ret; }

// *****************************************************************************
// *** backend POST ***
// *****************************************************************************

        $html_backend_ext = $this->model->html_backend($_SESSION["user_role"], $_SESSION["user_name"]);

        $ret = null;	// ["content","error"]

// *****************************************************************************
// *** backend POST password ***
// *****************************************************************************

        // POST überprüfen
        if (isset($_POST["password"], $_POST["password_new1"], $_POST["password_new2"])) {
          // password in POST

          // password überprüfen
          $password = trim($_POST["password"]);	// überflüssige leerzeichen entfernen
          $password_new1 = trim($_POST["password_new1"]);
          $password_new2 = trim($_POST["password_new2"]);
          if (DEBUG) { $debug_str .= "014 pwd = ".$password."\n"; }
          if (DEBUG) { $debug_str .= "015 pwd-n1 = ".$password_new1."\n"; }
          if (DEBUG) { $debug_str .= "016 pwd-n2 = ".$password_new2."\n"; }

          if ($password != "" and (mb_strlen($password, MB_ENCODING) <= MAXLEN_PASSWORD)
              and $password_new1 != "" and mb_strlen($password_new1, MB_ENCODING) <= MAXLEN_PASSWORD
              and $password_new2 != "" and mb_strlen($password_new2, MB_ENCODING) <= MAXLEN_PASSWORD) {
            // test auf leeres passwort

            if (DEBUG) { $debug_str .= "017 pwd = ".$password."\n"; }
            if (DEBUG) { $debug_str .= "018 pwd-n1 = ".$password_new1."\n"; }
            if (DEBUG) { $debug_str .= "019 pwd-n2 = ".$password_new2."\n"; }

            // passwort neu 1 und 2 gleich?
            if ($password_new1 == $password_new2) {

              // vergleich mit datenbank (passwort in datenbank ist blowfish hash mit random salt)
              $ret = $this->model->check_password();

              if ($ret["check"] == true) {

                $password_hash = $ret["password_hash"];

                if (DEBUG) { $debug_str .= "020 pwd-hash = ".$password_hash."\n"; }
                if (hash_equals(crypt($password, $password_hash), $password_hash)) {
                  // passwort stimmt

                  // CRYPT_BLOWFISH (60 Zeichen): "$2y$" + default cost "10" + "$" + random 22 zeichen salt + 31 zeichen hash
                  $password_new_hash = password_hash($password_new1, PASSWORD_BCRYPT);
                  if (DEBUG) { $debug_str .= "021 pwd-n-hash = ".$password_new_hash."\n"; }

                  // update in datenbank (passwort in datenbank ist blowfish hash mit random salt)
                  $ret = $this->model->update_password($password_new_hash);
                  $html_backend_ext .= $ret["content"];
                  $errorstring = $ret["error"];

                } // passwort ok

                else {
                  $errorstring = "wrong password\n";
                }

              } // check password ok

              else {
                $errorstring = $ret["error"];
              }

            } // passwort neu 1 und 2 gleich?

            else {
              $errorstring = "password new (1) and new (2) not equal\n";
            }

          } // password error

          else {
            $errorstring = "password error\n";
          }

        } // password in POST

// *****************************************************************************
// *** backend POST twofa ***
// *****************************************************************************

        elseif (isset($_POST["base64_secret"])) {
          // base64_secret, request_new_secret und use_2fa in POST

          // überflüssige leerzeichen entfernen
          $base64_secret = trim($_POST["base64_secret"]);

          // zeichen limit
          if (strlen($base64_secret) > MAXLEN_SHAREDSECRET) {
            $base64_secret = substr($base64_secret, 0, MAXLEN_SHAREDSECRET);
          }

          $use_2fa = 0;	// default
          if (isset($_POST["use_2fa"])) {
            // überflüssige leerzeichen entfernen
            $use_2fa_str = trim($_POST["use_2fa"]);

            if ($use_2fa_str == "yes") {
              // checked
              $use_2fa = 1;
            }
            else {
              // unchecked
              $use_2fa = 0;
            }
          } // isset use_2fa

          $request_new_secret = 0;	// default
          if (isset($_POST["request_new_secret"])) {
            // überflüssige leerzeichen entfernen
            $request_new_secret_str = trim($_POST["request_new_secret"]);

            if ($request_new_secret_str == "yes") {
              // checked
              $random_pwd = $this->gen_password(16);
              $shared_secret = $this->generate_shared_secret($random_pwd);
              $base64_secret = base64_encode($shared_secret);	// für db
            }
          } // isset request_new_secret

          // update in datenbank
          $ret = $this->model->update_twofa($use_2fa, $base64_secret);
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // base64_secret, request_new_secret und use_2fa in POST

// *****************************************************************************
// *** backend POST base ***
// *****************************************************************************

        elseif (isset($_POST["ba_base"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_base[ba_title, ba_description, ba_author, ba_nav, ba_nav_links, ba_startpage]

          $model_base = new Base();	// model erstellen
          $ba_base_array = $_POST["ba_base"];

          // überflüssige leerzeichen entfernen
          $ba_title = trim($ba_base_array["ba_title"]);
          $ba_description = trim($ba_base_array["ba_description"]);
          $ba_author = trim($ba_base_array["ba_author"]);
          $ba_nav_links = trim($ba_base_array["ba_nav_links"]);
          $ba_startpage = trim($ba_base_array["ba_startpage"]);

          // zeichen limit
          if (strlen($ba_title) > MAXLEN_BASETITLE) {
            $ba_title = substr($ba_title, 0, MAXLEN_BASETITLE);
          }
          if (strlen($ba_description) > MAXLEN_BASEDESCRIPTION) {
            $ba_description = substr($ba_description, 0, MAXLEN_BASEDESCRIPTION);
          }
          if (strlen($ba_author) > MAXLEN_BASEAUTHOR) {
            $ba_author = substr($ba_author, 0, MAXLEN_BASEAUTHOR);
          }
          if (strlen($ba_nav_links) > MAXLEN_BASELINKS) {
            $ba_nav_links = substr($ba_nav_links, 0, MAXLEN_BASELINKS);
          }

          $ba_nav_arr = $ba_base_array["ba_nav"];
          $ba_nav_arr_new = array();

          // nur definierte werte
          $actions = array(ACTION_HOME, ACTION_PROFILE, ACTION_PHOTOS, ACTION_BLOG);
          foreach ($ba_nav_arr as $action) {
            $action = trim($action);	// überflüssige leerzeichen entfernen
            if (in_array($action, $actions)) {
              $ba_nav_arr_new[] = $action;
            }
          }

          $ba_nav = implode(",", $ba_nav_arr_new);

          if (!in_array($ba_startpage, $actions)) {
            $ba_startpage = ACTION_HOME;	// default
          }

          $ret = $model_base->postBase($ba_title, $ba_description, $ba_author, $ba_nav, $ba_nav_links, $ba_startpage);	// daten für basis in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_base[]

// *****************************************************************************
// *** backend POST home ***
// *****************************************************************************

        elseif (isset($_POST["ba_home"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_home[ba_id][ba_css, ba_value]

          $model_home = new Home();	// model erstellen
          $ba_home_array = $_POST["ba_home"];
          $ba_home_array_replaced = array();

          foreach ($ba_home_array as $ba_id => $ba_array) {
            $ba_css = trim($ba_array["ba_css"]);	// überflüssige leerzeichen entfernen
            $ba_value = trim($ba_array["ba_value"]);

            // zeichen limit
            if (mb_strlen($ba_css, MB_ENCODING) > MAXLEN_HPCSS) {
              $ba_css = mb_substr($ba_css, 0, MAXLEN_HPCSS, MB_ENCODING);
            }
            if (mb_strlen($ba_value, MB_ENCODING) > MAXLEN_HPVALUE) {
              $ba_value = mb_substr($ba_value, 0, MAXLEN_HPVALUE, MB_ENCODING);
            }

            $ba_home_array_replaced[$ba_id] = array("ba_css" => $ba_css, "ba_value" => $ba_value);
          }

          $ret = $model_home->postHome($ba_home_array_replaced);	// daten für home in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_home[ba_id]

// *****************************************************************************
// *** backend POST home elements (neu) ***
// *****************************************************************************

        elseif (isset($_POST["ba_home_elements_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_home_elements_new[element]

          $model_home = new Home();	// model erstellen
          $ba_home_elements_new_array = $_POST["ba_home_elements_new"];

          // überflüssige leerzeichen entfernen
          $element = trim($ba_home_elements_new_array["element"]);

          // zeichen limit
          if (strlen($element) > MAXLEN_HPELEMENT) {
            $element = substr($element, 0, MAXLEN_HPELEMENT);
          }

          $ret = $model_home->postElementNew($element);	// daten für elements (neu) in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_home_elements_new[]

// *****************************************************************************
// *** backend POST home elements ***
// *****************************************************************************

        elseif (isset($_POST["ba_home_elements"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_home_elements[ba_id]

          $model_home = new Home();	// model erstellen
          $ba_home_elements_array = $_POST["ba_home_elements"];
          $ret = $model_home->postElements($ba_home_elements_array);	// daten für elements in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_home_elements[]

// *****************************************************************************
// *** backend POST profile ***
// *****************************************************************************

        elseif (isset($_POST["ba_profile"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_profile[ba_id][ba_css, ba_value]

          $model_profile = new Profile();	// model erstellen
          $ba_profile_array = $_POST["ba_profile"];
          $ba_profile_array_replaced = array();

          foreach ($ba_profile_array as $ba_id => $ba_array) {
            $ba_css = trim($ba_array["ba_css"]);	// überflüssige leerzeichen entfernen
            $ba_value = trim($ba_array["ba_value"]);

            // zeichen limit
            if (mb_strlen($ba_css, MB_ENCODING) > MAXLEN_HPCSS) {
              $ba_css = mb_substr($ba_css, 0, MAXLEN_HPCSS, MB_ENCODING);
            }
            if (mb_strlen($ba_value, MB_ENCODING) > MAXLEN_HPVALUE) {
              $ba_value = mb_substr($ba_value, 0, MAXLEN_HPVALUE, MB_ENCODING);
            }

            $ba_profile_array_replaced[$ba_id] = array("ba_css" => $ba_css, "ba_value" => $ba_value);
          }

          $ret = $model_profile->postProfile($ba_profile_array_replaced);	// daten für profile in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_profile[ba_id][ba_css, ba_value]

// *****************************************************************************
// *** backend POST profile elements (neu) ***
// *****************************************************************************

        elseif (isset($_POST["ba_profile_elements_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_profile_elements_new[element]

          $model_profile = new Profile();	// model erstellen
          $ba_profile_elements_new_array = $_POST["ba_profile_elements_new"];

          // überflüssige leerzeichen entfernen
          $element = trim($ba_profile_elements_new_array["element"]);

          // zeichen limit
          if (strlen($element) > MAXLEN_HPELEMENT) {
            $element = substr($element, 0, MAXLEN_HPELEMENT);
          }

          $ret = $model_profile->postElementNew($element);	// daten für elements (neu) in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_profile_elements_new[]

// *****************************************************************************
// *** backend POST profile elements ***
// *****************************************************************************

        elseif (isset($_POST["ba_profile_elements"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_profile_elements[ba_id]

          $model_profile = new Profile();	// model erstellen
          $ba_profile_elements_array = $_POST["ba_profile_elements"];
          $ret = $model_profile->postElements($ba_profile_elements_array);	// daten für elements in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_profile_elements[]

// *****************************************************************************
// *** backend POST profile tables (neu) ***
// *****************************************************************************

        elseif (isset($_POST["ba_profile_tables_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_profile_tables_new[element, table_name, rows, cols, language_codes]

          $model_profile = new Profile();	// model erstellen
          $ba_profile_tables_new_array = $_POST["ba_profile_tables_new"];

          // überflüssige leerzeichen entfernen
          $element = trim($ba_profile_tables_new_array["element"]);
          $table_name = trim($ba_profile_tables_new_array["table_name"]);

          // str zu int
          $rows = intval($ba_profile_tables_new_array["rows"]);
          $cols = intval($ba_profile_tables_new_array["cols"]);

          // zeichen limit
          if (strlen($element) > MAXLEN_HPELEMENT) {
            $element = substr($element, 0, MAXLEN_HPELEMENT);
          }
          if (strlen($table_name) > MAXLEN_PROFILETABLENAME) {
            $table_name = substr($table_name, 0, MAXLEN_PROFILETABLENAME);
          }

          // werte eingrenzen
          if ($rows < 1) {
            $rows = 1;
          }
          if ($rows > 255) {
            $rows = 255;
          }
          if ($cols < 1) {
            $cols = 1;
          }
          if ($cols > 255) {
            $cols = 255;
          }

          $language_codes = array();
          foreach ($ba_profile_tables_new_array["language_codes"] as $language_code) {
            $language_code = trim($language_code);	// überflüssige leerzeichen entfernen

            // zeichen limit
            if (strlen($language_code) > MAXLEN_PROFILELANGUAGE) {
              $language_code = substr($language_code, 0, MAXLEN_PROFILELANGUAGE);
            }

            $language_codes[] = $language_code;
          }

          $ba_profile_tables_new_array_replaced = array("element" => $element, "table_name" => $table_name, "rows" => $rows, "cols" => $cols, "language_codes" => $language_codes);

          $ret = $model_profile->postTablesNew($ba_profile_tables_new_array_replaced);	// daten für tables (neu) in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_profile_tables_new[]

// *****************************************************************************
// *** backend POST profile tables ***
// *****************************************************************************

        elseif (isset($_POST["ba_profile_tables"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_profile_tables[ba_table_name][ba_row][ba_col][ba_language][ba_value]

          $model_profile = new Profile();	// model erstellen
          $ba_profile_tables_array = $_POST["ba_profile_tables"];

          $ba_profile_tables_array_replaced = array();

          foreach ($ba_profile_tables_array as $ba_table_name => $rows) {
            $ba_table_name = trim($ba_table_name);	// überflüssige leerzeichen entfernen

            // zeichen limit
            if (strlen($ba_table_name) > MAXLEN_PROFILETABLENAME) {
              $ba_table_name = substr($ba_table_name, 0, MAXLEN_PROFILETABLENAME);
            }

            $ba_profile_tables_array_replaced[$ba_table_name] = array();

            foreach ($rows as $ba_row => $cols) {
              $ba_row = intval($ba_row);	// str zu int

              $ba_profile_tables_array_replaced[$ba_table_name][$ba_row] = array();

              foreach ($cols as $ba_col => $languages) {
                $ba_col = intval($ba_col);	// str zu int

                $ba_profile_tables_array_replaced[$ba_table_name][$ba_row][$ba_col] = array();

                foreach ($languages as $ba_language => $ba_value) {
                  $ba_language = trim($ba_language);	// überflüssige leerzeichen entfernen
                  $ba_value = trim($ba_value);

                  // zeichen limit
                  if (strlen($ba_language) > MAXLEN_PROFILELANGUAGE) {
                    $ba_language = substr($ba_language, 0, MAXLEN_PROFILELANGUAGE);
                  }
                  if (strlen($ba_value) > MAXLEN_PROFILEVALUE) {
                    $ba_value = substr($ba_value, 0, MAXLEN_PROFILEVALUE);
                  }

                  $ba_profile_tables_array_replaced[$ba_table_name][$ba_row][$ba_col][$ba_language] = $ba_value;

                } // foreach languages

              } // foreach columns

            } // foreach rows

          } // foreach table names

          $ret = $model_profile->postTables($ba_profile_tables_array_replaced);	// daten für tables in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_profile_tables[]

// *****************************************************************************
// *** backend POST gallery (neu) ***
// *****************************************************************************

        elseif (isset($_POST["ba_gallery_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_gallery_new[ba_alias]	MAXLEN_GALLERYALIAS
          // ba_gallery_new[ba_text]	MAXLEN_GALLERYTEXT
          // ba_gallery_new[ba_order]	ASC DESC

          $model_photos = new Photos();	// model erstellen
          $ba_gallery_new_array = $_POST["ba_gallery_new"];

          // überflüssige leerzeichen entfernen
          $ba_alias = trim($ba_gallery_new_array["ba_alias"]);
          $ba_text = trim($ba_gallery_new_array["ba_text"]);
          $ba_order = trim($ba_gallery_new_array["ba_order"]);

          // zeichen limit
          if (strlen($ba_alias) > MAXLEN_GALLERYALIAS) {
            $ba_alias = substr($ba_alias, 0, MAXLEN_GALLERYALIAS);
          }

          // zeichen limit
          if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_GALLERYTEXT) {
            $ba_text = mb_substr($ba_text, 0, MAXLEN_GALLERYTEXT, MB_ENCODING);
          }

          // nur ASC oder DESC, sonst ASC
          $ba_order = strtoupper($ba_order);
          if ($ba_order != "ASC" and $ba_order != "DESC") {
            $ba_order = "ASC";
          }

          $ret = $model_photos->postGalleryNew($ba_alias, $ba_text, $ba_order);	// daten für gallery (neu) in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_gallery_new[ba_text]

// *****************************************************************************
// *** backend POST gallery ***
// *****************************************************************************

        elseif (isset($_POST["ba_gallery"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_gallery[ba_id][ba_alias]	MAXLEN_GALLERYALIAS
          // ba_gallery[ba_id][ba_text]		MAXLEN_GALLERYTEXT
          // ba_gallery[ba_id][ba_order]	ASC DESC
          // ba_gallery[ba_id]["delete"]

          $model_photos = new Photos();	// model erstellen
          $ba_gallery_array = $_POST["ba_gallery"];
          $ba_gallery_array_replaced = array();

          foreach ($ba_gallery_array as $ba_id => $ba_array) {
            // überflüssige leerzeichen entfernen, str zu int
            $ba_id = intval($ba_id);
            $ba_alias = trim($ba_array["ba_alias"]);
            $ba_text = trim($ba_array["ba_text"]);
            $ba_order = trim($ba_array["ba_order"]);

            // zeichen limit
            if (strlen($ba_alias) > MAXLEN_GALLERYALIAS) {
              $ba_alias = substr($ba_alias, 0, MAXLEN_GALLERYALIAS);
            }

            // zeichen limit
            if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_GALLERYTEXT) {
              $ba_text = mb_substr($ba_text, 0, MAXLEN_GALLERYTEXT, MB_ENCODING);
            }

            // nur ASC oder DESC, sonst ASC
            $ba_order = strtoupper($ba_order);
            if ($ba_order != "ASC" and $ba_order != "DESC") {
              $ba_order = "ASC";
            }

            $ba_delete = in_array("delete", $ba_array);	// in array nach string suchen

            $ba_gallery_array_replaced[$ba_id] = array("ba_alias" => $ba_alias, "ba_text" => $ba_text, "ba_order" => $ba_order, "delete" => $ba_delete);
          }

          $ret = $model_photos->postGallery($ba_gallery_array_replaced);	// daten für gallery in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_gallery[ba_id][ba_text]

// *****************************************************************************
// *** backend POST photos (neu) ***
// *****************************************************************************

        elseif (isset($_POST["ba_photos_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_photos_new[ba_galleryid]
          // ba_photos_new[ba_photoid]	MAXLEN_PHOTOID
          // ba_photos_new[ba_text]	MAXLEN_PHOTOTEXT
          // ba_photos_new["hide"]

          $model_photos = new Photos();	// model erstellen
          $ba_photos_new_array = $_POST["ba_photos_new"];

          // überflüssige leerzeichen entfernen, str zu int
          $ba_galleryid = intval($ba_photos_new_array["ba_galleryid"]);
          $ba_photoid = trim($ba_photos_new_array["ba_photoid"]);
          $ba_text = trim($ba_photos_new_array["ba_text"]);

          // zeichen limit
          if (strlen($ba_photoid) > MAXLEN_PHOTOID) {
            $ba_photoid = substr($ba_photoid, 0, MAXLEN_PHOTOID);
          }
          if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_PHOTOTEXT) {
            $ba_text = mb_substr($ba_text, 0, MAXLEN_PHOTOTEXT, MB_ENCODING);
          }

          $ba_hide = 0;
          if (in_array("hide", $ba_photos_new_array)) {
            $ba_hide = 1;
          }

          $ret = $model_photos->postPhotosNew($ba_galleryid, $ba_photoid, $ba_text, $ba_hide);	// daten für photos (neu) in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_photos_new[ba_text]

// *****************************************************************************
// *** backend POST photos ***
// *****************************************************************************

        elseif (isset($_POST["ba_photos"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_photos[ba_id][ba_galleryid]
          // ba_photos[ba_id][ba_photoid]	MAXLEN_PHOTOID
          // ba_photos[ba_id][ba_text]		MAXLEN_PHOTOTEXT
          // ba_photos[ba_id]["hide"]
          // ba_photos[ba_id]["delete"]

          $model_photos = new Photos();	// model erstellen
          $ba_photos_array = $_POST["ba_photos"];
          $ba_photos_array_replaced = array();

          foreach ($ba_photos_array as $ba_id => $ba_array) {

            // überflüssige leerzeichen entfernen, str zu int
            $ba_id = intval($ba_id);
            $ba_galleryid = intval($ba_array["ba_galleryid"]);
            $ba_photoid = trim($ba_array["ba_photoid"]);
            $ba_text = trim($ba_array["ba_text"]);

            // zeichen limit
            if (strlen($ba_photoid) > MAXLEN_PHOTOID) {
              $ba_photoid = substr($ba_photoid, 0, MAXLEN_PHOTOID);
            }
            if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_PHOTOTEXT) {
              $ba_text = mb_substr($ba_text, 0, MAXLEN_PHOTOTEXT, MB_ENCODING);
            }

            $ba_hide = 0;
            $ba_delete = in_array("delete", $ba_array);	// in array nach string suchen
            if (in_array("hide", $ba_array)) {
              $ba_hide = 1;
            }

            $ba_photos_array_replaced[$ba_id] = array("ba_galleryid" => $ba_galleryid, "ba_photoid" => $ba_photoid, "ba_text" => $ba_text, "hide" => $ba_hide, "delete" => $ba_delete);
          }

          $ret = $model_photos->postPhotos($ba_photos_array_replaced);	// daten für photos in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_photos[ba_id][ba_text]

// *****************************************************************************
// *** backend POST blog ***
// *****************************************************************************

        elseif (isset($_POST["ba_blog"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_blog[ba_id, ba_userid, ba_datetime, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, ba_catid, ba_tags, ba_state, "delete"]
          // ba_id == 0 -> neuer blog eintrag
          // ba_id == 0xffff -> error

          $model_blog = new Blog();	// model erstellen
          $ba_blog_array = $_POST["ba_blog"];

          $ba_id = $ba_blog_array["ba_id"];
          $ba_userid = $ba_blog_array["ba_userid"];

          // überflüssige leerzeichen entfernen
          $ba_datetime = trim($ba_blog_array["ba_datetime"]);
          $ba_header = trim($ba_blog_array["ba_header"]);
          $ba_intro = trim($ba_blog_array["ba_intro"]);
          $ba_text = trim($ba_blog_array["ba_text"]);
          $ba_videoid = trim($ba_blog_array["ba_videoid"]);
          $ba_photoid = trim($ba_blog_array["ba_photoid"]);
          $ba_tags = trim($ba_blog_array["ba_tags"]);

          // str zu int
          $ba_catid = intval($ba_blog_array["ba_catid"]);
          $ba_state = intval($ba_blog_array["ba_state"]);

          // zeichen limit
          if (strlen($ba_datetime) > MAXLEN_DATETIME) {
            $ba_datetime = substr($ba_datetime, 0, MAXLEN_DATETIME);
          }
          if (strlen($ba_header) > MAXLEN_BLOGHEADER) {
            $ba_header = substr($ba_header, 0, MAXLEN_BLOGHEADER);
          }
          if (strlen($ba_intro) > MAXLEN_BLOGINTRO) {
            $ba_intro = substr($ba_intro, 0, MAXLEN_BLOGINTRO);
          }
          if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_BLOGTEXT) {
            $ba_text = mb_substr($ba_text, 0, MAXLEN_BLOGTEXT, MB_ENCODING);
          }
          if (strlen($ba_videoid) > MAXLEN_BLOGVIDEOID) {
            $ba_videoid = substr($ba_videoid, 0, MAXLEN_BLOGVIDEOID);
          }
          if (strlen($ba_photoid) > MAXLEN_BLOGPHOTOID) {
            $ba_photoid = substr($ba_photoid, 0, MAXLEN_BLOGPHOTOID);
          }
          if (mb_strlen($ba_tags, MB_ENCODING) > MAXLEN_BLOGTAGS) {
            $ba_tags = mb_substr($ba_tags, 0, MAXLEN_BLOGTAGS, MB_ENCODING);
          }

          // nur definierte states, sonst 0 (STATE_CREATED)
          $defined_states = array(STATE_CREATED, STATE_EDITED, STATE_APPROVAL, STATE_PUBLISHED);
          if (!in_array($ba_state, $defined_states)) {
            $ba_state = STATE_CREATED;
          }

          $ba_delete = in_array("delete", $ba_blog_array);	// in array nach string suchen

          $ret = $model_blog->postBlog($ba_id, $ba_userid, $ba_datetime, $ba_header, $ba_intro, $ba_text, $ba_videoid, $ba_photoid, $ba_catid, $ba_tags, $ba_state, $ba_delete);	// daten für blog in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_blog[]

// *****************************************************************************
// *** backend POST blogroll (neu) ***
// *****************************************************************************

        elseif (isset($_POST["ba_blogroll_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_blogroll_new[feed]

          $model_blog = new Blog();	// model erstellen
          $ba_blogroll_new_array = $_POST["ba_blogroll_new"];

          // überflüssige leerzeichen entfernen
          $feed = trim($ba_blogroll_new_array["feed"]);

          // zeichen limit
          if (strlen($feed) > MAXLEN_FEED) {
            $feed = substr($feed, 0, MAXLEN_FEED);
          }

          $ret = $model_blog->postBlogrollNew($feed);	// daten für blogroll (neu) in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_blogroll_new[]

// *****************************************************************************
// *** backend POST blogroll ***
// *****************************************************************************

        elseif (isset($_POST["ba_blogroll"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_blogroll[id]

          $model_blog = new Blog();	// model erstellen
          $ba_blogroll_array = $_POST["ba_blogroll"];
          $ret = $model_blog->postBlogroll($ba_blogroll_array);	// daten für blogroll in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_blogroll[]

// *****************************************************************************
// *** backend POST blogcategory (neu) ***
// *****************************************************************************

        elseif (isset($_POST["ba_blogcategory_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_blogcategory_new[category]

          $model_blog = new Blog();	// model erstellen
          $ba_blogcategory_new_array = $_POST["ba_blogcategory_new"];

          // überflüssige leerzeichen entfernen
          $category = trim($ba_blogcategory_new_array["category"]);

          // zeichen limit
          if (strlen($category) > MAXLEN_BLOGCATEGORY) {
            $category = substr($category, 0, MAXLEN_BLOGCATEGORY);
          }

          $ret = $model_blog->postBlogcategoryNew($category);	// daten für blogcategory (neu) in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_blogcategory_new[]

// *****************************************************************************
// *** backend POST blogcategory ***
// *****************************************************************************

        elseif (isset($_POST["ba_blogcategory"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_blogcategory[id]

          $model_blog = new Blog();	// model erstellen
          $ba_blogcategory_array = $_POST["ba_blogcategory"];
          $ret = $model_blog->postBlogcategory($ba_blogcategory_array);	// daten für blogcategory in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_blogcategory[]

// *****************************************************************************
// *** backend POST options ***
// *****************************************************************************

        elseif (isset($_POST["ba_options"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
          // ba_options[ba_name][ba_value, "str_flag"]

          $model_blog = new Blog();	// model erstellen
          $ba_options_array = $_POST["ba_options"];
          $ba_options_array_replaced = array();

          foreach ($ba_options_array as $ba_name => $ba_array) {

            // überflüssige leerzeichen entfernen, str zu int
            $ba_name = trim($ba_name);
            $str_flag = in_array("str_flag", $ba_array);
            if ($str_flag) {
              $ba_value = trim($ba_array["ba_value"]);
            }
            else {
              $ba_value = intval($ba_array["ba_value"]);
            }

            $ba_options_array_replaced[$ba_name] = array("ba_value" => $ba_value, "str_flag" => $str_flag);
          }

          $ret = $model_blog->postOptions($ba_options_array_replaced);	// daten für options in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_options[ba_name][ba_value]

// *****************************************************************************
// *** backend POST comment ***
// *****************************************************************************

        elseif (isset($_POST["ba_comment"]) and ($_SESSION["user_role"] >= ROLE_MASTER)) {
          // ba_comment[ba_id, ba_userid, ba_datetime, ba_ip, ba_name, ba_mail, ba_text, ba_comment, ba_blogid, ba_state, "delete"]
          // ba_id == 0 -> neuer kommentar eintrag
          // ba_id == 0xffff -> error

          $model_comment = new Comment();	// model erstellen
          $ba_comment_array = $_POST["ba_comment"];

          $ba_id = $ba_comment_array["ba_id"];
          $ba_userid = $ba_comment_array["ba_userid"];

          // überflüssige leerzeichen entfernen, str zu int
          $ba_datetime = trim($ba_comment_array["ba_datetime"]);
          $ba_ip = trim($ba_comment_array["ba_ip"]);
          $ba_name = trim($ba_comment_array["ba_name"]);
          $ba_mail = trim($ba_comment_array["ba_mail"]);
          $ba_text = trim($ba_comment_array["ba_text"]);
          $ba_comment = trim($ba_comment_array["ba_comment"]);
          $ba_blogid = intval($ba_comment_array["ba_blogid"]);
          $ba_state = intval($ba_comment_array["ba_state"]);

          // zeichen limit
          if (strlen($ba_datetime) > MAXLEN_DATETIME) {
            $ba_datetime = substr($ba_datetime, 0, MAXLEN_DATETIME);
          }
          if (strlen($ba_ip) > MAXLEN_COMMENTIP) {
            $ba_ip = substr($ba_ip, 0, MAXLEN_COMMENTIP);
          }
          if (mb_strlen($ba_name, MB_ENCODING) > MAXLEN_COMMENTNAME) {
            $ba_name = mb_substr($ba_name, 0, MAXLEN_COMMENTNAME, MB_ENCODING);
          }
          if (strlen($ba_mail) > MAXLEN_COMMENTMAIL) {
            $ba_mail = substr($ba_mail, 0, MAXLEN_COMMENTMAIL);
          }
          if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_COMMENTTEXT) {
            $ba_text = mb_substr($ba_text, 0, MAXLEN_COMMENTTEXT, MB_ENCODING);
          }
          if (mb_strlen($ba_comment, MB_ENCODING) > MAXLEN_COMMENTCOMMENT) {
            $ba_comment = mb_substr($ba_comment, 0, MAXLEN_COMMENTCOMMENT, MB_ENCODING);
          }

          // nur definierte states, sonst 0 (STATE_CREATED)
          $defined_states = array(STATE_CREATED, STATE_EDITED, STATE_APPROVAL, STATE_PUBLISHED);
          if (!in_array($ba_state, $defined_states)) {
            $ba_state = STATE_CREATED;
          }

          $ba_delete = in_array("delete", $ba_comment_array);	// in array nach string suchen

          $ret = $model_comment->postComment($ba_id, $ba_userid, $ba_datetime, $ba_ip, $ba_name, $ba_mail, $ba_text, $ba_comment, $ba_blogid, $ba_state, $ba_delete);	// daten für comment in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_comment[]

// *****************************************************************************
// *** backend POST upload ***
// *****************************************************************************

        elseif (isset($_FILES["upfile"]["tmp_name"]) and ($_FILES["upfile"]["type"]=="image/jpeg" or $_FILES["upfile"]["type"]=="video/mp4") and $_FILES["upfile"]["size"] < MAXSIZE_FILEUPLOAD and ($_SESSION["user_role"] >= ROLE_MASTER)) {
          // nur *.jpg oder *.mp4, 2 MB default

          $model_upload = new Upload();	// model erstellen
          $ret = $model_upload->postUpload($_FILES["upfile"]["tmp_name"], $_FILES["upfile"]["name"]);	// daten für upload in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        }

// *****************************************************************************
// *** backend POST languages (neu) ***
// *****************************************************************************

        elseif (isset($_POST["ba_languages_new"]) and ($_SESSION["user_role"] >= ROLE_MASTER)) {
          // ba_languages_new[locale]

          $model_languages = new Languages();	// model erstellen
          $ba_languages_new_array = $_POST["ba_languages_new"];

          // überflüssige leerzeichen entfernen
          $locale = trim($ba_languages_new_array["locale"]);

          // zeichen limit
          if (strlen($locale) > MAXLEN_LOCALE) {
            $locale = substr($locale, 0, MAXLEN_LOCALE);
          }

          $ret = $model_languages->postLanguagesNew($locale, 0);	// daten für blogroll (neu) in das model, 2.parameter "selected" immer 0
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_languages_new[]

// *****************************************************************************
// *** backend POST languages ***
// *****************************************************************************

        elseif (isset($_POST["ba_languages"]) and ($_SESSION["user_role"] >= ROLE_MASTER)) {
          // ba_languages[ba_id][ba_locale]	MAXLEN_LOCALE
          // ba_languages[ba_id]["delete"]
          // ba_languages[ba_selected][ba_id]	radio button für alle ba_id (ba_id als value)

          $model_languages = new Languages();	// model erstellen
          $ba_languages_array = $_POST["ba_languages"];
          $ba_languages_array_replaced = array();

          $ba_id_selected = intval($ba_languages_array["ba_selected"]);	// str zu int
          unset($ba_languages_array["ba_selected"]);	// nur ba_id in array

          foreach ($ba_languages_array as $ba_id => $ba_array) {
            // überflüssige leerzeichen entfernen, str zu int
            $ba_id = intval($ba_id);
            $ba_locale = trim($ba_array["ba_locale"]);

            // zeichen limit
            if (strlen($ba_locale) > MAXLEN_LOCALE) {
              $ba_locale = substr($ba_locale, 0, MAXLEN_LOCALE);
            }

            // nur 0 oder 1, sonst 0
            if ($ba_id == $ba_id_selected) {
              $ba_selected = 1;
            }
            else {
              $ba_selected = 0;
            }

            $delete = in_array("delete", $ba_array);	// in array nach string suchen

            $ba_languages_array_replaced[$ba_id] = array("ba_locale" => $ba_locale, "ba_selected" => $ba_selected, "delete" => $delete);
          }

          $ret = $model_languages->postLanguages($ba_languages_array_replaced);	// daten für languages in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_languages[ba_id][ba_locale]

// *****************************************************************************
// *** backend POST admin (neu) ***
// *****************************************************************************

        elseif (isset($_POST["ba_admin_new"]) and ($_SESSION["user_role"] >= ROLE_ADMIN)) {
          // ba_admin_new[user]
          // ba_admin_new[email]
          // ba_admin_new[full_name]
          // ba_admin_new[locale]
          // ba_admin_new[role]

          $model_admin = new Admin();	// model erstellen
          $ba_admin_new_array = $_POST["ba_admin_new"];

          // überflüssige leerzeichen entfernen, str zu int
          $user = trim($ba_admin_new_array["user"]);
          $email = trim($ba_admin_new_array["email"]);
          $full_name = trim($ba_admin_new_array["full_name"]);
          $locale = trim($ba_admin_new_array["locale"]);
          $role = intval($ba_admin_new_array["role"]);

          // zeichen limit
          if (mb_strlen($user, MB_ENCODING) > MAXLEN_USER) {
            $user = mb_substr($user, 0, MAXLEN_USER, MB_ENCODING);
          }
          if (strlen($email) > MAXLEN_EMAIL) {
            $email = substr($email, 0, MAXLEN_EMAIL);
          }
          if (strlen($full_name) > MAXLEN_FULLNAME) {
            $full_name = substr($full_name, 0, MAXLEN_FULLNAME);
          }
          if (strlen($locale) > MAXLEN_LOCALE) {
            $locale = substr($locale, 0, MAXLEN_LOCALE);
          }

          // größe limit
          if ($role > ROLE_ADMIN or $role < ROLE_NONE) {
            $role = ROLE_NONE;
          }

          // automatisches passwort für email benachrichtigung
          $tmp_password = $this->gen_password(8);

          $ret = $model_admin->postAdminNew($user, $email, $full_name, $locale, $role, $tmp_password);	// daten für admin (neu) in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_admin_new[]

// *****************************************************************************
// *** backend POST admin ***
// *****************************************************************************

        elseif (isset($_POST["ba_admin"]) and ($_SESSION["user_role"] >= ROLE_ADMIN)) {
          // ba_admin[id][0] = role
          // ba_admin[id][1] = "delete"

          $model_admin = new Admin();	// model erstellen
          $ba_admin_array = $_POST["ba_admin"];
          $ba_admin_array_replaced = array();

          foreach ($ba_admin_array as $id => $ba_array) {
            $role = intval($ba_array[0]);	// str zu int

            // größe limit
            if ($role > ROLE_ADMIN or $role < ROLE_NONE) {
              $role = ROLE_NONE;
            }

            $delete = in_array("delete", $ba_array);	// in array nach string suchen

            $ba_admin_array_replaced[$id] = array($role, $delete);
          }

          $ret = $model_admin->postAdmin($ba_admin_array_replaced);	// daten für admin in das model
          $html_backend_ext .= $ret["content"];
          $errorstring = $ret["error"];

        } // ba_admin[id][]

        else {
          $errorstring = "POST error\n";
        }

      } // else login

    } // no panic

    // setze inhalt, falls string vorhanden, sonst leer
    $view->setContent("content", isset($html_backend_ext) ? $html_backend_ext : "");
    $view->setContent("error", isset($errorstring) ? $errorstring : "");
    $view->setContent("debug", $debug_str);

    return $view->parseTemplate(true);	// ausgabe geändertes template, mit backend flag
  }

}

?>
