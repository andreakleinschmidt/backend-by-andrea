<?php

// *****************************************************************************
// * ba_model - profile
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXLEN_PROFILETAG",64);
define("MAXLEN_PROFILETEXT",256);

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

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  public function getProfile() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $html_backend_ext .= "<p><b>".$this->language["HEADER_PROFILE"]."</b></p>\n\n";

      $size1 = "size_16";
      $size2 = "size_32";
      for ($i=1; $i<=4; $i++) {
        if ($i == 4) {
          $size1 = "size_32";
          $size2 = "size_96";
        }

        $html_backend_ext .= "<p><b>- ".$i."</b></p>\n\n";

        // zugriff auf mysql datenbank (2)
        $sql = "SELECT ba_id, ba_tag, ba_text FROM ba_profile WHERE ba_id2 = ".$i;
        $ret = $this->database->query($sql);	// liefert in return db-objekt
        if ($ret) {

          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n";
          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $html_backend_ext .= "<tr>\n<td class=\"td_backend\">".
                                 "<input type=\"text\" name=\"ba_profile[".$dataset["ba_id"]."][ba_tag]\" class=\"".$size1."\" maxlength=\"".MAXLEN_PROFILETAG."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_tag"]))."\"/>".
                                 "</td>\n<td>".
                                 "<input type=\"text\" name=\"ba_profile[".$dataset["ba_id"]."][ba_text]\" class=\"".$size2."\" maxlength=\"".MAXLEN_PROFILETEXT."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_text"]))."\"/>".
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
          $errorstring .= "<p>db error 3b</p>\n\n";
        }

      } // for

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postProfile($ba_profile_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      foreach ($ba_profile_array_replaced as $ba_id => $ba_array) {
        $ba_tag = $ba_array["ba_tag"];
        $ba_text = $ba_array["ba_text"];

        // update in datenbank , mit prepare() - sql injections verhindern
        $sql = "UPDATE ba_profile SET ba_tag = ?, ba_text = ? WHERE ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4b

          // austauschen ??? durch strings und int
          $stmt->bind_param("ssi", $ba_tag, $ba_text, $ba_id);
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4b</p>\n\n";
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
