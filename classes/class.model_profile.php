<?php

// *****************************************************************************
// * model - profile
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

class Profile extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  public function getProfile($language) {
    $replace = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank (3)
      if ($language == "de") {
        $sql = "SELECT ba_id2, ba_tag, ba_text FROM ba_profile WHERE ba_language = \"de\"";
      }
      else {
        $sql = "SELECT ba_id2, ba_tag, ba_text FROM ba_profile WHERE ba_language = \"en\"";
      }
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3

        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

          $ba_tag = stripslashes($this->html5specialchars($dataset["ba_tag"]));
          $ba_text = stripslashes($this->html5specialchars($dataset["ba_text"]));

          if ($dataset["ba_id2"] == 1) {
            $profile1[$ba_tag] = $ba_text;
          }
          if ($dataset["ba_id2"] == 2) {
            $profile2[$ba_tag] = $ba_text;
          }
          if ($dataset["ba_id2"] == 3) {
            $profile3[$ba_tag] = $ba_text;
          }
          if ($dataset["ba_id2"] == 4) {
            $profile4[$ba_tag] = $ba_text;
          }

        }

        $replace = "<!-- profile -->\n".
                   "<div id=\"profile\">\n".
                   "<p><b>".$this->language["FRONTEND_PROFILE"]."</b></p>\n".
                   "<table class=\"tb_profile\">\n";

        foreach ($profile1 as $spalte1 => $spalte2) {
          $replace .= "<tr>\n".
                      "<td class=\"td_profile\">".$spalte1."</td>\n".
                      "<td>".$spalte2."</td>\n".
                      "</tr>\n";
        }

        $replace .= "</table>\n".
                    "<p><img class=\"kante_platz_25\" src=\"morgana4.jpg\" height=\"105\" width=\"95\"></p>\n".
                    "<table class=\"tb_profile\">\n";

        foreach ($profile2 as $spalte1 => $spalte2) {
          $replace .= "<tr>\n".
                      "<td class=\"td_profile\">".$spalte1."</td>\n".
                      "<td>".$spalte2."</td>\n".
                      "</tr>\n";
        }

        $replace .= "</table>\n".
                    "<p><img class=\"kante_platz_25\" src=\"morgana2.jpg\" height=\"30\" width=\"180\"></p>\n".
                    "<table class=\"tb_profile\">\n";

        foreach ($profile3 as $spalte1 => $spalte2) {
          $replace .= "<tr>\n".
                      "<td class=\"td_profile\">".$spalte1."</td>\n".
                      "<td>".$spalte2."</td>\n".
                      "</tr>\n";
        }

        $replace .= "</table>\n".
                    "<p><img class=\"kante_platz_25\" src=\"morgana1.jpg\" height=\"150\" width=\"300\"></p>\n".
                    "<table class=\"tb_profile\">\n";

        foreach ($profile4 as $spalte1 => $spalte2) {
          $replace .= "<tr>\n".
                      "<td class=\"td_profile\">".$spalte1."</td>\n".
                      "<td>".$spalte2."</td>\n".
                      "</tr>\n";
        }

        // (alt)    "<a href=\"index.php?action=profile&lang=de\">".$this->language["LANGUAGE_GERMAN"]."</a>\n".
        //          "<a href=\"index.php?action=profile&lang=en\">".$this->language["LANGUAGE_ENGLISH"]."</a>\n".
        $replace .= "</table>\n".
                    "<p>\n".
                    "<a href=\"profile/de/\">".$this->language["LANGUAGE_GERMAN"]."</a>\n".
                    "<a href=\"profile/en/\">".$this->language["LANGUAGE_ENGLISH"]."</a>\n".
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

    return array("content" => $replace, "error" => $errorstring);
  }

}

?>
