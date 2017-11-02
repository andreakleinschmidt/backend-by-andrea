<?php

// *****************************************************************************
// * model - home
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

class Home extends Model {

  public function getHome() {
    $ersetzen = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_text FROM ba_home";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 2

        $datensatz = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $home1 = stripslashes($this->html5specialchars($datensatz["ba_text"]));
        $datensatz = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $home2 = stripslashes($this->html5specialchars($datensatz["ba_text"]));

        $ersetzen = "<!-- home -->\n".
                    "<div id=\"home\">\n".
                    "<p><img class=\"kante_platz_unten_25\" src=\"morgana.jpg\" height=\"330\" width=\"240\"></p>\n".
                    "<p>".$home1."\n".
                    "<br>".$home2."</p>\n".
                    "<p><img class=\"kante\" src=\"x1.jpg\" height=\"30\" width=\"60\"><img class=\"kante\" src=\"x2.jpg\" height=\"30\" width=\"60\"><img class=\"kante\" src=\"x3.jpg\" height=\"30\" width=\"60\"><img class=\"kante\" src=\"x4.jpg\" height=\"30\" width=\"60\"></p>\n".
                    "</div>";

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<br>db error 2\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error\n";
    }

    return array("inhalt" => $ersetzen, "error" => $errorstring);
  }

}

?>
