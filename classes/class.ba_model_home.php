<?php

// *****************************************************************************
// * ba_model - home
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXLEN_HOMETEXT",256);	// aus TABLE VARCHAR(xx) , für zeichen limit

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

  public function getHome() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // TABLE ba_home (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_text VARCHAR(256) NOT NULL);

      $html_backend_ext .= "<p><b>".$this->language["HEADER_HOME"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (1)
      $sql = "SELECT ba_id, ba_text FROM ba_home";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {

        $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                             "<table class=\"backend\">\n";
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $html_backend_ext .= "<tr>\n<td class=\"td_backend\">".
                               $dataset["ba_id"].
                               "</td>\n<td>".
                               "<input type=\"text\" name=\"ba_home[".$dataset["ba_id"]."]\" class=\"size_96\" maxlength=\"".MAXLEN_HOMETEXT."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_text"]))."\"/>".
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
        $errorstring .= "<p>db error 3a</p>\n\n";
      }

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

      foreach ($ba_home_array_replaced as $ba_id => $ba_text) {

        // update in datenbank , mit prepare() - sql injections verhindern
        $sql = "UPDATE ba_home SET ba_text = ? WHERE ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4a

          // austauschen ?? durch string und int
          $stmt->bind_param("si", $ba_text, $ba_id);
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

}

?>
