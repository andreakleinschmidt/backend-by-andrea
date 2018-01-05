<?php

// *****************************************************************************
// * ba_model - blog
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXLEN_BLOGDATE",32);
define("MAXLEN_BLOGHEADER",128);
define("MAXLEN_BLOGINTRO",1024);
define("MAXLEN_BLOGTEXT",8192);
define("MAXLEN_BLOGVIDEOID",32);
define("MAXLEN_BLOGPHOTOID",128);
define("MAXLEN_BLOGTAGS",128);
define("MAXLEN_BLOGCATEGORY",32);
define("MAXLEN_FEED",128);	// blogroll
define("STATE_CREATED",0);
define("STATE_EDITED",1);
define("STATE_APPROVAL",2);
define("STATE_PUBLISHED",3);
define("MB_ENCODING","UTF-8");

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

  private function getBloglist($page) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_blog (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_userid INT UNSIGNED NOT NULL,
      //                ba_datetime DATETIME NOT NULL,
      //                ba_date VARCHAR(32) NOT NULL,
      //                ba_header VARCHAR(128) NOT NULL,
      //                ba_intro VARCHAR(1024) NOT NULL,
      //                ba_text VARCHAR(8192) NOT NULL,
      //                ba_videoid VARCHAR(32) NOT NULL,
      //                ba_photoid VARCHAR(128) NOT NULL,
      //                ba_catid INT UNSIGNED NOT NULL,
      //                ba_tags VARCHAR(128) NOT NULL,
      //                ba_state TINYINT UNSIGNED NOT NULL);

      // options
      $anzahl_eps = intval($this->getOption_by_name("blog_entries_per_page"));	// anzahl einträge pro seite = 20
      $diary_mode = boolval($this->getOption_by_name("blog_diary_mode"));	// tagebuch modus an = 1

      // liste mit älteren blog-einträgen
      $html_backend_ext .= "<p id=\"bloglist\"><b>".$this->language["HEADER_BLOG_LIST"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_id FROM ba_blog";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3e

        $anzahl_e = $ret->num_rows;	// anzahl einträge in ba_blog
        $anzahl_s = ceil($anzahl_e/$anzahl_eps);	// anzahl seiten in ba_blog, ceil() rundet auf

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

        }
        else {
          $page = 1;
        }

        // LIMIT für sql berechnen
        $lmt_start = ($page-1) * $anzahl_eps;

        // zugriff auf mysql datenbank (3)
        $sql = "SELECT ba_id, ba_date, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, ba_catid, ba_tags, ba_state FROM ba_blog ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;
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

            $ba_date = stripslashes($this->html5specialchars($dataset["ba_date"]));
            $ba_header = stripslashes($this->html5specialchars(mb_substr($dataset["ba_header"], 0, 40, MB_ENCODING)));	// 80 wie blogbox in class.model.php, substr problem bei trennung umlaute
            $ba_intro = stripslashes($this->html5specialchars(mb_substr($dataset["ba_intro"], 0, 40, MB_ENCODING)));	// 80 wie blogbox in class.model.php, substr problem bei trennung umlaute
            $ba_text = stripslashes($this->html5specialchars(mb_substr($dataset["ba_text"], 0, 80, MB_ENCODING)));	// 80 wie blogbox in class.model.php, substr problem bei trennung umlaute
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
                                 "<a href=\"backend.php?".$this->html_build_query(array("action" => "blog", "id" => $dataset["ba_id"]))."\">".$ba_date." - ";
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

          for ($i=1; $i<=$anzahl_s; $i++) {														// seitenauswahl
            if ($i == $page) {
              $html_backend_ext .= $i." \n";
            }
            else {
              $query_data["page"] = $i;
              $html_backend_ext .= "<a href=\"backend.php?".$this->html_build_query($query_data)."\">".$i."</a> \n";
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
              $html_backend_ext .= "<input type=\"number\" name=\"ba_options[".$ba_name."][ba_value]\" class=\"size_4\" min=\"1\" max=\"255\" value=\"".$ba_value."\"/>";
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

  public function getBlog($id, $page) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // TABLE ba_blog (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_userid INT UNSIGNED NOT NULL,
      //                ba_datetime DATETIME NOT NULL,
      //                ba_date VARCHAR(32) NOT NULL,
      //                ba_header VARCHAR(128) NOT NULL,
      //                ba_intro VARCHAR(1024) NOT NULL,
      //                ba_text VARCHAR(8192) NOT NULL,
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
      $ba_date = "";
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
        $sql = "SELECT ba_id, ba_datetime, ba_date, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, ba_catid, ba_tags, ba_state FROM ba_blog WHERE ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 3d

          // austauschen ? durch int (i)
          $stmt->bind_param("i", $id);
          $stmt->execute();	// ausführen geänderte zeile

          $stmt->bind_result($dataset["ba_id"],$dataset["ba_datetime"],$dataset["ba_date"],$dataset["ba_header"],$dataset["ba_intro"],$dataset["ba_text"],$dataset["ba_videoid"],$dataset["ba_photoid"],$dataset["ba_catid"],$dataset["ba_tags"],$dataset["ba_state"]);
          // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

          if ($stmt->fetch()) {
            // wenn kein fehler (id nicht vorhanden, datensatz leer)

            // preset überschreiben
            $ba_id = $dataset["ba_id"];
            $ba_datetime = stripslashes($this->html5specialchars($dataset["ba_datetime"]));
            $ba_date = stripslashes($this->html5specialchars($dataset["ba_date"]));
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
        $ba_date = date("d.m.y / H:i");	// 18.11.11 / 03:00

        // ba_datetime mit datetime-wert aus ba_date-string 
        $date = date_create_from_format("d.m.y / H:i|", $ba_date);	// alles ab "|" ist 0
        $ba_datetime = date_format($date, "Y-m-d H:i:s");	// return string "2014-11-13 23:45:00"
        if ($date == false or $ba_datetime == false) {
          $ba_datetime = "0000-00-00 00:00:00";	// im fehlerfall
        }

      } // keine id

      $categories = $this->getCategories();	// return array($category => $catid)

      // formular felder für blog eintrag , GET id oder neu , ba_id und ba_userid in hidden feld , ba_daten aus preset (ohne ba_datetime, hier nur info-anzeige, wird bei POST neu gesetzt)

      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_DATE"].
                           "</td>\n<td>\n".
                           "<input type=\"hidden\" name=\"ba_blog[ba_id]\" value=\"".$ba_id."\"/>\n".
                           "<input type=\"hidden\" name=\"ba_blog[ba_userid]\" value=\"".$ba_userid."\"/>\n".
                           "<input type=\"text\" name=\"ba_blog[ba_date]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGDATE."\" value=\"".$ba_date."\"/>\n".
                           "(".$ba_datetime.")\n".
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
                           "</td>\n<td>".
                           "<textarea name=\"ba_blog[ba_text]\" class=\"cols_96_rows_22\">".$ba_text."</textarea>".
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
                           "<input type=\"text\" name=\"ba_blog[ba_tags]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGTAGS."\" value=\"".$ba_tags."\"/>".
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
      $bloglist = $this->getBloglist($page);
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

  public function postBlog($ba_id, $ba_userid, $ba_date, $ba_header, $ba_intro, $ba_text, $ba_videoid, $ba_photoid, $ba_catid, $ba_tags, $ba_state, $ba_delete) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      if ($ba_id != 0xffff) {

        $count = 0;

        // ba_datetime mit datetime-wert aus ba_date-string 
        $date = date_create_from_format("d.m.y / H:i|", $ba_date);	// alles ab "|" ist 0
        $ba_datetime = date_format($date, "Y-m-d H:i:s");	// return string "2014-11-13 23:45:00"
        if ($date == false or $ba_datetime == false) {
          $ba_datetime = "0000-00-00 00:00:00";	// im fehlerfall
        }

        // einfügen in datenbank:
        if ($ba_id == 0) {
          $sql = "INSERT INTO ba_blog (ba_userid, ba_datetime, ba_date, ba_header, ba_intro, ba_text, ba_videoid, ba_photoid, ba_catid, ba_tags, ba_state) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }

        // löschen in datenbank:
        elseif ($ba_delete) {
          $sql = "DELETE FROM ba_blog WHERE ba_id = ?";
        }

        // update in datenbank:
        else {
          $sql = "UPDATE ba_blog SET ba_userid = ?, ba_datetime = ?, ba_date = ?, ba_header = ?, ba_intro = ?, ba_text = ?, ba_videoid = ?, ba_photoid = ?, ba_catid = ?, ba_tags = ?, ba_state = ? WHERE ba_id = ?";
        }

        // mit prepare() - sql injections verhindern
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4e

          // austauschen ???????????, ? oder ???????????? durch string und int
          if ($ba_id == 0) {
            $stmt->bind_param("isssssssisi", $ba_userid, $ba_datetime, $ba_date, $ba_header, $ba_intro, $ba_text, $ba_videoid, $ba_photoid, $ba_catid, $ba_tags, $ba_state);	// einfügen in datenbank
            $html_backend_ext .= "<p>".$this->language["MSG_BLOG_NEW"]."</p>\n\n";
          }
          elseif ($ba_delete) {
            $stmt->bind_param("i", $ba_id);	// löschen in datenbank
            $html_backend_ext .= "<p>".$this->language["MSG_BLOG_DELETE"]."</p>\n\n";
          }
          else {
            $stmt->bind_param("isssssssisii", $ba_userid, $ba_datetime, $ba_date, $ba_header, $ba_intro, $ba_text, $ba_videoid, $ba_photoid, $ba_catid, $ba_tags, $ba_state, $ba_id);	// update in datenbank
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
