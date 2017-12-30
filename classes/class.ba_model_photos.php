<?php

// *****************************************************************************
// * ba_model - photos
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXLEN_GALLERYALIAS",16);
define("MAXLEN_GALLERYTEXT",64);
define("MAXLEN_PHOTOID",8);
define("MAXLEN_PHOTOTEXT",64);

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 3c - ret bei backend GET photos
// db error 3k - ret bei backend GET gallery
//
// db error 4c - stmt bei backend POST photos neu
// db error 4d - stmt bei backend POST photos
// db error 4i - stmt bei backend POST gallery neu
// db error 4j - stmt bei backend POST gallery
//
// photo error - bei backend POST photos neu (nicht eingefügt)

class Photos extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  public function getPhotos($galleryid) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // TABLE ba_gallery (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                   ba_alias VARCHAR(16) NOT NULL,
      //                   ba_text VARCHAR(64) NOT NULL,
      //                   ba_order VARCHAR(4) NOT NULL);

      $html_backend_ext .= "<p id=\"gallery\"><b>".$this->language["HEADER_GALLERY"]."</b></p>\n\n".
                           "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<th>".
                           $this->language["TABLE_HD_ID"].
                           "</th>\n<th>".
                           $this->language["TABLE_HD_ALIAS"].
                           "</th>\n<th>".
                           $this->language["TABLE_HD_TEXT"].
                           "</th>\n<th>".
                           $this->language["TABLE_HD_ORDER"].
                           "</th>\n</tr>\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_NEW"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_gallery_new[ba_alias]\" class=\"size_12\" maxlength=\"".MAXLEN_GALLERYALIAS."\" />".
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_gallery_new[ba_text]\" class=\"size_40\" maxlength=\"".MAXLEN_GALLERYTEXT."\" />".
                           "</td>\n<td>\n".
                           $this->language["PROMPT_ASCENDING"]."<input type=\"radio\" name=\"ba_gallery_new[ba_order]\" value=\"ASC\" checked=\"checked\"/>\n<br>".
                           $this->language["PROMPT_DESCENDING"]."<input type=\"radio\" name=\"ba_gallery_new[ba_order]\" value=\"DESC\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_NEW"]."\" />".
                           "</td>\n<td></td>\n<td></td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // zugriff auf mysql datenbank (1), liste der galerien mit text und reihenfolge
      $sql = "SELECT ba_id, ba_alias, ba_text, ba_order FROM ba_gallery";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3k
        if ($ret->num_rows > 0) {

          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n".
                               "<tr>\n<th>".
                               $this->language["TABLE_HD_ID"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_ALIAS"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_TEXT"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_ORDER"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_DELETE"].
                               "</th>\n</tr>\n".
                               "<tr>\n<td class=\"td_backend\">".
                               "<a href=\"backend.php?".$this->html_build_query(array("action" => "photos", "gallery" => 0))."\">0</a>".
                               "</td>\n<td>".
                               $this->language["MSG_NOT_IN_GALLERY"].
                               "</td>\n<td>---</td>\n<td>---</td>\n<td>---</td>\n</tr>\n";

          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $html_backend_ext .= "<tr>\n<td class=\"td_backend\">".
                                 "<a href=\"backend.php?".$this->html_build_query(array("action" => "photos", "gallery" => $dataset["ba_id"]))."\">".$dataset["ba_id"]."</a>".
                                 "</td>\n<td>".
                                 "<input type=\"text\" name=\"ba_gallery[".$dataset["ba_id"]."][ba_alias]\" class=\"size_12\" maxlength=\"".MAXLEN_GALLERYALIAS."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_alias"]))."\"/>".
                                 "</td>\n<td>".
                                 "<input type=\"text\" name=\"ba_gallery[".$dataset["ba_id"]."][ba_text]\" class=\"size_40\" maxlength=\"".MAXLEN_GALLERYTEXT."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_text"]))."\"/>".
                                 "</td>\n<td>\n".
                                 $this->language["PROMPT_ASCENDING"]."<input type=\"radio\" name=\"ba_gallery[".$dataset["ba_id"]."][ba_order]\" value=\"ASC\"";
            if ($dataset["ba_order"] == "ASC") {
              $html_backend_ext .= " checked=\"checked\"";
            }
            $html_backend_ext .= "/>\n<br>".
                                 $this->language["PROMPT_DESCENDING"]."<input type=\"radio\" name=\"ba_gallery[".$dataset["ba_id"]."][ba_order]\" value=\"DESC\"";
            if ($dataset["ba_order"] == "DESC") {
              $html_backend_ext .= " checked=\"checked\"";
            }
            $html_backend_ext .= "/>\n".
                                 "</td>\n<td>".
                                 $this->language["PROMPT_DELETE"]."<input type=\"checkbox\" name=\"ba_gallery[".$dataset["ba_id"]."][]\" value=\"delete\" />".
                                 "</td>\n</tr>\n";
            // für tabelle fotos:
            //$galleries[$dataset["ba_id"]]["alias"] = stripslashes($this->html5specialchars($dataset["ba_alias"]));
            //$galleries[$dataset["ba_id"]]["text"] = stripslashes($this->html5specialchars($dataset["ba_text"]));
            $galleries[$dataset["ba_id"]]["order"] = stripslashes($this->html5specialchars($dataset["ba_order"]));
          }

          $html_backend_ext .= "<tr>\n<td class=\"td_backend\"></td>\n<td>".
                               "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />".
                               "</td>\n<td></td>\n<td></td>\n<td></td>\n</tr>\n".
                               "</table>\n".
                               "</form>\n\n";

        } // $ret->num_rows > 0
        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3k</p>\n\n";
      }

      // TABLE ba_photos (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                  ba_galleryid INT UNSIGNED NOT NULL,
      //                  ba_photoid VARCHAR(8) NOT NULL,
      //                  ba_text VARCHAR(64) NOT NULL,
      //                  ba_hide TINYINT UNSIGNED NOT NULL);

      // GET gallery auslesen
      if (isset($galleryid) and is_numeric($galleryid) and (isset($galleries[$galleryid]) or $galleryid == 0)) {
        // gallery als zahl vorhanden und nicht NULL
        // nur die gewählte galerie anzeigen

        $html_backend_ext .= "<p id=\"photos\"><b>".$this->language["HEADER_PHOTOS"]." (".$this->language["HEADER_GALLERY"]." ".$galleryid.")</b></p>\n\n".
                             "<form action=\"backend.php\" method=\"post\">\n".
                             "<table class=\"backend\">\n".
                             "<tr>\n<th>".
                             $this->language["TABLE_HD_ID"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_PHOTOID"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_TEXT"].
                             "</th>\n<th>".
                             $this->language["TABLE_HD_HIDE"].
                             "</th>\n</tr>\n".
                             "<tr>\n<td class=\"td_backend\">".
                             $this->language["PROMPT_NEW"].
                             "</td>\n<td>\n".
                             "<input type=\"hidden\" name=\"ba_photos_new[ba_galleryid]\" value=\"".$galleryid."\"/>\n".
                             "<input type=\"text\" name=\"ba_photos_new[ba_photoid]\" class=\"size_4\" maxlength=\"".MAXLEN_PHOTOID."\" />\n".
                             "</td>\n<td>".
                             "<input type=\"text\" name=\"ba_photos_new[ba_text]\" class=\"size_32\" maxlength=\"".MAXLEN_PHOTOTEXT."\" />".
                             "</td>\n<td>".
                             $this->language["PROMPT_HIDE"]."<input type=\"checkbox\" name=\"ba_photos_new[]\" value=\"hide\"/>".
                             "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                             "<input type=\"submit\" value=\"".$this->language["BUTTON_NEW"]."\" />".
                             "</td>\n<td></td>\n<td></td>\n</tr>\n".
                             "</table>\n".
                             "</form>\n\n";

        // zugriff auf mysql datenbank (2)
        $sql = "SELECT ba_id, ba_galleryid, ba_photoid, ba_text, ba_hide FROM ba_photos WHERE ba_galleryid = ".$galleryid." ORDER BY ba_id ".$galleries[$galleryid]["order"];
        $ret = $this->database->query($sql);	// liefert in return db-objekt
        if ($ret) {
          // wenn kein fehler 3c
          if ($ret->num_rows > 0) {

            $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                                 "<table class=\"backend\">\n".
                                 "<tr>\n<th>".
                                 $this->language["TABLE_HD_ID"].
                                 "</th>\n<th>".
                                 $this->language["TABLE_HD_GID"].
                                 "</th>\n<th>".
                                 $this->language["TABLE_HD_PHOTOID"].
                                 "</th>\n<th>".
                                 $this->language["TABLE_HD_TEXT"].
                                 "</th>\n<th>".
                                 $this->language["TABLE_HD_HIDE"].
                                 "</th>\n<th>".
                                 $this->language["TABLE_HD_DELETE"].
                                 "</th>\n</tr>\n";

            while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
              $html_backend_ext .= "<tr>\n<td class=\"td_backend\">".
                                   $dataset["ba_id"].
                                   "</td>\n<td>\n".
                                   "<select name=\"ba_photos[".$dataset["ba_id"]."][ba_galleryid]\" size=\"1\">\n".
                                   "<option value=\"0\">0</option>\n";
                                   foreach ($galleries as $id => $alias_text_order) {
                                     $html_backend_ext .= "<option value=\"".$id."\"";
                                     if ($id == $galleryid) {
                                       $html_backend_ext .= " selected=\"selected\"";
                                     }
                                     $html_backend_ext .= ">".$id."</option>\n";
                                   }
              $html_backend_ext .= "</select>\n".
                                   "</td>\n<td>".
                                   "<input type=\"text\" name=\"ba_photos[".$dataset["ba_id"]."][ba_photoid]\" class=\"size_4\" maxlength=\"".MAXLEN_PHOTOID."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_photoid"]))."\"/>".
                                   "</td>\n<td>".
                                   "<input type=\"text\" name=\"ba_photos[".$dataset["ba_id"]."][ba_text]\" class=\"size_32\" maxlength=\"".MAXLEN_PHOTOTEXT."\" value=\"".stripslashes($this->html5specialchars($dataset["ba_text"]))."\"/>".
                                   "</td>\n<td>".
                                   $this->language["PROMPT_HIDE"]."<input type=\"checkbox\" name=\"ba_photos[".$dataset["ba_id"]."][]\" value=\"hide\"";
              if ($dataset["ba_hide"] == 1) {
                $html_backend_ext .= " checked=\"checked\"";
              }
              $html_backend_ext .= "/>".
                                   "</td>\n<td>".
                                   $this->language["PROMPT_DELETE"]."<input type=\"checkbox\" name=\"ba_photos[".$dataset["ba_id"]."][]\" value=\"delete\" />".
                                   "</td>\n</tr>\n";
            }

            $html_backend_ext .= "<tr>\n<td class=\"td_backend\"></td>\n<td>".
                                 "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />".
                                 "</td>\n<td></td>\n<td></td>\n<td></td>\n<td></td>\n</tr>\n".
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
        $html_backend_ext .= "<p>".$this->language["MSG_NO_GALLERY_CHOSEN"]."</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postGalleryNew($ba_alias, $ba_text, $ba_order) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_gallery (ba_alias, ba_text, ba_order) VALUES (?, ?, ?)";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4i

        // austauschen ??? durch strings
        $stmt->bind_param("sss", $ba_alias, $ba_text, $ba_order);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>".$this->language["MSG_DONE"]."</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>".$this->language["MSG_GALLERY_ERROR"]."</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4c</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postGallery($ba_gallery_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

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
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
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

      $html_backend_ext .= "<p>".$count." ".$this->language["MSG_ROWS_CHANGED"]."</p>\n\n";

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postPhotosNew($ba_galleryid, $ba_photoid, $ba_text, $ba_hide) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_photos (ba_galleryid, ba_photoid, ba_text, ba_hide) VALUES (?, ?, ?, ?)";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4c

        // austauschen ???? durch strings und int
        $stmt->bind_param("issi", $ba_galleryid, $ba_photoid, $ba_text, $ba_hide);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>".$this->language["MSG_DONE"]."</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>".$this->language["MSG_PHOTO_ERROR"]."</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4c</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postPhotos($ba_photos_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      foreach ($ba_photos_array_replaced as $ba_id => $ba_array) {
        $ba_galleryid = $ba_array["ba_galleryid"];
        $ba_photoid = $ba_array["ba_photoid"];
        $ba_text = $ba_array["ba_text"];
        $ba_hide = $ba_array["hide"];
        $ba_delete = $ba_array["delete"];

        // update oder löschen in datenbank , mit prepare() - sql injections verhindern
        if ($ba_delete) {
          $sql = "DELETE FROM ba_photos WHERE ba_id = ?";
        }
        else {
          $sql = "UPDATE ba_photos SET ba_galleryid = ?, ba_photoid = ?, ba_text = ?, ba_hide = ? WHERE ba_id = ?";
        }
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4d

          // austauschen ? oder ????? durch string und int
          if ($ba_delete) {
            $stmt->bind_param("i", $ba_id);
          }
          else {
            $stmt->bind_param("issii", $ba_galleryid, $ba_photoid, $ba_text, $ba_hide, $ba_id);
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4d</p>\n\n";
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
