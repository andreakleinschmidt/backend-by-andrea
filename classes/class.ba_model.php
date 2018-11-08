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
// * ba_model
// * - html bausteine
// * - daten aus datenbank holen
// * - daten aufbereiten
// * - daten an controller zurückgeben
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXLEN_USER",32);	// login form
//define("MAXLEN_PASSWORD",32);	// login form
//define("MAXLEN_CODE",8);	// login form
//define("MAXLEN_SHAREDSECRET",64);
//define("ROLE_NONE",0);
//define("ROLE_EDITOR",1);
//define("ROLE_MASTER",2);
//define("ROLE_ADMIN",3);
//define("DEFAULT_LOCALE","de-DE");	// "de-DE" oder "en-US"

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 2a - stmt bei login user/password
// db error 2b - stmt bei backend POST password
// db error 2c - stmt bei login timestamp

class Model {

  public $database;
  public $language;

  // konstruktor
  public function __construct() {
    // datenbank:
    $this->database = @new Database();	// @ unterdrückt fehlermeldung
    if (!$this->database->connect_errno) {
      // wenn kein fehler
      $this->database->set_charset("utf8");	// change character set to utf8
    }
    // language:
    if (!isset($_SESSION["language"])) {
      // falls session variable noch nicht existiert
      $this->language = $this->readLanguage();
      $_SESSION["language"] = $this->language;	// in SESSION speichern
    } // neue session variable
    elseif ($_SESSION["language"]["locale"] != $_SESSION["user_locale"]) {
      // session variable abweichend zur locale vom benutzer
      $this->language = $this->readLanguage();
      $_SESSION["language"] = $this->language;	// SESSION überschreiben
    }
    else {
      // alte session variable
      $this->language = $_SESSION["language"];	// aus SESSION lesen
    }
  }

  // mit simplexml language.xml laden als array key->value
  private function readLanguage() {
    $language = array();

    // locale für backend
    if (isset($_SESSION["user_locale"])) {
      $locale = $_SESSION["user_locale"];
    }
    else {
      $locale = DEFAULT_LOCALE;
    }

    $path = "languages/";
    $filename = $path."language_".$locale.".xml";
    if (file_exists($filename)) {
      $xml_content = file_get_contents($filename);
    }
    else {
      $xml_content = "";
    }

    if ($xml = @simplexml_load_string($xml_content)) {
      if ($xml->getName() == "language" and $xml->attributes()->tag == $locale) {
        // xml language file
        $language["locale"] = $locale;
        foreach ($xml->children() as $child) {
          $key = $child->getName();
          $value = (string)$child->attributes()->text;
          $language[$key] = $value;
        } // foreach child
      } // xml language file
    } // if xml

    return $language;
  }

  // return base32 encoded string (RFC4648)
  public function base32_encode($input_str) {
    $ret = "";
    // old
    $base32_map = array("A","B","C","D","E","F","G","H",
                        "I","J","K","L","M","N","O","P",
                        "Q","R","S","T","U","V","W","X",
                        "Y","Z","2","3","4","5","6","7");
    // new (hex)
    //$base32_map = array("0","1","2","3","4","5","6","7",
    //                    "8","9","A","B","C","D","E","F",
    //                    "G","H","I","J","K","L","M","N",
    //                    "O","P","Q","R","S","T","U","V");

    $bytes = unpack("C*", $input_str);
    $five_byte_blocks = array_chunk($bytes, 5);
    $five_bit_groups = array();
    foreach ($five_byte_blocks as $five_byte_block) {
      $padding = 0.0;
      while (sizeof($five_byte_block) < 5) {
        // block auffüllen
        $five_byte_block[] = 0;
        $padding += 1.6;	// 8.0/5.0
      }

      $five_bit_group = array();
      $i = 0;
      $marker = 8;
      while ($i<sizeof($five_byte_block)) {
        if ($marker > 5) {
          $five_bit_group[] = (($five_byte_block[$i] << (8 - $marker)) & 0xff) >> 3;
          $marker -= 5;
        }
        elseif ($marker == 5) {
          $five_bit_group[] = (($five_byte_block[$i] << (8 - $marker)) & 0xff) >> 3;
          $i++;
        }
        else { // $marker < 5
          $part1 = (($five_byte_block[$i] << (8 - $marker)) & 0xff) >> 3;
          $part2 = $five_byte_block[$i+1] >> (8 - (5 - $marker));
          $five_bit_group[] = $part1 | $part2;
          $marker += 3;
          $i++;
        }
      }

      $part_str = "";
      foreach ($five_bit_group as $five_bit) {
        $part_str .= $base32_map[$five_bit];	// mapping
      }
      $replace_str = str_repeat("=", intval($padding));
      $ret .= substr_replace($part_str, $replace_str, -$padding, $padding);	// overriding
    }

    return $ret;
  }

// *****************************************************************************
// *** html ***
// *****************************************************************************

  public function html_form() {
    $html_form = "<form name=\"pwd_form\" action=\"backend.php\" method=\"post\">\n".
                 "<table class=\"backend\">\n".
                 "<tr>\n<td class=\"td_backend\">".
                 $this->language["PROMPT_USER"].
                 "</td>\n<td>".
                 "<input type=\"text\" name=\"user_login\" class=\"size_16\" maxlength=\"".MAXLEN_USER."\" />".
                 "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                 $this->language["PROMPT_PASSWORD"].
                 "</td>\n<td>".
                 "<input type=\"password\" name=\"password\" class=\"size_16\" maxlength=\"".MAXLEN_PASSWORD."\" />".
                 "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                 "</td>\n<td>".
                 "<input type=\"submit\" value=\"".$this->language["BUTTON_SEND"]."\" />".
                 "</td>\n</tr>\n".
                 "</table>\n".
                 "</form>\n\n";
    return $html_form;
  }

  public function html_form_2fa() {
    $html_form = "<form name=\"pwd_form\" action=\"backend.php\" method=\"post\">\n".
                 "<table class=\"backend\">\n".
                 "<tr>\n<td class=\"td_backend\">".
                 $this->language["PROMPT_CODE"].
                 "</td>\n<td>".
                 "<input type=\"text\" name=\"code\" class=\"size_16\" maxlength=\"".MAXLEN_CODE."\" />".
                 "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                 "</td>\n<td>".
                 "<input type=\"submit\" value=\"".$this->language["BUTTON_SEND"]."\" />".
                 "</td>\n</tr>\n".
                 "</table>\n".
                 "</form>\n\n";
    return $html_form;
  }

  // passwort ändern formular
  // - alt (zur überprüfung)
  // - neu
  // - neu2
  public function password_form($section_start=false, $section_end=false) {
    $section_start_str = $section_start ? "<section>\n\n" : "";
    $section_end_str = $section_end ? "</section>\n\n" : "";
    $password_form = $section_start_str.
                     "<p><b>".$this->language["HEADER_PASSWORD"]."</b></p>\n".
                     "<form name=\"pwd_form\" action=\"backend.php\" method=\"post\">\n".
                     "<table class=\"backend\">\n".
                     "<tr>\n<td class=\"td_backend\">".
                     $this->language["PROMPT_PASSWORD"].
                     "</td>\n<td>".
                     "<input type=\"password\" name=\"password\" class=\"size_16\" maxlength=\"".MAXLEN_PASSWORD."\" />".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                     $this->language["PROMPT_PASSWORD_NEW_1"].
                     "</td>\n<td>".
                     "<input type=\"password\" name=\"password_new1\" class=\"size_16\" maxlength=\"".MAXLEN_PASSWORD."\" />".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                     $this->language["PROMPT_PASSWORD_NEW_2"].
                     "</td>\n<td>".
                     "<input type=\"password\" name=\"password_new2\" class=\"size_16\" maxlength=\"".MAXLEN_PASSWORD."\" />".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                     "</td>\n<td>".
                     "<input type=\"submit\" value=\"".$this->language["BUTTON_SEND"]."\" />".
                     "</td>\n</tr>\n".
                     "</table>\n".
                     "</form>\n\n".
                     $section_end_str;
    return $password_form;
  }

  // zwei-faktor-authentifizierung formular
  // - shared secret
  // - use_2fa (an/aus)
  public function twofa_form($base64_secret, $use_2fa, $section_start=false, $section_end=false) {
    $section_start_str = $section_start ? "<section>\n\n" : "";
    $section_end_str = $section_end ? "</section>\n\n" : "";
    $shared_secret = base64_decode($base64_secret);
    $base32_secret = $this->base32_encode($shared_secret); // für qrcode
    $twofa_form = $section_start_str.
                  "<p><b>".$this->language["HEADER_2FA"]."</b></p>\n".
                  "<form name=\"twofa_form\" action=\"backend.php\" method=\"post\">\n".
                  "<table class=\"backend\">\n".
                  "<tr>\n<td class=\"td_backend\">".
                  $this->language["PROMPT_SHARED_SECRET"].
                  "</td>\n<td>\n".
                  "<input type=\"hidden\" name=\"base64_secret\" value=\"".$base64_secret."\"/>\n".
                  "<input type=\"text\" name=\"base32_secret\" class=\"size_16\" maxlength=\"".MAXLEN_SHAREDSECRET."\" value=\"".stripslashes($this->html5specialchars($base32_secret))."\" readonly=\"readonly\" />\n".
                  "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                  $this->language["PROMPT_QRCODE"].
                  "</td>\n<td>".
                  "<img src=\"qrcode.php?data=".$base32_secret."\" alt=\"".$base32_secret."\">".
                  "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                  $this->language["PROMPT_REQUEST_NEW_SECRET"].
                  "</td>\n<td>".
                  "<input type=\"checkbox\" name=\"request_new_secret\" value=\"yes\" />".
                  "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                  $this->language["PROMPT_USE_2FA"].
                  "</td>\n<td>".
                  "<input type=\"checkbox\" name=\"use_2fa\" value=\"yes\"";
    if ($use_2fa > 0) {
      $twofa_form .= " checked=\"checked\"";
                }
    $twofa_form .= " />".
                   "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                   "</td>\n<td>".
                   "<input type=\"submit\" value=\"".$this->language["BUTTON_SEND"]."\" />".
                   "</td>\n</tr>\n".
                   "</table>\n".
                   "</form>\n\n".
                   $section_end_str;
    return $twofa_form;
  }

// *****************************************************************************
// *** funktionen ***
// *****************************************************************************

  // wrapper htmlspecialchars()
  public function html5specialchars($str) {
    return htmlspecialchars($str, ENT_COMPAT | ENT_HTML5, "UTF-8");
  }

  // input array("x" => "y z", "a" => "b")
  // return query "x=y%20z&amp;a=123" ("x=y z&a=b")
  public function html_build_query($query_data) {
    return http_build_query($query_data, "", "&amp;", PHP_QUERY_RFC3986);
  }

  // echo $html_backend individuell mit user_name und links nach user_role
  public function html_backend($user_role, $user_name) {
    $roles = array(ROLE_NONE => $this->language["ROLE_NONE"], ROLE_EDITOR => $this->language["ROLE_EDITOR"], ROLE_MASTER => $this->language["ROLE_MASTER"], ROLE_ADMIN => $this->language["ROLE_ADMIN"]);
    $ret = "<nav class=\"sticky\">\n".
           "<ul>\n".
           "<li><span>".stripslashes($this->html5specialchars($user_name))." (".$roles[$user_role].")</span></li>\n".
           "<li><a href=\"backend.php?action=password\">".$this->language["HEADER_PASSWORD"]."</a></li>\n".
           "<li><a href=\"backend.php?action=logout\">".$this->language["HEADER_LOGOUT"]."</a></li>\n".
           "</ul>\n".
           "</nav>\n\n";

    $ret .= "<aside class=\"sticky\">\n".
            "<pre>backend\n    by andrea</pre>\n".
            "<details class=\"details_backend\">\n".
            "<summary><a href=\"backend.php\">".$this->language["HEADER_BACKEND"]."</a></summary>\n".
            "</details>\n";
    if ($user_role >= ROLE_EDITOR) {
      $ret .= "<details class=\"details_backend\">\n".
              "<summary><a href=\"backend.php?action=base\">".$this->language["HEADER_BASE"]."</a></summary>\n".
              "<ul>\n".
              "<li><a href=\"backend.php?action=base#base\">".$this->language["HEADER_BASE"]."</a></li>\n".
              "</ul>\n".
              "</details>\n";
    }
    if ($user_role >= ROLE_EDITOR) {
      $ret .= "<details class=\"details_backend\">\n".
              "<summary><a href=\"backend.php?action=home\">".$this->language["HEADER_HOME"]."</a></summary>\n".
              "<ul>\n".
              "<li><a href=\"backend.php?action=home#home\">".$this->language["HEADER_HOME"]."</a></li>\n".
              "<li><a href=\"backend.php?action=home#elements\">".$this->language["HEADER_ELEMENTS"]."</a></li>\n".
              "</ul>\n".
              "</details>\n";
    }
    if ($user_role >= ROLE_EDITOR) {
      $ret .= "<details class=\"details_backend\">\n".
              "<summary><a href=\"backend.php?action=profile\">".$this->language["HEADER_PROFILE"]."</a></summary>\n".
              "<ul>\n".
              "<li><a href=\"backend.php?action=profile#profile\">".$this->language["HEADER_PROFILE"]."</a></li>\n".
              "<li><a href=\"backend.php?action=profile#tables\">".$this->language["HEADER_TABLES"]."</a></li>\n".
              "<li><a href=\"backend.php?action=profile#elements\">".$this->language["HEADER_ELEMENTS"]."</a></li>\n".
              "</ul>\n".
              "</details>\n";
    }
    if ($user_role >= ROLE_EDITOR) {
      $ret .= "<details class=\"details_backend\">\n".
              "<summary><a href=\"backend.php?action=photos\">".$this->language["HEADER_PHOTOS"]."</a></summary>\n".
              "<ul>\n".
              "<li><a href=\"backend.php?action=photos#gallery\">".$this->language["HEADER_GALLERY"]."</a></li>\n".
              "<li><a href=\"backend.php?action=photos#photos\">".$this->language["HEADER_PHOTOS"]."</a></li>\n".
              "</ul>\n".
              "</details>\n";
    }
    if ($user_role >= ROLE_EDITOR) {
      $ret .= "<details class=\"details_backend\">\n".
              "<summary><a href=\"backend.php?action=blog\">".$this->language["HEADER_BLOG"]."</a></summary>\n".
              "<ul>\n".
              "<li><a href=\"backend.php?action=blog#blog\">".$this->language["HEADER_BLOG_NEW"]."</a></li>\n".
              "<li><a href=\"backend.php?action=blog#bloglist\">".$this->language["HEADER_BLOG_LIST"]."</a></li>\n".
              "<li><a href=\"backend.php?action=blog#blogroll\">".$this->language["HEADER_BLOGROLL"]."</a></li>\n".
              "<li><a href=\"backend.php?action=blog#categories\">".$this->language["HEADER_CATEGORIES"]."</a></li>\n".
              "<li><a href=\"backend.php?action=blog#options\">".$this->language["HEADER_OPTIONS"]."</a></li>\n".
              "</ul>\n".
              "</details>\n";
    }
    if ($user_role >= ROLE_MASTER) {
      $ret .= "<details class=\"details_backend\">\n".
              "<summary><a href=\"backend.php?action=comment\">".$this->language["HEADER_COMMENT"]."</a></summary>\n".
              "<ul>\n".
              "<li><a href=\"backend.php?action=comment#comment\">".$this->language["HEADER_COMMENT_NEW"]."</a></li>\n".
              "<li><a href=\"backend.php?action=comment#commentlist\">".$this->language["HEADER_COMMENT_LIST"]."</a></li>\n".
              "</ul>\n".
              "</details>\n";
    }
    if ($user_role >= ROLE_MASTER) {
      $ret .= "<details class=\"details_backend\">\n".
              "<summary><a href=\"backend.php?action=upload\">".$this->language["HEADER_UPLOAD"]."</a></summary>\n".
              "<ul>\n".
              "<li><a href=\"backend.php?action=upload#upload\">".$this->language["HEADER_UPLOAD"]."</a></li>\n".
              "<li><a href=\"backend.php?action=upload#media\">".$this->language["HEADER_MEDIA"]."</a></li>\n".
              "</ul>\n".
              "</details>\n";
    }
    if ($user_role >= ROLE_MASTER) {
      $ret .= "<details class=\"details_backend\">\n".
              "<summary><a href=\"backend.php?action=lang\">".$this->language["HEADER_LANGUAGES"]."</a></summary>\n".
              "</details>\n";
    }
    if ($user_role >= ROLE_ADMIN) {
      $ret .= "<details class=\"details_backend\">\n".
              "<summary><a href=\"backend.php?action=admin\">".$this->language["HEADER_ADMINISTRATION"]."</a></summary>\n".
              "<ul>\n".
              "<li><a href=\"backend.php?action=admin#admin\">".$this->language["HEADER_ADMINISTRATION"]."</a></li>\n".
              "<li><a href=\"backend.php?action=admin#new_user\">".$this->language["HEADER_NEW_USER"]."</a></li>\n".
              "</ul>\n".
              "</details>\n";
    }
    $ret .= "</aside>\n\n";

    return $ret;
  }

  // vergleich mit datenbank (passwort in datenbank ist blowfish hash mit random salt)
  public function check_user($user_login) {
    $check = false;
    $errorstring = "";

    $user_id = 0;
    $user_role = 0;
    $password_hash = "";
    $locale = "";
    $use_2fa = 0;
    $last_code = 0;
    $base64_secret = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE backend (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                role TINYINT UNSIGNED NOT NULL,
      //                user VARCHAR(32) NOT NULL,
      //                password VARCHAR(32) NOT NULL,
      //                email VARCHAR(64) NOT NULL,
      //                full_name VARCHAR(64) NOT NULL,
      //                locale VARCHAR(8) NOT NULL,
      //                last_login DATETIME NOT NULL,
      //                use_2fa TINYINT UNSIGNED NOT NULL,
      //                last_code INT UNSIGNED NOT NULL),
      //                base64_secret VARBINARY(64) NOT NULL;

      // mit prepare() - sql injections verhindern
      $sql = "SELECT id, role, user, password, locale, use_2fa, last_code, AES_DECRYPT(base64_secret, UNHEX('".Database::$AES_KEY."')) AS base64_secret FROM backend WHERE user = ?";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 2a

        // austauschen ? durch user string (s)
        $stmt->bind_param("s", $user_login);
        $stmt->execute();	// ausführen geänderte zeile
        $stmt->store_result();	// sonst $database->error "Commands out of sync; you can't run this command now" bei stmt_opt

        $stmt->bind_result($user_id, $user_role, $user_login, $password_hash, $user_locale, $use_2fa, $last_code, $base64_secret);	// ausgabe in $user_id, user_role, $user_login, $password_hash, $use_2fa, $last_code, $base64_secret
        // fetch liefert wert oder NULL (user aus SELECT stimmt nicht überein)
        if ($stmt->fetch()) {
          // SELECT lieferte wert, user stimmt

          $check = true;

        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .=  "db error 2a\n";
      }

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("check" => $check, "user_id" => $user_id, "user_role" => $user_role, "user_login" => $user_login, "user_locale" => $user_locale, "password_hash" => $password_hash, "use_2fa" => $use_2fa, "last_code" => $last_code, "base64_secret" => $base64_secret, "error" => $errorstring);
  }

  // login zeitstempel
  public function timestamp($code) {
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank
      // mit prepare() - sql injections verhindern
      $sql_ts = "UPDATE backend SET last_login = NOW(), last_code = ? WHERE id = ?";
      $stmt_ts = $this->database->prepare($sql_ts);	// liefert mysqli-statement-objekt
      if ($stmt_ts) {
        // wenn kein fehler 2c

        // austauschen ?? durch code und user_id aus session variable (ii)
        $stmt_ts->bind_param("ii", $code, $_SESSION["user_id"]);
        $stmt_ts->execute();	// ausführen geänderte zeile
        $stmt_ts->close();

      } // stmt_ts

      else {
        $errorstring .=  "db error 2c\n";	// db error 2c - stmt bei login timestamp
      }

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("error" => $errorstring);
  }

  // vergleich mit datenbank (passwort in datenbank ist blowfish hash mit random salt)
  public function check_password() {
    $check = false;
    $errorstring = "";

    $password_hash = 0;

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // mit prepare() - sql injections verhindern
      $sql_select = "SELECT password FROM backend WHERE id = ?";
      $stmt_select = $this->database->prepare($sql_select);	// liefert mysqli-statement-objekt
      if ($stmt_select) {
        // wenn kein fehler 2b1

        // austauschen ? durch id int (i)
        $stmt_select->bind_param("i", $_SESSION["user_id"]);
        $stmt_select->execute();	// ausführen geänderte zeile
        $stmt_select->store_result();	// sonst $database->error "Commands out of sync; you can't run this command now" bei stmt_opt

        $stmt_select->bind_result($password_hash);	// ausgabe in $password_hash
        // fetch liefert wert oder NULL
        if ($stmt_select->fetch()) {
          // SELECT lieferte wert

          $check = true;

        } // fetch ok

        else {
          $errorstring .= "id error\n";
        }

        $stmt_select->close();

      } // stmt_select

      else {
        $errorstring .= "db error 2b1\n";
      }

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("check" => $check, "password_hash" => $password_hash, "error" => $errorstring);
  }

  // update in datenbank (passwort in datenbank ist blowfish hash mit random salt)
  public function update_password($password_new_hash) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // mit prepare() - sql injections verhindern
      $sql_update = "UPDATE backend SET password = ? WHERE id = ?";
      $stmt_update = $this->database->prepare($sql_update);	// liefert mysqli-statement-objekt
      if ($stmt_update) {
        // wenn kein fehler 2b2

        // austauschen ?? durch password_hash string (s) und int (i)
        $stmt_update->bind_param("si", $password_new_hash, $_SESSION["user_id"]);
        $stmt_update->execute();	// ausführen geänderte zeile

        if ($stmt_update->affected_rows == 1) {
          $html_backend_ext .= "<p>".$this->language["MSG_DONE"]."</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>".$this->language["MSG_NO_CHANGE"]."</p>\n\n";
        }

        $stmt_update->close();

      } // stmt_select

      else {
        $errorstring .= "db error 2b2\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  // use_2fa und base64_secret aus datenbank
  public function getTwofa() {
    $result = false;
    $errorstring = "";

    $use_2fa = 0;
    $base64_secret = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // mit prepare() - sql injections verhindern
      $sql_select = "SELECT use_2fa, AES_DECRYPT(base64_secret, UNHEX('".Database::$AES_KEY."')) AS base64_secret FROM backend WHERE id = ?";
      $stmt_select = $this->database->prepare($sql_select);	// liefert mysqli-statement-objekt
      if ($stmt_select) {
        // wenn kein fehler 2b3

        // austauschen ? durch id int (i)
        $stmt_select->bind_param("i", $_SESSION["user_id"]);
        $stmt_select->execute();	// ausführen geänderte zeile
        $stmt_select->store_result();	// sonst $database->error "Commands out of sync; you can't run this command now" bei stmt_opt

        $stmt_select->bind_result($use_2fa, $base64_secret);	// ausgabe in $use_2fa, $base64_secret
        // fetch liefert wert oder NULL
        if ($stmt_select->fetch()) {

          $result = true;

        } // fetch ok


        else {
          $errorstring .= "id error\n";
        }

        $stmt_select->close();

      } // stmt_select

      else {
        $errorstring .= "db error 2b3\n";
      }

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("result" => $result, "use_2fa" => $use_2fa, "base64_secret" => $base64_secret, "error" => $errorstring);
  }

  // update use_2fa  und base64_secret in datenbank
  public function update_twofa($use_2fa, $base64_secret) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // mit prepare() - sql injections verhindern
      $sql_update = "UPDATE backend SET use_2fa = ?, base64_secret = AES_ENCRYPT(?, UNHEX('".Database::$AES_KEY."')) WHERE id = ?";
      $stmt_update = $this->database->prepare($sql_update);	// liefert mysqli-statement-objekt
      if ($stmt_update) {
        // wenn kein fehler 2b4

        // austauschen ??? durch 2x int (i) und 1x (s)
        $stmt_update->bind_param("isi", $use_2fa, $base64_secret, $_SESSION["user_id"]);
        $stmt_update->execute();	// ausführen geänderte zeile

        if ($stmt_update->affected_rows == 1) {
          $html_backend_ext .= "<p>".$this->language["MSG_DONE"]."</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>".$this->language["MSG_NO_CHANGE"]."</p>\n\n";
        }

        $stmt_update->close();

      } // stmt_select

      else {
        $errorstring .= "db error 2b4\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  // get defined VERSION from version.php
  public function getVersion() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!empty(VERSION)) {
      $html_backend_ext .= "<section>\n\n".
                           "<p><b>".$this->language["HEADER_BACKEND"]."</b></p>\n\n".
                           "<p>".stripslashes($this->html5specialchars(VERSION))."</p>\n\n".
                           "</section>\n\n";
    }
    else {
      $errorstring .= "no version defined\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
