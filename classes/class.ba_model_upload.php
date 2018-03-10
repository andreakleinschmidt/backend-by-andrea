<?php

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2018 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
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
// * ba_model - upload
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXSIZE_FILEUPLOAD",2097152);	// 2048*1024 (2 MB default)

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// error fileupload 1 - bei backend POST upload (kein type jpg oder mp4)
// error fileupload 2 - bei backend POST upload (datei schreiben)

class Upload extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  // MAXSIZE_FILEUPLOAD passende einheit berechnen
  private function getMaxsizeUpload() {
    $size = MAXSIZE_FILEUPLOAD;
    $unit_arr = array("Byte", "kB", "MB", "GB");
    $i = 0;
    while ($size >= 1024.0) {
      $size = $size/1024.0;
      $i++;
    }
    if ($i < count($unit_arr)) {
      $ret = "".$size." ".$unit_arr[$i];
    }
    else {
      $ret = "".MAXSIZE_FILEUPLOAD." ".$unit_arr[0];
    }
    return $ret;
  }

  // liste der dateien im verzeichnis mit dateierweiterung 
  public function listFiles($dir, $filename_extension) {
    $html_backend_ext = "<ul class=\"ul_backend_".$filename_extension."\">\n";
    $i = 0;	// count
    $listfiles = scandir($dir);
    foreach ($listfiles as $file) {
      if (substr($file, -3, 3) == $filename_extension) {
        $filename = $dir.$file;
        $details = array("jpg", "mp4");
        if (in_array($filename_extension, $details)) {
          $html_backend_ext .= "<li class=\"li_backend\" onclick=\"ajax('details','".rawurlencode($filename)."')\">".stripslashes($this->html5specialchars($file))."</li>\n";
        }
        else {
          $html_backend_ext .= "<li class=\"li_backend\">".stripslashes($this->html5specialchars($file))."</li>\n";
        }
        $i++;
      }
    }
    $html_backend_ext .= "<li class=\"li_backend\">".$i." files</li>\n</ul>\n";
    return $html_backend_ext;
  }

  public function getUpload() {
    $html_backend_ext = "<section>\n\n";

    // fileupload formular
    $html_backend_ext .= "<p id=\"upload\"><b>".$this->language["HEADER_UPLOAD"]."</b></p>\n\n".
                         "<form action=\"backend.php\" method=\"post\" enctype=\"multipart/form-data\">\n".
                         "<table class=\"backend\">\n".
                         "<tr>\n<td class=\"td_backend\">\n".
                         "<input type=\"hidden\" name=\"max_file_size\" value=\"".MAXSIZE_FILEUPLOAD."\">\n".
                         "<input type=\"file\" name=\"upfile\">\n".
                         "<input type=\"submit\" value=\"upload\">\n".
                         "(Limit: ".$this->getMaxsizeUpload().")\n".
                         "</td>\n</tr>\n".
                         "</table>\n".
                         "</form>\n\n";

    // liste dateien in "jpeg/" (nur *.jpg) und "video/" (nur *.mp4)
    $html_backend_ext .= "<p id=\"media\"><b>".$this->language["HEADER_MEDIA"]."</b></p>\n\n".
                         "<div id=\"details\"><noscript>no javascript</noscript></div>\n".
                         "<table class=\"backend\">\n".
                         "<tr>\n<td class=\"td_media\">".
                         "jpeg/".
                         "</td>\n<td class=\"td_media\">".
                         "video/".
                         "</td>\n</tr>\n<tr>\n<td class=\"td_media\">\n".
                         $this->listFiles("jpeg/", "jpg").
                         "</td>\n<td class=\"td_media\">\n".
                         $this->listFiles("video/", "mp4").
                         "</td>\n</tr>\n".
                         "</table>\n\n";

    $html_backend_ext .= "</section>\n\n";

    return array("content" => $html_backend_ext);
  }

  public function postUpload($tmp_name, $name) {
    $html_backend_ext = "<section>\n\n";
    $errorstring = "";

    if (function_exists("finfo_open")) {

      $finfo = finfo_open(FILEINFO_MIME_TYPE);	// resource für rückgabe mime type
      if ($finfo) {
        $mimetype = finfo_file($finfo, $tmp_name);

        if ($mimetype == "image/jpeg" and getimagesize($tmp_name)[2] == 2) {
          // getimagesize[2]: 1=gif, 2=jpg, 3=png, 4=swf
          $ret = move_uploaded_file($tmp_name, "jpeg/".$name);
          if ($ret) {
            $html_backend_ext .= "<p>fileupload (jpeg)</p>\n\n";
          }
          else {
            $errorstring .= "<p>error fileupload 2 (jpeg)</p>\n\n";
          }
        }
        elseif ($mimetype == "video/mp4") {
          $ret = move_uploaded_file($tmp_name, "video/".$name);
          if ($ret) {
            $html_backend_ext .= "<p>fileupload (video)</p>\n\n";
          }
          else {
            $errorstring .= "<p>error fileupload 2 (video)</p>\n\n";
          }
        }
        else {
          $errorstring .= "<p>error fileupload 1 (wrong mime type)</p>\n\n";
        } // if mimetype

        finfo_close($finfo);
      } // $finfo
      else {
        $errorstring .= "<p>error fileupload - no ressource finfo</p>\n\n";
      }

    } // module fileinfo
    else {
      $errorstring .= "<p>error fileupload - module fileinfo doesn't exist</p>\n\n";
    }

    $html_backend_ext .= "</section>\n\n";

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
