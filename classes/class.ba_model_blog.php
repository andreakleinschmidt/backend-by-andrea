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
// * ba_model - blog
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXLEN_BLOGDATETIME",20);
//define("MAXLEN_BLOGHEADER",128);
//define("MAXLEN_BLOGINTRO",1024);
//define("MAXLEN_BLOGTEXT",11264);
//define("MAXLEN_BLOGVIDEOID",32);
//define("MAXLEN_BLOGPHOTOID",128);
//define("MAXLEN_BLOGTAGS",128);
//define("MAXLEN_BLOGCATEGORY",32);
//define("MAXLEN_FEED",128);	// blogroll
//define("MAXLEN_OPTIONSTR",64);
//define("MAXLEN_PERMALINK",50);
//define("STATE_CREATED",0);
//define("STATE_EDITED",1);
//define("STATE_APPROVAL",2);
//define("STATE_PUBLISHED",3);
//define("MB_ENCODING","UTF-8");

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 3d - stmt bei backend GET blog
// db error 3d - stmt bei backend GET history
// db error 3e - ret bei backend GET blog liste (anzahl ba_id)
// db error 3f - ret bei backend GET blog liste (ausgabe)
// db error 3l - ret bei backend GET blogroll
// db error 3m - ret bei backend GET categories
// db error 3o - ret bei backend GET options
//
// db error 4e - stmt bei backend POST blog
// db error 4k - stmt bei backend POST blogroll neu
// db error 4l - stmt bei backend POST blogroll
// db error 4m - stmt bei backend POST category neu
// db error 4n - stmt bei backend POST category
// db error 4o - stmt bei backend POST options
//
// no id! - bei backend GET blog (kein datensatz)
// no id! - bei backend POST blog (id=0xffff)

class Blog extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  // (start: doppelt in frontend und backend model blog)
  public function getOption_by_name($ba_name, $str_flag=false) {
    if ($str_flag) {
      $value = "";
    }
    else {
      $value = 1;
    }

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      if ($str_flag) {
        $sql = "SELECT ba_value FROM ba_options_str WHERE ba_name = ?";
      }
      else {
        $sql = "SELECT ba_value FROM ba_options WHERE ba_name = ?";
      }
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler

        // austauschen ? durch string
        $stmt->bind_param("s", $ba_name);
        $stmt->execute();	// ausführen geänderte zeile

        $stmt->bind_result($dataset["ba_value"]);
        // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

        if ($stmt->fetch()) {
          // wenn kein fehler (name nicht vorhanden, datensatz leer)
          if ($str_flag) {
            $value = trim($dataset["ba_value"]);
          }
          else {
            $value = intval($dataset["ba_value"]);
          }
        }

        $stmt->close();
        unset($stmt);	// referenz löschen

      } // stmt

    } // datenbank

    return $value;
  }

  // division durch null verhindern
  public function check_zero($num) {
    $ret = intval($num);
    if ($ret < 1) {
      $ret = 1;
    }
    return $ret;
  }

  // falls year = -0001 (mysql "0000-00-00 00:00:00"), neue datetime mit unix ts 0 "1970-01-01 00:00:00"
  public function check_datetime($datetime) {
    if (intval(date_format($datetime, "Y")) < 0) {
      $datetime = date_create_from_format("U", 0);	// im fehlerfall
    }
    return $datetime;
  }

  // ersetze tag kommandos im blogtext ~cmd{content_str} mit html tags <a>, <b>, <i>, <img>
  public function html_tags($text_str, $tag_flag, $encoding="UTF-8") {
    for ($start=0; mb_strpos($text_str, "~", $start, $encoding) !== false; $start++) {
      // suche tilde, abbruch der schleife wenn keine tilde mehr in text_str vorhanden (strpos return bool(false))

      $start   = mb_strpos($text_str, "~", $start, $encoding);
      $brace   = mb_strpos($text_str, "{", $start, $encoding);
      $stop    = mb_strpos($text_str, "}", $start, $encoding);

      if ($brace and $stop) {
        // nur ausführen wenn {} gefunden
        $cmd         = mb_substr($text_str, $start+1, $brace-$start-1, $encoding);
        $content_str = mb_substr($text_str, $brace+1, $stop-$brace-1 , $encoding);

        switch ($cmd) {

          case "link":

            if (mb_strlen($content_str, $encoding) > 0 and $tag_flag) {
              $link = explode("|", $content_str);
              if (count($link) == 2) {
                $tag_str = "<a href=\"".$link[0]."\">".$link[1]."</a>";
              }
              else {
                $tag_str = "<a href=\"".$link[0]."\">".$link[0]."</a>";
              }
            }
            elseif (mb_strlen($content_str, $encoding) > 0 and !$tag_flag) {
              $link = explode("|", $content_str);
              if (count($link) == 2) {
                $tag_str = $link[1];
              }
              else {
                $tag_str = $link[0];
              }
            }
            else {
              $tag_str = "";
            }
            break;

          case "bold":

            if (mb_strlen($content_str, $encoding) > 0 and $tag_flag) {
              $tag_str = "<b>".$content_str."</b>";
            }
            elseif (mb_strlen($content_str, $encoding) > 0 and !$tag_flag) {
              $tag_str = $content_str;
            }
            else {
              $tag_str = "";
            }
            break;

          case "italic":

            if (mb_strlen($content_str, $encoding) > 0 and $tag_flag) {
              $tag_str = "<i>".$content_str."</i>";
            }
            elseif (mb_strlen($content_str, $encoding) > 0 and !$tag_flag) {
              $tag_str = $content_str;
            }
            else {
              $tag_str = "";
            }
            break;

          case "list":

            $tag_str = "";
            if (mb_strlen($content_str, $encoding) > 0 and $tag_flag) {
              $list = explode("|", $content_str);
              foreach($list as $item) {
                $tag_str .= "<span class=\"list\">".$item."</span>\n";
              }
            }
            elseif (mb_strlen($content_str, $encoding) > 0 and !$tag_flag) {
              $list = explode("|", $content_str);
              foreach($list as $item) {
                $tag_str .= " - ".$item."\n";
              }
            }
            break;

          case "image":

            if (mb_strlen($content_str, $encoding) > 0 and $tag_flag) {
              $imagename = "jpeg/".$content_str.".jpg";
              if (is_readable($imagename)) {
                $imagesize = getimagesize($imagename);
                $caption = Photos::getText($content_str);	// imagename als photoid, return caption text
                $tag_str = "<figure class=\"floating\">".
                           "<img class=\"border\" src=\"".$imagename."\" ".$imagesize[3].">".
                           "<figcaption class=\"floating_caption\">".$caption."</figcaption>".
                           "</figure>";
              }
              else {
                $tag_str = "[".$content_str."]";
              }
            }
            elseif (mb_strlen($content_str, $encoding) > 0 and !$tag_flag) {
              $tag_str = "[".$content_str."]";
            }
            else {
              $tag_str = "";
            }
            break;

          default:
            $tag_str = $cmd.$content_str;

        } // switch

        // falls liste, <br> nach </span> entfernen
        if ($cmd == "list") {
          if (mb_substr($text_str, $stop+1, 8, $encoding) == "<br />\r\n") {
            $stop += 8;
          }
          elseif (mb_substr($text_str, $stop+1, 7, $encoding) == "<br />\r") {
            $stop += 7;
          }
          elseif (mb_substr($text_str, $stop+1, 7, $encoding) == "<br />\n") {
            $stop += 7;
          }
          elseif (mb_substr($text_str, $stop+1, 6, $encoding) == "<br>\r\n") {
            $stop += 6;
          }
          elseif (mb_substr($text_str, $stop+1, 5, $encoding) == "<br>\r") {
            $stop += 5;
          }
          elseif (mb_substr($text_str, $stop+1, 5, $encoding) == "<br>\n") {
            $stop += 5;
          }
        }

        $text_str = mb_substr($text_str, 0, $start, $encoding).$tag_str.mb_substr($text_str, $stop+1, NULL, $encoding);
        // mb_substr_replace($text_str, $tag_str, $start, $stop-$start+1);

      } // if

      elseif ($brace) {
        // ohne stop
        $text_str = mb_substr($text_str, 0, $start, $encoding).mb_substr($text_str, $brace+1, NULL, $encoding);
      }

    } // for

    return $text_str;
  }
  // (ende: doppelt in frontend und backend model blog)

  // umlaute ersetzen und nicht-wort-zeichen (außer leerzeichen und "-") entfernen
  private function replace_chars($str) {
    $mask = array(
      "/ä/" => "ae",
      "/ö/" => "oe",
      "/ü/" => "ue",
      "/ß/" => "ss",
      "/[^\w^\s^-]/" => ""
    );
    return preg_replace(array_keys($mask), array_values($mask), $str);
  }

  // alias für permalink, return NULL wenn fehler
  private function get_alias_from_text($datetime, $str) {
    $alias = "";
    $datetime = $this->check_datetime(date_create_from_format("Y-m-d H:i:s", $datetime));	// "YYYY-MM-DD HH:MM:SS"

    // satzendzeichen als trennzeichen, nur erster satz (bzw. kommagetrennter nebensatz)
    $split_str = preg_split("/(?<=\!\s|,\s|\.\s|\:\s|\?\s)/", $str, 2, PREG_SPLIT_NO_EMPTY)[0];

    // keine html_tags, lowercase, umlaute ersetzen und nicht-wort-zeichen (außer leerzeichen und "-") entfernen
    $clean_str = $this->replace_chars(mb_strtolower($this->html_tags($split_str, false)));

    // text zerlegen, trennzeichen: leerzeichen oder "-"
    $split_arr = preg_split("/[\s-]+/", $clean_str, 0, PREG_SPLIT_NO_EMPTY);

    $alias = array_shift($split_arr);	// erstes element, return NULL wenn array leer
    foreach ($split_arr as $part) {
      // ab zweites element
      if (strlen($alias."-".$part) <= MAXLEN_PERMALINK) {
        $alias .= "-".$part;	// auffüllen bis maxlen
      }
      else {
        break;	// maxlen erreicht
      }
    }

    return $alias;
  }

  private function getHistory($id) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_blog_history (history_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                        history_datetime DATETIME NOT NULL,
      //                        history_info VARCHAR(8) NOT NULL,
      //                        ba_blogid INT UNSIGNED NOT NULL,
      //                        ba_userid INT UNSIGNED NOT NULL);

      // TRIGGER trigger_ba_blog_insert AFTER INSERT ON ba_blog
      //  FOR EACH ROW INSERT INTO ba_blog_history (history_datetime, history_info, ba_blogid, ba_userid) VALUES(NOW(), "created", NEW.ba_id, NEW.ba_userid);
      // TRIGGER trigger_ba_blog_update AFTER UPDATE ON ba_blog
      //  FOR EACH ROW INSERT INTO ba_blog_history (history_datetime, history_info, ba_blogid, ba_userid) VALUES(NOW(), "modifed", NEW.ba_id, NEW.ba_userid);
      // TRIGGER trigger_ba_blog_delete BEFORE DELETE ON ba_blog
      //  FOR EACH ROW INSERT INTO ba_blog_history (history_datetime, history_info, ba_blogid, ba_userid) VALUES(NOW(), "deleted", OLD.ba_id, OLD.ba_userid);

      // GET id auslesen
      if (isset($id) and is_numeric($id)) {
        // id als zahl vorhanden und nicht NULL

        // zugriff auf mysql datenbank (1) , select mit prepare() , ($id aus GET)
        $sql = "SELECT history_id, history_datetime, history_info, user FROM ba_blog_history INNER JOIN backend ON backend.id = ba_blog_history.ba_userid WHERE ba_blogid = ? ORDER BY history_id DESC";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 3d2

          // austauschen ? durch int (i)
          $stmt->bind_param("i", $id);
          $stmt->execute();	// ausführen geänderte zeile

          $stmt->store_result();

          $stmt->bind_result($dataset["history_id"],$dataset["history_datetime"],$dataset["history_info"],$dataset["user"]);
          // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

          if ($stmt->num_rows > 0) {
            // erste zeile
            $html_backend_ext .= "<table class=\"backend\">\n".
                                 "<tr>\n<td class=\"td_backend\">".
                                 "<i>".$this->language["HISTORY_PROMPT"]."</i>".
                                 "</td>\n<td>\n".
                                 "<i>".$this->language["HISTORY_LAST"]."</i>\n";
          }

          $translate = array("created" => $this->language["STATE_CREATED"], "modifed" => $this->language["STATE_MODIFIED"], "deleted" => $this->language["STATE_DELETED"]);

          // ausgabeschleife
          while ($stmt->fetch()) {
            // solange nicht NULL (letzter datensatz, oder datensatz leer)

            $history_datetime = stripslashes($this->html5specialchars($dataset["history_datetime"]));
            $history_info_key = trim($dataset["history_info"]);
            $history_info = $translate[$history_info_key];
            $user = stripslashes($this->html5specialchars($dataset["user"]));

            $html_backend_ext .= "<br><i>".$history_info." ".$this->language["HISTORY_AT"]." ".$history_datetime." ".$this->language["HISTORY_BY"]." ".$user."</i>\n";

          } // while

          if ($stmt->num_rows > 0) {
            // letzte zeile
            $html_backend_ext .= "</td>\n</tr>\n".
                                 "</table>\n\n";
          }

          $stmt->close();	// stmt-ojekt schließen
          unset($stmt);	// referenz löschen

        }
        else {
          $errorstring .= "<p>db error 3d2</p>\n\n";
        }
      } // id

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  private function getBloglist($page, $date) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_blog (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_userid INT UNSIGNED NOT NULL,
      //                ba_datetime DATETIME NOT NULL,
      //                ba_alias VARCHAR(64) NOT NULL,
      //                ba_header VARCHAR(128) NOT NULL,
      //                ba_intro VARCHAR(1024) NOT NULL,
      //                ba_text VARCHAR(11264) NOT NULL,
      //                ba_videoid VARCHAR(32) NOT NULL,
      //                ba_photoid VARCHAR(128) NOT NULL,
      //                ba_catid INT UNSIGNED NOT NULL,
      //                ba_tags VARCHAR(128) NOT NULL,
      //                ba_state TINYINT UNSIGNED NOT NULL);

      // options
      $anzahl_eps = $this->check_zero($this->getOption_by_name("blog_entries_per_page"));	// anzahl einträge pro seite = 20
      $diary_mode = boolval($this->getOption_by_name("blog_diary_mode"));	// tagebuch modus an = 1

      // liste mit älteren blog-einträgen
      $html_backend_ext .= "<p id=\"bloglist\"><b>".$this->language["HEADER_BLOG_LIST"]."</b></p>\n\n";

      // suche nach datum
      $html_backend_ext .= "<form action=\"backend.php\" method=\"get\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_DATE"].
                           "</td>\n<td>\n".
                           "<input type=\"hidden\" name=\"action\" value=\"blog\"/>\n".
                           "<input type=\"date\" name=\"date\"/>\n".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_SEARCH"]."\" />\n".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_id FROM ba_blog";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3e

        $anzahl_e = $ret->num_rows;	// anzahl einträge in ba_blog
        $anzahl_s = ceil($anzahl_e/$anzahl_eps);	// anzahl seiten in ba_blog, ceil() rundet auf

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

        // init
        $show_page = false;
        $show_date = false;
        //$page = 1;

        // GET page auslesen
        if (isset($page) and is_numeric($page)) {
          // page als zahl vorhanden und nicht NULL

          // page eingrenzen
          if  ($page < 1) {
            $page = 1;
          }
          elseif ($page > $anzahl_s) {
            $page = $anzahl_s;
          }

          $show_page = true;
        }

        // GET date auslesen
        elseif (isset($date) and date_create_from_format("Y-m-d|", $date)) {
          // date als datum-string vorhanden und nicht NULL

          // datum validieren
          $datetime = date_create_from_format("Y-m-d|", $date);	// alles ab "|" ist 0
          $date = date_format($datetime, "Y-m-d H:i:s");	// return string "2018-02-09 00:00:00"

          $show_date = true;
        }

        else {
          // init
          $page = 1;

          $show_page = true;
        }

        // LIMIT für sql berechnen
        $lmt_start = ($page-1) * $anzahl_eps;

        // zugriff auf mysql datenbank (3)
        if ($show_page == true) {
          $sql = "SELECT ba_id, ba_datetime, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, ba_catid, ba_tags, ba_state FROM ba_blog ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;
        }
        elseif ($show_date == true) {
          $sql = "SELECT ba_id, ba_datetime, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, ba_catid, ba_tags, ba_state FROM ba_blog WHERE ba_datetime >= '".$date."' AND ba_datetime < '".$date."' + INTERVAL 1 DAY ORDER BY ba_id DESC LIMIT 0,".$anzahl_e;	// funktioniert nur mit limit
        }
        $ret = $this->database->query($sql);	// liefert in return db-objekt
        if ($ret) {
          // wenn kein fehler 3f

          $html_backend_ext .= "<table class=\"backend\">\n".
                               "<tr>\n<th>".
                               $this->language["TABLE_HD_DATE"]." - ";
          if (!$diary_mode) {
            $html_backend_ext .= $this->language["TABLE_HD_HEADER"]." / ".$this->language["TABLE_HD_INTRO"]." / ";
          }
          $html_backend_ext .= $this->language["TABLE_HD_TEXT"]." (".$this->language["PROMPT_TOTAL"].$anzahl_e.")".
                               "</th>\n<th>".
                               $this->language["TABLE_HD_VIDEOID"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_PHOTOID"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_CATID"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_TAGS"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_STATE"].
                               "</th>\n</tr>\n";

          // ausgabeschleife
          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

            $datetime = $this->check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["ba_datetime"]));	// "YYYY-MM-DD HH:MM:SS"
            $blogdate = date_format($datetime, $this->language["FORMAT_DATE"]." / ".$this->language["FORMAT_TIME"]);	// "DD.MM.YY / HH:MM"
            $ba_header = stripslashes($this->html5specialchars(mb_substr($dataset["ba_header"], 0, 40, MB_ENCODING)));	// 80 wie blogbox in class.model.php, substr problem bei trennung umlaute
            $ba_intro = stripslashes($this->html_tags($this->html5specialchars(mb_substr($dataset["ba_intro"], 0, 40, MB_ENCODING)), false));	// 80 wie blogbox in class.model.php, substr problem bei trennung umlaute
            $ba_text = stripslashes($this->html_tags($this->html5specialchars(mb_substr($dataset["ba_text"], 0, 80, MB_ENCODING)), false));	// 80 wie blogbox in class.model.php, substr problem bei trennung umlaute
            $ba_videoid = stripslashes($this->html5specialchars($dataset["ba_videoid"]));
            $ba_photoid = stripslashes($this->html5specialchars($dataset["ba_photoid"]));
            $ba_catid = intval($dataset["ba_catid"]);
            $ba_tags_flag = "";
            if (!empty($dataset["ba_tags"])) {
              $ba_tags_flag = "x";
            }

            $ba_state = intval($dataset["ba_state"]);

            switch($ba_state) {
              case STATE_CREATED: {
                $ba_state_short = "-c-";
                break;
              }
              case STATE_EDITED: {
                $ba_state_short = "-e-";
                break;
              }
              case STATE_APPROVAL: {
                $ba_state_short = "-a-";
                break;
              }
              case STATE_PUBLISHED: {
                $ba_state_short = "-p-";
                break;
              }
              default: {
                $ba_state_short = "";
              }
            }

            $html_backend_ext .= "<tr>\n<td>".
                                 "<a href=\"backend.php?".$this->html_build_query(array("action" => "blog", "id" => $dataset["ba_id"]))."\">".$blogdate." - ";
            if (!$diary_mode) {
              $html_backend_ext .= $ba_header."... / ".$ba_intro."... / ";
            }
            $html_backend_ext .= $ba_text."...</a>".
                                 "</td>\n<td>";
            if (strlen($ba_videoid) > 0) {
              $html_backend_ext .= "(".$ba_videoid.")";	// nur wenn verwendet
            }
            $html_backend_ext .= "</td>\n<td>";
            if (strlen($ba_photoid) > 0) {
              $html_backend_ext .= "(".$ba_photoid.")";	// nur wenn verwendet
            }
            $html_backend_ext .= "</td>\n<td>";
            if ($ba_catid > 0) {
              $html_backend_ext .= "(".$ba_catid.")";	// nur wenn verwendet
            }
            $html_backend_ext .= "</td>\n<td>".$ba_tags_flag.
                                 "</td>\n<td>".$ba_state_short.
                                 "</td>\n</tr>\n";
          }

          // seitenauswahl mit links und vor/zurück
          $html_backend_ext .= "<tr>\n<td>\n";
          $query_data = array("action" => "blog", "page" => 0);

          if ($page > 1) {
            $i = $page - 1;
            $query_data["page"] = $i;
            $html_backend_ext .= "<a href=\"backend.php?".$this->html_build_query($query_data)."\">".$this->language["PAGE_PREVIOUS"]."</a> \n";	// zurück
          }

          for ($dec=0; $dec<=floor($anzahl_s/10); $dec++) {												// seitenauswahl (mit zehnergruppen)
            if ($dec == 0) {
              $start = 1;
            }
            else {
              $start = $dec*10;
            }
            if ($dec == floor($anzahl_s/10)) {
              $ende = $anzahl_s;
            }
            else {
              $ende = ($dec*10)+9;
            }
            if ($dec == floor($page/10)) {
              for ($i=$start; $i<=$ende; $i++) {
                if ($i == $page) {
                  $html_backend_ext .= $i." \n";
                }
                else {
                  $query_data["page"] = $i;
                  $html_backend_ext .= "<a href=\"backend.php?".$this->html_build_query($query_data)."\">".$i."</a> \n";
                }
              }
            }
            else {
              $query_data["page"] = $start;
              $html_backend_ext .= "<a href=\"backend.php?".$this->html_build_query($query_data)."\">[".$start."-".$ende."]</a> \n";
            }
          }

          if ($page < $anzahl_s) {
            $i = $page + 1;
            $query_data["page"] = $i;
            $html_backend_ext .= "<a href=\"backend.php?".$this->html_build_query($query_data)."\">".$this->language["PAGE_NEXT"]."</a>\n";		// vor
          }

          $html_backend_ext .= "</td>\n<td></td>\n<td></td>\n<td></td>\n</tr>\n".
                               "</table>\n\n";

          $ret->close();	// db-ojekt schließen
          unset($ret);	// referenz löschen

        }
        else {
          $errorstring .= "<p>db error 3f</p>\n\n";
        }

      }
      else {
        $errorstring .= "<p>db error 3e</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  private function getBlogroll() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_blogroll (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                    ba_feed VARCHAR(128) NOT NULL);

      $html_backend_ext .= "<p id=\"blogroll\"><b>".$this->language["HEADER_BLOGROLL"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (4)
      $sql = "SELECT ba_id, ba_feed FROM ba_blogroll";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3l
        if ($ret->num_rows > 0) {
          // feeds anzeigen, auswählen&löschen
          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n".
                               "<tr>\n<td class=\"td_backend\">".
                               $this->language["PROMPT_FEED"].
                               "</td>\n<td>\n".
                              "<select multiple name=\"ba_blogroll[]\" size=\"5\">\n";
          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $html_backend_ext .= "<option value=\"".$dataset["ba_id"]."\">".$dataset["ba_feed"]."</option>\n";
          }
          $html_backend_ext .= "</select>\n".
                               "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                               "<input type=\"submit\" value=\"".$this->language["BUTTON_DELETE"]."\" />".
                               "</td>\n</tr>\n".
                               "</table>\n".
                               "</form>\n\n";
        } // $ret->num_rows > 0
        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3l</p>\n\n";
      }

      // neuer feed
      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_NEW_FEED"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_blogroll_new[feed]\" class=\"size_32\" maxlength=\"".MAXLEN_FEED."\" value=\"http://\"/>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_NEW"]."\" />".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  private function getBlogcategory() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_blogcategory (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                        ba_category VARCHAR(32) NOT NULL);

      $html_backend_ext .= "<p id=\"categories\"><b>".$this->language["HEADER_CATEGORIES"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (5)
      $sql = "SELECT ba_id, ba_category FROM ba_blogcategory";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3m
        if ($ret->num_rows > 0) {
          // rubriken anzeigen, auswählen&löschen
          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n".
                               "<tr>\n<td class=\"td_backend\">".
                               $this->language["PROMPT_CATEGORY"].
                               "</td>\n<td>\n".
                              "<select multiple name=\"ba_blogcategory[]\" size=\"5\">\n";
          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $html_backend_ext .= "<option value=\"".$dataset["ba_id"]."\">".$dataset["ba_category"]."</option>\n";
          }
          $html_backend_ext .= "</select>\n".
                               "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                               "<input type=\"submit\" value=\"".$this->language["BUTTON_DELETE"]."\" />".
                               "</td>\n</tr>\n".
                               "</table>\n".
                               "</form>\n\n";
        } // $ret->num_rows > 0
        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3m</p>\n\n";
      }

      // neue rubrik
      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_NEW_CATEGORY"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_blogcategory_new[category]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGCATEGORY."\"/>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_NEW"]."\" />".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  private function getOptions() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_options (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                   ba_name VARCHAR(32) NOT NULL,
      //                   ba_value TINYINT UNSIGNED NOT NULL);

      // TABLE ba_options_str (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                       ba_name VARCHAR(32) NOT NULL,
      //                       ba_value VARCHAR(64) NOT NULL);

      $html_backend_ext .= "<p id=\"options\"><b>".$this->language["HEADER_OPTIONS"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (6)
      $sql = "(SELECT ba_name, ba_value, 0 AS str_flag FROM ba_options)".
             "UNION".
             "(SELECT ba_name, ba_value, 1 AS str_flag FROM ba_options_str)";

      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3o
        if ($ret->num_rows > 0) {
          // options anzeigen, einstellen
          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n";

          // ausgabeschleife
          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $ba_name = stripslashes($this->html5specialchars($dataset["ba_name"]));
            $str_flag = boolval($dataset["str_flag"]);
            if ($str_flag) {
              $ba_value = stripslashes($this->html5specialchars($dataset["ba_value"]));
            }
            else {
              $ba_value = intval($dataset["ba_value"]);
            }

            $html_backend_ext .= "<tr>\n<td class=\"td_backend\">".
                                 $ba_name.":".
                                 "</td>\n<td>";
            if ($str_flag) {
              $html_backend_ext .= "<input type=\"text\" name=\"ba_options[".$ba_name."][ba_value]\" class=\"size_32\" maxlength=\"".MAXLEN_OPTIONSTR."\" value=\"".$ba_value."\"/>".
                                   "<input type=\"hidden\" name=\"ba_options[".$ba_name."][]\" value=\"str_flag\"/>";
            }
            else {
              $html_backend_ext .= "<input type=\"number\" name=\"ba_options[".$ba_name."][ba_value]\" class=\"size_4\" min=\"0\" max=\"255\" value=\"".$ba_value."\"/>";
            }
            $html_backend_ext .= "</td>\n</tr>\n";
          }
          $html_backend_ext .= "<tr>\n<td class=\"td_backend\"></td>\n<td>".
                               "<input type=\"submit\" value=\"".$this->language["BUTTON_SET"]."\" />".
                               "</table>\n".
                               "</form>\n\n";
        } // $ret->num_rows > 0
        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3o</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  private function getCategories() {
    $categories = array("none" => 0);	// init

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_id, ba_category FROM ba_blogcategory";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $ba_id = intval($dataset["ba_id"]);
          $ba_category = stripslashes($this->html5specialchars($dataset["ba_category"]));
          $categories[$ba_category] = $ba_id;
        }
        $ret->close();
        unset($ret);
      }

    } // datenbank

    return $categories;
  }

  private function getTags() {
    $tags = array();	// init

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_tags FROM ba_blog GROUP BY ba_tags";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $ba_tags = stripslashes($this->html5specialchars($dataset["ba_tags"]));
          $tags[] = $ba_tags;
        }
        $ret->close();
        unset($ret);
      }

    } // datenbank

    return $tags;
  }

  public function getBlog($id, $page, $date) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // TABLE ba_blog (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_userid INT UNSIGNED NOT NULL,
      //                ba_datetime DATETIME NOT NULL,
      //                ba_alias VARCHAR(64) NOT NULL,
      //                ba_header VARCHAR(128) NOT NULL,
      //                ba_intro VARCHAR(1024) NOT NULL,
      //                ba_text VARCHAR(11264) NOT NULL,
      //                ba_videoid VARCHAR(32) NOT NULL,
      //                ba_photoid VARCHAR(128) NOT NULL,
      //                ba_catid INT UNSIGNED NOT NULL,
      //                ba_tags VARCHAR(128) NOT NULL,
      //                ba_state TINYINT UNSIGNED NOT NULL);

      // options
      $diary_mode = boolval($this->getOption_by_name("blog_diary_mode"));	// tagebuch modus an = 1

      // preset
      $ba_id = 0xffff;	// error
      $ba_userid = $_SESSION["user_id"];	// wird immer neu gesetzt
      $ba_datetime = "0000-00-00 00:00:00";
      $ba_header = "";
      $ba_intro = "";
      $ba_text = "";
      $ba_videoid = "";
      $ba_photoid = "";
      $ba_catid = 0;
      $ba_tags = "";
      $ba_state = STATE_CREATED;

      // GET id auslesen
      if (isset($id) and is_numeric($id)) {
        // id als zahl vorhanden und nicht NULL

        $html_backend_ext .= "<p id=\"blog\"><b>".$this->language["HEADER_BLOG"]."</b></p>\n\n";

        // zugriff auf mysql datenbank (1) , select mit prepare() , ($id aus GET)
        $sql = "SELECT ba_id, ba_datetime, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, ba_catid, ba_tags, ba_state FROM ba_blog WHERE ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 3d

          // austauschen ? durch int (i)
          $stmt->bind_param("i", $id);
          $stmt->execute();	// ausführen geänderte zeile

          $stmt->bind_result($dataset["ba_id"],$dataset["ba_datetime"],$dataset["ba_header"],$dataset["ba_intro"],$dataset["ba_text"],$dataset["ba_videoid"],$dataset["ba_photoid"],$dataset["ba_catid"],$dataset["ba_tags"],$dataset["ba_state"]);
          // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

          if ($stmt->fetch()) {
            // wenn kein fehler (id nicht vorhanden, datensatz leer)

            // preset überschreiben
            $ba_id = $dataset["ba_id"];
            $ba_datetime = stripslashes($this->html5specialchars($dataset["ba_datetime"]));
            $ba_header = stripslashes($this->html5specialchars($dataset["ba_header"]));
            $ba_intro = stripslashes($this->html5specialchars($dataset["ba_intro"]));
            $ba_text = stripslashes($this->html5specialchars($dataset["ba_text"]));
            $ba_videoid = stripslashes($this->html5specialchars($dataset["ba_videoid"]));
            $ba_photoid = stripslashes($this->html5specialchars($dataset["ba_photoid"]));
            $ba_catid = intval($dataset["ba_catid"]);
            $ba_tags = stripslashes($this->html5specialchars($dataset["ba_tags"]));
            $ba_state = intval($dataset["ba_state"]);

          }
          else {
            $errorstring .= "<p>no id!</p>\n\n";
          }

          $stmt->close();	// stmt-ojekt schließen
          unset($stmt);	// referenz löschen

        }
        else {
          $errorstring .= "<p>db error 3d</p>\n\n";
        }

      } // id

      else {
        // keine id , neuer blog-eintrag

        $html_backend_ext .= "<p id=\"blog\"><b>".$this->language["HEADER_BLOG_NEW"]."</b></p>\n\n";

        // preset teilweise überschreiben
        $ba_id = 0;
        $datetime = date_create();
        date_time_set($datetime, date_format($datetime, "H"), date_format($datetime, "i"), 0);	// ohne sekunden
        $ba_datetime = date_format($datetime, "Y-m-d H:i:s");	// return string "2014-11-13 23:45:00"
        if ($datetime == false or $ba_datetime == false) {
          $ba_datetime = "0000-00-00 00:00:00";	// im fehlerfall
        }

      } // keine id

      $categories = $this->getCategories();	// return array($category => $catid)
      $tags = $this->getTags();	// return array mit tags als string (auch mit kommas): ["tag_1", "tag_1, tag_2", "tag_2", ...]

      // formular felder für blog eintrag , GET id oder neu , ba_id und ba_userid in hidden feld , ba_daten aus preset

      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_DATE"].
                           "</td>\n<td>\n".
                           "<input type=\"hidden\" name=\"ba_blog[ba_id]\" value=\"".$ba_id."\"/>\n".
                           "<input type=\"hidden\" name=\"ba_blog[ba_userid]\" value=\"".$ba_userid."\"/>\n".
                           "<input type=\"text\" name=\"ba_blog[ba_datetime]\" class=\"size_32\" maxlength=\"".MAXLEN_DATETIME."\" value=\"".$ba_datetime."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">";
      if ($diary_mode) {
        $html_backend_ext .= "</td>\n<td>\n".
                             "<input type=\"hidden\" name=\"ba_blog[ba_header]\" value=\"".$ba_header."\"/>\n".
                             "<input type=\"hidden\" name=\"ba_blog[ba_intro]\" value=\"".$ba_intro."\"/>\n".
                             "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">";
      }
      else {
        $html_backend_ext .= $this->language["PROMPT_HEADER"].
                             "</td>\n<td>".
                             "<input type=\"text\" name=\"ba_blog[ba_header]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGHEADER."\" value=\"".$ba_header."\"/>".
                             "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                             $this->language["PROMPT_INTRO"].
                             "</td>\n<td>".
                             "<textarea name=\"ba_blog[ba_intro]\" class=\"cols_96_rows_11\">".$ba_intro."</textarea>".
                             "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">";
      }
      $html_backend_ext .= $this->language["PROMPT_TEXT"].
                           "</td>\n<td>\n".
                           "<button type=\"button\" onclick=\"insert_tag('blog_text','bold')\">".$this->language["BUTTON_BOLD"]."</button>\n".
                           "<button type=\"button\" onclick=\"insert_tag('blog_text','italic')\">".$this->language["BUTTON_ITALIC"]."</button>\n".
                           "<button type=\"button\" onclick=\"insert_tag('blog_text','link')\">".$this->language["BUTTON_LINK"]."</button>\n".
                           "<button type=\"button\" onclick=\"insert_tag('blog_text','list')\">".$this->language["BUTTON_LIST"]."</button>\n".
                           "<button type=\"button\" onclick=\"insert_tag('blog_text','image')\">".$this->language["BUTTON_IMAGE"]."</button>\n".
                           "<br><textarea name=\"ba_blog[ba_text]\" class=\"cols_96_rows_22\" id=\"blog_text\">".$ba_text."</textarea>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_VIDEOID"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_blog[ba_videoid]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGVIDEOID."\" value=\"".$ba_videoid."\"/>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_PHOTOID"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_blog[ba_photoid]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGPHOTOID."\" value=\"".$ba_photoid."\"/>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_CATEGORY"].
                           "</td>\n<td>\n".
                           "<select name=\"ba_blog[ba_catid]\" size=\"1\">\n";
      foreach ($categories as $category => $catid) {
        $html_backend_ext .= "<option value=\"".$catid."\"";
        if ($ba_catid == $catid) {
          $html_backend_ext .= " selected";
        }
        $html_backend_ext .= ">".$category."</option>\n";
      }
      $html_backend_ext .= "</select>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_TAGS"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_blog[ba_tags]\" class=\"size_32\" list=\"ba_tags\" maxlength=\"".MAXLEN_BLOGTAGS."\" value=\"".$ba_tags."\"/>".
                           "<datalist id=\"ba_tags\">\n";
      foreach ($tags as $tag_str) {
        $html_backend_ext .= "<option>".$tag_str."</option>\n";
      }
      $html_backend_ext .= "</datalist>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_STATE"].
                           "</td>\n<td>\n".
                           "<input type=\"radio\" name=\"ba_blog[ba_state]\" value=\"".STATE_CREATED."\"";
      if ($ba_state == STATE_CREATED) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>".$this->language["STATE_CREATED"]."\n".
                           "<br><input type=\"radio\" name=\"ba_blog[ba_state]\" value=\"".STATE_EDITED."\"";
      if ($ba_state == STATE_EDITED) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>".$this->language["STATE_EDITED"]."\n".
                           "<br><input type=\"radio\" name=\"ba_blog[ba_state]\" value=\"".STATE_APPROVAL."\"";
      if ($ba_state == STATE_APPROVAL) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>".$this->language["STATE_APPROVAL"]."\n".
                           "<br><input type=\"radio\" name=\"ba_blog[ba_state]\" value=\"".STATE_PUBLISHED."\"";
      if ($ba_state == STATE_PUBLISHED) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>".$this->language["STATE_PUBLISHED"]."\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />\n".
                           " ".$this->language["PROMPT_DELETE"]."<input type=\"checkbox\" name=\"ba_blog[]\" value=\"delete\" />".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // blog entry history
      $history = $this->getHistory($id);
      $html_backend_ext .= $history["content"];
      $errorstring .= $history["error"];

      // liste mit älteren blog-einträgen
      $bloglist = $this->getBloglist($page, $date);
      $html_backend_ext .= $bloglist["content"];
      $errorstring .= $bloglist["error"];

      // blogroll
      $blogroll = $this->getBlogroll();
      $html_backend_ext .= $blogroll["content"];
      $errorstring .= $blogroll["error"];

      // blogcategory
      $blogcategory = $this->getBlogcategory();
      $html_backend_ext .= $blogcategory["content"];
      $errorstring .= $blogcategory["error"];

      // options
      $options = $this->getOptions();
      $html_backend_ext .= $options["content"];
      $errorstring .= $options["error"];

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlog($ba_id, $ba_userid, $ba_datetime, $ba_header, $ba_intro, $ba_text, $ba_videoid, $ba_photoid, $ba_catid, $ba_tags, $ba_state, $ba_delete) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // options
      $diary_mode = boolval($this->getOption_by_name("blog_diary_mode"));	// tagebuch modus an = 1

      if ($ba_id != 0xffff) {

        // alias für permalink:
        if ($diary_mode) {
          $str = $ba_text;
        }
        else {
          $str = $ba_header;
        }
        $ba_alias = $this->get_alias_from_text($ba_datetime, $str);	// return NULL wenn fehler
        if (is_null($ba_alias)) {
          $ba_alias = "";
        }

        $count = 0;

        // einfügen in datenbank:
        if ($ba_id == 0) {
          $sql = "INSERT INTO ba_blog (ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, ba_catid, ba_tags, ba_state) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }

        // löschen in datenbank:
        elseif ($ba_delete) {
          $sql = "DELETE FROM ba_blog WHERE ba_id = ?";
        }

        // update in datenbank:
        else {
          $sql = "UPDATE ba_blog SET ba_userid = ?, ba_datetime = ?, ba_alias = ?, ba_header = ?, ba_intro = ?, ba_text = ?, ba_videoid = ?, ba_photoid = ?, ba_catid = ?, ba_tags = ?, ba_state = ? WHERE ba_id = ?";
        }

        // mit prepare() - sql injections verhindern
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4e

          // austauschen ???????????, ? oder ???????????? durch string und int
          if ($ba_id == 0) {
            $stmt->bind_param("isssssssisi", $ba_userid, $ba_datetime, $ba_alias, $ba_header, $ba_intro, $ba_text, $ba_videoid, $ba_photoid, $ba_catid, $ba_tags, $ba_state);	// einfügen in datenbank
            $html_backend_ext .= "<p>".$this->language["MSG_BLOG_NEW"]."</p>\n\n";
          }
          elseif ($ba_delete) {
            $stmt->bind_param("i", $ba_id);	// löschen in datenbank
            $html_backend_ext .= "<p>".$this->language["MSG_BLOG_DELETE"]."</p>\n\n";
          }
          else {
            $stmt->bind_param("isssssssisii", $ba_userid, $ba_datetime, $ba_alias, $ba_header, $ba_intro, $ba_text, $ba_videoid, $ba_photoid, $ba_catid, $ba_tags, $ba_state, $ba_id);	// update in datenbank
            $html_backend_ext .= "<p>".$this->language["MSG_BLOG_UPDATE"]."</p>\n\n";
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4e</p>\n\n";
        }

        $html_backend_ext .= "<p>".$count." ".$this->language["MSG_ROWS_CHANGED"]."</p>\n\n";

      }
      else {
        $errorstring .= "<p>no id!</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlogrollNew($feed) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_blogroll (ba_feed) VALUES (?)";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4k

        // austauschen ? durch string
        $stmt->bind_param("s", $feed);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>".$this->language["MSG_DONE"]."</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>".$this->language["MSG_BLOGROLL_ERROR"]."</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4k</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlogroll($ba_blogroll_array) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      foreach ($ba_blogroll_array as $id) {

        $sql = "DELETE FROM ba_blogroll WHERE ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4l

          // austauschen ? durch int
          $stmt->bind_param("i", $id);
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4l</p>\n\n";
        }

      }

      $html_backend_ext .= "<p>".$count." ".$this->language["MSG_ROWS_DELETED"]."</p>\n\n";

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlogcategoryNew($category) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_blogcategory (ba_category) VALUES (?)";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4m

        // austauschen ? durch string
        $stmt->bind_param("s", $category);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>".$this->language["MSG_DONE"]."</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>".$this->language["MSG_BLOGCATEGORY_ERROR"]."</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4m</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlogcategory($ba_blogcategory_array) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = array("delete" => 0, "update" => 0);

      foreach ($ba_blogcategory_array as $id) {

        $sql_pt1 = "DELETE FROM ba_blogcategory WHERE ba_id = ?";
        $sql_pt2 = "UPDATE ba_blog SET ba_catid = 0 WHERE ba_catid = ?";
        $sql_array = array("delete" => $sql_pt1, "update" => $sql_pt2);

        foreach ($sql_array as $key => $sql) {

          $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
          if ($stmt) {
            // wenn kein fehler 4n

            // austauschen ? durch int
            $stmt->bind_param("i", $id);
            $stmt->execute();	// ausführen geänderte zeile
            $count[$key] += $stmt->affected_rows;
            $stmt->close();

          } // stmt

          else {
            $errorstring .= "<p>db error 4n</p>\n\n";
          }

        } // sql part

      } // id

      $html_backend_ext .= "<p>".$count["delete"]." ".$this->language["MSG_ROWS_DELETED"]."</p>\n\n";
      $html_backend_ext .= "<p>".$count["update"]." ".$this->language["MSG_ROWS_CHANGED"]."</p>\n\n";

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postOptions($ba_options_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      foreach ($ba_options_array_replaced as $ba_name => $ba_array) {
        $ba_value = $ba_array["ba_value"];
        $str_flag = $ba_array["str_flag"];

        // update in datenbank , mit prepare() - sql injections verhindern
        if ($str_flag) {
          $sql = "UPDATE ba_options_str SET ba_value = ? WHERE ba_name = ?";
        }
        else {
          $sql = "UPDATE ba_options SET ba_value = ? WHERE ba_name = ?";
        }
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4o

          // austauschen ?? durch int und string
          if ($str_flag) {
            $stmt->bind_param("ss", $ba_value, $ba_name);
          }
          else {
            $stmt->bind_param("is", $ba_value, $ba_name);
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4o</p>\n\n";
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
