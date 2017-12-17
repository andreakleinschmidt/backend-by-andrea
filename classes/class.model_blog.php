<?php

// *****************************************************************************
// * model - blog
// *****************************************************************************

define("STATE_APPROVAL",2);
define("STATE_PUBLISHED",3);
define("MB_ENCODING","UTF-8");
define("MAILADDR","morgana@oscilloworld.de");

class Blog extends Model {

  // variablen
  private static $month_min = 1;
  private static $month_max = 12;
  private static $year_min = 2009;
  private function year_max() {
    return intval(date("Y"));
  }

  // (doppelt in frontend und backend model blog)
  public function getOption_by_name($ba_name) {
    $value = 1;

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_value FROM ba_options WHERE ba_name = ?";
      $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler

        // austauschen ? durch string
        $stmt->bind_param("s", $ba_name);
        $stmt->execute();	// ausführen geänderte zeile

        $stmt->bind_result($datensatz["ba_value"]);
        // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

        if ($stmt->fetch()) {
          // wenn kein fehler (name nicht vorhanden, datensatz leer)
          $value = intval($datensatz["ba_value"]);
        }

        $stmt->close();
        unset($stmt);	// referenz löschen

      } // stmt

    } // datenbank

    return $value;
  }

  // ersetze tag kommandos im blogtext ~cmd{content} mit html tags <a>, <b>, <i>
  public function html_tags($text_str, $tag_flag, $encoding="UTF-8") {
    for ($start=0; mb_strpos($text_str, "~", $start, $encoding); $start++) {
      // suche tilde, abbruch der schleife wenn keine tilde mehr in text_str vorhanden (strpos return false)

      $start   = mb_strpos($text_str, "~", $start, $encoding);
      $brace   = mb_strpos($text_str, "{", $start, $encoding);
      $stop    = mb_strpos($text_str, "}", $start, $encoding);

      if ($brace and $stop) {
        // nur ausführen wenn {} gefunden
        $cmd     = mb_substr($text_str, $start+1, $brace-$start-1, $encoding);
        $content = mb_substr($text_str, $brace+1, $stop-$brace-1 , $encoding);

        switch ($cmd) {

          case "link":

            if (mb_strlen($content, $encoding) > 0 and $tag_flag) {
              $link = explode("|", $content);
              if (count($link) == 2) {
                $tag_str = "<a href=\"".$link[0]."\">".$link[1]."</a>";
              }
              else {
                $tag_str = "<a href=\"".$link[0]."\">".$link[0]."</a>";
              }
            }
            elseif (mb_strlen($content, $encoding) > 0 and !$tag_flag) {
              $link = explode("|", $content);
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

            if (mb_strlen($content, $encoding) > 0 and $tag_flag) {
              $tag_str = "<b>".$content."</b>";
            }
            elseif (mb_strlen($content, $encoding) > 0 and !$tag_flag) {
              $tag_str = $content;
            }
            else {
              $tag_str = "";
            }
            break;

          case "italic":

            if (mb_strlen($content, $encoding) > 0 and $tag_flag) {
              $tag_str = "<i>".$content."</i>";
            }
            elseif (mb_strlen($content, $encoding) > 0 and !$tag_flag) {
              $tag_str = $content;
            }
            else {
              $tag_str = "";
            }
            break;

          default:
            $tag_str = $cmd.$content;

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
  private function blog_line($datensatz, &$option_array, &$blog_comment_id_array, $query_data, $header_flag, $num_sentences) {
    $ersetzen = "";

    $datum = stripslashes($this->html5specialchars($datensatz["ba_date"]));
    $blogtext = stripslashes($this->html_tags(nl2br($this->html5specialchars($datensatz["ba_text"])), true));
    $blogtext40 = stripslashes($this->html_tags($this->html5specialchars(mb_substr($datensatz["ba_text"], 0, 40, MB_ENCODING)), false));	// substr problem bei trennung umlaute

    if ($header_flag) {
      $split_array = array_slice(preg_split("/(?<=\!\s|\.\s|\:\s|\?\s)/", $datensatz["ba_text"], $num_sentences+1, PREG_SPLIT_NO_EMPTY), 0, $num_sentences);
      $blogheader = stripslashes($this->html_tags(nl2br($this->html5specialchars(implode($split_array))), false));	// satzendzeichen als trennzeichen, anzahl sätze optional
      $ersetzen .= "<h1>".$blogheader."</h1>\n";
    }

    // blog id für anker
    $jahr   = substr($datum, 6, 2);
    $monat  = substr($datum, 3, 2);
    $tag    = substr($datum, 0, 2);
    $stunde = substr($datum, 11, 2);
    $minute = substr($datum, 14, 2);
    $jmtsm = "20".$jahr.$monat.$tag.$stunde.$minute."00";

    $ersetzen .= "<p><a name=\"".$jmtsm."\"></a><b>[".$datum."]</b> ".$blogtext."</p>\n";

    // optional videos in blog
    if (function_exists("finfo_open")) {

      $finfo = finfo_open(FILEINFO_MIME_TYPE);	// resource für rückgabe mime type
      if ($finfo and strlen($datensatz["ba_videoid"]) > 0) {
        $videoid_array = explode(",",$datensatz["ba_videoid"]);
        foreach ($videoid_array as $videoid) {
          $videoname = "video/".$videoid.".mp4";
          $mimetype = finfo_file($finfo, $videoname);
          if (is_readable($videoname) and $mimetype == "video/mp4") {
            $ersetzen .= "<p>\n".
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
    if (strlen($datensatz["ba_fotoid"]) > 0) {
      $fotoid_array = explode(",",$datensatz["ba_fotoid"]);
      foreach ($fotoid_array as $fotoid) {
        $imagename = "jpeg/".$fotoid.".jpg";
        if (is_readable($imagename) and $image_str = exif_thumbnail($imagename, $width, $height, $type)) {
          $ersetzen .= "<div id=\"blogfoto\">\n";
          $ersetzen .= "<a href=\"".$imagename."\" onMouseOver=\"ajax('blogfoto','".$fotoid."');\"><img class=\"kantefarbig\" src=\"thumbnail.php?".$this->html_build_query(array("image" => $imagename))."\" width=\"".$width."\" height=\"".$height."\"></a>\n";
          $ersetzen .= "<div id=\"foto_".$fotoid."\"><noscript>no javascript</noscript></div>\n";
          $ersetzen .= "</div>\n";
        }
      }
    }

    $blogid = $datensatz["ba_id"];
    $option_array[$blogid] = "[".$datum."] ".$blogtext40."...";	// für select option in kommentar formular

    // optional link zu kommentar mit comment-id
    if (array_key_exists($blogid, $blog_comment_id_array)) {
      $ersetzen .= "<div id=\"blogcomment\"><a href=\"index.php?".$this->html_build_query($query_data)."#comment".$blog_comment_id_array[$blogid]."\">Kommentar</a></div>";
    }

    return $ersetzen;
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
  private function seitenauswahl($anzahl_s, $page, $vz_flag=true, $eq_flag=true, $query_data=array(), $anchor="") {
    $ersetzen = "<p>\n";

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
      $ersetzen .= "<a href=\"index.php?".$this->html_build_query($query_data).$anchor."\">prev</a> \n";	// zurück
    }

    for ($i=1; $i<=$anzahl_s; $i++) {										// seitenauswahl
      if ($i == $page and $eq_flag == true) {
        $ersetzen .= $i." \n";
      }
      else {
        $query_data[$page_key] = $i;
        $ersetzen .= "<a href=\"index.php?".$this->html_build_query($query_data).$anchor."\">".$i."</a> \n";
      }
    }

    if ($page < $anzahl_s and $vz_flag == true) {
      $i = $page + 1;
      $query_data[$page_key] = $i;
      $ersetzen .= "<a href=\"index.php?".$this->html_build_query($query_data).$anchor."\">next</a>\n";		// vor
    }

    $ersetzen .= "</p>\n";
    return $ersetzen;
  }

  // liste mit tags
  private function tagliste($tag, $tags_from_db) {
    $ersetzen = "<p><b>Tags:</b></p>\n".
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
    $tagliste_arr = array();
    foreach ($tag_arr as $tag_key => $entries) {
      $tag_key_html = stripslashes($this->html5specialchars($tag_key));

      if (mb_strtolower($tag_key, MB_ENCODING) == mb_strtolower($tag, MB_ENCODING)) {
        // tag GET
        $tagliste_arr[] = $tag_key_html." (".$entries.")";
      }
      else {
        // (alt)          "<a href=\"index.php?action=blog&tag=".rawurlencode(mb_strtolower($tag_key, MB_ENCODING))."\">".$tag_key_html."</a> (".$entries.")";
        $tagliste_arr[] = "<a href=\"blog/".rawurlencode(mb_strtolower($tag_key, MB_ENCODING))."/\">".$tag_key_html."</a> (".$entries.")";
      }

    }
    $ersetzen .= implode(",\n", $tagliste_arr)."\n";

    $ersetzen .= "</p>\n";
    return $ersetzen;
  }

// *****************************************************************************
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

  // links jahr, monat mit anzahl einträge als aufklappbare liste
  private function jahr_monat_liste($year, $month, $show_year=false, $show_month=false) {
    $ersetzen = "<p><b>Archiv:</b></p>\n";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // array für links jahr (einträge), aktuelles jahr zuerst
      $year_arr = array();
      for ($i=$this->year_max(); $i>=self::$year_min; $i--) {
        $sql = "SELECT ba_id FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND YEAR(ba_datetime) = ".$i;
        $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
        if ($ret) {
          $year_arr[$i] = $ret->num_rows;
          $ret->close();
          unset($ret);
        }
      }

      $month_names = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");

      // links jahr(einträge) als liste
      foreach ($year_arr as $year_key => $year_entries) {
        if ($year_entries > 0) {
          $ersetzen .= "<details>\n";

          // array für links monat (einträge), neuster monat zuerst
          $month_arr = array();
          for ($i=self::$month_max; $i>=self::$month_min; $i--) {
            $sql = "SELECT ba_id FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND YEAR(ba_datetime) = ".$year_key." AND MONTH(ba_datetime) = ".$i;
            $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
            if ($ret) {
              $month_arr[$i] = $ret->num_rows;
              $ret->close();
              unset($ret);
            }
          }

          // jahr
          if ($year_key == $year and $show_year == true and $show_month == false) {
            $ersetzen .= "<summary>".$year_key." (".$year_entries.")</summary>\n";
          }
          else {
            // (alt)     "<summary><a href=\"index.php?action=blog&year=".$year_key."\">".$year_key."</a> (".$year_entries.")</summary>\n";
            $ersetzen .= "<summary><a href=\"blog/".$year_key."/\">".$year_key."</a> (".$year_entries.")</summary>\n";
          }

          // links monat(einträge) als liste
          $ersetzen .= "<ul>\n";
          foreach ($month_arr as $month_key => $month_entries) {
            if ($month_entries > 0) {

              // monat
              if ($year_key == $year and $show_year == true and $month_key == $month and $show_month == true) {
                $ersetzen .= "<li>".$month_names[$month_key-1]." (".$month_entries.")</li>\n";
              }
              else {
                // (alt)     "<li><a href=\"index.php?action=blog&year=".$year_key."&month=".$month_key."\">".$month_names[$month_key-1]."</a> (".$month_entries.")</li>\n";
                $ersetzen .= "<li><a href=\"blog/".$year_key."/".str_pad($month_key, 2, "0", STR_PAD_LEFT)."/\">".$month_names[$month_key-1]."</a> (".$month_entries.")</li>\n";
              }

            } // month_entries > 0
          } // foreach month
          $ersetzen .= "</ul>\n";

          $ersetzen .= "</details>\n";
        } // year_entries > 0
      } // foreach year

    } // datenbank

    return $ersetzen;
  }

  private function get_tags_from_db() {
    $tags_from_db = array();

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_category, ba_tags FROM ba_blog INNER JOIN ba_blogcategory ON ba_blog.ba_catid = ba_blogcategory.ba_id WHERE ba_state >= ".STATE_PUBLISHED." AND (ba_category != '' OR ba_tags != '')";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // ausgabeschleife
        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $ba_category = trim($datensatz["ba_category"]);
          $ba_tags = trim($datensatz["ba_tags"]);
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

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_id, ba_blogid FROM ba_comment WHERE ba_state >= ".STATE_PUBLISHED." AND ba_blogid > 1";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // ausgabeschleife
        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $commentid = $datensatz["ba_id"];
          $blogid = $datensatz["ba_blogid"];
          $blog_comment_id_array[$blogid] = $commentid;
        }
        $ret->close();
        unset($ret);
      }

    } // datenbank

    return $blog_comment_id_array;
  }

  public function getBlog($blog_query, $tag, $page, $year, $month, $compage) {
    $hd_title_str = "";
    $ersetzen = "";
    $errorstring = "";

    //$blog_query = rawurldecode($blog_query)
    //$tag = rawurldecode($tag);

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $ersetzen = "<!-- blog -->\n";

      $parameter_array = array();	// für links
      $option_array = array();	// für kommentar

      // formular für suche
      $ersetzen .= "<div id=\"blogquery\">\n";
      $ersetzen .= "<form action=\"index.php\" method=\"get\">\n";
      $ersetzen .= "<input type=\"hidden\" name=\"action\" value=\"blog\" />\n";
      $ersetzen .= "<input type=\"text\" name=\"q\" placeholder=\"Suche\" class=\"size_20\" maxlength=\"64\" onkeyup=\"ajax('suggest',this.value);\" />\n";
      $ersetzen .= "<input type=\"submit\" value=\"Suche\" style=\"display:none;\" />\n";
      $ersetzen .= "<div id=\"suggestion\"></div>";
      $ersetzen .= "</form>\n";
      $ersetzen .= "</div>\n";

      // tags
      $tags_from_db = $this->get_tags_from_db();	// array mit category als key und zweites array mit allen tag_data zeilen, weiterverwendung weiter unten

      $rubriken = array();
      foreach ($tags_from_db as $key => $tag_data_arr) {
        if ($key != "none") {
          // keine leeren rubriken, keine einzelnen tags
          $tag_data_arr_unique = array_unique(array_filter($tag_data_arr));	// unique und keine leeren elemente
          $rubriken[$key] = implode(", ", $tag_data_arr_unique);	// rubrik mit tag string
        }
      }

      // tags und rubriken als drop down menue (checkbox + label für mobile version)
      $ersetzen .= "<div id=\"blogtag\">\n".
                   "<input type=\"checkbox\" class=\"checkbox_hack more\" id=\"mobile_menue\" checked=\"checked\">\n".
                   "<label class=\"more\" for=\"mobile_menue\">&equiv;</label>\n".
                   "<ul>\n";

      foreach ($rubriken as $rubrik => $tag_str) {
        $tag_arr = preg_split("/\s*,+\s*|^\s+|\s+$/", $tag_str, 0, PREG_SPLIT_NO_EMPTY);	// tags einzeln, ohne leerzeichen
        $tag_arr_unique = array_unique($tag_arr);
        $tag_arr_unique_lower = array();
        foreach ($tag_arr_unique as $tag_str) {
          $tag_arr_unique_lower[] = mb_strtolower($tag_str, MB_ENCODING);
        }
        $html_a_ext = "";
        if (mb_strtolower($tag, MB_ENCODING) == mb_strtolower($rubrik, MB_ENCODING) or in_array(mb_strtolower($tag, MB_ENCODING), $tag_arr_unique_lower)) {
          // tag GET
          $html_a_ext = "id=\"aktiviert\" ";
        }
        // (alt)     "<li><a ".$html_a_ext."href=\"index.php?action=blog&tag=".rawurlencode(mb_strtolower($rubrik, MB_ENCODING))."\">".stripslashes($this->html5specialchars($rubrik))."</a>\n".
        $ersetzen .= "<li><a ".$html_a_ext."href=\"blog/".rawurlencode(mb_strtolower($rubrik, MB_ENCODING))."/\">".stripslashes($this->html5specialchars($rubrik))."</a>\n".
                     "<ul>\n";
        foreach ($tag_arr_unique as $tag_out) {
          if (mb_strtolower($tag, MB_ENCODING) == mb_strtolower($tag_out, MB_ENCODING)) {
            // tag GET
            $ersetzen .= "<li><span>".stripslashes($this->html5specialchars($tag_out))."</span>\n";
          }
          else {
            // (alt)     "<li><a href=\"index.php?action=blog&tag=".rawurlencode(mb_strtolower($tag_out, MB_ENCODING))."\">".stripslashes($this->html5specialchars($tag_out))."</a>\n";
            $ersetzen .= "<li><a href=\"blog/".rawurlencode(mb_strtolower($tag_out, MB_ENCODING))."/\">".stripslashes($this->html5specialchars($tag_out))."</a>\n";
          }
        }
        $ersetzen .= "</ul>\n".
                     "</li>\n";
      }

      $ersetzen .= "</ul>\n".
                   "</div>\n";

      // array mit comment-id und blog-id
      $blog_comment_id_array = $this->get_blog_comment_id_array();

      // blog - anzeigen oder suchen
      if (isset($blog_query)) {
        // suchen, ohne tag flag

        $ret_array = $this->getQuery($blog_query, $page, $parameter_array, $option_array, $blog_comment_id_array, false, $tags_from_db);
        $hd_title_str = $ret_array["hd_titel"];

      }
      elseif (isset($tag)) {
        // tag suchen, mit tag flag

        $ret_array = $this->getQuery($tag, $page, $parameter_array, $option_array, $blog_comment_id_array, true, $tags_from_db);
        $hd_title_str = $ret_array["hd_titel"];

      }
      else {
        // anzeigen

        $ret_array = $this->getEntry($page, $year, $month, $parameter_array, $option_array, $blog_comment_id_array);
        $hd_title_str = $ret_array["hd_titel"];

      } // suche oder anzeigen

      $ersetzen .= "<div id=\"blog\">\n";

      $ersetzen .= $ret_array["inhalt"];
      $errorstring .= $ret_array["error"];

      $ersetzen .= "</div>\n";

      // blogleiste

      if (isset($ret_array["tagliste"])) {
        $tagliste = $ret_array["tagliste"];
      }
      else {
        $tagliste .= $this->tagliste("", $tags_from_db);	// default
      }

      if (isset($ret_array["jahr_monat_liste"])) {
        $jahr_monat_liste = $ret_array["jahr_monat_liste"];
      }
      else {
        $jahr_monat_liste .= $this->jahr_monat_liste(0, 0);	// default
      }

      $ersetzen .= "<div id=\"blogleiste\">\n".$tagliste.$jahr_monat_liste."</div>\n";

      $ersetzen .= "<div id=\"blogcommentform\">\n";

      // kommentar
      if (sizeof($option_array) > 0) {
        // nur wenn einträge angezeigt werden

        $ret_array = $this->getComment($parameter_array, $option_array, $compage);
        $ersetzen .= $ret_array["inhalt"];
        $errorstring .= $ret_array["error"];

      } // kommentar

      $ersetzen .= "</div>";

    } // datenbank
    else {
      $errorstring .= "<br>db error\n";
    }

    return array("hd_titel" => $hd_title_str, "inhalt" => $ersetzen, "error" => $errorstring);
  }

  // in blog suchen
  private function getQuery($blog_query_or_tag, $page, &$parameter_array, &$option_array, &$blog_comment_id_array, $tagflag, $tags_from_db) {
    $hd_title_str = "";
    $ersetzen = "";
    $tagliste = "";
    $errorstring = "";
    if (!$tagflag) { 
      $hd_title_str = " query";
    }

    // options
    $anzahl_eps = intval($this->getOption_by_name("blog_entries_per_page"));	// anzahl einträge pro seite = 20
    $num_sentences = intval($this->getOption_by_name("blog_num_sentences_header"));	// anzahl sätze header = 1

    // auf leer überprüfen
    if (mb_strlen($blog_query_or_tag, MB_ENCODING) > 0) {
      // wenn kein fehler

      $blog_query_or_tag2 = "[[:<:]]".$blog_query_or_tag."[[:>:]]";	// vorher mit LIKE "%".$blog_query."%" jetzt mit RLIKE word boundaries

      // zugriff auf mysql datenbank (5), in mysql select mit prepare() - sql injections verhindern
      if (!$tagflag) {
        // query
        $sql = "SELECT ba_id FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND (ba_text RLIKE ?)";	// (1) ohne LIMIT
      }
      else {
        // tag
        $sql = "SELECT ba_blog.ba_id FROM ba_blog INNER JOIN ba_blogcategory ON ba_blog.ba_catid = ba_blogcategory.ba_id WHERE ba_state >= ".STATE_PUBLISHED." AND (CONCAT(ba_category, ', ', ba_tags) RLIKE ?)";	// (1) ohne LIMIT
      }
      $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
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
          $results = $anzahl_e." Ergebnisse";
          if ($anzahl_e == 1) {
            $results = $anzahl_e." Ergebnis";
          }
          $ersetzen = "<p>".$results."</p>\n";
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
            $sql = "SELECT ba_id, ba_date, ba_text, ba_videoid, ba_fotoid FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND (ba_text RLIKE ?) ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;	// (2) mit LIMIT
          }
          else {
            // tag
            $sql = "SELECT ba_blog.ba_id, ba_date, ba_text, ba_videoid, ba_fotoid FROM ba_blog INNER JOIN ba_blogcategory ON ba_blog.ba_catid = ba_blogcategory.ba_id WHERE ba_state >= ".STATE_PUBLISHED." AND (CONCAT(ba_category, ', ', ba_tags) RLIKE ?) ORDER BY ba_blog.ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;		// (2) mit LIMIT
          }
          $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
          if ($stmt) {
            // wenn kein fehler 6a

            // austauschen ? durch string (s)
            $stmt->bind_param("s", $blog_query_or_tag2);
            $stmt->execute();	// ausführen geänderte zeile

            $stmt->store_result();

            $stmt->bind_result($datensatz["ba_id"],$datensatz["ba_date"],$datensatz["ba_text"],$datensatz["ba_videoid"],$datensatz["ba_fotoid"]);
            // oder ohne array datensatz: $stmt->bind_result($ba_id, $ba_date, $ba_text, $ba_videoid, $ba_fotoid);
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
            $header_flag = true;
            while ($stmt->fetch()) {	// $datensatz = $stmt->fetch_assoc(), fetch_assoc() liefert array, solange nicht NULL (letzter datensatz), hier jetzt nur fetch()
              $ersetzen .= $this->blog_line($datensatz, $option_array, $blog_comment_id_array, $query_data, $header_flag, $num_sentences);
              $header_flag = false;	// nur erster schleifenaufruf
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
            $ersetzen .= $this->seitenauswahl($anzahl_s, $page, false, true, $query_data);

            // tags
            if ($tagflag) {
              $tagliste .= $this->tagliste($blog_query_or_tag, $tags_from_db);
            }
            else {
              $tagliste .= $this->tagliste("", $tags_from_db);
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

    return array("hd_titel" => $hd_title_str, "inhalt" => $ersetzen, "tagliste" => $tagliste, "error" => $errorstring);
  }

  // blog anzeigen
  private function getEntry($page, $year, $month, &$parameter_array, &$option_array, &$blog_comment_id_array) {
    $hd_title_str = "";
    $ersetzen = "";
    $tagliste = "";
    $errorstring = "";

    // options
    $anzahl_eps = intval($this->getOption_by_name("blog_entries_per_page"));	// anzahl einträge pro seite = 20
    $num_sentences = intval($this->getOption_by_name("blog_num_sentences_header"));	// anzahl sätze header = 1

    // zugriff auf mysql datenbank (5)
    $sql = "SELECT ba_id FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND ba_id != 1";
    $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
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

        // year eingrenzen
        if ($year < self::$year_min) {
          $year = sel::$year_min;
        }
        elseif ($year > $this->year_max()) {
          $year = $this->year_max();
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

        }

      }

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
        $sql .= "SELECT ba_id, ba_date, ba_text, ba_videoid, ba_fotoid FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND ba_id = 1".
                "\nUNION\n";
      }
      if ($show_page == true) {
        $sql .= "(SELECT ba_id, ba_date, ba_text, ba_videoid, ba_fotoid FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND ba_id != 1 ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps.")";
      }
      elseif ($show_year == true and $show_month == false) {
        $sql .= "(SELECT ba_id, ba_date, ba_text, ba_videoid, ba_fotoid FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND YEAR(ba_datetime) = ".$year." ORDER BY ba_id DESC LIMIT 0,".$anzahl_e.")";	// funktioniert nur mit limit
      }
      elseif ($show_year == true and $show_month == true) {
        $sql .= "(SELECT ba_id, ba_date, ba_text, ba_videoid, ba_fotoid FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND YEAR(ba_datetime) = ".$year." AND MONTH(ba_datetime) = ".$month." ORDER BY ba_id DESC LIMIT 0,".$anzahl_e.")";	// funktioniert nur mit limit
      }
      else {
        $sql .= "(SELECT ba_id, ba_date, ba_text, ba_videoid, ba_fotoid FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." AND ba_id = 1)";
      }
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
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
          }
        }

        // blog schreiben , ausgabeschleife
        $header_flag = true;
        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $ersetzen .= $this->blog_line($datensatz, $option_array, $blog_comment_id_array, $query_data, $header_flag, $num_sentences);
          $header_flag = false;	// nur erster schleifenaufruf
        }

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<br>db error 6b\n";
      }

      // seitenauswahl mit links und vor/zurück
      $ersetzen .= $this->seitenauswahl($anzahl_s, $page, $show_page, $show_page);

      // links jahr, monat mit anzahl einträge als aufklappbare liste
      $jahr_monat_liste .= $this->jahr_monat_liste($year, $month, $show_year, $show_month);

    }
    else {
      $errorstring .= "<br>db error 5b\n";
    }

    return array("hd_titel" => $hd_title_str, "inhalt" => $ersetzen, "jahr_monat_liste" => $jahr_monat_liste, "error" => $errorstring);
  }

  // kommentar
  private function getComment(&$parameter_array, &$option_array, $compage) {
    $ersetzen = "";
    $errorstring = "";

    // options
    $anzahl_cps = intval($this->getOption_by_name("blog_comments_per_page"));	// anzahl kommentare pro seite = 20

    $ersetzen .= "<p><a name=\"comment\"></a><b>Kommentar:</b></p>\n";

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
    $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
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
      $sql = "SELECT ba_id, ba_date, ba_name, ba_mail, ba_text, ba_comment, ba_blogid FROM ba_comment WHERE ba_state >= ".STATE_PUBLISHED." AND (ba_blogid = 1 OR ".$sql_part.") ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_cps;
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 8

        // kommentare schreiben, ausgabeschleife
        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

          $comment_id = intval($datensatz["ba_id"]);
          $date = date_create($datensatz["ba_date"]);
          $comment_date = date_format($date, "d.m.y / H:i");
          $comment_name = stripslashes($this->html5specialchars($datensatz["ba_name"]));
          $comment_mail = stripslashes($this->html5specialchars($datensatz["ba_mail"]));
          $comment_text = stripslashes(nl2br($this->html5specialchars($datensatz["ba_text"])));
          $comment_comment = stripslashes(nl2br($this->html5specialchars($datensatz["ba_comment"])));
          //$comment_blogid = intval($datensatz["ba_blogid"]);

          $ersetzen .= "<p><a name=\"comment".$comment_id."\"></a><b>[".$comment_date."]</b> <span id=\"white\">";
          if ($comment_mail != "") {
            $ersetzen .= "<a href=\"mailto:".$comment_mail."\">".$comment_name."</a>";
          }
          else {
            $ersetzen .= $comment_name;
          }
          $ersetzen .= ":</span> ".$comment_text."</p>\n";

          if ($comment_comment != "") {
            $ersetzen .= "<p><i>Morgana: ".$comment_comment."</i></p>\n";
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
      $ersetzen .= $this->seitenauswahl($anzahl_s, $compage, true, true, $query_data, "#comment");

    }
    else {
      $errorstring .= "<br>db error 7\n";
    }

    // kommentar formular
    $ersetzen .= "<form action=\"index.php\" method=\"post\">\n".
                 "<div id=\"input_name\">Name:\n".
                 "<br><input type=\"text\" name=\"comment[name]\" class=\"size_20\" maxlength=\"".MAXLEN_COMMENTNAME."\" />\n".
                 "</div>\n".
                 "<div id=\"input_mail\">Mail:\n".
                 "<br><input type=\"text\" name=\"comment[mail]\" class=\"size_20\" maxlength=\"".MAXLEN_COMMENTMAIL."\" />\n".
                 "</div>\n".
                 "<div id=\"input_website\">Website:\n".
                 "<br><input type=\"text\" name=\"comment[website]\" class=\"size_20\" maxlength=\"".MAXLEN_COMMENTURL."\" value=\"http://\" />\n".
                 "</div>\n".
                 "<p>Der Blog-Eintrag auf dem sich dein Kommentar bezieht:\n".
                 "<br><select name=\"comment[blogid]\" size=\"1\">\n".
                 "<option value=\"0\" selected>Ich bin ein Bot und mein Kommentar wird ignoriert</option>\n";
    foreach ($option_array as $blogid => $blogtext) {
      if ($blogid == 1) {
        $blogtext = "keiner";
      }
      $ersetzen .= "<option value=\"".$blogid."\">".$blogtext."</option>\n";
    }
    $ersetzen .= "</select></p>\n".
                 "<p>Kommentar (max. 2048 Zeichen):\n".
                 "<br><textarea name=\"comment[text]\" class=\"cols_60_rows_6\"></textarea></p>\n".
                 "<p><input type=\"submit\" value=\"send\" /><input type=\"reset\" value=\"clear\" /></p>\n".
                 "</form>\n";

    return array("inhalt" => $ersetzen, "error" => $errorstring);
  }

  // comment_array[name, mail, website, blogid, text]
  public function postComment($comment_name, $comment_mail, $comment_website, $comment_blogid, $comment_text) {
    $ersetzen = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $ersetzen = "<!-- blog (POST comment) -->\n".
                  "<div id=\"blog\">\n";

      // IP zeitlimit überprüfen
      $comment_ip = $_SERVER["REMOTE_ADDR"];
      $sql = "SELECT ba_ip,
                     TIMESTAMPDIFF(MINUTE, ba_date, NOW()) AS zeitdifferenz
              FROM ba_comment ORDER BY ba_id DESC LIMIT 1";
      $ret = $this->datenbank->query($sql);	// letzte eingetragene zeile IP und zeitdifferenz auslesen
      $datensatz = $ret->fetch_assoc();	// IP und zeitdifferenz auswertbar als array

      // gleiche IP und weniger als 1 minute ist nicht ok
      if ($comment_ip != $datensatz["ba_ip"] or $datensatz["zeitdifferenz"] > 1) {
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
              $sql = "INSERT INTO ba_comment(ba_date, ba_ip, ba_name, ba_mail, ba_text, ba_blogid, ba_state) VALUES (NOW(), ?, ?, ?, ?, ?, ?)";
              $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
              if ($stmt) {
                // wenn kein fehler 9

                // austauschen ?????? durch string und int 
                $stmt->bind_param("ssssii", $comment_ip, $comment_name, $comment_mail, $comment_text, $comment_blogid, $comment_state);
                $stmt->execute();	// ausführen geänderte zeile

                $ersetzen .= "<p>Dein Kommentar wird in Kürze freigegeben, zensiert oder gelöscht - <a href=\"index.php?action=blog#comment\">Zurück zum Blog</a> (automatisch in 10 Sekunden)</p>\n";

                mail(MAILADDR, "neuer blog kommentar", $comment_name." (".$comment_mail."): ".$comment_text." (".$comment_blogid.")", "from:".MAILADDR);

              }
              else {
                $errorstring .= "<br>db error 9\n";
              }

            }
            else {
              $ersetzen .= "<p>spamfilter</p>\n";
            }

          }
          else {
            $ersetzen .= "<p>Leere Felder - Kein Inhalt</p>\n";
          }

        } // kein honeypot

      }
      else {
        $ersetzen .= "<p>Zeit-Limit 1 Minute</p>\n";
      }

      $ersetzen .= "</div>";

    } // datenbank
    else {
      $errorstring .= "<br>db error\n";
    }

    return array("inhalt" => $ersetzen, "error" => $errorstring);
  }

}

?>
