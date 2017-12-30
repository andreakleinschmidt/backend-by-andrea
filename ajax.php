<?php
header("Content-Type: application/xhtml+xml; charset=utf-8");
session_start();

/* xmlHttp.responseText für GET id suggest (blog formular suche) oder blogphoto
 * parameter falls id=suggest: q (query)
 * parameter falls id=blogphoto: photoid (photoid)
 * weiterer parameter: request (zufallszahl 1...1000)
 */

define("FILENAME","suggest.txt");

// wrapper htmlspecialchars()
function xhtmlspecialchars($str) {
  return htmlspecialchars($str, ENT_COMPAT | ENT_XHTML, "UTF-8");	// als utf-8 (für xmlhttp)
}

// ausgabe für suggest in <div id="suggestion"></div>
function suggest($query) {

  if (!isset($_SESSION["suggest_tab"])) {
    // falls session variable noch nicht existiert

    if (file_exists(FILENAME)) {
      // datei mit wortvorschlägen einlesen

      if ($handle = fopen (FILENAME, "r")) {
        // 64 byte char , while not eof
        while (($buffer = fgets($handle, 64)) !== false) {
          $suggest_tab[] = mb_convert_encoding(trim($buffer), "UTF-8");	// als utf-8 (für xmlhttp)
        }
        fclose($handle);
        $_SESSION["suggest_tab"] = $suggest_tab;	// in SESSION speichern
      } // handle
      else {
        $ret = "suggest handle error";
      }

    } // suggest.txt
    else {
      $ret = "suggest file error";
    }

  } // neue session variable
  else {
    // alte session variable
    $suggest_tab = $_SESSION["suggest_tab"];	// aus SESSION lesen
  }

  // tabelle mit wortvorschlägen
  if (isset($suggest_tab) and count($suggest_tab) > 0) {
    $ret = "";
    foreach($suggest_tab as $suggestion) {
      if ($query == mb_strtolower(mb_substr($suggestion, 0, mb_strlen($query, "UTF-8"), "UTF-8"), "UTF-8")) {
        $ret .= "<a href=\"index.php?".http_build_query(array("action" => "blog", "q" => $suggestion), "", "&amp;", PHP_QUERY_RFC3986)."\">".stripslashes(xhtmlspecialchars($suggestion))."</a><br>\n";
        // vergleich der strings als utf-8, ausgabe als utf-8 (für xmlhttp), übergabe in link als utf-8, strlen problem bei umlaute, substr problem bei trennung umlaute
      }
    }
  } // suggest_tab
  else {
    $ret = "suggest table error";
  }

  return $ret;
}

// ausgabe für blogphoto in <div id="photo_$photoid"></div>
function blogphoto($photoid) {
  $imagename = "jpeg/".$photoid.".jpg";
  if (is_readable($imagename)) {
    $imagesize = getimagesize($imagename);
    $ret = "<img src=\"".$imagename."\" ".$imagesize[3].">";
  }
  else {
    $ret = "photoid file error";
  }
  return $ret;
}

// ausgabe für backend upload media in <div id="details"></div>
function details($filename) {
  if (is_readable($filename)) {
    $details_arr = array();
    $ret = "<table class=\"backend\">\n<tr>\n<td class=\"td_backend\">\n";

    // dateiname
    $details_arr[] = stripslashes(xhtmlspecialchars($filename));

    // mime type
    $mime_type = mime_content_type($filename);
    $details_arr[] = stripslashes(xhtmlspecialchars($mime_type));

    // width x height
    if ($mime_type == "image/jpeg" or $mime_type == "image/png") {
      $imagesize = getimagesize($filename);
      $details_arr[] = $imagesize[0]." x ".$imagesize[1];
    }

    // size (Bytes)
    $Bytes = filesize($filename);
    $kBytes = round(floatval($Bytes)/pow(1024, 1), 2);
    $MBytes = round(floatval($Bytes)/pow(1024, 2), 2);
    if ($MBytes >= 0.9) {
      $details_arr[] = strval($MBytes)." MB (".$Bytes." Bytes)";
    }
    elseif ($kBytes >= 0.9) {
      $details_arr[] = strval($kBytes)." kB (".$Bytes." Bytes)";
    }
    else {
      $details_arr[] = $Bytes." Bytes";
    }

    // datum (rfc2822)
    $details_arr[] = date("r", filemtime($filename));

    // vorschaubild
    if ($mime_type == "image/jpeg" or $mime_type == "image/png") {
      if ($thumbnail = exif_thumbnail($filename, $width, $height, $type)) {
        $ret .= "<img class=\"kantefarbig\" src=\"thumbnail.php?image=".rawurlencode($filename)."\" width=\"".$width."\" height=\"".$height."\">\n";
      }
      else {
        $ret .= "<p>(no thumbnail)</p>\n";
      }
    }
    else {
      $ret .= "<p>(no image)</p>\n";
    }

    $ret .= "</td>\n<td>\n<p>".implode("\n<br>", $details_arr)."</p>\n</td>\n</tr>\n</table>\n";
  }
  else {
    $ret = "filename file error";
  }
  return $ret;
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  // GET auslesen
  if (isset($_GET["id"]) and isset($_GET["request"])) {
    $id = trim($_GET["id"]);	// überflüssige leerzeichen entfernen
    $id = substr($id, 0, 16);	// zu langes GET abschneiden
    $request = intval($_GET["request"]);	// string zu int

    if (strlen($id) > 0 and $request > 0 and $request <= 1000) {
      // id nicht leer, request ok

      if ($id == "suggest") {
        if (isset($_GET["q"])) {
          $query = trim($_GET["q"]);	// überflüssige leerzeichen entfernen, als utf-8 (für xmlhttp)
          $query = mb_strtolower(mb_substr($query, 0, 64, "UTF-8"), "UTF-8");	// zu langes GET abschneiden, alles kleinbuchstaben, substr problem bei trennung umlaute
          if (mb_strlen($query, "UTF-8") > 0) {
            // query nicht leer, strlen problem bei umlaute
            $ret = suggest($query);
          }
          else {
            $ret = "query error";
          }
        } // if GET q
        else {
          $ret = "query not set";
        }
      } // if suggest

      elseif ($id == "blogphoto") {
        if (isset($_GET["photoid"])) {
          $photoid = trim($_GET["photoid"]);	// überflüssige leerzeichen entfernen
          $photoid = substr($photoid, 0, 8);	// zu langes GET abschneiden
          if (strlen($photoid) > 0) {
            // $photoid nicht leer
            $ret = blogphoto($photoid);
          }
          else {
            $ret = "photoid error";
          }
        } // if GET photoid
        else {
          $ret = "photoid not set";
        }
      } // if blogphoto

      elseif ($id == "details") {
        if (isset($_GET["filename"])) {
          $filename = trim($_GET["filename"]);
          if (strlen($filename) > 0) {
            // $filename nicht leer
            $ret = details($filename);
          }
          else {
            $ret = "filename error";
          }
        } // if GET filename
        else {
          $ret = "filename not set";
        }
      } // if details

      else {
        $ret = "default";
      }

      echo $ret;	// ausgabe

    } // id und request ok
    else {
      echo "id or request error";
    }

  } // GET id, request
  else {
    echo "id or request not set";
  }

} // GET
else {
  echo "GET error";
}

?>
