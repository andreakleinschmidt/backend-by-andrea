<?php

// *****************************************************************************
// * ba_model - home
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXLEN_HOMETEXT",256);	// aus TABLE VARCHAR(xx) , für zeichen limit

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

  public function getHome() {
    $html_backend_ext = "<p><b>home</b></p>\n\n";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // TABLE ba_home (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_text VARCHAR(256) NOT NULL);

      // zugriff auf mysql datenbank (1)
      $sql = "SELECT ba_id, ba_text FROM ba_home";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {

        $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                             "<table class=\"backend\">\n";
        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n".
                               $datensatz["ba_id"].
                               "</td>\n<td>\n".
                               "<input type=\"text\" name=\"ba_home[".$datensatz["ba_id"]."]\" class=\"size_96\" maxlength=\"".MAXLEN_HOMETEXT."\" value=\"".stripslashes($this->html5specialchars($datensatz["ba_text"]))."\"/>\n".
                               "</td>\n</tr>\n";
        }
        $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                             "<input type=\"submit\" value=\"POST\" />\n".
                             "</td>\n</tr>\n".
                             "</table>\n".
                             "</form>\n\n";

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3a</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postHome($ba_home_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $count = 0;

      foreach ($ba_home_array_replaced as $ba_id => $ba_text) {

        // update in datenbank , mit prepare() - sql injections verhindern
        $sql = "UPDATE ba_home SET ba_text = ? WHERE ba_id = ?";
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
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

      $html_backend_ext .= "<p>".$count." rows changed</p>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
