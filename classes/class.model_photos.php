<?php

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2025 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
 *
 * This program includes a MERGED version of PHP QR Code library
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// *****************************************************************************
// * model - photos
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

class Photos extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  // text zu photoid
  public function getText($photoid) {
    $ret = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank
      $sql = "SELECT ba_text FROM ba_photos WHERE ba_photoid = ?";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler

        // austauschen ? durch string
        $stmt->bind_param("s", $photoid);
        $stmt->execute();	// ausführen geänderte zeile

        $stmt->bind_result($dataset["ba_text"]);
        // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

        if ($stmt->fetch()) {
          // wenn kein fehler (text nicht vorhanden, datensatz leer)
          $ret = stripslashes($this->html5specialchars($dataset["ba_text"]));
        }

        $stmt->close();
        unset($stmt);	// referenz löschen

      } // stmt

    } // datenbank

    return $ret;
  }

  public function getPhotos($alias, $id, $bannerstrip_array=array()) {
    $hd_title_str = "";
    $replace = "";
    $errorstring = "";

    //$alias = rawurldecode($alias);

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank (4a), liste der galerien mit alias, text und reihenfolge
      $sql = "SELECT ba_id, ba_alias, ba_text, ba_order FROM ba_gallery";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 4a

        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $galleries[$dataset["ba_id"]]["alias"] = stripslashes($this->html5specialchars($dataset["ba_alias"]));
          $galleries[$dataset["ba_id"]]["text"] = stripslashes($this->html5specialchars($dataset["ba_text"]));
          $galleries[$dataset["ba_id"]]["order"] = stripslashes($this->html5specialchars($dataset["ba_order"]));
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

          $ret_array = $this->getPhoto($galleries, $galleryid, $id);
          $hd_title_str = $ret_array["hd_title_ext"];

        } // GET gallery
        else {
          // alle galerien anzeigen

          $ret_array = $this->getGallery($galleries, $bannerstrip_array);

        } // galerien anzeigen

        $replace = $ret_array["content"];
        $errorstring .= $ret_array["error"];

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "db error 4a\n";
      }

    } // datenbank
    else {
      $errorstring .= "db error\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("hd_title_ext" => $hd_title_str, "content" => $replace, "error" => $errorstring);
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
  private function getPhoto($galleries, $galleryid, $id) {
    $hd_title_str = "";
    $replace = "";
    $errorstring = "";

    $alias = rawurlencode($galleries[$galleryid]["alias"]);

    // zugriff auf mysql datenbank (4b)
    $sql = "SELECT ba_photoid, ba_text FROM ba_photos WHERE ba_hide = 0 AND ba_galleryid = ".$galleryid." ORDER BY ba_id ".$galleries[$galleryid]["order"];
    $ret = $this->database->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 4b

      while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
        $photos[$dataset["ba_photoid"]] = stripslashes($this->html5specialchars($dataset["ba_text"]));
      }

      $replace = "<!-- photos -->\n".
                 "<div id=\"grid-container-photos\">\n".
                 "<div id=\"photos\">\n";

      // GET id auslesen
      if (isset($id) and isset($photos[$id])) {
        // id vorhanden und nicht NULL

        $hd_title_str .= " - ".$id;

        $imagename = "jpeg/".$id.".jpg";
        if (is_readable($imagename)) {
          $imagesize = getimagesize($imagename);
          $replace_photo = "<img class=\"border\" src=\"".$imagename."\" ".$imagesize[3].">";
        }
        else {
          $replace_photo = $this->language["FRONTEND_PHOTO"];
        }
        $replace_text = $photos[$id];

        $replace_figure = "<figure>\n".
                          $replace_photo."\n".
                          "<figcaption>".$replace_text."</figcaption>\n".
                          "</figure>\n";

        // vor und zurück
        $keys = array_keys($photos);	// array mit ba_photoid
        $current = array_search($id, $keys);
        if (isset($keys[$current-1])) {
          $prev_id = $keys[$current-1];
          $query_data = array("action" => "photos", "gallery" => $alias, "id" => $prev_id);
          $replace_figure .= "<a href=\"index.php?".$this->html_build_query($query_data)."\">".$this->language["PAGE_PREVIOUS"]."</a>\n";
        }
        if (isset($keys[$current+1])) {
          $next_id = $keys[$current+1];
          $query_data = array("action" => "photos", "gallery" => $alias, "id" => $next_id);
          $replace_figure .= "<a href=\"index.php?".$this->html_build_query($query_data)."\">".$this->language["PAGE_NEXT"]."</a>\n";
        }

      }
      else {
        // keine id

        $hd_title_str .= " - ".$galleries[$galleryid]["alias"];

        $replace_figure = "";
      }

      $replace .= $replace_figure.
                  "</div>\n".
                  "<div id=\"photostripscroll\">\n".
                  "<a href=\"#\" onMouseOver=\"scrollup()\" onMouseOut=\"scrollstop()\"><img class=\"photos_arrow\" src=\"up.gif\" height=\"10\" width=\"50\"></a>\n".
                  "<noscript>no javascript</noscript>\n".
                  "</div>\n".
                  "<div id=\"photostrip\">\n";

      foreach ($photos as $photoid => $phototext) {
        $imagename = "jpeg/".$photoid.".jpg";
        $query_data = array("action" => "photos", "gallery" => $alias, "id" => $photoid);
        if (is_readable($imagename) and $image_str = exif_thumbnail($imagename, $width, $height, $type)) {
          $replace .= "<p><a href=\"index.php?".$this->html_build_query($query_data)."\"><img class=\"thumbnail\" src=\"thumbnail.php?image=".$imagename."\" width=\"".$width."\" height=\"".$height."\" title=\"".stripslashes($this->html5specialchars($phototext))."\"></a></p>\n";
        }
        else {
          $replace .= "<p><a href=\"index.php?".$this->html_build_query($query_data)."\">".$this->language["FRONTEND_PHOTO"]."</a></p>\n";
        }
      }

      $replace .= "</div>\n".
                  "<div id=\"photostripscroll\">\n".
                  "<a href=\"#\" onMouseOver=\"scrolldown()\" onMouseOut=\"scrollstop()\"><img class=\"photos_arrow\" src=\"down.gif\" height=\"10\" width=\"50\"></a>\n".
                  "<noscript>no javascript</noscript>\n".
                  "</div>\n".
                  "</div>";

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

    }
    else {
      $errorstring .= "db error 4b\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("hd_title_ext" => $hd_title_str, "content" => $replace, "error" => $errorstring);
  }

  // alle galerien anzeigen
  private function getGallery($galleries, $bannerstrip_array) {
    $replace = "";
    $errorstring = "";

    $columns = 3;	// anzahl spalten in tabelle
    $rows = ceil(sizeof($galleries)/$columns);	// anzahl reihen in tabelle, aufgerundet

    $replace .= "<!-- photos -->\n".
                "<div id=\"grid-container-gallery\">\n";

    // zugriff auf mysql datenbank (4c), liste aller galerien mit anzahl darin enthaltener fotos
    $sql = "";
    foreach ($galleries as $galleryid => $alias_text_order) {
      $sql .= "SELECT ba_galleryid, ba_photoid, COUNT(ba_id) FROM ba_photos WHERE ba_galleryid = ".$galleryid." AND ba_hide = 0".
              "\nUNION\n";
    }
    $sql .= "SELECT 'total', 'picture', COUNT(ba_id) FROM ba_photos WHERE ba_galleryid > 0 AND ba_hide = 0";
    $ret = $this->database->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 4c

      $galleries_count = array();
      while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
        if ($dataset["COUNT(ba_id)"] > 0) {
          // ohne leere galerien
          $galleries_count[$dataset["ba_galleryid"]]["photoid"] = $dataset["ba_photoid"];
          $galleries_count[$dataset["ba_galleryid"]]["count"] = $dataset["COUNT(ba_id)"];
        }
      }

      // leere galerien nicht anzeigen, aus array löschen
      foreach ($galleries as $galleryid => $alias_text_order) {
        if (!in_array($galleryid, array_keys($galleries_count))) {
          unset($galleries[$galleryid]);
        }
      }

      $replace .= "<div id=\"gallery\">\n".
                  "<p><b>".$this->language["FRONTEND_GALLERIES"]." (".sizeof($galleries)."):</b></p>\n".
                  "<table class=\"tb_gallery\">\n";

      for ($row = 0; $row < $rows; $row++) {
        $replace .= "<tr>\n";
        $galleries_in_the_row = array_slice($galleries, $row*$columns, $columns, true);	// (array, offset, length, preserve_keys)
        foreach ($galleries_in_the_row as $galleryid => $alias_text_order) {
          $alias = rawurlencode($alias_text_order["alias"]);
          $imagename = "jpeg/".$galleries_count[$galleryid]["photoid"].".jpg";
          $replace .= "<td class=\"td_photos\">\n";
          if (is_readable($imagename) and $image_str = exif_thumbnail($imagename, $width, $height, $type)) {
            // alt      "<a href=\"index.php?action=photos&gallery=".$alias."\"><img class=\"thumbnail\" src=\"thumbnail.php?image=".$imagename."\" width=\"".$width."\" height=\"".$height."\"></a>\n";
            $replace .= "<a href=\"photos/".$alias."/\"><img class=\"thumbnail\" src=\"thumbnail.php?image=".$imagename."\" width=\"".$width."\" height=\"".$height."\"></a>\n";
          }
          else {
            // alt      "<a href=\"index.php?action=photos&gallery=".$alias."\">Galerie</a>\n";
            $replace .= "<a href=\"photos/".$alias."/\">".$this->language["FRONTEND_GALLERY"]."</a>\n";
          }
          $replace .= "<br>".$alias_text_order["text"]." (".$galleries_count[$galleryid]["count"]." ".$this->language["FRONTEND_PHOTOS"].")\n".
                      "</td>\n";
        }
        $replace .= "</tr>\n";
      }

      $replace .= "</table>\n".
                  "<p>(".$galleries_count["total"]["count"]." ".$this->language["FRONTEND_PHOTOS_TOTAL"].")</p>\n".
                  "</div>\n";	// gallery

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

    }
    else {
      $errorstring .= "db error 4c\n";
    }

    // {content} erweitern mit bannerstrip
    if(!empty($bannerstrip_array)) {
      $replace .= $bannerstrip_array["bannerstrip"];
      $errorstring .= $bannerstrip_array["error"];
    }

    $replace .= "</div>";	// grid-container

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $replace, "error" => $errorstring);
  }

}

?>
