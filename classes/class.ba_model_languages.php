<?php

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2025 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
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
// * ba_model - languages
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXLEN_LOCALE",8);

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 3p - ret bei backend GET languages
//
// db error 4p - stmt bei backend POST languages (neu)
// db error 4q - stmt bei backend POST languages

class Languages extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

// *****************************************************************************
// *** funktionen ***
// *****************************************************************************

  public function getLanguages() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<main>\n\n";

      // TABLE ba_languages (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                     ba_locale VARCHAR(8) NOT NULL,
      //                     ba_selected TINYINT UNSIGNED NOT NULL);

      $html_backend_ext .= "<p><b>".$this->language["HEADER_LANGUAGES"]."</b></p>\n\n";

      // zugriff auf mysql datenbank
      $sql = "SELECT ba_id, ba_locale, ba_selected FROM ba_languages";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {

        // anzeigen: locale, selected als radio button, löschen als checkbox
        $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                             "<table class=\"backend\">\n".
                             "<tr>\n<th>".
                             $this->language["TABLE_HD_ID"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_LOCALE"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_SELECTED"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_DELETE"].
                             "</th>\n</tr>\n";
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $html_backend_ext .= "<tr>\n<td class=\"td_backend\">".
                               $dataset["ba_id"].
                               "</td>\n<td>".
                               "<input type=\"text\" name=\"ba_languages[".$dataset["ba_id"]."][ba_locale]\" class=\"size_12\" maxlength=\"".MAXLEN_LOCALE."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_locale"]))."\"/>".
                               "</td>\n<td>".
                               $this->language["PROMPT_SELECTED"]."<input type=\"radio\" name=\"ba_languages[ba_selected]\" value=\"".$dataset["ba_id"]."\"";
          if ($dataset["ba_selected"] == 1) {
            $html_backend_ext .= " checked=\"checked\"";
          }
          $html_backend_ext .= "/>\n".
                               "</td>\n<td>".
                               $this->language["PROMPT_DELETE"]."<input type=\"checkbox\" name=\"ba_languages[".$dataset["ba_id"]."][]\" value=\"delete\" />".
                               "</td>\n</tr>\n";
        }
        $html_backend_ext .= "<tr>\n<td class=\"td_backend\"></td>\n<td>".
                             "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />".
                             "</td>\n<td></td>\n<td></td>\n</tr>\n".
                             "</table>\n".
                             "</form>\n\n";

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "db error 3p\n";
      }

      // neue language
      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_NEW_LANGUAGE"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_languages_new[locale]\" class=\"size_12\" maxlength=\"".MAXLEN_LOCALE."\" />".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_NEW"]."\" />".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // liste der xml-dateien im verzeichnis languages
      $dir = "languages/";
      $html_backend_ext .= "<table class=\"backend\">\n".
                           "<tr>\n<th>".
                           $dir.
                           "</th>\n</tr>\n<tr>\n<td>\n".
                           (new Upload())->listFiles($dir, "xml").
                           "</td>\n</tr>\n".
                           "</table>\n\n";

      $html_backend_ext .= "</main>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postLanguagesNew($ba_locale, $ba_selected) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<main>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_languages (ba_locale, ba_selected) VALUES (??)";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4p

        // austauschen ?? durch string und int
        $stmt->bind_param("si", $ba_locale, $ba_selected);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>".$this->language["MSG_DONE"]."</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>".$this->language["MSG_LANGUAGES_ERROR"]."</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "db error 4p\n";
      }

      $html_backend_ext .= "</main>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postLanguages($ba_languages_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<main>\n\n";

      $count = 0;

      foreach ($ba_languages_array_replaced as $ba_id => $ba_array) {
        $ba_locale = $ba_array["ba_locale"];
        $ba_selected = $ba_array["ba_selected"];
        $delete = $ba_array["delete"];

        // update oder löschen in datenbank , mit prepare() - sql injections verhindern
        if ($delete) {
          $sql = "DELETE FROM ba_languages WHERE ba_id = ?";
        }
        else {
          $sql = "UPDATE ba_languages SET ba_selected = ? WHERE ba_id = ?";
        }
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4q

          // austauschen ? oder ?? durch int
          if ($delete) {
            $stmt->bind_param("i", $ba_id);
          }
          else {
            $stmt->bind_param("ii", $ba_selected, $ba_id);
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "db error 4q\n";
        }

      }

      $html_backend_ext .= "<p>".$count." ".$this->language["MSG_ROWS_CHANGED"]."</p>\n\n";

      $html_backend_ext .= "</main>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
