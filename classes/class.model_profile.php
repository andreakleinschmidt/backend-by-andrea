<?php

// *****************************************************************************
// * model - profile
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

//define("ELEMENT_IMAGE","image");
//define("ELEMENT_PARAGRAPH","paragraph");
//define("ELEMENT_TABLE_H","table+h");
//define("ELEMENT_TABLE","table");

class Profile extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  private function get_last_text_id() {
    $last_text_id = 0;

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_id FROM ba_profile WHERE ba_element RLIKE '".ELEMENT_PARAGRAPH."|".ELEMENT_TABLE."' ORDER BY ba_id DESC LIMIT 1";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        $dataset = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $last_text_id = intval($dataset["ba_id"]);
        $ret->close();
        unset($ret);
      }

    } // datenbank

    return $last_text_id;
  }

  private function get_languages_list() {
    $languages_list = array();

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT DISTINCT ba_language FROM ba_profile_tables";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $languages_list[] = trim($dataset["ba_language"]);
        }
        $ret->close();
        unset($ret);
      }

    } // datenbank

    return $languages_list;
  }

  private function getTable($table_name, $language, $header_flag=false) {
    $replace = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank (3a)
      $sql = "SELECT ba_row, ba_col, ba_value FROM ba_profile_tables WHERE ba_table_name = ? AND ba_language = ?";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 3a

        // austauschen ?? durch strings
        $stmt->bind_param("ss", $table_name, $language);
        $stmt->execute();	// ausführen geänderte zeile

        $stmt->bind_result($dataset["ba_row"],$dataset["ba_col"],$dataset["ba_value"]);
        // oder ohne array dataset: $stmt->bind_result($ba_row, $ba_col, $ba_value);
        // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein array als rückgabe

        $rows = array();	// rows array

        while ($stmt->fetch()) {	// $dataset = $stmt->fetch_assoc(), fetch_assoc() liefert array, solange nicht NULL (letzter datensatz), hier jetzt nur fetch()
          $ba_row = intval($dataset["ba_row"]);
          $ba_col = intval($dataset["ba_col"]);
          $ba_value = stripslashes($this->html5specialchars($dataset["ba_value"]));

          if (isset($rows[$ba_row])) {
            if (!isset($rows[$ba_row][$ba_col])) {
              $rows[$ba_row][$ba_col] = $ba_value;
            }
          }
          else {
            $rows[$ba_row] = array($ba_col => $ba_value);	// columns array
          }

        } // while

        $replace .= "<table class=\"tb_profile\">\n";

        foreach ($rows as $row => $columns) {
          $replace .= "<tr>\n";
          foreach ($columns as $col => $text) {
            if ($header_flag and $row == 1) {
              if ($col == 1) {
                $replace .= "<th class=\"th_profile\">".$text."</th>\n";
              }
              else {
                $replace .= "<th>".$text."</th>\n";
              }
            }
            elseif ($col == 1) {
              $replace .= "<td class=\"td_profile\">".$text."</td>\n";
            }
            else {
              $replace .= "<td>".$text."</td>\n";
            }
          } // foreach column
          $replace .= "</tr>\n";
        } // foreach row

        $replace .= "</table>\n";

        $stmt->close();
        unset($stmt);	// referenz löschen

      } // stmt
      else {
        $errorstring .= "<br>db error 3a\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error\n";
    }

    return array("content" => $replace, "error" => $errorstring);
  }

  public function getProfile($language) {
    $replace = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $last_text_id = $this->get_last_text_id();
      $languages_list = $this->get_languages_list();

      // language überprüfen
      if (!in_array($language, $languages_list)) {
        $language = substr(DEFAULT_LOCALE, 0, 2);
      }

      // zugriff auf mysql datenbank (3)
      $sql = "SELECT ba_id, ba_element, ba_css, ba_value FROM ba_profile";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3

        $replace .= "<!-- profile -->\n".
                    "<div id=\"profile\">\n";

        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $ba_id = intval($dataset["ba_id"]);
          $ba_element = trim($dataset["ba_element"]);
          $ba_css = stripslashes($this->html5specialchars($dataset["ba_css"]));

          switch($ba_element) {

            case ELEMENT_IMAGE: {
              $replace .= "<p>";
              $images_array = explode(",",$dataset["ba_value"]);
              foreach ($images_array as $imagename) {
                if (is_readable($imagename)) {
                  $imagesize = getimagesize($imagename);
                  $replace .= "<img ";
                  if (!empty($ba_css)) {
                    $replace .= "class=\"".$ba_css."\" ";
                  }
                  $replace .= "src=\"".$imagename."\" ".$imagesize[3].">";
                }
              }
              $replace .= "</p>\n";
              break;
            }

            case ELEMENT_PARAGRAPH: {
              $profiletext = stripslashes(nl2br($this->html5specialchars($dataset["ba_value"])));
              $replace .= "<p>".$hometext."</p>\n";
              break;
            }

            case ELEMENT_TABLE_H: {
              $table_name = trim($dataset["ba_value"]);
              $ret_array = $this->getTable($table_name, $language, true);	// mit header flag
              $replace .= $ret_array["content"];
              $errorstring .= $ret_array["error"];
              break;
            }

            case ELEMENT_TABLE: {
              $table_name = trim($dataset["ba_value"]);
              $ret_array = $this->getTable($table_name, $language);	// ohne header flag
              $replace .= $ret_array["content"];
              $errorstring .= $ret_array["error"];
              break;
            }

            default: {
              // nichts
            }

          } // switch

          if ($ba_id == $last_text_id) {
            $translate = array("de" => $this->language["LANGUAGE_GERMAN"], "en" => $this->language["LANGUAGE_ENGLISH"]);
            $replace .= "<p>\n";
            foreach ($languages_list as $language) {
              $language_url = rawurlencode($language);
              if (array_key_exists($language, $translate)) {
                $language_name = $translate[$language];
              }
              else {
                $language_name = stripslashes($this->html5specialchars($language));
              }
              // alt      "<a href=\"index.php?action=profile&lang=".$language_url."\">".$language_name."</a>\n";
              $replace .= "<a href=\"profile/".$language_url."/\">".$language_name."</a>\n";
            }
            $replace .= "</p>\n";
          }

        } // while

        $replace .= "</div>";

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<br>db error 3\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error\n";
    }

    return array("content" => $replace, "error" => $errorstring);
  }

}

?>
