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
// * ba_model - profile
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXLEN_HPELEMENT",16);
//define("MAXLEN_HPCSS",32);
//define("MAXLEN_HPVALUE",1024);
//define("MAXLEN_PROFILETABLENAME",32);
//define("MAXLEN_PROFILELANGUAGE",2);
//define("MAXLEN_PROFILEVALUE",256);
//define("ELEMENT_IMAGE","image");
//define("ELEMENT_PARAGRAPH","paragraph");
//define("ELEMENT_TABLE_H","table+h");
//define("ELEMENT_TABLE","table");

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 3b - ret bei backend GET profile
//
// db error 4b - stmt bei backend POST profile

class Profile extends Model {

  // variablen
  private static $language_codes = array("cz","da","de","en","es","fi","fr","it","nl","no","pl","pt","ro","ru","sk","sv");

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  private function translate($element) {
    $key_image = ELEMENT_IMAGE;
    $key_paragraph = ELEMENT_PARAGRAPH;
    $key_table_h = ELEMENT_TABLE_H;
    $key_table = ELEMENT_TABLE;
    $translate = array($key_image => $this->language["ELEMENT_IMAGE"], $key_paragraph => $this->language["ELEMENT_PARAGRAPH"],
                       $key_table_h => $this->language["ELEMENT_TABLE_H"], $key_table => $this->language["ELEMENT_TABLE"]);
    return $translate[$element];
  }

  private function getElements() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_profile (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                   ba_element VARCHAR(16) NOT NULL,
      //                   ba_css VARCHAR(32) NOT NULL,
      //                   ba_value VARCHAR(1024) NOT NULL);

      $html_backend_ext .= "<p id=\"elements\"><b>".$this->language["HEADER_ELEMENTS"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_id, ba_element FROM ba_profile";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3b-e

        if ($ret->num_rows > 0) {
          // elemente anzeigen, auswählen&löschen

          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n".
                               "<tr>\n<td class=\"td_backend\">".
                               $this->language["PROMPT_ELEMENTS"].
                               "</td>\n<td>\n".
                               "<select multiple name=\"ba_profile_elements[]\" size=\"10\">\n";

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
        $errorstring .= "db error 3b-e\n";
      }

      // neues element
      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_NEW_ELEMENT"].
                           "</td>\n<td>\n".
                           "<select name=\"ba_profile_elements_new[element]\" size=\"1\">\n".
                           "<option value=\"".ELEMENT_IMAGE."\">".$this->language["ELEMENT_IMAGE"]."</option>\n".
                           "<option value=\"".ELEMENT_PARAGRAPH."\">".$this->language["ELEMENT_PARAGRAPH"]."</option>\n".
                           "</select>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_NEW"]."\" />".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // neue tabelle
      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_NEW_TABLE"].
                           "</td>\n<td>\n".
                           "<select name=\"ba_profile_tables_new[element]\" size=\"1\">\n".
                           "<option value=\"".ELEMENT_TABLE_H."\">".$this->language["ELEMENT_TABLE_H"]."</option>\n".
                           "<option value=\"".ELEMENT_TABLE."\">".$this->language["ELEMENT_TABLE"]."</option>\n".
                           "</select>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_TABLENAME"].
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_profile_tables_new[table_name]\" class=\"size_32\" maxlength=\"".MAXLEN_PROFILETABLENAME."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_ROWSCOLS"].
                           "</td>\n<td>\n".
                           "<input type=\"number\" name=\"ba_profile_tables_new[rows]\" class=\"size_4\" min=\"1\" max=\"255\"/>\n".
                           "<input type=\"number\" name=\"ba_profile_tables_new[cols]\" class=\"size_4\" min=\"1\" max=\"255\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_LANGUAGECODES"].
                           "</td>\n<td>\n".
                           "<select multiple name=\"ba_profile_tables_new[language_codes][]\" size=\"5\">\n";
      foreach (self::$language_codes as $language_code) {
        $html_backend_ext .= "<option value=\"".$language_code."\">".$language_code."</option>\n";
      }
      $html_backend_ext .= "</select>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_NEW"]."\" />".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  private function get_table_names() {
    $table_names = array();

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT DISTINCT ba_table_name FROM ba_profile_tables";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $table_names[] = trim($dataset["ba_table_name"]);
        }
        $ret->close();
        unset($ret);
      }

    } // datenbank

    return $table_names;
  }

  private function getTables() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_profile_tables (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                          ba_table_name VARCHAR(32),
      //                          ba_row INT UNSIGNED NOT NULL,
      //                          ba_col INT UNSIGNED NOT NULL,
      //                          ba_language VARCHAR(2) NOT NULL,
      //                          ba_value VARCHAR(256) NOT NULL);

      $html_backend_ext .= "<p id=\"tables\"><b>".$this->language["HEADER_TABLES"]."</b></p>\n\n";

      $table_names = $this->get_table_names();

      foreach ($table_names as $ba_table_name) {

        // zugriff auf mysql datenbank (3)
        $sql = "SELECT ba_row, ba_col, ba_language, ba_value FROM ba_profile_tables WHERE ba_table_name = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4b-t1

          // austauschen ? durch string
          $stmt->bind_param("s", $ba_table_name);
          $stmt->execute();	// ausführen geänderte zeile

          $stmt->bind_result($dataset["ba_row"],$dataset["ba_col"],$dataset["ba_language"],$dataset["ba_value"]);
          // oder ohne array dataset: $stmt->bind_result($ba_row, $ba_col, $ba_language, $ba_value);
          // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein array als rückgabe

          $rows = array();	// rows array

          while ($stmt->fetch()) {	// $dataset = $stmt->fetch_assoc(), fetch_assoc() liefert array, solange nicht NULL (letzter datensatz), hier jetzt nur fetch()
            $ba_row = intval($dataset["ba_row"]);
            $ba_col = intval($dataset["ba_col"]);
            $ba_language = stripslashes($this->html5specialchars($dataset["ba_language"]));
            $ba_value = stripslashes($this->html5specialchars($dataset["ba_value"]));

            if (isset($rows[$ba_row])) {
              if (!isset($rows[$ba_row][$ba_col])) {
                $rows[$ba_row][$ba_col] = array($ba_language => $ba_value);	// languages array
              }
              else {
                $rows[$ba_row][$ba_col][$ba_language] = $ba_value;
              }
            }
            else {
              $rows[$ba_row] = array($ba_col => array($ba_language => $ba_value));	// columns array
            }

          } // while

          $html_backend_ext .= "<p>".stripslashes($this->html5specialchars($ba_table_name))."</p>".
                               "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n";

          $num_columns = 0;

          foreach ($rows as $ba_row => $columns) {
            $num_columns = count($columns);
            $html_backend_ext .= "<tr>\n"; 
            foreach ($columns as $ba_col => $languages) {
              $html_backend_ext .= "<td>\n";
              foreach ($languages as $ba_language => $ba_value) {
                $html_backend_ext .= "(".$ba_language.")<input type=\"text\" name=\"ba_profile_tables[".$ba_table_name."][".$ba_row."][".$ba_col."][".$ba_language."]\" class=\"size_32\" maxlength=\"".MAXLEN_PROFILEVALUE."\" value=\"".$ba_value."\"/><br>\n";
              } // foreach language
              $html_backend_ext .= "</td>\n";
            } // foreach column
            $html_backend_ext .= "</tr>\n";
          } // foreach row

          $html_backend_ext .= "<tr>\n<td class=\"td_backend\"></td>\n<td colspan=\"".($num_columns-1)."\">".
                               "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />".
                               "</td>\n</tr>\n".
                               "</table>\n".
                               "</form>\n\n";

          $stmt->close();
          unset($stmt);	// referenz löschen

        } // stmt
        else {
          $errorstring .= "db error 4b-t1\n";
        }

      } // foreach table name

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function getProfile() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<main>\n\n";

      // TABLE ba_profile (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                   ba_element VARCHAR(16) NOT NULL,
      //                   ba_css VARCHAR(32) NOT NULL,
      //                   ba_value VARCHAR(1024) NOT NULL);

      $html_backend_ext .= "<p id=\"profile\"><b>".$this->language["HEADER_PROFILE"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (1)
      $sql = "SELECT ba_id, ba_element, ba_css, ba_value FROM ba_profile";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3b

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
              $html_backend_ext .= "<input type=\"text\" name=\"ba_profile[".$ba_id."][ba_css]\" class=\"size_32\" maxlength=\"".MAXLEN_HPCSS."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_css"]))."\"/><br>\n".
                                   "<input type=\"text\" name=\"ba_profile[".$ba_id."][ba_value]\" class=\"size_48\" maxlength=\"".MAXLEN_HPVALUE."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_value"]))."\"/>\n";
              break;
            }

            case ELEMENT_PARAGRAPH: {
              $html_backend_ext .= "<input type=\"hidden\" name=\"ba_profile[".$ba_id."][ba_css]\" value=\"".stripslashes($this->html5specialchars($dataset["ba_css"]))."\"/>\n".
                                   "<textarea name=\"ba_profile[".$ba_id."][ba_value]\" class=\"cols_96_rows_11\">".stripslashes($this->html5specialchars($dataset["ba_value"]))."</textarea>\n";
              break;
            }

            case ELEMENT_TABLE_H: {
              $html_backend_ext .= "<input type=\"hidden\" name=\"ba_profile[".$ba_id."][ba_css]\" value=\"".stripslashes($this->html5specialchars($dataset["ba_css"]))."\"/>\n".
                                   "<input type=\"text\" name=\"ba_profile[".$ba_id."][ba_value]\" class=\"size_32\" maxlength=\"".MAXLEN_PROFILETABLENAME."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_value"]))."\" readonly/>\n";
                                   // readonly workaround, nur update in ba_profile, update mit left join on ba_profile_tables funktioniert nicht korrekt
              break;
            }

            case ELEMENT_TABLE: {
              $html_backend_ext .= "<input type=\"hidden\" name=\"ba_profile[".$ba_id."][ba_css]\" value=\"".stripslashes($this->html5specialchars($dataset["ba_css"]))."\"/>\n".
                                   "<input type=\"text\" name=\"ba_profile[".$ba_id."][ba_value]\" class=\"size_32\" maxlength=\"".MAXLEN_PROFILETABLENAME."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_value"]))."\" readonly/>\n";
                                   // readonly workaround, nur update in ba_profile, update mit left join on ba_profile_tables funktioniert nicht korrekt
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
        $errorstring .= "db error 3b\n";
      }

      // tables
      $tables = $this->getTables();
      $html_backend_ext .= $tables["content"];
      $errorstring .= $tables["error"];

      // elements
      $elements = $this->getElements();
      $html_backend_ext .= $elements["content"];
      $errorstring .= $elements["error"];

      $html_backend_ext .= "</main>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postProfile($ba_profile_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<main>\n\n";

      $count = 0;

      foreach ($ba_profile_array_replaced as $ba_id => $ba_array) {
        $ba_css = $ba_array["ba_css"];
        $ba_value = $ba_array["ba_value"];

        // update in datenbank , mit prepare() - sql injections verhindern
        $sql = "UPDATE ba_profile SET ba_css = ?, ba_value = ? WHERE ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4b

          // austauschen ??? durch strings und int
          $stmt->bind_param("ssi", $ba_css, $ba_value, $ba_id);
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "db error 4b\n";
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

  public function postElementNew($element, $value=NULL) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<main>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      if (isset($value)) {
        // table name
        $sql = "INSERT INTO ba_profile (ba_element, ba_value) VALUES (?, ?)";
      }
      else {
        $sql = "INSERT INTO ba_profile (ba_element) VALUES (?)";
      }
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4b-n

        // austauschen ? durch string oder ?? durch strings
        if (isset($value)) {
          $stmt->bind_param("ss", $element, $value);
        }
        else {
          $stmt->bind_param("s", $element);
        }
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
        $errorstring .= "db error 4b-n\n";
      }

      $html_backend_ext .= "</main>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postElements($ba_elements_array) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<main>\n\n";

      $count = 0;

      foreach ($ba_elements_array as $ba_id) {

        // löschen in ba_profile, falls id->value auch tabellenname in ba_profile_tables, löschen aller einträge mit tabellenname in ba_profile_tables
        $sql = "DELETE ba_profile, ba_profile_tables FROM ba_profile LEFT JOIN ba_profile_tables ON ba_profile_tables.ba_table_name = ba_profile.ba_value WHERE ba_profile.ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4b-e

          // austauschen ? durch int
          $stmt->bind_param("i", $ba_id);
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "db error 4b-e\n";
        }

      }

      $html_backend_ext .= "<p>".$count." ".$this->language["MSG_ROWS_DELETED"]."</p>\n\n";

      $html_backend_ext .= "</main>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postTablesNew($ba_profile_tables_new_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    // neues element
    $element = $ba_profile_tables_new_array_replaced["element"];
    $table_name = $ba_profile_tables_new_array_replaced["table_name"];
    $post_element = $this->postElementNew($element, $table_name);
    $html_backend_ext .= $post_element["content"];
    $errorstring .= $post_element["error"];

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<main>\n\n";

      $rows = $ba_profile_tables_new_array_replaced["rows"];
      $cols = $ba_profile_tables_new_array_replaced["cols"];
      $language_codes = $ba_profile_tables_new_array_replaced["language_codes"];

      $count = 0;

      foreach (range(1, $rows) as $row) {
        foreach (range(1, $cols) as $col) {
          foreach ($language_codes as $language_code) {

            // einfügen in datenbank , mit prepare() - sql injections verhindern
            $sql = "INSERT INTO ba_profile_tables (ba_table_name, ba_row, ba_col, ba_language) VALUES (?, ?, ?, ?)";
            $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
            if ($stmt) {
              // wenn kein fehler 4b-tn

              // austauschen ??? durch string und int
              $stmt->bind_param("siis", $table_name, $row, $col, $language_code);
              $stmt->execute();	// ausführen geänderte zeile
              $count += $stmt->affected_rows;
              $stmt->close();

            } // stmt

            else {
              $errorstring .= "db error 4b-tn\n";
            }

          } // foreach language code
        } // foreach column
      } // foreach row

      $html_backend_ext .= "<p>".$count." ".$this->language["MSG_ROWS_INSERTED"]."</p>\n\n";

      $html_backend_ext .= "</main>\n\n";

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postTables($ba_profile_tables_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<main>\n\n";

      $count = 0;

      foreach ($ba_profile_tables_array_replaced as $ba_table_name => $rows) {
        foreach ($rows as $ba_row => $cols) {
          foreach ($cols as $ba_col => $languages) {
            foreach ($languages as $ba_language => $ba_value) {

              // update in datenbank , mit prepare() - sql injections verhindern
              $sql = "UPDATE ba_profile_tables SET ba_value = ? WHERE ba_table_name = ? AND ba_row = ? AND ba_col = ? AND ba_language = ?";
              $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
              if ($stmt) {
                // wenn kein fehler 4b-t2

                // austauschen ????? durch strings und int
                $stmt->bind_param("ssiis", $ba_value, $ba_table_name, $ba_row, $ba_col, $ba_language);
                $stmt->execute();	// ausführen geänderte zeile
                $count += $stmt->affected_rows;
                $stmt->close();

              } // stmt

              else {
                $errorstring .= "db error 4b-t2\n";
              }

            } // foreach languages
          } // foreach cols
        } // foreach rows
      } // foreach table names

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
