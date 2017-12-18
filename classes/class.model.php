<?php

// *****************************************************************************
// * model
// * - daten aus datenbank holen
// * - daten aufbereiten
// * - daten an controller zurückgeben
// *****************************************************************************

define("STATE_PUBLISHED",3);
define("MB_ENCODING","UTF-8");

class Model {

  //private $datenbank;

  // konstruktor
  public function __construct() {
    $this->datenbank = @new Database();	// @ unterdrückt fehlermeldung
    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler
      $this->datenbank->set_charset("utf8");	// change character set to utf8
    }
  }

  // wrapper htmlspecialchars()
  public function html5specialchars($str) {
    return htmlspecialchars($str, ENT_COMPAT | ENT_HTML5, "UTF-8");
  }

  // input array("x" => "y z", "a" => "b")
  // return query "x=y%20z&amp;a=123" ("x=y z&a=b")
  public function html_build_query($query_data) {
    return http_build_query($query_data, "", "&amp;", PHP_QUERY_RFC3986);
  }

  // file_get_contents mit cURL
  public function file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);		// kein header in output data
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	// output data als string (sonst true), bei fehler false
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);		// 3s timeout
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);	// sonst default leer

    $data = curl_exec($ch);

    curl_close($ch);

    return $data;
  }

  // xml feed lesen
  private function readXML($content) {

/******************************************************************************

rss:
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
  <channel>
    <title>Titel</title>
    <link>URL</link>
    <description>Beschreibung</description>
  </channel>
</rss>

atom:
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title type="text">Titel</title>
  <link href="URL"/>
  <subtitle></subtitle>
</feed>

******************************************************************************/

    if ($xml = @simplexml_load_string($content)) {
      $rc = 0;	// ok

      $title = "unknown feed";
      $link = "#";
      $description80 = "";

      if ($xml->getName() == "rss") {
        // rss feed
        if (isset($xml->channel->title) and isset($xml->channel->link)) {
          $title = stripslashes($this->html5specialchars($xml->channel->title));
          $link = $xml->channel->link;
          if (isset($xml->channel->description)) {
            $description80 = stripslashes($this->html5specialchars(mb_substr($xml->channel->description,0 ,80, MB_ENCODING)));	// substr problem bei trennung umlaute
          }
        }
      } // rss feed
      elseif ($xml->getName() == "feed") {
        // atom feed
        if (isset($xml->title) and isset($xml->link)) {
          $title = stripslashes($this->html5specialchars($xml->title));
          $link = $xml->link["href"];
          // alternate link
          foreach ($xml->children() as $child) {
            if ($child->getName() == "link" and $child->attributes()->rel == "alternate") {
              $link = $child["href"];
              break;
            }
          } // alternate link
          if (isset($xml->subtitle)) {
            $description80 = stripslashes($this->html5specialchars(mb_substr($xml->subtitle,0 ,80, MB_ENCODING)));	// substr problem bei trennung umlaute
          }
        }
      } // atom feed

    } // if xml
    else {
      $rc = 2;	// error (1 ist timeout)

      $title = "error";
      $link = "#";
      $description80 = "";
    }

    return array("error" => $rc, "title" => $title, "link" => $link, "description80" => $description80);
  }

// *****************************************************************************
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

  public function getLogin($extension_array=array()) {
    $login = "login";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank (1)
      $sql = "SELECT user, ba_date FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid ORDER BY ba_id DESC LIMIT 1";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 1

        // {login} ersetzen mit user und letzten datum-eintrag in blog
        $datensatz = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $user = stripslashes($this->html5specialchars($datensatz["user"]));
        $datum = stripslashes($this->html5specialchars($datensatz["ba_date"]));
        $login = "<p>letzter login:</p>\n".
                 "<p>".$user."\n".
                 "<br>".substr($datum, 0, 8)."</p>";	// nur datum

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<br>db error 1\n";
      }

      // {login} erweitern mit blogroll oder blogbox
      if(!empty($extension_array)) {
        $login .= $extension_array["login"];
        $errorstring .= $extension_array["error"];
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error\n";
    }

    return array("login" => $login, "error" => $errorstring);
  }

  // {login} erweitern mit blogbox
  public function blogbox() {
    $login = "";
    $errorstring = "";

    // zugriff auf mysql datenbank (1a)
    $sql = "SELECT ba_date, ba_text FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." ORDER BY ba_id DESC LIMIT 0,3";
    $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 1a

      // {login} erweitern mit blogbox (ersten 3 einträge aus blog)
      $login .= "\n<!-- blogbox -->\n".
                "<div id=\"blogbox\">\n".
                "letzte Blogeinträge:\n";

      // ausgabeschleife
      while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

        $datum = stripslashes($this->html5specialchars($datensatz["ba_date"]));
        $blogtext80 = stripslashes(Blog::html_tags($this->html5specialchars(mb_substr($datensatz["ba_text"], 0, 80, MB_ENCODING)), false));	// substr problem bei trennung umlaute

        // blog id für anker
        $jahr   = substr($datum, 6, 2);
        $monat  = substr($datum, 3, 2);
        $tag    = substr($datum, 0, 2);
        $stunde = substr($datum, 11, 2);
        $minute = substr($datum, 14, 2);
        $jmtsm = "20".$jahr.$monat.$tag.$stunde.$minute."00";

        $login .= "<br><a href=\"index.php?action=blog#".$jmtsm."\">".$datum."</a>\n".
                  "<br>".$blogtext80."...\n";
      }

      $login .= "</div>";

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

    }
    else {
      $errorstring .= "<br>db error 1a\n";
    }

    return array("login" => $login, "error" => $errorstring);
  }

  // {login} erweitern mit blogroll
  public function blogroll() {
    $login = "";
    $errorstring = "";

    // zugriff auf mysql datenbank (1b)
    $sql = "SELECT ba_id, ba_feed FROM ba_blogroll ORDER BY ba_id ASC LIMIT 5";
    $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 1b

      // {login} erweitern mit blogroll (nur 5 einträge)
      $login .= "\n<!-- blogroll -->\n".
                "<div id=\"blogbox\">\n".
                "Blogroll:\n";

      // parameter
      $local_file_path = "cache/";
      $local_file_name = "blogroll_";
      $local_file_suffix = ".txt";
      $seconds_last_modification = 4*7*24*3600;	// 4 wochen

      // ausgabeschleife
      while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
        $ba_id = $datensatz["ba_id"];
        $ba_feed = $datensatz["ba_feed"];

        // filename
        $filename = $local_file_path.$local_file_name.$ba_id.$local_file_suffix;
        $filename_gz = $filename.".gz";

        if (@file_exists($filename_gz) and (@filemtime($filename_gz) > time()-$seconds_last_modification)) {
          // feed als kopie im cache und noch aktuell

          $file_gz = @file_get_contents($filename_gz);	// feed ist komprimiert
          $file_content = gzdecode($file_gz);		// entpacken
          $ret_array = $this->readXML($file_content);	// feed lesen
        }
        else {
          // feed nicht im cache, feed aus db

          if ($file_content = $this->file_get_contents_curl($ba_feed)) {
            $ret_array = $this->readXML($file_content);		// feed lesen
            if (!$ret_array["error"]) {
              // kein fehler
              $file_gz = gzencode($file_content);		// einpacken
              @file_put_contents($filename_gz, $file_gz);	// feed ist komprimiert
            }  // if xml
          } // if file
          else {
            $ret_array = array("error" => 1, "title" => "...timeout...", "link" => $ba_feed, "description80" => "...");	// ret_array im fehlerfall/timeout
          }
        }

        // schreibe blogroll:
        if ($ret_array["error"] < 2) {
          // 0=ok, 1=timeout, 2=error

          // titel als link + beschreibung
          $login .= "<br><a href=\"".$ret_array["link"]."\">".$ret_array["title"]."</a>\n";
          $description80 = $ret_array["description80"];
          if (mb_strlen($description80, MB_ENCODING) > 0) {
            if (mb_strlen($description80, MB_ENCODING) >= 79) {
              // strlen problem bei umlaute
              $description80 .= "...";
            }
            $login .= "<br>".$description80."\n";
          }

        } // kein error

      } // while

      $login .= "</div>";

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

    }
    else {
      $errorstring .= "<br>db error 1b\n";
    }

    return array("login" => $login, "error" => $errorstring);
  }

}

?>
