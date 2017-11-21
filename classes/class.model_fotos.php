<?php

// *****************************************************************************
// * model - fotos
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

class Fotos extends Model {

  public function getFotos($alias, $id) {
    $hd_title_str = "";
    $ersetzen = "";
    $errorstring = "";

    //$alias = rawurldecode($alias);

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank (4a), liste der galerien mit alias, text und reihenfolge
      $sql = "SELECT ba_id, ba_alias, ba_text, ba_order FROM ba_gallery";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 4a

        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $galleries[$datensatz["ba_id"]]["alias"] = stripslashes($this->html5specialchars($datensatz["ba_alias"]));
          $galleries[$datensatz["ba_id"]]["text"] = stripslashes($this->html5specialchars($datensatz["ba_text"]));
          $galleries[$datensatz["ba_id"]]["order"] = stripslashes($this->html5specialchars($datensatz["ba_order"]));
        }

        // GET alias auslesen
        if (isset($alias) and $this->check_alias($galleries, $alias)) {
          // alias vorhanden und nicht NULL
          $galleryid = $this->translate_alias($galleries, $alias);
        }

        // GET gallery auslesen
        if (isset($galleryid) and is_numeric($galleryid) and isset($galleries[$galleryid])) {
          // gallery als zahl vorhanden und nicht NULL
          // nur die gewählte galerie anzeigen

          $ret_array = $this->getFoto($galleries, $galleryid, $id);
          $hd_title_str = $ret_array["hd_titel"];

        } // GET gallery
        else {
          // alle galerien anzeigen

          $ret_array = $this->getGalerie($galleries);

        } // galerien anzeigen

        $ersetzen = $ret_array["inhalt"];
        $errorstring .= $ret_array["error"];

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<br>db error 4a\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error\n";
    }

    return array("hd_titel" => $hd_title_str, "inhalt" => $ersetzen, "error" => $errorstring);
  }

  // prüfen ob alias in galleries
  private function check_alias($galleries, $alias) {
    $ret = 0;	// false
    foreach ($galleries as $id => $alias_text_order) {
      if ($alias_text_order["alias"] == $alias) {
        $ret = 1;	// true
        break;
      }
    }
    return $ret;
  }

  // zuordnen von alias zu galleryid
  private function translate_alias($galleries, $alias) {
    $ret = NULL;
    foreach ($galleries as $id => $alias_text_order) {
      if ($alias_text_order["alias"] == $alias) {
        $ret = $id;
        break;
      }
    }
    return $ret;
  }

  // nur die gewählte galerie anzeigen (mit foto)
  private function getFoto($galleries, $galleryid, $id) {
    $hd_title_str = "";
    $ersetzen = "";
    $errorstring = "";

    $alias = rawurlencode($galleries[$galleryid]["alias"]);

    // zugriff auf mysql datenbank (4b)
    $sql = "SELECT ba_fotoid, ba_text, ba_sperrlist FROM ba_fotos WHERE ba_hide = 0 AND ba_galleryid = ".$galleryid." ORDER BY ba_id ".$galleries[$galleryid]["order"];
    $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 4b

      $fotos_sperrlist = array();
      $fotos_sperr_start = 6;
      $fotos_sperr_end = 22;
      $fotos_sperrtext = "nur sichtbar zwischen 22 und 6 Uhr ".date("T")." / only visible between 10 pm and 6 am ".date("T");

      while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

        $fotos[$datensatz["ba_fotoid"]] = stripslashes($this->html5specialchars($datensatz["ba_text"]));
        if ($datensatz["ba_sperrlist"] == 1) {
          $fotos_sperrlist[] = $datensatz["ba_fotoid"];
        }

      }

      $ersetzen = "<!-- fotos -->\n".
                  "<div id=\"fotos\">\n";

      // GET id auslesen
      if (isset($id) and isset($fotos[$id])) {
        // id vorhanden und nicht NULL

        $hour = intval(date("G"));	// aktuelle stunde

        $hd_title_str .= " - ".$id;

        if (in_array($id, $fotos_sperrlist) and $hour >= $fotos_sperr_start and $hour < $fotos_sperr_end) {
          $ersetzen_f = "";
          $ersetzen_t = "<i>".$fotos_sperrtext."</i>";
        }
        else {
          $imagename = "jpeg/".$id.".jpg";
          if (is_readable($imagename)) {
            $imagesize = getimagesize($imagename);
            $ersetzen_f = "<img class=\"kante\" src=\"".$imagename."\" ".$imagesize[3].">";
          }
          else {
            $ersetzen_f = "Foto";
          }
          $ersetzen_t = $fotos[$id];
        }

      }

      else {
        // keine id

        $hd_title_str .= " - ".$galleries[$galleryid]["alias"];

        $ersetzen_f = "";
        $ersetzen_t = "";

      }

      $ersetzen .= $ersetzen_f."\n".
                   "<br>".$ersetzen_t."\n".
                   "</div>\n".
                   "<div id=\"fotoleistescroll\">\n".
                   "<a href=\"#\" onMouseOver=\"scrollup()\" onMouseOut=\"scrollstop()\"><img class=\"kante_platz_oben_5\" src=\"up.gif\" height=\"10\" width=\"50\"></a>\n".
                   "<noscript>no javascript</noscript>\n".
                   "</div>\n".
                   "<div id=\"fotoleiste\">\n";

      foreach ($fotos as $fotoid => $fototext) {
        $imagename = "jpeg/".$fotoid.".jpg";
        $query_data = array("action" => "fotos", "gallery" => $alias, "id" => $fotoid);
        if (is_readable($imagename) and $image_str = exif_thumbnail($imagename, $width, $height, $type)) {
          $ersetzen .= "<p><a href=\"index.php?".$this->html_build_query($query_data)."\"><img class=\"kantefarbig\" src=\"thumbnail.php?image=".$imagename."\" width=\"".$width."\" height=\"".$height."\" title=\"".stripslashes($this->html5specialchars($fototext))."\"></a></p>\n";
        }
        else {
          $ersetzen .= "<p><a href=\"index.php?".$this->html_build_query($query_data)."\">Foto</a></p>\n";
        }
      }

      $ersetzen .= "</div>\n".
                   "<div id=\"fotoleistescroll\">\n".
                   "<a href=\"#\" onMouseOver=\"scrolldown()\" onMouseOut=\"scrollstop()\"><img class=\"kante_platz_oben_5\" src=\"down.gif\" height=\"10\" width=\"50\"></a>\n".
                   "<noscript>no javascript</noscript>\n".
                   "</div>";

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

    }
    else {
      $errorstring .= "<br>db error 4b\n";
    }

    return array("hd_titel" => $hd_title_str, "inhalt" => $ersetzen, "error" => $errorstring);
  }

  // alle galerien anzeigen
  private function getGalerie($galleries) {
    $ersetzen = "";
    $errorstring = "";

    $columns = 5;	// anzahl spalten in tabelle
    $rows = ceil(sizeof($galleries)/$columns);	// anzahl reihen in tabelle, aufgerundet

    // zugriff auf mysql datenbank (4c), liste aller galerien mit anzahl darin enthaltener fotos
    $sql = "";
    foreach ($galleries as $galleryid => $alias_text_order) {
      $sql .= "SELECT ba_galleryid, ba_fotoid, COUNT(ba_id) FROM ba_fotos WHERE ba_galleryid = ".$galleryid." AND ba_hide = 0".
              "\nUNION\n";
    }
    $sql .= "SELECT 'total', 'picture', COUNT(ba_id) FROM ba_fotos WHERE ba_galleryid > 0 AND ba_hide = 0";
    $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 4c

      $galleries_count = array();
      while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
        if ($datensatz["COUNT(ba_id)"] > 0) {
          // ohne leere galerien
          $galleries_count[$datensatz["ba_galleryid"]]["fotoid"] = $datensatz["ba_fotoid"];
          $galleries_count[$datensatz["ba_galleryid"]]["count"] = $datensatz["COUNT(ba_id)"];
        }
      }

      // leere galerien nicht anzeigen, aus array löschen
      foreach ($galleries as $galleryid => $alias_text_order) {
        if (!in_array($galleryid, array_keys($galleries_count))) {
          unset($galleries[$galleryid]);
        }
      }

      $ersetzen = "<!-- fotos -->\n".
                  "<div id=\"gallery\">\n".
                  "<p><b>Galerien (".sizeof($galleries)."):</b></p>\n".
                  "<table class=\"tb_gallery\">\n";

      for ($row = 0; $row < $rows; $row++) {
        $ersetzen .= "<tr>\n";
        $galleries_in_the_row = array_slice($galleries, $row*$columns, $columns, true);	// (array, offset, length, preserve_keys)
        foreach ($galleries_in_the_row as $galleryid => $alias_text_order) {
          $alias = rawurlencode($alias_text_order["alias"]);
          $imagename = "jpeg/".$galleries_count[$galleryid]["fotoid"].".jpg";
          $ersetzen .= "<td class=\"td_fotos\">\n";
          if (is_readable($imagename) and $image_str = exif_thumbnail($imagename, $width, $height, $type)) {
            // alt       "<a href=\"index.php?action=fotos&gallery=".$alias."\"><img class=\"kantefarbig\" src=\"thumbnail.php?image=".$imagename."\" width=\"".$width."\" height=\"".$height."\"></a>\n";
            $ersetzen .= "<a href=\"fotos/".$alias."/\"><img class=\"kantefarbig\" src=\"thumbnail.php?image=".$imagename."\" width=\"".$width."\" height=\"".$height."\"></a>\n";
          }
          else {
            // alt       "<a href=\"index.php?action=fotos&gallery=".$alias."\">Galerie</a>\n";
            $ersetzen .= "<a href=\"fotos/".$alias."/\">Galerie</a>\n";
          }
          $ersetzen .= "<br>".$alias_text_order["text"]." (".$galleries_count[$galleryid]["count"]." Fotos)\n".
                       "</td>\n";
        }
        $ersetzen .= "</tr>\n";
      }

      $ersetzen .= "</table>\n".
                   "<p>(".$galleries_count["total"]["count"]." Fotos insgesamt)</p>\n".
                   "</div>\n";

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

    }
    else {
      $errorstring .= "<br>db error 4c\n";
    }

    return array("inhalt" => $ersetzen, "error" => $errorstring);
  }

}

?>
