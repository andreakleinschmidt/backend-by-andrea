<?php

// *****************************************************************************
// * ba_model - fotos
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXLEN_GALLERYALIAS",16);
define("MAXLEN_GALLERYTEXT",64);
define("MAXLEN_FOTOID",8);
define("MAXLEN_FOTOTEXT",64);

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 3c - ret bei backend GET fotos
// db error 3k - ret bei backend GET galerie
//
// db error 4c - stmt bei backend POST fotos neu
// db error 4d - stmt bei backend POST fotos
// db error 4i - stmt bei backend POST galerie neu
// db error 4j - stmt bei backend POST galerie
//
// foto error - bei backend POST fotos neu (nicht eingefügt)

class Fotos extends Model {

  public function getFotos($galleryid) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // TABLE ba_gallery (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                   ba_alias VARCHAR(16) NOT NULL,
      //                   ba_text VARCHAR(64) NOT NULL,
      //                   ba_order VARCHAR(4) NOT NULL);

      $html_backend_ext .= "<p><b>galerie</b></p>\n\n".
                           "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">ID</td>\n<td>ALIAS</td>\n<td>TEXT</td>\n<td>ORDER</td>\n</tr>\n".
                           "<tr>\n<td class=\"td_backend\">neu:</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_gallery_new[ba_alias]\" class=\"size_12\" maxlength=\"".MAXLEN_GALLERYALIAS."\" />\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_gallery_new[ba_text]\" class=\"size_40\" maxlength=\"".MAXLEN_GALLERYTEXT."\" />\n".
                           "</td>\n<td>\n".
                           "ASC:<input type=\"radio\" name=\"ba_gallery_new[ba_order]\" value=\"ASC\" checked=\"checked\"/>\n".
                           "DESC:<input type=\"radio\" name=\"ba_gallery_new[ba_order]\" value=\"DESC\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                           "<input type=\"submit\" value=\"new\" />\n".
                           "</td>\n<td>\n</td>\n<td>\n</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // zugriff auf mysql datenbank (1), liste der galerien mit text und reihenfolge
      $sql = "SELECT ba_id, ba_alias, ba_text, ba_order FROM ba_gallery";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3k
        if ($ret->num_rows > 0) {

          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n".
                               "<tr>\n<td class=\"td_backend\">ID</td>\n<td>ALIAS</td>\n<td>TEXT</td>\n<td>ORDER</td>\n<td>DEL</td>\n</tr>\n".
                               "<tr>\n<td class=\"td_backend\">\n".
                               "<a href=\"backend.php?action=fotos&gallery=0\">0</a>\n".
                               "</td>\n<td>\n".
                               "not in gallery\n".
                               "</td>\n<td>---</td>\n<td>---</td>\n<td>---</td>\n</tr>\n";

          while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n".
                                 "<a href=\"backend.php?action=fotos&gallery=".$datensatz["ba_id"]."\">".$datensatz["ba_id"]."</a>\n".
                                 "</td>\n<td>\n".
                                 "<input type=\"text\" name=\"ba_gallery[".$datensatz["ba_id"]."][ba_alias]\" class=\"size_12\" maxlength=\"".MAXLEN_GALLERYALIAS."\" value=\"".stripslashes($this->html5specialchars($datensatz["ba_alias"]))."\"/>\n".
                                 "</td>\n<td>\n".
                                 "<input type=\"text\" name=\"ba_gallery[".$datensatz["ba_id"]."][ba_text]\" class=\"size_40\" maxlength=\"".MAXLEN_GALLERYTEXT."\" value=\"".stripslashes($this->html5specialchars($datensatz["ba_text"]))."\"/>\n".
                                 "</td>\n<td>\n".
                                 "ASC:<input type=\"radio\" name=\"ba_gallery[".$datensatz["ba_id"]."][ba_order]\" value=\"ASC\"";
            if ($datensatz["ba_order"] == "ASC") {
              $html_backend_ext .= " checked=\"checked\"";
            }
            $html_backend_ext .= "/>\n".
                                 "DESC:<input type=\"radio\" name=\"ba_gallery[".$datensatz["ba_id"]."][ba_order]\" value=\"DESC\"";
            if ($datensatz["ba_order"] == "DESC") {
              $html_backend_ext .= " checked=\"checked\"";
            }
            $html_backend_ext .= "/>\n".
                                 "</td>\n<td>\n".
                                 "del:<input type=\"checkbox\" name=\"ba_gallery[".$datensatz["ba_id"]."][]\" value=\"delete\" />\n".
                                 "</td>\n</tr>\n";
            // für tabelle fotos:
            //$galleries[$datensatz["ba_id"]]["alias"] = stripslashes($this->html5specialchars($datensatz["ba_alias"]));
            //$galleries[$datensatz["ba_id"]]["text"] = stripslashes($this->html5specialchars($datensatz["ba_text"]));
            $galleries[$datensatz["ba_id"]]["order"] = stripslashes($this->html5specialchars($datensatz["ba_order"]));
          }

          $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                               "<input type=\"submit\" value=\"POST\" />\n".
                               "</td>\n<td>\n</td>\n<td>\n</td>\n<td>\n</td>\n</tr>\n".
                               "</table>\n".
                               "</form>\n\n";

        } // $ret->num_rows > 0
        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3k</p>\n\n";
      }

      // TABLE ba_fotos (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                 ba_galleryid INT UNSIGNED NOT NULL,
      //                 ba_fotoid VARCHAR(8) NOT NULL,
      //                 ba_text VARCHAR(64) NOT NULL,
      //                 ba_sperrlist TINYINT UNSIGNED NOT NULL,
      //                 ba_hide TINYINT UNSIGNED NOT NULL);

      // GET gallery auslesen
      if (isset($galleryid) AND is_numeric($galleryid) AND (isset($galleries[$galleryid]) OR $galleryid == 0)) {
        // gallery als zahl vorhanden und nicht NULL
        // nur die gewählte galerie anzeigen

        $html_backend_ext .= "<p><b>fotos (galerie ".$galleryid.")</b></p>\n\n".
                             "<form action=\"backend.php\" method=\"post\">\n".
                             "<table class=\"backend\">\n".
                             "<tr>\n<td class=\"td_backend\">ID</td>\n<td>FOTOID</td>\n<td>TEXT</td>\n<td>TEMP/HIDE</td>\n</tr>\n".
                             "<tr>\n<td class=\"td_backend\">neu:</td>\n<td>\n".
                             "<input type=\"hidden\" name=\"ba_fotos_new[ba_galleryid]\" value=\"".$galleryid."\"/>\n".
                             "<input type=\"text\" name=\"ba_fotos_new[ba_fotoid]\" class=\"size_4\" maxlength=\"".MAXLEN_FOTOID."\" />\n".
                             "</td>\n<td>\n".
                             "<input type=\"text\" name=\"ba_fotos_new[ba_text]\" class=\"size_32\" maxlength=\"".MAXLEN_FOTOTEXT."\" />\n".
                             "</td>\n<td>\n".
                             "temp:<input type=\"checkbox\" name=\"ba_fotos_new[]\" value=\"sperrlist\"/>\n".
                             "hide:<input type=\"checkbox\" name=\"ba_fotos_new[]\" value=\"hide\"/>\n".
                             "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                             "<input type=\"submit\" value=\"new\" />\n".
                             "</td>\n<td>\n</td>\n<td>\n</td>\n</tr>\n".
                             "</table>\n".
                             "</form>\n\n";

        // zugriff auf mysql datenbank (2)
        $sql = "SELECT ba_id, ba_galleryid, ba_fotoid, ba_text, ba_sperrlist, ba_hide FROM ba_fotos WHERE ba_galleryid = ".$galleryid." ORDER BY ba_id ".$galleries[$galleryid]["order"];
        $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
        if ($ret) {
          // wenn kein fehler 3c
          if ($ret->num_rows > 0) {

            $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                                 "<table class=\"backend\">\n".
                                 "<tr>\n<td class=\"td_backend\">ID</td>\n<td>G.ID</td>\n<td>FOTOID</td>\n<td>TEXT</td>\n<td>TEMP/HIDE</td>\n<td>DELETE</td>\n</tr>\n";

            while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
              $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n".
                                   $datensatz["ba_id"].
                                   "</td>\n<td>\n".
                                   "<select name=\"ba_fotos[".$datensatz["ba_id"]."][ba_galleryid]\" size=\"1\">\n".
                                   "<option value=\"0\">0</option>\n";
                                   foreach ($galleries as $id => $alias_text_order) {
                                     $html_backend_ext .= "<option value=\"".$id."\"";
                                     if ($id == $galleryid) {
                                       $html_backend_ext .= " selected=\"selected\"";
                                     }
                                     $html_backend_ext .= ">".$id."</option>\n";
                                   }
              $html_backend_ext .= "</select>\n".
                                   "</td>\n<td>\n".
                                   "<input type=\"text\" name=\"ba_fotos[".$datensatz["ba_id"]."][ba_fotoid]\" class=\"size_4\" maxlength=\"".MAXLEN_FOTOID."\" value=\"".stripslashes($this->html5specialchars($datensatz["ba_fotoid"]))."\"/>\n".
                                   "</td>\n<td>\n".
                                   "<input type=\"text\" name=\"ba_fotos[".$datensatz["ba_id"]."][ba_text]\" class=\"size_32\" maxlength=\"".MAXLEN_FOTOTEXT."\" value=\"".stripslashes($this->html5specialchars($datensatz["ba_text"]))."\"/>\n".
                                   "</td>\n<td>\n".
                                   "temp:<input type=\"checkbox\" name=\"ba_fotos[".$datensatz["ba_id"]."][]\" value=\"sperrlist\"";
              if ($datensatz["ba_sperrlist"] == 1) {
                $html_backend_ext .= " checked=\"checked\"";
              }
              $html_backend_ext .= "/>\n".
                                   "hide:<input type=\"checkbox\" name=\"ba_fotos[".$datensatz["ba_id"]."][]\" value=\"hide\"";
              if ($datensatz["ba_hide"] == 1) {
                $html_backend_ext .= " checked=\"checked\"";
              }
              $html_backend_ext .= "/>\n".
                                   "</td>\n<td>\n".
                                   "del:<input type=\"checkbox\" name=\"ba_fotos[".$datensatz["ba_id"]."][]\" value=\"delete\" />\n".
                                   "</td>\n</tr>\n";
            }

            $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                                 "<input type=\"submit\" value=\"POST\" />\n".
                                 "</td>\n<td>\n</td>\n<td>\n</td>\n<td>\n</td>\n<td>\n</td>\n</tr>\n".
                                 "</table>\n".
                                 "</form>\n\n";

          } // $ret->num_rows > 0
          $ret->close();	// db-ojekt schließen
          unset($ret);	// referenz löschen

        }
        else {
          $errorstring .= "<p>db error 3c</p>\n\n";
        }

      }
      else {
        $html_backend_ext .= "<p>keine galerie gewählt</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postGalleryNew($ba_alias, $ba_text, $ba_order) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_gallery (ba_alias, ba_text, ba_order) VALUES (?, ?, ?)";
      $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4i

        // austauschen ??? durch strings
        $stmt->bind_param("sss", $ba_alias, $ba_text, $ba_order);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>done</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>gallery error</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4c</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postGallery($ba_gallery_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $count = 0;

      foreach ($ba_gallery_array_replaced as $ba_id => $ba_array) {
        $ba_alias = $ba_array["ba_alias"];
        $ba_text = $ba_array["ba_text"];
        $ba_order = $ba_array["ba_order"];
        $ba_delete = $ba_array["delete"];

        // update oder löschen in datenbank , mit prepare() - sql injections verhindern
        if ($ba_delete) {
          $sql = "DELETE FROM ba_gallery WHERE ba_id = ?";
        }
        else {
          $sql = "UPDATE ba_gallery SET ba_alias = ?, ba_text = ?, ba_order = ? WHERE ba_id = ?";
        }
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4j

          // austauschen ? oder ???? durch strings und int
          if ($ba_delete) {
            $stmt->bind_param("i", $ba_id);
          }
          else {
            $stmt->bind_param("sssi", $ba_alias, $ba_text, $ba_order, $ba_id);
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4j</p>\n\n";
        }

      }

      $html_backend_ext .= "<p>".$count." rows changed</p>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postFotosNew($ba_galleryid, $ba_fotoid, $ba_text, $ba_sperrlist, $ba_hide) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_fotos (ba_galleryid, ba_fotoid, ba_text, ba_sperrlist, ba_hide) VALUES (?, ?, ?, ?, ?)";
      $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4c

        // austauschen ????? durch strings und int
        $stmt->bind_param("issii", $ba_galleryid, $ba_fotoid, $ba_text, $ba_sperrlist, $ba_hide);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>done</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>foto error</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4c</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postFotos($ba_fotos_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $count = 0;

      foreach ($ba_fotos_array_replaced as $ba_id => $ba_array) {
        $ba_galleryid = $ba_array["ba_galleryid"];
        $ba_fotoid = $ba_array["ba_fotoid"];
        $ba_text = $ba_array["ba_text"];
        $ba_sperrlist = $ba_array["sperrlist"];
        $ba_hide = $ba_array["hide"];
        $ba_delete = $ba_array["delete"];

        // update oder löschen in datenbank , mit prepare() - sql injections verhindern
        if ($ba_delete) {
          $sql = "DELETE FROM ba_fotos WHERE ba_id = ?";
        }
        else {
          $sql = "UPDATE ba_fotos SET ba_galleryid = ?, ba_fotoid = ?, ba_text = ?, ba_sperrlist = ?, ba_hide = ? WHERE ba_id = ?";
        }
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4d

          // austauschen ? oder ?????? durch string und int
          if ($ba_delete) {
            $stmt->bind_param("i", $ba_id);
          }
          else {
            $stmt->bind_param("issiii", $ba_galleryid, $ba_fotoid, $ba_text, $ba_sperrlist, $ba_hide, $ba_id);
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4d</p>\n\n";
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
