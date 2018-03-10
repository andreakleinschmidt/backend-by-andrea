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
// * ba_model - admin
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXLEN_USER",32);	// admin form
//define("MAXLEN_EMAIL",64);	// admin form
//define("MAXLEN_FULLNAME",64);	// admin form
//define("ROLE_NONE",0);
//define("ROLE_EDITOR",1);
//define("ROLE_MASTER",2);
//define("ROLE_ADMIN",3);

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 3j - ret bei backend GET admin
//
// db error 4g - stmt bei backend POST admin neu
// db error 4h - stmt bei backend POST admin
//
// admin error - bei backend POST admin neu (nicht eingefügt)

class Admin extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

// *****************************************************************************
// *** html ***
// *****************************************************************************

  // user anlegen (user, email, full_name, rolle)
  private function new_user_form($locales) {
    $new_user_form = "<p id=\"new_user\"><b>".$this->language["HEADER_NEW_USER"]."</b></p>\n".
                     "<form action=\"backend.php\" method=\"post\">\n".
                     "<table class=\"backend\">\n".
                     "<tr>\n<td class=\"td_backend\">".
                     $this->language["PROMPT_USER"].
                     "</td>\n<td>".
                     "<input type=\"text\" name=\"ba_admin_new[user]\" class=\"size_32\" maxlength=\"".MAXLEN_USER."\" />".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                     $this->language["PROMPT_MAIL"].
                     "</td>\n<td>".
                     "<input type=\"text\" name=\"ba_admin_new[email]\" class=\"size_32\" maxlength=\"".MAXLEN_EMAIL."\" />".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                     $this->language["PROMPT_FULLNAME"].
                     "</td>\n<td>".
                     "<input type=\"text\" name=\"ba_admin_new[full_name]\" class=\"size_32\" maxlength=\"".MAXLEN_FULLNAME."\" />".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                     $this->language["PROMPT_LOCALE"].
                     "</td>\n<td>\n".
                     "<select name=\"ba_admin_new[locale]\" size=\"1\">\n";
    foreach ($locales as $locale) {
      $new_user_form .= "<option value=\"".$locale."\">".stripslashes($this->html5specialchars($locale))."</option>\n";
    }
    $new_user_form .= "</select>\n".
                      "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                      $this->language["PROMPT_ROLE"].
                      "</td>\n<td>\n".
                      "<select name=\"ba_admin_new[role]\" size=\"1\">\n".
                      "<option value=\"".ROLE_NONE."\">".$this->language["ROLE_NONE"]."</option>\n".
                      "<option value=\"".ROLE_EDITOR."\">".$this->language["ROLE_EDITOR"]."</option>\n".
                      "<option value=\"".ROLE_MASTER."\">".$this->language["ROLE_MASTER"]."</option>\n".
                      "<option value=\"".ROLE_ADMIN."\">".$this->language["ROLE_ADMIN"]."</option>\n".
                      "</select>\n".
                      "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                      "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />".
                      "</td>\n</tr>\n".
                      "</table>\n".
                      "</form>\n\n";
    return $new_user_form;
  }

// *****************************************************************************
// *** funktionen ***
// *****************************************************************************

  private function getLocales() {
    $locales = array();

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank
      $sql = "SELECT ba_locale FROM ba_languages";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler

        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $locales[] = trim($dataset["ba_locale"]);
        }

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }

    } // datenbank

    return $locales;
  }

  public function getAdmin() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

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

      $html_backend_ext .= "<p id=\"admin\"><b>".$this->language["HEADER_ADMINISTRATION"]."</b></p>\n\n";

      // zugriff auf mysql datenbank
      $sql = "SELECT id, role, user, email, full_name, locale, last_login FROM backend";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {

        // anzeigen: user, email, full_name, letzter login, rolle
        // rolle durch auswahlliste änderbar
        // admin auswahlliste für admin ausgegraut
        // user löschen
        $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                             "<table class=\"backend\">\n".
                             "<tr>\n<th>".
                             $this->language["TABLE_HD_ID"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_USER"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_MAIL"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_FULLNAME"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_LOCALE"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_LAST_LOGIN"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_ROLE"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_DELETE"].
                             "</th>\n</tr>\n";
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $html_backend_ext .= "<tr>\n<td class=\"td_backend\">".
                               $dataset["id"].
                               "</td>\n<td>".
                               stripslashes($this->html5specialchars($dataset["user"])).
                               "</td>\n<td>".
                               stripslashes($this->html5specialchars($dataset["email"])).
                               "</td>\n<td>".
                               stripslashes($this->html5specialchars($dataset["full_name"])).
                               "</td>\n<td>".
                               stripslashes($this->html5specialchars($dataset["locale"])).
                               "</td>\n<td>".
                               stripslashes($this->html5specialchars($dataset["last_login"])).
                               "</td>\n<td>\n".
                               "<select name=\"ba_admin[".$dataset["id"]."][0]\" size=\"1\"";
          if (($dataset["id"] == $_SESSION["user_id"]) and ($_SESSION["user_role"] >= ROLE_ADMIN)) {
            // admin kann sich nicht selbst die adminrolle entziehen
            $html_backend_ext .= " disabled=\"disabled\"";
          }
          $html_backend_ext .= ">\n".
                               "<option value=\"".ROLE_NONE."\"";
          if ($dataset["role"] == ROLE_NONE) {
            $html_backend_ext .= " selected=\"selected\"";
          }
          $html_backend_ext .= ">".$this->language["ROLE_NONE"]."</option>\n".
                               "<option value=\"".ROLE_EDITOR."\"";
          if ($dataset["role"] == ROLE_EDITOR) {
            $html_backend_ext .= " selected=\"selected\"";
          }
          $html_backend_ext .= ">".$this->language["ROLE_EDITOR"]."</option>\n".
                               "<option value=\"".ROLE_MASTER."\"";
          if ($dataset["role"] == ROLE_MASTER) {
            $html_backend_ext .= " selected=\"selected\"";
          }
          $html_backend_ext .= ">".$this->language["ROLE_MASTER"]."</option>\n".
                               "<option value=\"".ROLE_ADMIN."\"";
          if ($dataset["role"] == ROLE_ADMIN) {
            $html_backend_ext .= " selected=\"selected\"";
          }
          $html_backend_ext .= ">".$this->language["ROLE_ADMIN"]."</option>\n".
                               "</select>\n".
                               "</td>\n<td>".
                               "<input type=\"checkbox\" name=\"ba_admin[".$dataset["id"]."][1]\" value=\"delete\"";
          if (($dataset["id"] == $_SESSION["user_id"]) and ($_SESSION["user_role"] >= ROLE_ADMIN)) {
            $html_backend_ext .= " disabled=\"disabled\"";	// admin kann sich nicht selbst löschen
          }
          $html_backend_ext .= "/>".
                               "</td>\n</tr>\n";
        }

        $html_backend_ext .= "<tr>\n<td class=\"td_backend\"></td>\n<td>".
                             "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />".
                             "</td>\n</tr>\n".
                             "</table>\n".
                             "</form>\n\n";

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3j</p>\n\n";
      }

      $locales = $this->getLocales();	// locales als array

      // user anlegen (user, email, full_name, locale, rolle)
      $html_backend_ext .= $this->new_user_form($locales);

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postAdminNew($user, $email, $full_name, $locale, $role, $tmp_password) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // options
      $contact_mail = stripslashes(Blog::getOption_by_name("contact_mail", true));	// als string

      $html_backend_ext .= "<section>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO backend (role, user, password, email, full_name, locale) VALUES (?, ?, ?, ?, ?, ?)";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4g

        // austauschen ?????? durch strings und int
        // CRYPT_BLOWFISH (60 Zeichen): "$2y$" + default cost "10" + "$" + random 22 zeichen salt + 31 zeichen hash
        $password_hash = password_hash($tmp_password, PASSWORD_BCRYPT);
        $stmt->bind_param("isssss", $role, $user, $password_hash, $email, $full_name, $locale);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>".$this->language["MSG_DONE"]."</p>\n\n";
          // email benachrichtigung mit passwort
          mail($email, $this->language["MSG_ADDED_TO_BACKEND"], $this->language["MSG_HELLO"]." ".$user.", ".$this->language["MSG_YOUR_TEMPORARY_PASSWORD_IS"]." ".$tmp_password, "from:".$contact_mail);
        }
        else {
          $html_backend_ext .= "<p>".$this->language["MSG_ADMIN_ERROR"]."</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4g</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postAdmin($ba_admin_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      foreach ($ba_admin_array_replaced as $id => $ba_array) {
        $role = $ba_array[0];
        $delete = $ba_array[1];

        // update oder löschen in datenbank , mit prepare() - sql injections verhindern
        if ($delete) {
          $sql = "DELETE FROM backend WHERE id = ?";
        }
        else {
          $sql = "UPDATE backend SET role = ? WHERE id = ?";
        }
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4h

          // austauschen ? oder ?? durch int
          if ($delete) {
            $stmt->bind_param("i", $id);
          }
          else {
            $stmt->bind_param("ii", $role, $id);
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4h</p>\n\n";
        }

      }

      $html_backend_ext .= "<p>".$count." ".$this->language["MSG_ROWS_CHANGED"]."</p>\n\n";

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
