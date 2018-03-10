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
// * ba_model - home
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXLEN_HPELEMENT",16);
//define("MAXLEN_HPCSS",32);
//define("MAXLEN_HPVALUE",1024);
//define("ELEMENT_IMAGE","image");
//define("ELEMENT_PARAGRAPH","paragraph");

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 3a - ret bei backend GET home
//
// db error 4a - stmt bei backend POST home

class Home extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  private function translate($element) {
    $key_image = ELEMENT_IMAGE;
    $key_paragraph = ELEMENT_PARAGRAPH;
    $translate = array($key_image => $this->language["ELEMENT_IMAGE"], $key_paragraph => $this->language["ELEMENT_PARAGRAPH"]);
    return $translate[$element];
  }

  private function getElements() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_home (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_element VARCHAR(16) NOT NULL,
      //                ba_css VARCHAR(32) NOT NULL,
      //                ba_value VARCHAR(1024) NOT NULL);

      $html_backend_ext .= "<p id=\"elements\"><b>".$this->language["HEADER_ELEMENTS"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_id, ba_element FROM ba_home";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3a-e

        if ($ret->num_rows > 0) {
          // elemente anzeigen, auswählen&löschen

          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n".
                               "<tr>\n<td class=\"td_backend\">".
                               $this->language["PROMPT_ELEMENTS"].
                               "</td>\n<td>\n".
                               "<select multiple name=\"ba_home_elements[]\" size=\"10\">\n";

          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $ba_id = intval($dataset["ba_id"]);
            $ba_element = trim($dataset["ba_element"]);
            $html_backend_ext .= "<option value=\"".$ba_id."\">".$this->translate($ba_element)."</option>\n";
          }

          $html_backend_ext .= "</select>\n".
                               "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                               "<input type=\"submit\" value=\"".$this->language["BUTTON_DELETE"]."\" />".
                               "</td>\n</tr>\n".
                               "</table>\n".
                               "</form>\n\n";

        } // $ret->num_rows > 0

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3a-e</p>\n\n";
      }

      // neues element
      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_NEW_ELEMENT"].
                           "</td>\n<td>\n".
                           "<select name=\"ba_home_elements_new[element]\" size=\"1\">\n".
                           "<option value=\"".ELEMENT_IMAGE."\">".$this->language["ELEMENT_IMAGE"]."</option>\n".
                           "<option value=\"".ELEMENT_PARAGRAPH."\">".$this->language["ELEMENT_PARAGRAPH"]."</option>\n".
                           "</select>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_NEW"]."\" />".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function getHome() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // TABLE ba_home (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_element VARCHAR(16) NOT NULL,
      //                ba_css VARCHAR(32) NOT NULL,
      //                ba_value VARCHAR(1024) NOT NULL);

      $html_backend_ext .= "<p id=\"home\"><b>".$this->language["HEADER_HOME"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (1)
      $sql = "SELECT ba_id, ba_element, ba_css, ba_value FROM ba_home";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3a

        $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                             "<table class=\"backend\">\n";

        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $ba_id = intval($dataset["ba_id"]);
          $ba_element = trim($dataset["ba_element"]);
          $elements[$ba_id] = $ba_element;

          $html_backend_ext .= "<tr>\n<td class=\"td_backend\">".
                               "(".$ba_id.") ".$this->translate($ba_element).
                               "</td>\n<td>\n";

          switch($ba_element) {

            case ELEMENT_IMAGE: {
              $html_backend_ext .= "<input type=\"text\" name=\"ba_home[".$ba_id."][ba_css]\" class=\"size_32\" maxlength=\"".MAXLEN_HPCSS."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_css"]))."\"/><br>\n".
                                   "<input type=\"text\" name=\"ba_home[".$ba_id."][ba_value]\" class=\"size_48\" maxlength=\"".MAXLEN_HPVALUE."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_value"]))."\"/>\n";
              break;
            }

            case ELEMENT_PARAGRAPH: {
              $html_backend_ext .= "<input type=\"hidden\" name=\"ba_home[".$ba_id."][ba_css]\" value=\"".stripslashes($this->html5specialchars($dataset["ba_css"]))."\"/>\n".
                                   "<textarea name=\"ba_home[".$ba_id."][ba_value]\" class=\"cols_96_rows_11\">".stripslashes($this->html5specialchars($dataset["ba_value"]))."</textarea>\n";
              break;
            }

            default: {
              // nichts
            }

          } // switch

          $html_backend_ext .= "</td>\n</tr>\n";

        } // while

        $html_backend_ext .= "<tr>\n<td class=\"td_backend\"></td>\n<td>".
                             "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />".
                             "</td>\n</tr>\n".
                             "</table>\n".
                             "</form>\n\n";

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3a</p>\n\n";
      }

      // elements
      $elements = $this->getElements();
      $html_backend_ext .= $elements["content"];
      $errorstring .= $elements["error"];

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postHome($ba_home_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      foreach ($ba_home_array_replaced as $ba_id => $ba_array) {
        $ba_css = $ba_array["ba_css"];
        $ba_value = $ba_array["ba_value"];

        // update in datenbank , mit prepare() - sql injections verhindern
        $sql = "UPDATE ba_home SET ba_css = ?, ba_value = ? WHERE ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4a

          // austauschen ??? durch strings und int
          $stmt->bind_param("ssi", $ba_css, $ba_value, $ba_id);
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4a</p>\n\n";
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

  public function postElementNew($element) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_home (ba_element) VALUES (?)";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4a-n

        // austauschen ? durch string
        $stmt->bind_param("s", $element);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>".$this->language["MSG_DONE"]."</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>".$this->language["MSG_ELEMENT_ERROR"]."</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4a-n</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postElements($ba_elements_array) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      foreach ($ba_elements_array as $ba_id) {

        $sql = "DELETE FROM ba_home WHERE ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4a-e

          // austauschen ? durch int
          $stmt->bind_param("i", $ba_id);
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4a-e</p>\n\n";
        }

      }

      $html_backend_ext .= "<p>".$count." ".$this->language["MSG_ROWS_DELETED"]."</p>\n\n";

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
