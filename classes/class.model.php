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
// * model
// * - daten aus datenbank holen
// * - daten aufbereiten
// * - daten an controller zurückgeben
// *****************************************************************************

//define("STATE_PUBLISHED",3);
//define("MB_ENCODING","UTF-8");
//define("DEFAULT_LOCALE","de-DE");	// "de-DE" oder "en-US"

class Model {

  public $database;
  public $language;

  // konstruktor
  public function __construct() {
    // datenbank:
    $this->database = @new Database();	// @ unterdrückt fehlermeldung
    if (!$this->database->connect_errno) {
      // wenn kein fehler
      $this->database->set_charset("utf8");	// change character set to utf8
    }
    // language:
    if (!isset($_SESSION["language"])) {
      // falls session variable noch nicht existiert
      $this->language = $this->readLanguage();
      $_SESSION["language"] = $this->language;	// in SESSION speichern
    } // neue session variable
    else {
      // alte session variable
      $this->language = $_SESSION["language"];	// aus SESSION lesen
    }
  }

  // mit simplexml language.xml laden als array key->value
  private function readLanguage() {
    $language = array();

    // locale für frontend
    $ret = $this->getLocale();
    if (!empty($ret["locale"])) {
      $locale = $ret["locale"];
    }
    else {
      $locale = DEFAULT_LOCALE;
    }

    $path = "languages/";
    $filename = $path."language_".$locale.".xml";
    if (file_exists($filename)) {
      $xml_content = file_get_contents($filename);
    }
    else {
      $xml_content = "";
    }

    if ($xml = @simplexml_load_string($xml_content)) {
      if ($xml->getName() == "language" and $xml->attributes()->tag == $locale) {
        // xml language file
        $language["locale"] = $locale;
        foreach ($xml->children() as $child) {
          $key = $child->getName();
          $value = (string)$child->attributes()->text;
          $language[$key] = $value;
        } // foreach child
      } // xml language file
    } // if xml

    return $language;
  }

  // return "de" oder "en"
  public function getLang() {
    return substr($this->language["locale"], 0, 2);
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

  // werte aus der basis
  public function getBase($name) {
    $value = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      switch($name) {

        case "nav": {
          $ba_name = "ba_nav";
          break;
        }

        case "links": {
          $ba_name = "ba_nav_links";
          break;
        }

        case "startpage": {
          // default startpage
          $ba_name = "ba_startpage";
          break;
        }

        default: {
          // nichts
          $ba_name = "''";	// empty string
        }

      } // switch

      // zugriff auf mysql datenbank (1b)
      $sql = "SELECT ".$ba_name." FROM ba_base LIMIT 1";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 1b

        $dataset = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $value = trim($dataset[$ba_name]);

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "db error 1b\n";
      }

    } // datenbank
    else {
      $errorstring .= "db error\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array($name => $value, "error" => $errorstring);
  }

  // html head
  public function getHead() {
    $hd_title = "";
    $hd_description = "";
    $hd_author = "";
    $feed_title = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // options
      $model_blog = new Blog();
      $feed_title = stripslashes($this->html5specialchars($model_blog->getOption_by_name("feed_title", true)));	// als string

      // zugriff auf mysql datenbank (1h)
      $sql = "SELECT ba_title, ba_description, ba_author FROM ba_base LIMIT 1";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 1h

        $dataset = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $hd_title = stripslashes($this->html5specialchars($dataset["ba_title"]));
        $hd_description = stripslashes($this->html5specialchars($dataset["ba_description"]));
        $hd_author = stripslashes($this->html5specialchars($dataset["ba_author"]));

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "db error 1h\n";
      }

    } // datenbank
    else {
      $errorstring .= "db error\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("hd_title" => $hd_title, "hd_description" => $hd_description, "hd_author" => $hd_author, "feed_title" => $feed_title, "error" => $errorstring);
  }

  // menue-liste
  public function getMenue() {
    $menue = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // options
      $model_blog = new Blog();
      $contact_mail = stripslashes($model_blog->getOption_by_name("contact_mail", true));	// als string
      $feed_title = stripslashes($this->html5specialchars($model_blog->getOption_by_name("feed_title", true)));	// als string

      // daten aus der basis
      $ret_array = $this->getBase("nav");
      $nav_str = $ret_array["nav"];
      $errorstring .= $ret_array["error"];

      $ret_array = $this->getBase("links");
      $links_str = $ret_array["links"];
      $errorstring .= $ret_array["error"];

      $menue .= "<ul>\n";

      $key_home = ACTION_HOME;
      $key_profile = ACTION_PROFILE;
      $key_photos = ACTION_PHOTOS;
      $key_blog = ACTION_BLOG;
      $translate = array($key_home => $this->language["FRONTEND_NAV_HOME"], $key_profile => $this->language["FRONTEND_NAV_PROFILE"], $key_photos => $this->language["FRONTEND_NAV_PHOTOS"], $key_blog => $this->language["FRONTEND_NAV_BLOG"]);

      $nav_arr = explode(",", $nav_str);
      foreach ($nav_arr as $action) {
        $menue .= "<li><a href=\"".$action."/\">".$translate[$action]."</a></li>\n";
      }

      $links_arr = explode(",", $links_str);
      foreach ($links_arr as $link_str) {
        $link = explode("|", $link_str);
        $path = implode("/", array_map("rawurlencode", explode("/", $link[0])));
        if (count($link) == 2) {
          $menue .= "<li><a href=\"".$path."\" target=\"blank\">".stripslashes($this->html5specialchars($link[1]))."</a></li>\n";
        }
        else {
          $menue .= "<li><a href=\"".$path."\" target=\"blank\">".stripslashes($this->html5specialchars($link[0]))."</a></li>\n";
        }
      }

      // mail kontakt
      $menue .= "<li><a href=\"mailto:".$contact_mail."\">".$this->language["FRONTEND_NAV_CONTACT"]."</a></li>\n";

      // feed icon (nur wenn blog)
      if (in_array(ACTION_BLOG, $nav_arr)) {
        $menue .= "<li><a href=\"feed/\" type=\"application/atom+xml\" title=\"".$feed_title."\"><img class=\"noborder\" src=\"".FEED_PNG."\" height=\"14\" width=\"14\"></a></li>\n";
      }

      $menue .= "</ul>";

    } // datenbank
    else {
      $errorstring .= "db error\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("menue" => $menue, "error" => $errorstring);
  }

  // letzter login + extension
  public function getLogin($extension_array=array()) {
    $login = "login";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank (1l)
      $sql = "SELECT id, user, last_login FROM backend ORDER BY last_login DESC LIMIT 1";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 1l

        // {login} ersetzen mit user und letzten datum-eintrag in blog
        $dataset = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $user = stripslashes($this->html5specialchars($dataset["user"]));
        $datetime = Blog::check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["last_login"]));	// "YYYY-MM-DD HH:MM:SS"
        $date = date_format($datetime, $this->language["FORMAT_DATE"]);	// "DD.MM.YY"

        $login = "<p>".$this->language["FRONTEND_LAST_LOGIN"]."</p>\n".
                 "<p>".$user."\n".
                 "<br>".$date."</p>";	// nur datum

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "db error 1l\n";
      }

      // {login} erweitern mit blogroll oder blogbox
      if(!empty($extension_array)) {
        $login .= $extension_array["login"];
        $errorstring .= $extension_array["error"];
      }

    } // datenbank
    else {
      $errorstring .= "db error\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("login" => $login, "error" => $errorstring);
  }

  // {login} erweitern mit blogbox
  public function blogbox() {
    $login = "";
    $errorstring = "";

    // zugriff auf mysql datenbank (1x)
    $sql = "SELECT ba_datetime, ba_text FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." ORDER BY ba_id DESC LIMIT 0,3";
    $ret = $this->database->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 1x

      // {login} erweitern mit blogbox (ersten 3 einträge aus blog)
      $login .= "\n<!-- blogbox -->\n".
                "<div id=\"blogbox\">\n".
                "<span id=\"white_small\">".$this->language["FRONTEND_RECENT_BLOGENTRIES"]."</span>\n";

      // ausgabeschleife
      while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

        $datetime = Blog::check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["ba_datetime"]));	// "YYYY-MM-DD HH:MM:SS"
        $datetime_anchor = date_format($datetime, "YmdHis");	// blog id für anker
        $blogdate = date_format($datetime, $this->language["FORMAT_DATE"]." / ".$this->language["FORMAT_TIME"]);	// "DD.MM.YY / HH:MM"
        $blogtext80 = stripslashes(Blog::html_tags($this->html5specialchars(mb_substr($dataset["ba_text"], 0, 80, MB_ENCODING)), false));	// substr problem bei trennung umlaute

        // (alt)  "<br><a href=\"index.php?action=blog#".$datetime_anchor."\">".$blogdate."</a>\n".
        $login .= "<br><a href=\"blog/#".$datetime_anchor."\">".$blogdate."</a>\n".
                  "<br>".$blogtext80."...\n";
      }

      $login .= "</div>";

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

    }
    else {
      $errorstring .= "db error 1x\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("login" => $login, "error" => $errorstring);
  }

  // {login} erweitern mit blogroll
  public function blogroll() {
    $login = "";
    $errorstring = "";

    // zugriff auf mysql datenbank (1r)
    $sql = "SELECT ba_id, ba_feed FROM ba_blogroll ORDER BY ba_id ASC LIMIT 5";
    $ret = $this->database->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 1r

      // {login} erweitern mit blogroll (nur 5 einträge)
      $login .= "\n<!-- blogroll -->\n".
                "<div id=\"blogbox\" data-nosnippet>\n".
                "<span id=\"white_small\">".$this->language["FRONTEND_BLOGROLL"]."</span>\n";

      // parameter
      $local_file_path = "cache/";
      $local_file_name = "blogroll_";
      $local_file_suffix = ".txt";
      $seconds_last_modification = 4*7*24*3600;	// 4 wochen

      // ausgabeschleife
      while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
        $ba_id = $dataset["ba_id"];
        $ba_feed = $dataset["ba_feed"];

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
          $login .= "<br><a rel=\"nofollow\" href=\"".$ret_array["link"]."\">".$ret_array["title"]."</a>\n";
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
      $errorstring .= "db error 1r\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("login" => $login, "error" => $errorstring);
  }

  // locale für frontend
  public function getLocale() {
    $locale = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler 1o

      // zugriff auf mysql datenbank (1o)
      $sql = "SELECT ba_locale FROM ba_languages WHERE ba_selected = 1 LIMIT 1";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler

        $dataset = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $locale = trim($dataset["ba_locale"]);

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "db error 1o\n";
      }

    } // datenbank
    else {
      $errorstring .= "db error\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("locale" => $locale, "error" => $errorstring);
  }

  // footer mit links (wie blogbox nur ohne blogdate, dafür zweite liste kommentare)
  public function getFooter() {
    $footer = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // options
      $model_blog = new Blog();
      $footer_num_entries = Blog::check_zero($model_blog->getOption_by_name("footer_num_entries"));	// anzahl einträge footer = 5
      $feed_title = stripslashes($this->html5specialchars($model_blog->getOption_by_name("feed_title", true)));	// als string

      $footer .= "<div>\n";

      // schleife zweimal
      foreach (range(1,2) as $i) {
        if ($i == 1) {
          // zugriff auf mysql datenbank (1p)
          $sql = "SELECT ba_datetime, ba_alias, ba_text FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." ORDER BY ba_id DESC LIMIT 0,".$footer_num_entries;
          $header = $this->language["FRONTEND_RECENT_POSTS"];
          $errornumber = "1p";
        }
        else {
          // zugriff auf mysql datenbank (1c)
          $sql = "SELECT ba_comment.ba_id AS ba_commentid, ba_comment.ba_name AS ba_name, ba_comment.ba_mail AS ba_mail, ba_comment.ba_blogid AS ba_blogid, ba_blog.ba_datetime AS ba_datetime, ba_blog.ba_alias AS ba_alias, ba_blog.ba_text AS ba_text FROM ba_comment INNER JOIN ba_blog ON ba_comment.ba_blogid = ba_blog.ba_id WHERE ba_comment.ba_state >= ".STATE_PUBLISHED." ORDER BY ba_comment.ba_id DESC LIMIT 0,".$footer_num_entries;
          $header = $this->language["FRONTEND_RECENT_COMMENTS"];
          $errornumber = "1c";
        }
        $ret = $this->database->query($sql);	// liefert in return db-objekt
        if ($ret) {
          // wenn kein fehler 1p oder 1c

          // {footer} erweitern mit ersten 5 einträgen aus blog bzw. kommentar
          $footer .= "<ul>\n".
                     "<li><h3>".$header."</h3></li>\n";

          // ausgabeschleife
          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $datetime = Blog::check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["ba_datetime"]));	// "YYYY-MM-DD HH:MM:SS"
            $blogalias = trim(rawurlencode($dataset["ba_alias"]));
            $permalink = date_format($datetime, "Y/m/").$blogalias;
            $blogtext40 = stripslashes(Blog::html_tags($this->html5specialchars(mb_substr($dataset["ba_text"], 0, 40, MB_ENCODING)), false));	// substr problem bei trennung umlaute
            if ($i == 1) {
              $footer .= "<li><a href=\"blog/".$permalink."/\">".$blogtext40."</a>...</li>\n";
            }
            else {
              $ba_commentid = $dataset["ba_commentid"];
              $ba_name = stripslashes($this->html5specialchars($dataset["ba_name"]));
              $ba_mail = stripslashes($this->html5specialchars($dataset["ba_mail"]));
              $ba_blogid = $dataset["ba_blogid"];
              if ($ba_mail != "") {
                $commenter = "<a href=\"mailto:".$ba_mail."\">".$ba_name."</a>";
              }
              else {
                $commenter = $ba_name;
              }
              $footer .= "<li>".$commenter;
              if ($ba_blogid > 1) {
                $footer .= " ".$this->language["FRONTEND_ON"]." <a href=\"blog/".$permalink."/#comment".$ba_commentid."\">".$blogtext40."</a>...";
              }
              $footer .= "</li>\n";
            }
          }

          $footer .= "</ul>\n";

          $ret->close();	// db-ojekt schließen
          unset($ret);	// referenz löschen

        }
        else {
          $errorstring .= "db error ".$errornumber."\n";
        }

      } // schleife zweimal

      // follow blog (nur atom feed)
      $footer .= "<ul>\n".
                 "<li><h3>".$this->language["FRONTEND_SUBSCRIBE_BLOG"]."</h3></li>\n".
                 "<li><a href=\"feed/\" type=\"application/atom+xml\" title=\"".$feed_title."\"><img class=\"noborder\" src=\"".FEED_PNG."\" height=\"14\" width=\"14\"> Atom Feed</a></li>\n".
                 "</ul>\n".
                 "</div>";

    } // datenbank
    else {
      $errorstring .= "db error\n";
    }

    if (DEBUG and !empty($errorstring)) { $errorstring .= "# ".__METHOD__." [".__FILE__."]\n"; }
    return array("footer" => $footer, "error" => $errorstring);
  }

}

?>
