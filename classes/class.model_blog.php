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
// * model - blog
// *****************************************************************************

//define("MAXLEN_COMMENTNAME",64);
//define("MAXLEN_COMMENTMAIL",64);
//define("MAXLEN_COMMENTURL",128);
//define("STATE_APPROVAL",2);
//define("STATE_PUBLISHED",3);
//define("MB_ENCODING","UTF-8");

class Blog extends Model {

  // variablen
  private static $month_min = 1;
  private static $month_max = 12;

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  // (doppelt in frontend und backend model blog)
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

  // ausgabe komplette blogzeile
  private function blog_line($dataset, &$option_array, &$blog_comment_id_array, $query_data, $first_entry, $num_sentences, $diary_mode) {
    $replace = "";

    $blog_author_query = $this->html_build_query(array("action" => "blog", "author" => $dataset["ba_userid"]));
    $full_name = stripslashes($this->html5specialchars($dataset["full_name"]));
    $datetime = $this->check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["ba_datetime"]));	// "YYYY-MM-DD HH:MM:SS"
    $blogdate = date_format($datetime, $this->language["FORMAT_DATE"]." / ".$this->language["FORMAT_TIME"]);	// "DD.MM.YY / HH:MM"
    $blogalias = trim(rawurlencode($dataset["ba_alias"]));
    $blogheader = stripslashes($this->html5specialchars($dataset["ba_header"]));
    $blogintro = stripslashes($this->html_tags(nl2br($this->html5specialchars($dataset["ba_intro"])), false));
    $blogtext = stripslashes($this->html_tags(nl2br($this->html5specialchars($dataset["ba_text"])), true));

    if (!$diary_mode or ($diary_mode and $first_entry)) {
      if (empty($blogintro)) {
        $split_array = array_slice(preg_split("/(?<=\!\s|\.\s|\:\s|\?\s)/", $dataset["ba_text"], $num_sentences+1, PREG_SPLIT_NO_EMPTY), 0, $num_sentences);
        $blogintro = stripslashes($this->html_tags(nl2br($this->html5specialchars(implode($split_array))), false));	// satzendzeichen als trennzeichen, anzahl sätze optional
      }
    }

    if ($diary_mode or empty($blogheader)) {
      $blogtext40 = stripslashes($this->html_tags($this->html5specialchars(mb_substr($dataset["ba_text"], 0, 40, MB_ENCODING)), false));	// substr problem bei trennung umlaute
    }
    else {
      $blogtext40 = stripslashes($this->html_tags($this->html5specialchars(mb_substr($dataset["ba_header"], 0, 40, MB_ENCODING)), false));	// substr problem bei trennung umlaute
    }

    $replace .= "<article id=\"".date_format($datetime, "YmdHis")."\">\n";	// blog id für article

    $permalink = date_format($datetime, "Y/m/").$blogalias;

    if ($diary_mode) {
      // kein header, intro-text nur für ersten eintrag, full_name verdeckt
      if ($first_entry) {
        $replace .= "<h2>".$blogintro."</h2>\n";
      }
      $replace .= "<p><span id=\"white_bold\"><a href=\"blog/".$permalink."/\">[".$blogdate."]</a></span> <span id=\"white\"><a href=\"index.php?".$blog_author_query."\" title=\"".$full_name."\">&#9998;</a></span> ".$blogtext."</p>\n";
    }
    else {
      // mit header, full_name sichtbar, intro-text für jeden eintrag
      $replace .= "<h1>".$blogheader."</h1>\n".
                  "<p><span id=\"white_small\"><a href=\"blog/".$permalink."/\">[".$blogdate."]</a> <a href=\"index.php?".$blog_author_query."\">".$full_name."</a></span></p>\n".
                  "<h2>".$blogintro."</h2>\n".
                  "<p>".$blogtext."</p>\n";
    }

    // optional videos in blog
    if (function_exists("finfo_open")) {

      $finfo = finfo_open(FILEINFO_MIME_TYPE);	// resource für rückgabe mime type
      if ($finfo and strlen($dataset["ba_videoid"]) > 0) {
        $videoid_array = explode(",",$dataset["ba_videoid"]);
        foreach ($videoid_array as $videoid) {
          $videoname = "video/".$videoid.".mp4";
          $mimetype = finfo_file($finfo, $videoname);
          if (is_readable($videoname) and $mimetype == "video/mp4") {
            $replace .= "<p>\n".
                        "<video controls=\"\">\n".
                        "<source src=\"".$videoname."\" type=\"video/mp4\">\n".
                        "Your browser does not support the video tag.\n".
                        "</video>\n".
                        "</p>\n";
          } // if mimetype
        } // foreach
        finfo_close($finfo);
      } // $finfo

    } // module fileinfo

    // optional fotos in blog
    if (strlen($dataset["ba_photoid"]) > 0) {
      $photoid_array = explode(",",$dataset["ba_photoid"]);
      foreach ($photoid_array as $photoid) {
        $imagename = "jpeg/".$photoid.".jpg";
        if (is_readable($imagename) and $image_str = exif_thumbnail($imagename, $width, $height, $type)) {
          $replace .= "<div id=\"blogphoto\">\n";
          $replace .= "<a href=\"".$imagename."\" onMouseOver=\"ajax('blogphoto','".$photoid."');\"><img class=\"thumbnail\" src=\"thumbnail.php?".$this->html_build_query(array("image" => $imagename))."\" width=\"".$width."\" height=\"".$height."\"></a>\n";
          $replace .= "<div id=\"photo_".$photoid."\"><noscript>no javascript</noscript></div>\n";
          $replace .= "</div>\n";
        }
      }
    }

    $blogid = $dataset["ba_id"];
    $option_array[$blogid] = "[".$blogdate."] ".$blogtext40."...";	// für select option in kommentar formular

    // optional link zu kommentar mit comment-id
    if (array_key_exists($blogid, $blog_comment_id_array)) {
      $replace .= "<div id=\"blogcomment\"><a href=\"index.php?".$this->html_build_query($query_data)."#comment".$blog_comment_id_array[$blogid]."\">".$this->language["FRONTEND_COMMENT_LINK"]."</a></div>";
    }

    $replace .= "</article>\n";

    return $replace;
  }

  // GET page auslesen
  private function getPage(&$page, $anzahl_s) {
    if (isset($page) and is_numeric($page)) {
      // page als zahl vorhanden und nicht NULL

      // page eingrenzen
      if  ($page < 1) {
        $page = 1;
      }
      elseif ($page > $anzahl_s) {
        $page = $anzahl_s;
      }

      return true;
    }
    return false;
  }

  // seitenauswahl mit links und vor/zurück
  private function page_index($anzahl_s, $page, $vz_flag=true, $eq_flag=true, $query_data=array(), $anchor="") {
    $replace = "<p>\n";

    // default
    if(empty($query_data)) {
      $query_data["action"] = "blog";
      $query_data["page"] = 0;
    }

    // page oder compage
    if (array_key_exists("compage", $query_data)) {
      $page_key = "compage";
    }
    else {
      $page_key = "page";
    }

    if ($page > 1 and $vz_flag == true) {
      $i = $page - 1;
      $query_data[$page_key] = $i;
      $replace .= "<a href=\"index.php?".$this->html_build_query($query_data).$anchor."\">".$this->language["PAGE_PREVIOUS"]."</a> \n";	// zurück
    }

    for ($dec=0; $dec<=floor($anzahl_s/10); $dec++) {											// seitenauswahl (mit zehnergruppen)
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
          if ($i == $page and $eq_flag == true) {
            $replace .= $i." \n";
          }
          else {
            $query_data[$page_key] = $i;
            $replace .= "<a href=\"index.php?".$this->html_build_query($query_data).$anchor."\">".$i."</a> \n";
          }
        }
      }
      else {
        $query_data[$page_key] = $start;
        $replace .= "<a href=\"index.php?".$this->html_build_query($query_data).$anchor."\">[".$start."-".$ende."]</a> \n";
      }
    }

    if ($page < $anzahl_s and $vz_flag == true) {
      $i = $page + 1;
      $query_data[$page_key] = $i;
      $replace .= "<a href=\"index.php?".$this->html_build_query($query_data).$anchor."\">".$this->language["PAGE_NEXT"]."</a>\n";	// vor
    }

    $replace .= "</p>\n";
    return $replace;
  }

  // liste mit tags
  private function taglist($tag, $tags_from_db) {
    $replace = "<p><b>".$this->language["FRONTEND_TAGS"]."</b></p>\n".
               "<p>\n";

    // array für tags
    $tags = array();

    // array mit category als key und zweites array mit allen tag_data zeilen
    foreach ($tags_from_db as $key => $tag_data_arr) {
      foreach ($tag_data_arr as $tag_data) {
        // tags trennen
        $split_array = preg_split("/\s*,+\s*|^\s+|\s+$/", $tag_data, 0, PREG_SPLIT_NO_EMPTY);	// preg split liefert 1 array mit allen tags aus der tag_data zeile
        foreach ($split_array as $tag_str) {
          $tags[] = $tag_str;
        }
        if ($key != "none") {
          $tags[] = $key;	// category als tag
        }
      }
    }

    // tags zählen und als array mit tag => anzahl zurückgeben
    $tag_arr = array_count_values($tags);
    ksort($tag_arr);	// nach tag sortieren

    // links tag (einträge)
    $taglist_arr = array();
    foreach ($tag_arr as $tag_key => $entries) {
      $tag_key_html = stripslashes($this->html5specialchars($tag_key));

      if (mb_strtolower($tag_key, MB_ENCODING) == mb_strtolower($tag, MB_ENCODING)) {
        // tag GET
        $taglist_arr[] = $tag_key_html." (".$entries.")";
      }
      else {
        // (alt)          "<a href=\"index.php?action=blog&tag=".rawurlencode(mb_strtolower($tag_key, MB_ENCODING))."\">".$tag_key_html."</a> (".$entries.")";
        $taglist_arr[] = "<a href=\"blog/".rawurlencode(mb_strtolower($tag_key, MB_ENCODING))."/\">".$tag_key_html."</a> (".$entries.")";
      }

    }
    $replace .= implode(",\n", $taglist_arr)."\n";

    $replace .= "</p>\n";
    return $replace;
  }

// *****************************************************************************
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

  private function year_min() {
    $year_min = 1970;	// unix ts 0

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT YEAR(ba_datetime) AS year_min FROM ba_blog WHERE YEAR(ba_datetime) >= FROM_UNIXTIME(0,'%Y') LIMIT 1";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        $dataset = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $year_min = intval($dataset["year_min"]);
        $ret->close();
        unset($ret);
      }

    } // datenbank

    return $year_min;
  }

  private function year_max() {
    return intval(date("Y"));
  }

  // links jahr, monat mit anzahl einträge als aufklappbare liste
  private function year_month_list($year, $month, $show_year=false, $show_month=false) {
    $replace = "<p><b>".$this->language["FRONTEND_ARCHIVE"]."</b></p>\n";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $year_min = $this->year_min();
      $year_max = $this->year_max();

      // array für links jahr (einträge), aktuelles jahr zuerst
      $year_arr = array();
      for ($i=$year_max; $i>=$year_min; $i--) {
        $sql = "SELECT ba_id FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND YEAR(ba_datetime) = ".$i;
        $ret = $this->database->query($sql);	// liefert in return db-objekt
        if ($ret) {
          $year_arr[$i] = $ret->num_rows;
          $ret->close();
          unset($ret);
        }
      }

      $month_names = array($this->language["MONTH_01"], $this->language["MONTH_02"], $this->language["MONTH_03"],
                           $this->language["MONTH_04"], $this->language["MONTH_05"], $this->language["MONTH_06"],
                           $this->language["MONTH_07"], $this->language["MONTH_08"], $this->language["MONTH_09"],
                           $this->language["MONTH_10"], $this->language["MONTH_11"], $this->language["MONTH_12"]);

      // links jahr(einträge) als liste
      foreach ($year_arr as $year_key => $year_entries) {
        if ($year_entries > 0) {
          $replace .= "<details>\n";

          // array für links monat (einträge), neuster monat zuerst
          $month_arr = array();
          for ($i=self::$month_max; $i>=self::$month_min; $i--) {
            $sql = "SELECT ba_id FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND YEAR(ba_datetime) = ".$year_key." AND MONTH(ba_datetime) = ".$i;
            $ret = $this->database->query($sql);	// liefert in return db-objekt
            if ($ret) {
              $month_arr[$i] = $ret->num_rows;
              $ret->close();
              unset($ret);
            }
          }

          // jahr
          if ($year_key == $year and $show_year == true and $show_month == false) {
            $replace .= "<summary>".$year_key." (".$year_entries.")</summary>\n";
          }
          else {
            // (alt)    "<summary><a href=\"index.php?action=blog&year=".$year_key."\">".$year_key."</a> (".$year_entries.")</summary>\n";
            $replace .= "<summary><a href=\"blog/".$year_key."/\">".$year_key."</a> (".$year_entries.")</summary>\n";
          }

          // links monat(einträge) als liste
          $replace .= "<ul>\n";
          foreach ($month_arr as $month_key => $month_entries) {
            if ($month_entries > 0) {

              // monat
              if ($year_key == $year and $show_year == true and $month_key == $month and $show_month == true) {
                $replace .= "<li>".$month_names[$month_key-1]." (".$month_entries.")</li>\n";
              }
              else {
                // (alt)    "<li><a href=\"index.php?action=blog&year=".$year_key."&month=".$month_key."\">".$month_names[$month_key-1]."</a> (".$month_entries.")</li>\n";
                $replace .= "<li><a href=\"blog/".$year_key."/".str_pad($month_key, 2, "0", STR_PAD_LEFT)."/\">".$month_names[$month_key-1]."</a> (".$month_entries.")</li>\n";
              }

            } // month_entries > 0
          } // foreach month
          $replace .= "</ul>\n";

          $replace .= "</details>\n";
        } // year_entries > 0
      } // foreach year

    } // datenbank

    return $replace;
  }

  private function get_tags_from_db() {
    $tags_from_db = array();

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_category, ba_tags FROM ba_blog INNER JOIN ba_blogcategory ON ba_blog.ba_catid = ba_blogcategory.ba_id WHERE ba_state >= ".STATE_PUBLISHED." AND (ba_category != '' OR ba_tags != '')";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $ba_category = trim($dataset["ba_category"]);
          $ba_tags = trim($dataset["ba_tags"]);
          if ($ba_category == "") {
            $ba_category = "none";
          }
          $tags_from_db[$ba_category][] = $ba_tags;	// array mit category als key und zweites array mit allen tag_data zeilen
        }
        $ret->close();
        unset($ret);
      }

    } // datenbank

    return $tags_from_db;
  }

  private function get_blog_comment_id_array() {
    $blog_comment_id_array = array();

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_id, ba_blogid FROM ba_comment WHERE ba_state >= ".STATE_PUBLISHED." AND ba_blogid > 1";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $commentid = $dataset["ba_id"];
          $blogid = $dataset["ba_blogid"];
          $blog_comment_id_array[$blogid] = $commentid;
        }
        $ret->close();
        unset($ret);
      }

    } // datenbank

    return $blog_comment_id_array;
  }

  // return alias aus db oder ""
  private function check_alias($alias) {
    $ba_alias = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_alias FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND ba_alias RLIKE ? LIMIT 1";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler

        // austauschen ? durch string (s)
        $stmt->bind_param("s", $alias);
        $stmt->execute();	// ausführen geänderte zeile

        $stmt->bind_result($dataset["ba_alias"]);
        // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein array als rückgabe

        if ($stmt->fetch()) {
          // wenn kein fehler (alias nicht vorhanden, datensatz leer)
          $ba_alias = trim($dataset["ba_alias"]);
        }

        $stmt->close();	// stmt-ojekt schließen
        unset($stmt);	// referenz löschen

      } // stmt

    } // datenbank

    return $ba_alias;
  }

  public function getBlog($blog_query, $tag, $userid, $page, $year, $month, $alias, $compage) {
    $hd_title_str = "";
    $replace = "";
    $errorstring = "";

    //$blog_query = rawurldecode($blog_query)
    //$tag = rawurldecode($tag);

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $replace = "<!-- blog -->\n";

      $parameter_array = array();	// für links
      $option_array = array();	// für kommentar

      // formular für suche
      $replace .= "<div id=\"blogquery\">\n";
      $replace .= "<form action=\"index.php\" method=\"get\">\n";
      $replace .= "<input type=\"hidden\" name=\"action\" value=\"blog\" />\n";
      $replace .= "<input type=\"text\" name=\"q\" placeholder=\"Suche\" class=\"size_20\" maxlength=\"64\" onkeyup=\"ajax('suggest',this.value);\" />\n";
      $replace .= "<input type=\"submit\" value=\"".$this->language["BUTTON_SEARCH"]."\" style=\"display:none;\" />\n";
      $replace .= "<div id=\"suggestion\"></div>";
      $replace .= "</form>\n";
      $replace .= "</div>\n";

      // tags
      $tags_from_db = $this->get_tags_from_db();	// array mit category als key und zweites array mit allen tag_data zeilen, weiterverwendung weiter unten

      $categories = array();
      foreach ($tags_from_db as $key => $tag_data_arr) {
        if ($key != "none") {
          // keine leeren rubriken, keine einzelnen tags
          $tag_data_arr_unique = array_unique(array_filter($tag_data_arr));	// unique und keine leeren elemente
          $categories[$key] = implode(", ", $tag_data_arr_unique);	// rubrik mit tag string
        }
      }

      // tags und rubriken als drop down menue (checkbox + label für mobile version)
      $replace .= "<div id=\"blogtag\">\n".
                  "<input type=\"checkbox\" class=\"checkbox_hack more\" id=\"mobile_menue\" checked=\"checked\">\n".
                  "<label class=\"more\" for=\"mobile_menue\">&equiv;</label>\n".
                  "<ul>\n";

      foreach ($categories as $category => $tag_str) {
        $tag_arr = preg_split("/\s*,+\s*|^\s+|\s+$/", $tag_str, 0, PREG_SPLIT_NO_EMPTY);	// tags einzeln, ohne leerzeichen
        $tag_arr_unique = array_unique($tag_arr);
        $tag_arr_unique_lower = array();
        foreach ($tag_arr_unique as $tag_str) {
          $tag_arr_unique_lower[] = mb_strtolower($tag_str, MB_ENCODING);
        }
        $html_a_ext = "";
        if (mb_strtolower($tag, MB_ENCODING) == mb_strtolower($category, MB_ENCODING) or in_array(mb_strtolower($tag, MB_ENCODING), $tag_arr_unique_lower)) {
          // tag GET
          $html_a_ext = "id=\"activated\" ";
        }
        // (alt)    "<li><a ".$html_a_ext."href=\"index.php?action=blog&tag=".rawurlencode(mb_strtolower($category, MB_ENCODING))."\">".stripslashes($this->html5specialchars($category))."</a>\n".
        $replace .= "<li><a ".$html_a_ext."href=\"blog/".rawurlencode(mb_strtolower($category, MB_ENCODING))."/\">".stripslashes($this->html5specialchars($category))."</a>\n".
                    "<ul>\n";
        foreach ($tag_arr_unique as $tag_out) {
          if (mb_strtolower($tag, MB_ENCODING) == mb_strtolower($tag_out, MB_ENCODING)) {
            // tag GET
            $replace .= "<li><span>".stripslashes($this->html5specialchars($tag_out))."</span>\n";
          }
          else {
            // (alt)    "<li><a href=\"index.php?action=blog&tag=".rawurlencode(mb_strtolower($tag_out, MB_ENCODING))."\">".stripslashes($this->html5specialchars($tag_out))."</a>\n";
            $replace .= "<li><a href=\"blog/".rawurlencode(mb_strtolower($tag_out, MB_ENCODING))."/\">".stripslashes($this->html5specialchars($tag_out))."</a>\n";
          }
        }
        $replace .= "</ul>\n".
                    "</li>\n";
      }

      $replace .= "</ul>\n".
                  "</div>\n";

      // array mit comment-id und blog-id
      $blog_comment_id_array = $this->get_blog_comment_id_array();

      // blog - anzeigen oder suchen
      if (isset($blog_query)) {
        // suchen, ohne tag flag

        $ret_array = $this->getQuery($blog_query, $page, $parameter_array, $option_array, $blog_comment_id_array, false, $tags_from_db);
        $hd_title_str = $ret_array["hd_title_ext"];

      }
      elseif (isset($tag)) {
        // tag suchen, mit tag flag

        $ret_array = $this->getQuery($tag, $page, $parameter_array, $option_array, $blog_comment_id_array, true, $tags_from_db);
        $hd_title_str = $ret_array["hd_title_ext"];

      }
      elseif (isset($userid)) {
        // anzeigen mit userid (author)

        $ret_array = $this->getAuthor($userid, $page, $parameter_array, $option_array, $blog_comment_id_array);
        $hd_title_str = $ret_array["hd_title_ext"];

      }
      else {
        // anzeigen

        $ret_array = $this->getEntry($page, $year, $month, $alias, $parameter_array, $option_array, $blog_comment_id_array);
        $hd_title_str = $ret_array["hd_title_ext"];

      } // suche oder anzeigen

      $replace .= "<div id=\"blog\">\n";

      $replace .= $ret_array["content"];
      $errorstring .= $ret_array["error"];

      $replace .= "</div>\n";

      // blogleiste

      if (isset($ret_array["taglist"])) {
        $taglist = $ret_array["taglist"];
      }
      else {
        $taglist .= $this->taglist("", $tags_from_db);	// default
      }

      if (isset($ret_array["year_month_list"])) {
        $year_month_list = $ret_array["year_month_list"];
      }
      else {
        $year_month_list .= $this->year_month_list(0, 0);	// default
      }

      $replace .= "<div id=\"blogstrip\">\n".$taglist.$year_month_list."</div>\n";

      $replace .= "<div id=\"blogcommentform\">\n";

      // kommentar
      if (sizeof($option_array) > 0) {
        // nur wenn einträge angezeigt werden

        $ret_array = $this->getComment($parameter_array, $option_array, $compage);
        $replace .= $ret_array["content"];
        $errorstring .= $ret_array["error"];

      } // kommentar

      $replace .= "</div>";

    } // datenbank
    else {
      $errorstring .= "<br>db error\n";
    }

    return array("hd_title_ext" => $hd_title_str, "content" => $replace, "error" => $errorstring);
  }

  // in blog suchen
  private function getQuery($blog_query_or_tag, $page, &$parameter_array, &$option_array, &$blog_comment_id_array, $tagflag, $tags_from_db) {
    $hd_title_str = "";
    $replace = "";
    $taglist = "";
    $errorstring = "";
    if (!$tagflag) { 
      $hd_title_str = " query";
    }

    // options
    $anzahl_eps = $this->check_zero($this->getOption_by_name("blog_entries_per_page"));	// anzahl einträge pro seite = 20
    $num_sentences = $this->check_zero($this->getOption_by_name("blog_num_sentences_intro"));	// anzahl sätze einleitung = 1
    $diary_mode = boolval($this->getOption_by_name("blog_diary_mode"));	// tagebuch modus an = 1

    // auf leer überprüfen
    if (mb_strlen($blog_query_or_tag, MB_ENCODING) > 0) {
      // wenn kein fehler

      $blog_query_or_tag2 = "[[:<:]]".$blog_query_or_tag."[[:>:]]";	// vorher mit LIKE "%".$blog_query."%" jetzt mit RLIKE word boundaries

      // zugriff auf mysql datenbank (5), in mysql select mit prepare() - sql injections verhindern
      if (!$tagflag) {
        // query
        $sql = "SELECT ba_id FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND (CONCAT(ba_header, ba_intro, ba_text) RLIKE ?)";	// (1) ohne LIMIT
      }
      else {
        // tag
        $sql = "SELECT ba_blog.ba_id FROM ba_blog INNER JOIN ba_blogcategory ON ba_blog.ba_catid = ba_blogcategory.ba_id WHERE ba_state >= ".STATE_PUBLISHED." AND (CONCAT(ba_category, ', ', ba_tags) RLIKE ?)";	// (1) ohne LIMIT
      }
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 5a

        // austauschen ? durch string (s)
        $stmt->bind_param("s", $blog_query_or_tag2);
        $stmt->execute();	// ausführen geänderte zeile

        $stmt->store_result();

        // suche ausgeben (1), results

        $anzahl_e = $stmt->num_rows;	// anzahl ergebnisse

        if (!$tagflag) {
          // query
          $results = $anzahl_e." ".$this->language["FRONTEND_RESULTS"];
          if ($anzahl_e == 1) {
            $results = $anzahl_e." ".$this->language["FRONTEND_RESULT"];
          }
          $replace = "<p>".$results."</p>\n";
        }
        else {
          // tag
          if ($anzahl_e > 0) {
            $hd_title_str .= " - ".stripslashes($this->html5specialchars($blog_query_or_tag));
          }
        }

        if ($anzahl_e > 0) {

          $anzahl_s = ceil($anzahl_e/$anzahl_eps);	// anzahl seiten, ceil() rundet auf

          // GET page auslesen
          if ($this->getPage($page, $anzahl_s)) {
            $hd_title_str .= " - ".$page;
          }
          else {
            $page = 1;
          }

          // LIMIT für sql berechnen
          $lmt_start = ($page-1) * $anzahl_eps;

          // zugriff auf mysql datenbank (6), in mysql select mit prepare() - sql injections verhindern
          if (!$tagflag) {
            // query
            $sql = "SELECT ba_id, ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND (CONCAT(ba_header, ba_intro, ba_text) RLIKE ?) ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;	// (2) mit LIMIT
          }
          else {
            // tag
            $sql = "SELECT ba_blog.ba_id, ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid INNER JOIN ba_blogcategory ON ba_blog.ba_catid = ba_blogcategory.ba_id WHERE ba_state >= ".STATE_PUBLISHED." AND (CONCAT(ba_category, ', ', ba_tags) RLIKE ?) ORDER BY ba_blog.ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;	// (2) mit LIMIT
          }
          $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
          if ($stmt) {
            // wenn kein fehler 6a

            // austauschen ? durch string (s)
            $stmt->bind_param("s", $blog_query_or_tag2);
            $stmt->execute();	// ausführen geänderte zeile

            $stmt->store_result();

            $stmt->bind_result($dataset["ba_id"],$dataset["ba_userid"],$dataset["ba_datetime"],$dataset["ba_alias"],$dataset["ba_header"],$dataset["ba_intro"],$dataset["ba_text"],$dataset["ba_videoid"],$dataset["ba_photoid"],$dataset["full_name"]);
            // oder ohne array dataset: $stmt->bind_result($ba_id, $ba_userid, $ba_datetime, $ba_alias, $ba_header, $ba_intro, $ba_text, $ba_videoid, $ba_photoid, $full_name);
            // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein array als rückgabe

            // suche ausgeben (2), bei mehreren ergebnissen auf mehreren seiten

            // parameter für link zu kommentar
            $query_data = array("action" => "blog");

            if (!$tagflag) {
              // query
              $parameter_array["query"] = $blog_query_or_tag;	// für kommentar weiter unten
              $query_data["q"] = $blog_query_or_tag;
            }
            else {
              // tag
              $parameter_array["tag"] = $blog_query_or_tag;	// für kommentar weiter unten
              $query_data["tag"] = $blog_query_or_tag;
            }
            if (isset($page)) {
              $parameter_array["page"] = $page;	// für kommentar weiter unten
              $query_data["page"] = $page;
            }

            // blog schreiben , ausgabeschleife
            $first_entry = true;
            while ($stmt->fetch()) {	// $dataset = $stmt->fetch_assoc(), fetch_assoc() liefert array, solange nicht NULL (letzter datensatz), hier jetzt nur fetch()
              $replace .= $this->blog_line($dataset, $option_array, $blog_comment_id_array, $query_data, $first_entry, $num_sentences, $diary_mode);
              $first_entry = false;	// nur erster schleifenaufruf
            }

            // seitenauswahl mit links und vor/zurück, mehrere suchergebnisse
            $query_data = array("action" => "blog");
            if (!$tagflag) {
              // query
              $query_data["q"] = mb_strtolower($blog_query_or_tag, MB_ENCODING);
            }
            else {
              // tag
              $query_data["tag"] = mb_strtolower($blog_query_or_tag, MB_ENCODING);
            }
            $query_data["page"] = 0;
            $replace .= $this->page_index($anzahl_s, $page, false, true, $query_data);

            // tags
            if ($tagflag) {
              $taglist .= $this->taglist($blog_query_or_tag, $tags_from_db);
            }
            else {
              $taglist .= $this->taglist("", $tags_from_db);
            }

          }
          else {
            $errorstring .= "<br>db error 6a\n";
          }

        } // anzahl_e > 0

        $stmt->close();	// stmt-ojekt schließen
        unset($stmt);	// referenz löschen

      }
      else {
        $errorstring .= "<br>db error 5a\n";
      }

    }
    else {
      $errorstring .= "<br>empty query or tag\n";	// query leer
    }

    return array("hd_title_ext" => $hd_title_str, "content" => $replace, "taglist" => $taglist, "error" => $errorstring);
  }

  // blog anzeigen
  private function getEntry($page, $year, $month, $alias, &$parameter_array, &$option_array, &$blog_comment_id_array) {
    $hd_title_str = "";
    $replace = "";
    $taglist = "";
    $errorstring = "";

    // options
    $anzahl_eps = $this->check_zero($this->getOption_by_name("blog_entries_per_page"));	// anzahl einträge pro seite = 20
    $num_sentences = $this->check_zero($this->getOption_by_name("blog_num_sentences_intro"));	// anzahl sätze einleitung = 1
    $diary_mode = boolval($this->getOption_by_name("blog_diary_mode"));	// tagebuch modus an = 1

    // zugriff auf mysql datenbank (5)
    $sql = "SELECT ba_id FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND ba_id != 1";
    $ret = $this->database->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 5b

      $anzahl_e = $ret->num_rows;	// anzahl einträge in ba_blog
      $anzahl_s = ceil($anzahl_e/$anzahl_eps);	// anzahl seiten in blog, ceil() rundet auf

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

      // init
      $show_page = false;
      $show_year = false;
      $show_month = false;
      $show_alias = false;
      $month_now = intval(date("n"));	// ohne führende null
      //$year = $this->year_max();
      //$page = 1;

      // GET page auslesen
      if ($this->getPage($page, $anzahl_s)) {
        $show_page = true;

        $hd_title_str .= " - ".$page;
      }

      // GET year auslesen
      elseif (isset($year) and is_numeric($year)) {
        // year als zahl vorhanden und nicht NULL

        $year_min = $this->year_min();
        $year_max = $this->year_max();

        // year eingrenzen
        if ($year < $year_min) {
          $year = $year_min;
        }
        elseif ($year > $year_max) {
          $year = $year_max;
        }

        $show_year = true;

        if ($show_page == false) {
          $hd_title_str .= " - ".$year;
        }

        // GET month auslesen
        if (isset($month) and is_numeric($month)) {
          // month als zahl vorhanden und nicht NULL

          // month eingrenzen
          if ($month < self::$month_min) {
            $month = self::$month_min;
          }
          elseif ($month > self::$month_max) {
            $month = self::$month_max;
          }

          $show_month = true;

          if ($show_page == false) {
            $hd_title_str .= "/".str_pad($month, 2, "0", STR_PAD_LEFT);	// mit führender null
          }

          $date_str = $year."-".$month."-1";	// format Y-n-j ohne führende null

          // datum validieren
          $datetime = date_create_from_format("Y-n-j|", $date_str);	// alles ab "|" ist 0
          $date = date_format($datetime, "Y-m-d H:i:s");	// return string "2018-02-09 00:00:00"

          // GET alias auslesen
          if (isset($alias) and strlen($alias) > 0) {
            // alias vorhanden und nicht leer

            $alias = $this->check_alias($alias);	// return alias aus db oder ""

            if (!empty($alias)) {
              $show_alias = true;

              if ($show_page == false) {
                $hd_title_str .= "/".stripslashes($this->html5specialchars($alias));
              }

            } // !empty($alias)

          } // isset($alias)

        } // isset($month)

      } // isset($year)

      else {
        // init
        $year = $this->year_max();
        $month = $month_now;
        $page = 1;
      }

      // aufruf ohne page und year
      if ($show_page == false and $show_year == false) {
        $show_page = true;
      }
      // page vorrang vor year
      elseif ($show_page == true and $show_year == true) {
        $show_year = false;
      }

      // LIMIT für sql berechnen
      $lmt_start = ($page-1) * $anzahl_eps;

      // zugriff auf mysql datenbank (6)
      $sql = "";	// blog eintrag nr.1 nur auf erster seite, danach alle absteigend 100..99...2 (ohne 1)
      if ($page == 1 or $show_year == true) {
        $sql .= "SELECT ba_id, ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND ba_id = 1".
                "\nUNION\n";
      }
      if ($show_page == true) {
        $sql .= "(SELECT ba_id, ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND ba_id != 1 ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps.")";
      }
      elseif ($show_year == true and $show_month == false) {
        $sql .= "(SELECT ba_id, ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND YEAR(ba_datetime) = ".$year." ORDER BY ba_id DESC LIMIT 0,".$anzahl_e.")";	// funktioniert nur mit limit
      }
      elseif ($show_year == true and $show_month == true and $show_alias == false) {
        $sql .= "(SELECT ba_id, ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND ba_datetime >= '".$date."' AND ba_datetime < '".$date."' + INTERVAL 1 MONTH ORDER BY ba_id DESC LIMIT 0,".$anzahl_e.")";	// funktioniert nur mit limit
      }
      elseif ($show_year == true and $show_month == true and $show_alias == true) {
        $sql .= "(SELECT ba_id, ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND ba_datetime >= '".$date."' AND ba_datetime < '".$date."' + INTERVAL 1 MONTH AND ba_alias = '".$alias."' ORDER BY ba_id DESC LIMIT 0,".$anzahl_e.")";	// funktioniert nur mit limit
      }
      else {
        $sql .= "(SELECT ba_id, ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND ba_id = 1)";
      }
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 6b

        // parameter für link zu kommentar
        $query_data = array("action" => "blog");

        if (isset($page) and $show_page) {
          $parameter_array["page"] = $page;	// für kommentar weiter unten
          $query_data["page"] = $page;
        }
        elseif (isset($year) and $show_year) {
          $parameter_array["year"] = $year;	// für kommentar weiter unten
          $query_data["year"] = $year;
          if (isset($month) and $show_month) {
            $parameter_array["month"] = $month;	// für kommentar weiter unten
            $query_data["month"] = $month;
            if (isset($alias) and $show_alias) {
              $parameter_array["alias"] = $alias;	// für kommentar weiter unten
              $query_data["alias"] = $alias;
            }
          }
        }

        // blog schreiben , ausgabeschleife
        $first_entry = true;
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $replace .= $this->blog_line($dataset, $option_array, $blog_comment_id_array, $query_data, $first_entry, $num_sentences, $diary_mode);
          $first_entry = false;	// nur erster schleifenaufruf
        }

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<br>db error 6b\n";
      }

      // seitenauswahl mit links und vor/zurück
      $replace .= $this->page_index($anzahl_s, $page, $show_page, $show_page);

      // links jahr, monat mit anzahl einträge als aufklappbare liste
      $year_month_list .= $this->year_month_list($year, $month, $show_year, $show_month);

    }
    else {
      $errorstring .= "<br>db error 5b\n";
    }

    return array("hd_title_ext" => $hd_title_str, "content" => $replace, "year_month_list" => $year_month_list, "error" => $errorstring);
  }

  // blog anzeigen, nur userid (author)
  private function getAuthor($userid, $page, &$parameter_array, &$option_array, &$blog_comment_id_array) {
    $hd_title_str = "";
    $replace = "";
    $errorstring = "";

    // options
    $anzahl_eps = $this->check_zero($this->getOption_by_name("blog_entries_per_page"));	// anzahl einträge pro seite = 20
    $num_sentences = $this->check_zero($this->getOption_by_name("blog_num_sentences_intro"));	// anzahl sätze einleitung = 1
    $diary_mode = boolval($this->getOption_by_name("blog_diary_mode"));	// tagebuch modus an = 1

    // zugriff auf mysql datenbank (5)
    $sql = "SELECT ba_id, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND ba_userid = ".$userid;	// (1) ohne LIMIT
    $ret = $this->database->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 5c

      // autor einträge ausgeben (1), anzahl

      $anzahl_e = $ret->num_rows;	// anzahl autor einträge

      $entries = $anzahl_e." ".$this->language["FRONTEND_ENTRIES"];
      if ($anzahl_e == 1) {
        $entries = $anzahl_e." ".$this->language["FRONTEND_ENTRY"];
      }

      // author name
      while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
        $full_name = $dataset["full_name"];
      }

      if (isset($full_name)) {
        $replace = "<p>".stripslashes($this->html5specialchars($full_name))." (".$entries."):</p>\n";
      }

      if ($anzahl_e > 0) {

        $hd_title_str .= " - ".stripslashes($this->html5specialchars($full_name));

        $anzahl_s = ceil($anzahl_e/$anzahl_eps);	// anzahl seiten in blog, ceil() rundet auf

        // GET page auslesen
        if ($this->getPage($page, $anzahl_s)) {
          $hd_title_str .= " - ".$page;
        }
        else {
          $page = 1;
        }

        // LIMIT für sql berechnen
        $lmt_start = ($page-1) * $anzahl_eps;

        // zugriff auf mysql datenbank (6)
        $sql = "SELECT ba_id, ba_userid, ba_datetime, ba_alias, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, full_name FROM ba_blog INNER JOIN backend ON backend.id = ba_blog.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND ba_userid = ".$userid." ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;	// (2) mit LIMIT
        $ret = $this->database->query($sql);	// liefert in return db-objekt
        if ($ret) {
          // wenn kein fehler 6c

          // autor einträge ausgeben (2), bei mehreren einträgen auf mehreren seiten

          // parameter für link zu kommentar
          $query_data = array("action" => "blog");

          $parameter_array["author"] = $userid;	// für kommentar weiter unten
          $query_data["author"] = $userid;
          if (isset($page)) {
            $parameter_array["page"] = $page;	// für kommentar weiter unten
            $query_data["page"] = $page;
          }

          // blog schreiben , ausgabeschleife
          $first_entry = true;
          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $replace .= $this->blog_line($dataset, $option_array, $blog_comment_id_array, $query_data, $first_entry, $num_sentences, $diary_mode);
            $first_entry = false;	// nur erster schleifenaufruf
          }

          // seitenauswahl mit links und vor/zurück, mehrere einträge
          $query_data = array("action" => "blog");
          $query_data["author"] = $userid;
          $query_data["page"] = 0;
          $replace .= $this->page_index($anzahl_s, $page, false, true, $query_data);

        }
        else {
          $errorstring .= "<br>db error 6c\n";
        }

      } // anzahl_e > 0

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

    }
    else {
      $errorstring .= "<br>db error 5c\n";
    }

    return array("hd_title_ext" => $hd_title_str, "content" => $replace, "error" => $errorstring);
  }

  // kommentar
  private function getComment(&$parameter_array, &$option_array, $compage) {
    $replace = "";
    $errorstring = "";

    // options
    $anzahl_cps = $this->check_zero($this->getOption_by_name("blog_comments_per_page"));	// anzahl kommentare pro seite = 20

    $replace .= "<p><a name=\"comment\"></a><b>".$this->language["FRONTEND_COMMENT"]."</b></p>\n";

    // für SELECT
    $min_blogid = 0;
    $max_blogid = 0;
    $keys = array_keys($option_array);
    if (array_key_exists("query", $parameter_array) or array_key_exists("tag", $parameter_array)) {
      // WHERE ba_blogid IN
      $sql_part = "ba_blogid IN (".implode(",", $keys).")";
    }
    else {
      // WHERE ba_blogid BETWEEN
      $len = sizeof($keys);
      if ($len == 1 and $keys[0] != 1) {
        $max_blogid = $keys[0];
        $min_blogid = $keys[0];
      }
      elseif ($len >= 2) {
        if ($keys[0] == 1) {
          // [1,letztes,erstes]
          $max_blogid = $keys[1];
        }
        else {
          // [letztes,erstes]
          $max_blogid = $keys[0];
        }
        $min_blogid = $keys[$len-1];
      }
      $sql_part = "ba_blogid BETWEEN ".$min_blogid." AND ".$max_blogid;
    }

    // zugriff auf mysql datenbank (7)
    $sql = "SELECT ba_id FROM ba_comment WHERE ba_state >= ".STATE_PUBLISHED." AND (ba_blogid = 1 OR ".$sql_part.")";
    $ret = $this->database->query($sql);	// liefert in return db-objekt
    if ($ret) {
      // wenn kein fehler 7

      $anzahl_c = $ret->num_rows;	// anzahl kommentare in ba_comment
      $anzahl_s = ceil($anzahl_c/$anzahl_cps);	// anzahl seiten für kommentar, ceil() rundet auf

      $ret->close();	// db-ojekt schließen
      unset($ret);	// referenz löschen

      // GET comment-page auslesen
      if (!$this->getPage($compage, $anzahl_s)) {
        $compage = 1;
      }

      // LIMIT für sql berechnen
      $lmt_start = ($compage-1) * $anzahl_cps;

      // zugriff auf mysql datenbank (8)
      $sql = "SELECT ba_id, ba_datetime, ba_name, ba_mail, ba_text, ba_comment, ba_blogid, IFNULL(full_name,'nobody') FROM ba_comment LEFT JOIN backend ON backend.id = ba_comment.ba_userid WHERE ba_state >= ".STATE_PUBLISHED." AND (ba_blogid = 1 OR ".$sql_part.") ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_cps;
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 8

        // kommentare schreiben, ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

          $comment_id = intval($dataset["ba_id"]);
          $datetime = $this->check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["ba_datetime"]));	// "YYYY-MM-DD HH:MM:SS"
          $comment_date = date_format($datetime, $this->language["FORMAT_DATE"]." / ".$this->language["FORMAT_TIME"]);	// "DD.MM.YY / HH:MM"
          $comment_name = stripslashes($this->html5specialchars($dataset["ba_name"]));
          $comment_mail = stripslashes($this->html5specialchars($dataset["ba_mail"]));
          $comment_text = stripslashes(nl2br($this->html5specialchars($dataset["ba_text"])));
          $comment_comment = stripslashes(nl2br($this->html5specialchars($dataset["ba_comment"])));
          //$comment_blogid = intval($dataset["ba_blogid"]);
          $comment_full_name = stripslashes($this->html5specialchars($dataset["full_name"]));	// comment commenters full name

          $replace .= "<p><a name=\"comment".$comment_id."\"></a><b>[".$comment_date."]</b> <span id=\"white\">";
          if ($comment_mail != "") {
            $replace .= "<a href=\"mailto:".$comment_mail."\">".$comment_name."</a>";
          }
          else {
            $replace .= $comment_name;
          }
          $replace .= ":</span> ".$comment_text."</p>\n";

          if ($comment_comment != "") {
            $replace .= "<p><i>".$comment_full_name.": ".$comment_comment."</i></p>\n";
          }

        } // while

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<br>db error 8\n";
      }

      $query_data = array("action" => "blog");

      if (array_key_exists("query", $parameter_array)) {
        $query_data["q"] = $parameter_array["query"];
        if (array_key_exists("page", $parameter_array)) {
          $query_data["page"] = $parameter_array["page"];
        }
      }
      elseif (array_key_exists("tag", $parameter_array)) {
        $query_data["tag"] = $parameter_array["tag"];
        if (array_key_exists("page", $parameter_array)) {
          $query_data["page"] = $parameter_array["page"];
        }
      }
      elseif (array_key_exists("page", $parameter_array)) {
        $query_data["page"] = $parameter_array["page"];
      }
      elseif (array_key_exists("year", $parameter_array)) {
        $query_data["year"] = $parameter_array["year"];
        if (array_key_exists("month", $parameter_array)) {
          $query_data["month"] = $parameter_array["month"];
        }
      }

      // seitenauswahl mit links und vor/zurück
      $query_data["compage"] = 0;
      $replace .= $this->page_index($anzahl_s, $compage, true, true, $query_data, "#comment");

    }
    else {
      $errorstring .= "<br>db error 7\n";
    }

    // kommentar formular
    $replace .= "<form action=\"index.php\" method=\"post\">\n".
                "<div id=\"input_name\">".$this->language["PROMPT_NAME"]."\n".
                "<br><input type=\"text\" name=\"comment[name]\" class=\"size_20\" maxlength=\"".MAXLEN_COMMENTNAME."\" />\n".
                "</div>\n".
                "<div id=\"input_mail\">".$this->language["PROMPT_MAIL"]."\n".
                "<br><input type=\"text\" name=\"comment[mail]\" class=\"size_20\" maxlength=\"".MAXLEN_COMMENTMAIL."\" />\n".
                "</div>\n".
                "<div id=\"input_website\">".$this->language["PROMPT_WEBSITE"]."\n".
                "<br><input type=\"text\" name=\"comment[website]\" class=\"size_20\" maxlength=\"".MAXLEN_COMMENTURL."\" value=\"http://\" />\n".
                "</div>\n".
                "<p>".$this->language["FRONTEND_PROMPT_BLOGENTRY"]."\n".
                "<br><select name=\"comment[blogid]\" size=\"1\">\n".
                "<option value=\"0\" selected>".$this->language["FRONTEND_IGNORE_MY_COMMENT"]."</option>\n";
    foreach ($option_array as $blogid => $blogtext) {
      if ($blogid == 1) {
        $blogtext = $this->language["FRONTEND_NONE"];
      }
      $replace .= "<option value=\"".$blogid."\">".$blogtext."</option>\n";
    }
    $replace .= "</select></p>\n".
                "<p>".$this->language["FRONTEND_PROMPT_COMMENT_MAXLEN"]."\n".
                "<br><textarea name=\"comment[text]\" class=\"cols_60_rows_6\"></textarea></p>\n".
                "<p><input type=\"submit\" value=\"".$this->language["BUTTON_SEND"]."\" /><input type=\"reset\" value=\"".$this->language["BUTTON_CLEAR"]."\" /></p>\n".
                "</form>\n";

    return array("content" => $replace, "error" => $errorstring);
  }

  // comment_array[name, mail, website, blogid, text]
  public function postComment($comment_name, $comment_mail, $comment_website, $comment_blogid, $comment_text) {
    $replace = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // options
      $contact_mail = stripslashes($this->getOption_by_name("contact_mail", true));	// als string

      $replace = "<!-- blog (POST comment) -->\n".
                 "<div id=\"blog\">\n";

      // IP zeitlimit überprüfen
      $comment_ip = $_SERVER["REMOTE_ADDR"];
      $sql = "SELECT ba_ip,
                     TIMESTAMPDIFF(MINUTE, ba_datetime, NOW()) AS zeitdifferenz
              FROM ba_comment ORDER BY ba_id DESC LIMIT 1";
      $ret = $this->database->query($sql);	// letzte eingetragene zeile IP und zeitdifferenz auslesen
      $dataset = $ret->fetch_assoc();	// IP und zeitdifferenz auswertbar als array

      // gleiche IP und weniger als 1 minute ist nicht ok
      if ($comment_ip != $dataset["ba_ip"] or $dataset["zeitdifferenz"] > 1) {
        // wenn kein zeit-limit

        if ($comment_website == "http://") {
          $comment_website = "";
        }

        // kein honeypot
        if ($comment_website == "" and $comment_blogid > 0) {

          // auf leer überprüfen
          if ($comment_name != "" or $comment_mail != "" or $comment_text != "") {

            // spamfilter
            $spam = false;
            $keywords = array("<a href", "</a>");
            foreach ($keywords as $keyword) {
              if (strpos($comment_text, $keyword) !== false) {
                // found keyword
                $spam = true;
                break;
              }
            }

            if (!$spam) {
              // if not spam

              // in mysql einfügen mit prepare() - sql injections verhindern
              $sql = "INSERT INTO ba_comment(ba_datetime, ba_ip, ba_name, ba_mail, ba_text, ba_blogid, ba_state) VALUES (NOW(), ?, ?, ?, ?, ?, ?)";
              $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
              if ($stmt) {
                // wenn kein fehler 9

                // austauschen ?????? durch string und int 
                $stmt->bind_param("ssssii", $comment_ip, $comment_name, $comment_mail, $comment_text, $comment_blogid, $comment_state);
                $stmt->execute();	// ausführen geänderte zeile

                $replace .= "<p>".$this->language["FRONTEND_MSG_NEW_COMMENT"]." - <a href=\"index.php?action=blog#comment\">".$this->language["FRONTEND_MSG_RETURN_TO_BLOG"]."</a> ".$this->language["FRONTEND_MSG_AUTOMATIC_IN_10_SECONDS"]."</p>\n";

                mail($contact_mail, $this->language["FRONTEND_MSG_NEW_BLOG_COMMENT"], $comment_name." (".$comment_mail."): ".$comment_text." (".$comment_blogid.")", "from:".$contact_mail);

              }
              else {
                $errorstring .= "<br>db error 9\n";
              }

            }
            else {
              $replace .= "<p>".$this->language["FRONTEND_MSG_SPAMFILTER"]."</p>\n";
            }

          }
          else {
            $replace .= "<p>".$this->language["FRONTEND_MSG_EMPTY_FIELDS"]."</p>\n";
          }

        } // kein honeypot

      }
      else {
        $replace .= "<p>".$this->language["FRONTEND_MSG_TIMELIMIT_MINUTES"]."</p>\n";
      }

      $replace .= "</div>";

    } // datenbank
    else {
      $errorstring .= "<br>db error\n";
    }

    return array("content" => $replace, "error" => $errorstring);
  }

}

?>
