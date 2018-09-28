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
// * ba_model - comment
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXLEN_DATETIME",20);
//define("MAXLEN_COMMENTIP",48);
//define("MAXLEN_COMMENTNAME",64);
//define("MAXLEN_COMMENTMAIL",64);
//define("MAXLEN_COMMENTBLOGID",8);
//define("MAXLEN_COMMENTTEXT",2048);
//define("MAXLEN_COMMENTCOMMENT",2048);
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
// db error 3g - stmt bei backend GET comment
// db error 3h - ret bei backend GET comment liste (anzahl ba_id)
// db error 3i - ret bei backend GET comment liste (ausgabe)
//
// db error 4f - stmt bei backend POST comment
//
// no id! - bei backend GET comment (kein datensatz)
// no id! - bei backend POST comment (id=0xffff)

class Comment extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  private function getCommentlist($page, $date) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      // TABLE ba_comment (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                   ba_userid INT UNSIGNED NOT NULL,
      //                   ba_datetime DATETIME NOT NULL,
      //                   ba_ip VARCHAR(48) NOT NULL,
      //                   ba_name VARCHAR(64) NOT NULL,
      //                   ba_mail VARCHAR(64) NOT NULL,
      //                   ba_text VARCHAR(2048) NOT NULL,
      //                   ba_comment VARCHAR(2048) NOT NULL,
      //                   ba_blogid INT UNSIGNED NOT NULL,
      //                   ba_state TINYINT UNSIGNED NOT NULL);

      // options
      $anzahl_eps = Blog::check_zero(Blog::getOption_by_name("blog_comments_per_page"));	// anzahl einträge pro seite = 20

      // liste mit älteren kommentar-einträgen
      $html_backend_ext .= "<p id=\"commentlist\"><b>".$this->language["HEADER_COMMENT_LIST"]."</b></p>\n\n";

      // suche nach datum
      $html_backend_ext .= "<form action=\"backend.php\" method=\"get\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_DATE"].
                           "</td>\n<td>\n".
                           "<input type=\"hidden\" name=\"action\" value=\"comment\"/>\n".
                           "<input type=\"date\" name=\"date\"/>\n".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_SEARCH"]."\" />\n".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_id FROM ba_comment";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3h

        $anzahl_e = $ret->num_rows;	// anzahl einträge in ba_comment
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
          $sql = "SELECT ba_id, ba_datetime, ba_name, ba_text, ba_comment, ba_blogid, ba_state FROM ba_comment ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;
        }
        elseif ($show_date == true) {
          $sql = "SELECT ba_id, ba_datetime, ba_name, ba_text, ba_comment, ba_blogid, ba_state FROM ba_comment WHERE ba_datetime >= '".$date."' AND ba_datetime < '".$date."' + INTERVAL 1 DAY ORDER BY ba_id DESC LIMIT 0,".$anzahl_e;	// funktioniert nur mit limit
        }
        $ret = $this->database->query($sql);	// liefert in return db-objekt
        if ($ret) {
          // wenn kein fehler 3i

          $html_backend_ext .= "<table class=\"backend\">\n".
                               "<tr>\n<th>".
                               $this->language["TABLE_HD_DATE"]." - ".$this->language["PROMPT_NAME"]." ".$this->language["TABLE_HD_TEXT"]." (".$this->language["PROMPT_TOTAL"].$anzahl_e.")".
                               "</th>\n<th>".
                               $this->language["TABLE_HD_COMMENT"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_BLOGID"].
                               "</th>\n<th>".
                               $this->language["TABLE_HD_STATE"].
                               "</th>\n</tr>\n";

          // ausgabeschleife
          while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

            $datetime = Blog::check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["ba_datetime"]));	// "YYYY-MM-DD HH:MM:SS"
            $comment_date = date_format($datetime, $this->language["FORMAT_DATE"]." / ".$this->language["FORMAT_TIME"]);	// "DD.MM.YY / HH:MM"
            $ba_name = stripslashes($this->html5specialchars($dataset["ba_name"]));
            $ba_text = stripslashes($this->html5specialchars(mb_substr($dataset["ba_text"], 0, 80, MB_ENCODING)));	// substr problem bei trennung umlaute
            $ba_blogid = intval($dataset["ba_blogid"]);
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
                                 "<a href=\"backend.php?".$this->html_build_query(array("action" => "comment", "id" => $dataset["ba_id"]))."\">".$comment_date." - ".$ba_name.": ".$ba_text."...</a>".
                                 "</td>\n<td>";
            if (mb_strlen($dataset["ba_comment"], MB_ENCODING) > 0) {
              $html_backend_ext .= "x";	// nur wenn vorhanden
            }
            $html_backend_ext .= "</td>\n<td>".$ba_blogid.
                                 "</td>\n<td>".$ba_state_short.
                                 "</td>\n</tr>\n";
          }

          // seitenauswahl mit links und vor/zurück
          $html_backend_ext .= "<tr>\n<td>\n";
          $query_data = array("action" => "comment", "page" => 0);

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
          $errorstring .= "<p>db error 3i</p>\n\n";
        }

      }
      else {
        $errorstring .= "<p>db error 3h</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function getComment($id, $page, $date) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // TABLE ba_comment (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                   ba_userid INT UNSIGNED NOT NULL,
      //                   ba_datetime DATETIME NOT NULL,
      //                   ba_ip VARCHAR(48) NOT NULL,
      //                   ba_name VARCHAR(64) NOT NULL,
      //                   ba_mail VARCHAR(64) NOT NULL,
      //                   ba_text VARCHAR(2048) NOT NULL,
      //                   ba_comment VARCHAR(2048) NOT NULL,
      //                   ba_blogid INT UNSIGNED NOT NULL,
      //                   ba_state TINYINT UNSIGNED NOT NULL);

      // preset
      $ba_id = 0xffff;	// error
      $ba_userid = $_SESSION["user_id"];	// wird immer neu gesetzt
      $ba_datetime = "0000-00-00 00:00:00";
      $ba_ip = "";
      $ba_name = "";
      $ba_mail = "";
      $ba_text = "";
      $ba_comment = "";
      $ba_blogid = 0;
      $ba_state = STATE_CREATED;

      // GET id auslesen
      if (isset($id) and is_numeric($id)) {
        // id als zahl vorhanden und nicht NULL

        $html_backend_ext .= "<p id=\"comment\"><b>".$this->language["HEADER_COMMENT"]."</b></p>\n\n";

        // zugriff auf mysql datenbank (1) , select mit prepare() , ($id aus GET)
        $sql = "SELECT ba_id, ba_datetime, ba_ip, ba_name, ba_mail, ba_text, ba_comment, ba_blogid, ba_state FROM ba_comment WHERE ba_id = ?";
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 3g

          // austauschen ? durch int (i)
          $stmt->bind_param("i", $id);
          $stmt->execute();	// ausführen geänderte zeile

          $stmt->bind_result($dataset["ba_id"],$dataset["ba_datetime"],$dataset["ba_ip"],$dataset["ba_name"],$dataset["ba_mail"],$dataset["ba_text"],$dataset["ba_comment"],$dataset["ba_blogid"],$dataset["ba_state"]);
          // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

          if ($stmt->fetch()) {
            // wenn kein fehler (id nicht vorhanden, datensatz leer)

            // preset überschreiben
            $ba_id = intval($dataset["ba_id"]);
            $ba_datetime = stripslashes($this->html5specialchars($dataset["ba_datetime"]));
            $ba_ip = stripslashes($this->html5specialchars($dataset["ba_ip"]));
            $ba_name = stripslashes($this->html5specialchars($dataset["ba_name"]));
            $ba_mail = stripslashes($this->html5specialchars($dataset["ba_mail"]));
            $ba_text = stripslashes($this->html5specialchars($dataset["ba_text"]));
            $ba_comment = stripslashes($this->html5specialchars($dataset["ba_comment"]));
            $ba_blogid = intval($dataset["ba_blogid"]);
            $ba_state = intval($dataset["ba_state"]);

          }
          else {
            $errorstring .= "<p>no id!</p>\n\n";
          }

          $stmt->close();	// stmt-ojekt schließen
          unset($stmt);	// referenz löschen

        }
        else {
          $errorstring .= "<p>db error 3g</p>\n\n";
        }

      } // id

      else {
        // keine id , neuer kommentar-eintrag

        $html_backend_ext .= "<p id=\"comment\"><b>".$this->language["HEADER_COMMENT_NEW"]."</b></p>\n\n";

        // preset teilweise überschreiben
        $ba_id = 0;
        $ba_datetime = date("Y-m-d H:i:s");	// return string "2015-01-24 15:57:00"
        $ba_ip = $_SERVER["REMOTE_ADDR"];
        $ba_blogid = 1;

      } // keine id

      // formular felder für kommentar eintrag , GET id oder neu , ba_id und ba_userid in hidden feld , ba_daten aus preset

      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_DATE"].
                           "</td>\n<td>\n".
                           "<input type=\"hidden\" name=\"ba_comment[ba_id]\" value=\"".$ba_id."\"/>\n".
                           "<input type=\"hidden\" name=\"ba_comment[ba_userid]\" value=\"".$ba_userid."\"/>\n".
                           "<input type=\"text\" name=\"ba_comment[ba_datetime]\" class=\"size_32\" maxlength=\"".MAXLEN_DATETIME."\" value=\"".$ba_datetime."\"/>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_IP"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_comment[ba_ip]\" class=\"size_32\" maxlength=\"".MAXLEN_COMMENTIP."\" value=\"".$ba_ip."\"/>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_NAME"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_comment[ba_name]\" class=\"size_32\" maxlength=\"".MAXLEN_COMMENTNAME."\" value=\"".$ba_name."\"/>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_MAIL"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_comment[ba_mail]\" class=\"size_32\" maxlength=\"".MAXLEN_COMMENTMAIL."\" value=\"".$ba_mail."\"/>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_TEXT"].
                           "</td>\n<td>".
                           "<textarea name=\"ba_comment[ba_text]\" class=\"cols_96_rows_11\">".$ba_text."</textarea>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_COMMENT"].
                           "</td>\n<td>".
                           "<textarea name=\"ba_comment[ba_comment]\" class=\"cols_96_rows_11\">".$ba_comment."</textarea>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_BLOGID"].
                           "</td>\n<td>".
                           "<input type=\"text\" name=\"ba_comment[ba_blogid]\" class=\"size_4\" maxlength=\"".MAXLEN_COMMENTBLOGID."\" value=\"".$ba_blogid."\"/>".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                           $this->language["PROMPT_STATE"].
                           "</td>\n<td>\n".
                           "<input type=\"radio\" name=\"ba_comment[ba_state]\" value=\"".STATE_CREATED."\"";
      if ($ba_state == STATE_CREATED) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>".$this->language["STATE_CREATED"]."\n<br>".
                           "<input type=\"radio\" name=\"ba_comment[ba_state]\" value=\"".STATE_EDITED."\"";
      if ($ba_state == STATE_EDITED) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>".$this->language["STATE_EDITED"]."\n<br>".
                           "<input type=\"radio\" name=\"ba_comment[ba_state]\" value=\"".STATE_APPROVAL."\"";
      if ($ba_state == STATE_APPROVAL) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>".$this->language["STATE_APPROVAL"]."\n<br>".
                           "<input type=\"radio\" name=\"ba_comment[ba_state]\" value=\"".STATE_PUBLISHED."\"";
      if ($ba_state == STATE_PUBLISHED) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>".$this->language["STATE_PUBLISHED"]."\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\"></td>\n<td>\n".
                           "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />\n".
                           " ".$this->language["PROMPT_DELETE"]."<input type=\"checkbox\" name=\"ba_comment[]\" value=\"delete\" />\n".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // liste mit älteren kommentar-einträgen
      $commentlist = $this->getCommentlist($page, $date);
      $html_backend_ext .= $commentlist["content"];
      $errorstring .= $commentlist["error"];

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postComment($ba_id, $ba_userid, $ba_datetime, $ba_ip, $ba_name, $ba_mail, $ba_text, $ba_comment, $ba_blogid, $ba_state, $ba_delete) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      if ($ba_id != 0xffff) {

        $count = 0;

        // einfügen in datenbank:
        if ($ba_id == 0) {
          $sql = "INSERT INTO ba_comment (ba_userid, ba_datetime, ba_ip, ba_name, ba_mail, ba_text, ba_comment, ba_blogid, ba_state) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        }

        // löschen in datenbank:
        elseif ($ba_delete) {
          $sql = "DELETE FROM ba_comment WHERE ba_id = ?";
        }

        // update in datenbank:
        else {
          $sql = "UPDATE ba_comment SET ba_userid = ?, ba_datetime = ?, ba_ip = ?, ba_name = ?, ba_mail = ?, ba_text = ?, ba_comment = ?, ba_blogid = ?, ba_state = ? WHERE ba_id = ?";
        }

        // mit prepare() - sql injections verhindern
        $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4f

          // austauschen ?????????, ? oder ?????????? durch string und int
          if ($ba_id == 0) {
            $stmt->bind_param("sssssssii", $ba_userid, $ba_datetime, $ba_ip, $ba_name, $ba_mail, $ba_text, $ba_comment, $ba_blogid, $ba_state);	// einfügen in datenbank
            $html_backend_ext .= "<p>".$this->language["MSG_COMMENT_NEW"]."</p>\n\n";
          }
          elseif ($ba_delete) {
            $stmt->bind_param("i", $ba_id);	// löschen in datenbank
            $html_backend_ext .= "<p>".$this->language["MSG_COMMENT_DELETE"]."</p>\n\n";
          }
          else {
            $stmt->bind_param("sssssssiii", $ba_userid, $ba_datetime, $ba_ip, $ba_name, $ba_mail, $ba_text, $ba_comment, $ba_blogid, $ba_state, $ba_id);	// update in datenbank
            $html_backend_ext .= "<p>".$this->language["MSG_COMMENT_UPDATE"]."</p>\n\n";
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4f</p>\n\n";
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

}

?>
