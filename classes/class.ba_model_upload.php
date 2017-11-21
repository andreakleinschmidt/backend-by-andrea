<?php

// *****************************************************************************
// * ba_model - upload
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXSIZE_FILEUPLOAD",2097152);	// 2048*1024 (2 MB default)

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// error fileupload 1 - bei backend POST upload (kein type jpg oder mp4)
// error fileupload 2 - bei backend POST upload (datei schreiben)

class Upload extends Model {

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
  private function listFiles($dir, $filename_extension) {
    $html_backend_ext = "<ul class=\"ul_backend\">\n";
    $i = 0;	// count
    $listfiles = scandir($dir);
    foreach ($listfiles as $file) {
      if (substr($file, -3, 3) == $filename_extension) {
        $html_backend_ext .= "<li class=\"li_backend\">".$file."</li>\n";
        $i++;
      }
    }
    $html_backend_ext .= "<li class=\"li_backend\">".$i." files</li>\n</ul>\n";
    return $html_backend_ext;
  }

  public function getUpload() {
    $html_backend_ext = "<section>\n\n";

    // fileupload formular
    $html_backend_ext .= "<p><b>upload</b></p>\n\n".
                         "<form action=\"backend.php\" method=\"post\" enctype=\"multipart/form-data\">\n".
                         "<input type=\"hidden\" name=\"max_file_size\" value=\"".MAXSIZE_FILEUPLOAD."\">\n".
                         "<input type=\"file\" name=\"upfile\">\n".
                         "<input type=\"submit\" value=\"upload\">\n".
                         "(Limit: ".$this->getMaxsizeUpload().")\n".
                         "</form>\n\n";

    // liste dateien in "jpeg/" (nur *.jpg) und "video/" (nur *.mp4)
    $html_backend_ext .= "<table class=\"backend\">\n<tr>\n<td class=\"td_backend\">jpeg/</td>\n<td class=\"td_backend\">video/</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                         $this->listFiles("jpeg/", "jpg").
                         "</td>\n<td class=\"td_backend\">\n".
                         $this->listFiles("video/", "mp4").
                         "</td>\n</tr>\n</table>\n\n";

    $html_backend_ext .= "</section>\n\n";

    return array("inhalt" => $html_backend_ext);
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

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
