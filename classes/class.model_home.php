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

  public function getHome($bannerstrip_array=array()) {
    $replace = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $replace .= "<!-- home -->\n".
                  "<div id=\"grid-container-home\">\n";

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_element, ba_css, ba_value FROM ba_home";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 2

        $replace .= "<div id=\"home\">\n";

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

        $replace .= "</div>\n";	// home

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "db error 2\n";
      }

      // {content} erweitern mit bannerstrip
      if(!empty($bannerstrip_array)) {
        $replace .= $bannerstrip_array["bannerstrip"];
        $errorstring .= $bannerstrip_array["error"];
      }

      $replace .= "</div>";	// grid-container

    } // datenbank
    else {
      $errorstring .= "db error\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("content" => $replace, "error" => $errorstring);
  }

}

?>
