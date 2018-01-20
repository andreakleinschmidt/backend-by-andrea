<?php

// *****************************************************************************
// * model - home
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

//define("ELEMENT_IMAGE","image");
//define("ELEMENT_PARAGRAPH","paragraph");

class Home extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  public function getHome() {
    $replace = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_element, ba_css, ba_value FROM ba_home";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 2

        $replace .= "<!-- home -->\n".
                    "<div id=\"home\">\n";

        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
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
              $hometext = stripslashes(nl2br($this->html5specialchars($dataset["ba_value"])));
              $replace .= "<p>".$hometext."</p>\n";
              break;
            }

            default: {
              // nichts
            }

          } // switch

        } // while

        $replace .= "</div>";

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

    return array("content" => $replace, "error" => $errorstring);
  }

}

?>
