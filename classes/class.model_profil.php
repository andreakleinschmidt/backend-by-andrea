<?php

// *****************************************************************************
// * model - profil
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

class Profil extends Model {

  public function getProfil($language) {
    $ersetzen = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank (3)
      if ($language == "de") {
        $sql = "SELECT ba_id2, ba_tag, ba_text FROM ba_profile WHERE ba_language = \"de\"";
      }
      else {
        $sql = "SELECT ba_id2, ba_tag, ba_text FROM ba_profile WHERE ba_language = \"en\"";
      }
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3

        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

          $ba_tag = stripslashes($this->html5specialchars($datensatz["ba_tag"]));
          $ba_text = stripslashes($this->html5specialchars($datensatz["ba_text"]));

          if ($datensatz["ba_id2"] == 1) {
            $profil1[$ba_tag] = $ba_text;
          }
          if ($datensatz["ba_id2"] == 2) {
            $profil2[$ba_tag] = $ba_text;
          }
          if ($datensatz["ba_id2"] == 3) {
            $profil3[$ba_tag] = $ba_text;
          }
          if ($datensatz["ba_id2"] == 4) {
            $profil4[$ba_tag] = $ba_text;
          }

        }

        $ersetzen = "<!-- profil -->\n".
                    "<div id=\"profil\">\n".
                    "<p><b>Profil:</b></p>\n".
                    "<table class=\"tb_profil\">\n";

        foreach ($profil1 as $spalte1 => $spalte2) {
          $ersetzen .= "<tr>\n".
                       "<td class=\"td_profil\">".$spalte1."</td>\n".
                       "<td>".$spalte2."</td>\n".
                       "</tr>\n";
        }

        $ersetzen .= "</table>\n".
                     "<p><img class=\"kante_platz_25\" src=\"morgana4.jpg\" height=\"105\" width=\"95\"></p>\n".
                     "<table class=\"tb_profil\">\n";

        foreach ($profil2 as $spalte1 => $spalte2) {
          $ersetzen .= "<tr>\n".
                       "<td class=\"td_profil\">".$spalte1."</td>\n".
                       "<td>".$spalte2."</td>\n".
                       "</tr>\n";
        }

        $ersetzen .= "</table>\n".
                     "<p><img class=\"kante_platz_25\" src=\"morgana2.jpg\" height=\"30\" width=\"180\"></p>\n".
                     "<table class=\"tb_profil\">\n";

        foreach ($profil3 as $spalte1 => $spalte2) {
          $ersetzen .= "<tr>\n".
                       "<td class=\"td_profil\">".$spalte1."</td>\n".
                       "<td>".$spalte2."</td>\n".
                       "</tr>\n";
        }

        $ersetzen .= "</table>\n".
                     "<p><img class=\"kante_platz_25\" src=\"morgana1.jpg\" height=\"150\" width=\"300\"></p>\n".
                     "<table class=\"tb_profil\">\n";

        foreach ($profil4 as $spalte1 => $spalte2) {
          $ersetzen .= "<tr>\n".
                       "<td class=\"td_profil\">".$spalte1."</td>\n".
                       "<td>".$spalte2."</td>\n".
                       "</tr>\n";
        }

        // (alt)     "<a href=\"index.php?action=profil&lang=de\">deutsch</a>\n".
        //           "<a href=\"index.php?action=profil&lang=en\">english</a>\n".
        $ersetzen .= "</table>\n".
                     "<p>\n".
                     "<a href=\"profil/de/\">deutsch</a>\n".
                     "<a href=\"profil/en/\">english</a>\n".
                     "</p>\n".
                     "<p><img class=\"kante_platz_oben_100\" src=\"morgana6.jpg\" height=\"120\" width=\"80\"></p>\n".
                     "<p><img class=\"kante_platz_oben_100\" src=\"morgana5.jpg\" height=\"265\" width=\"250\"></p>\n".
                     "<p><img class=\"kante_platz_oben_100\" src=\"morgana7.jpg\" height=\"320\" width=\"240\"></p>\n".
                     "<p><img class=\"kante_platz_oben_100\" src=\"flyer.gif\" height=\"360\" width=\"270\"></p>\n".
                     "</div>";

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

    return array("inhalt" => $ersetzen, "error" => $errorstring);
  }

}

?>
