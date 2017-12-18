<?php

// *****************************************************************************
// * ba_model - blog
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXLEN_BLOGDATE",32);
define("MAXLEN_BLOGTEXT",8192);
define("MAXLEN_BLOGVIDEOID",32);
define("MAXLEN_BLOGFOTOID",128);
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
// db error 3m - ret bei backend GET rubriken
// db error 3o - ret bei backend GET options
//
// db error 4e - stmt bei backend POST blog
// db error 4k - stmt bei backend POST blogroll neu
// db error 4l - stmt bei backend POST blogroll
// db error 4m - stmt bei backend POST rubrik neu
// db error 4n - stmt bei backend POST rubrik
// db error 4o - stmt bei backend POST options
//
// no id! - bei backend GET blog (kein datensatz)
// no id! - bei backend POST blog (id=0xffff)

class Blog extends Model {

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

  private function getHistory($id) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
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
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 3d2

          // austauschen ? durch int (i)
          $stmt->bind_param("i", $id);
          $stmt->execute();	// ausführen geänderte zeile

          $stmt->store_result();

          $stmt->bind_result($datensatz["history_id"],$datensatz["history_datetime"],$datensatz["history_info"],$datensatz["user"]);
          // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

          if ($stmt->num_rows > 0) {
            // erste zeile
            $html_backend_ext .= "<table class=\"backend\">\n".
                                 "<tr>\n<td class=\"td_backend\">\n".
                                 "<i>history:</i>\n".
                                 "</td>\n<td>\n".
                                 "<i>last</i>\n";
          }

          // ausgabeschleife
          while ($stmt->fetch()) {
            // solange nicht NULL (letzter datensatz, oder datensatz leer)

            $history_datetime = stripslashes($this->html5specialchars($datensatz["history_datetime"]));
            $history_info = stripslashes($this->html5specialchars($datensatz["history_info"]));
            $user = stripslashes($this->html5specialchars($datensatz["user"]));

            $html_backend_ext .= "<br><i>".$history_info." at ".$history_datetime." by ".$user."</i>\n";

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

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  private function getBloglist($page) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // TABLE ba_blog (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_userid INT UNSIGNED NOT NULL,
      //                ba_datetime DATETIME NOT NULL,
      //                ba_date VARCHAR(32) NOT NULL,
      //                ba_text VARCHAR(8192) NOT NULL,
      //                ba_videoid VARCHAR(32) NOT NULL,
      //                ba_fotoid VARCHAR(128) NOT NULL,
      //                ba_catid INT UNSIGNED NOT NULL,
      //                ba_tags VARCHAR(128) NOT NULL,
      //                ba_state TINYINT UNSIGNED NOT NULL);

      // options
      $anzahl_eps = intval($this->getOption_by_name("blog_entries_per_page"));	// anzahl einträge pro seite = 20

      // liste mit älteren blog-einträgen
      $html_backend_ext .= "<p id=\"blogliste\"><b>blog (liste)</b></p>\n\n";

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_id FROM ba_blog";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
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
        $sql = "SELECT ba_id, ba_date, ba_text, ba_videoid, ba_fotoid, ba_catid, ba_tags, ba_state FROM ba_blog ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;
        $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
        if ($ret) {
          // wenn kein fehler 3f

          $html_backend_ext .= "<table class=\"backend\">\n".
                               "<tr>\n<td>\n".
                               "date - text (total:".$anzahl_e.")\n".
                               "</td>\n<td>\n".
                               "videoid\n".
                               "</td>\n<td>\n".
                               "fotoid\n".
                               "</td>\n<td>\n".
                               "catid\n".
                               "</td>\n<td>\n".
                               "tags\n".
                               "</td>\n<td>\n".
                               "state\n".
                               "</td>\n</tr>\n";

          // ausgabeschleife
          while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

            $ba_date = stripslashes($this->html5specialchars($datensatz["ba_date"]));
            $ba_text = stripslashes($this->html5specialchars(mb_substr($datensatz["ba_text"], 0, 80, MB_ENCODING)));	// 80 wie blogbox in class.model.php, substr problem bei trennung umlaute
            $ba_videoid = stripslashes($this->html5specialchars($datensatz["ba_videoid"]));
            $ba_fotoid = stripslashes($this->html5specialchars($datensatz["ba_fotoid"]));
            $ba_catid = intval($datensatz["ba_catid"]);
            $ba_tags_flag = "";
            if (!empty($datensatz["ba_tags"])) {
              $ba_tags_flag = "x";
            }

            $ba_state = intval($datensatz["ba_state"]);

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

            $html_backend_ext .= "<tr>\n<td>\n".
                                 "<a href=\"backend.php?".$this->html_build_query(array("action" => "blog", "id" => $datensatz["ba_id"]))."\">".$ba_date." - ".$ba_text."...</a>\n".
                                 "</td>\n<td>\n";
            if (strlen($ba_videoid) > 0) {
              $html_backend_ext .= "(".$ba_videoid.")\n";	// nur wenn verwendet
            }
            $html_backend_ext .= "</td>\n<td>\n";
            if (strlen($ba_fotoid) > 0) {
              $html_backend_ext .= "(".$ba_fotoid.")\n";	// nur wenn verwendet
            }
            $html_backend_ext .= "</td>\n<td>\n";
            if ($ba_catid > 0) {
              $html_backend_ext .= "(".$ba_catid.")\n";	// nur wenn verwendet
            }
            $html_backend_ext .= "</td>\n<td>\n".$ba_tags_flag."\n".
                                 "</td>\n<td>\n".$ba_state_short."\n".
                                 "</td>\n</tr>\n";
          }

          // seitenauswahl mit links und vor/zurück
          $html_backend_ext .= "<tr>\n<td>\n";
          $query_data = array("action" => "blog", "page" => 0);

          if ($page > 1) {
            $i = $page - 1;
            $query_data["page"] = $i;
            $html_backend_ext .= "<a href=\"backend.php?".$this->html_build_query($query_data)."\">prev</a> \n";	// zurück
          }

          for ($i=1; $i<=$anzahl_s; $i++) {										// seitenauswahl
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
            $html_backend_ext .= "<a href=\"backend.php?".$this->html_build_query($query_data)."\">next</a>\n";		// vor
          }

          $html_backend_ext .= "</td>\n<td>\n</td>\n<td>\n</td>\n<td>\n</td>\n</tr>\n".
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

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  private function getBlogroll() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // TABLE ba_blogroll (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                    ba_feed VARCHAR(128) NOT NULL);

      $html_backend_ext .= "<p id=\"blogroll\"><b>blogroll</b></p>\n\n";

      // zugriff auf mysql datenbank (4)
      $sql = "SELECT ba_id, ba_feed FROM ba_blogroll";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3l
        if ($ret->num_rows > 0) {
          // feeds anzeigen, auswählen&löschen
          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n".
                               "<tr>\n<td class=\"td_backend\">\n".
                               "feed:\n".
                               "</td>\n<td>\n".
                              "<select multiple name=\"ba_blogroll[]\" size=\"5\">\n";
          while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $html_backend_ext .= "<option value=\"".$datensatz["ba_id"]."\">".$datensatz["ba_feed"]."</option>\n";
          }
          $html_backend_ext .= "</select>\n".
                               "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                               "<input type=\"submit\" value=\"del\" />\n".
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
                           "<tr>\n<td class=\"td_backend\">\n".
                           "neuer feed:\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_blogroll_new[feed]\" class=\"size_32\" maxlength=\"".MAXLEN_FEED."\" value=\"http://\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                           "<input type=\"submit\" value=\"new\" />\n".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  private function getBlogcategory() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // TABLE ba_blogcategory (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                        ba_category VARCHAR(32) NOT NULL);

      $html_backend_ext .= "<p id=\"rubriken\"><b>rubriken</b></p>\n\n";

      // zugriff auf mysql datenbank (5)
      $sql = "SELECT ba_id, ba_category FROM ba_blogcategory";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3m
        if ($ret->num_rows > 0) {
          // rubriken anzeigen, auswählen&löschen
          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n".
                               "<tr>\n<td class=\"td_backend\">\n".
                               "rubrik:\n".
                               "</td>\n<td>\n".
                              "<select multiple name=\"ba_blogcategory[]\" size=\"5\">\n";
          while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $html_backend_ext .= "<option value=\"".$datensatz["ba_id"]."\">".$datensatz["ba_category"]."</option>\n";
          }
          $html_backend_ext .= "</select>\n".
                               "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                               "<input type=\"submit\" value=\"del\" />\n".
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
                           "<tr>\n<td class=\"td_backend\">\n".
                           "neue rubrik:\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_blogcategory_new[category]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGCATEGORY."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                           "<input type=\"submit\" value=\"new\" />\n".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  private function getOptions() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // TABLE ba_options (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                   ba_name VARCHAR(32) NOT NULL,
      //                   ba_value TINYINT UNSIGNED NOT NULL);

      $html_backend_ext .= "<p id=\"options\"><b>options</b></p>\n\n";

      // zugriff auf mysql datenbank (6)
      $sql = "SELECT ba_name, ba_value FROM ba_options";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3o
        if ($ret->num_rows > 0) {
          // options anzeigen, einstellen
          $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                               "<table class=\"backend\">\n";

          // ausgabeschleife
          while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
            $ba_name = stripslashes($this->html5specialchars($datensatz["ba_name"]));
            $ba_value = intval($datensatz["ba_value"]);

            $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n".
                                 $ba_name.":\n".
                                 "</td>\n<td>\n".
                                 "<input type=\"number\" name=\"ba_options[".$ba_name."]\" class=\"size_4\" min=\"1\" max=\"255\" value=\"".$ba_value."\"/>\n".
                                 "</td>\n</tr>\n";
          }
          $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                               "<input type=\"submit\" value=\"set\" />\n".
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

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  private function getCategories() {
    $categories = array("none" => 0);	// init

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $sql = "SELECT ba_id, ba_category FROM ba_blogcategory";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // ausgabeschleife
        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $ba_id = intval($datensatz["ba_id"]);
          $ba_category = stripslashes($this->html5specialchars($datensatz["ba_category"]));
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

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // TABLE ba_blog (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_userid INT UNSIGNED NOT NULL,
      //                ba_datetime DATETIME NOT NULL,
      //                ba_date VARCHAR(32) NOT NULL,
      //                ba_text VARCHAR(8192) NOT NULL,
      //                ba_videoid VARCHAR(32) NOT NULL,
      //                ba_fotoid VARCHAR(128) NOT NULL,
      //                ba_catid INT UNSIGNED NOT NULL,
      //                ba_tags VARCHAR(128) NOT NULL,
      //                ba_state TINYINT UNSIGNED NOT NULL);

      // preset
      $ba_id = 0xffff;	// error
      $ba_userid = $_SESSION["user_id"];	// wird immer neu gesetzt
      $ba_datetime = "0000-00-00 00:00:00";
      $ba_date = "";
      $ba_text = "";
      $ba_videoid = "";
      $ba_fotoid = "";
      $ba_catid = 0;
      $ba_tags = "";
      $ba_state = STATE_CREATED;

      // GET id auslesen
      if (isset($id) and is_numeric($id)) {
        // id als zahl vorhanden und nicht NULL

        $html_backend_ext .= "<p id=\"blog\"><b>blog</b></p>\n\n";

        // zugriff auf mysql datenbank (1) , select mit prepare() , ($id aus GET)
        $sql = "SELECT ba_id, ba_datetime, ba_date, ba_text, ba_videoid, ba_fotoid, ba_catid, ba_tags, ba_state FROM ba_blog WHERE ba_id = ?";
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 3d

          // austauschen ? durch int (i)
          $stmt->bind_param("i", $id);
          $stmt->execute();	// ausführen geänderte zeile

          $stmt->bind_result($datensatz["ba_id"],$datensatz["ba_datetime"],$datensatz["ba_date"],$datensatz["ba_text"],$datensatz["ba_videoid"],$datensatz["ba_fotoid"],$datensatz["ba_catid"],$datensatz["ba_tags"],$datensatz["ba_state"]);
          // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

          if ($stmt->fetch()) {
            // wenn kein fehler (id nicht vorhanden, datensatz leer)

            // preset überschreiben
            $ba_id = $datensatz["ba_id"];
            $ba_datetime = stripslashes($this->html5specialchars($datensatz["ba_datetime"]));
            $ba_date = stripslashes($this->html5specialchars($datensatz["ba_date"]));
            $ba_text = stripslashes($this->html5specialchars($datensatz["ba_text"]));
            $ba_videoid = stripslashes($this->html5specialchars($datensatz["ba_videoid"]));
            $ba_fotoid = stripslashes($this->html5specialchars($datensatz["ba_fotoid"]));
            $ba_catid = intval($datensatz["ba_catid"]);
            $ba_tags = stripslashes($this->html5specialchars($datensatz["ba_tags"]));
            $ba_state = intval($datensatz["ba_state"]);

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

        $html_backend_ext .= "<p id=\"blog\"><b>blog (neu)</b></p>\n\n";

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
                           "<tr>\n<td class=\"td_backend\">\n".
                           "date:\n".
                           "</td>\n<td>\n".
                           "<input type=\"hidden\" name=\"ba_blog[ba_id]\" value=\"".$ba_id."\"/>\n".
                           "<input type=\"hidden\" name=\"ba_blog[ba_userid]\" value=\"".$ba_userid."\"/>\n".
                           "<input type=\"text\" name=\"ba_blog[ba_date]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGDATE."\" value=\"".$ba_date."\"/>\n".
                           "(".$ba_datetime.")\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "text:\n".
                           "</td>\n<td>\n".
                           "<textarea name=\"ba_blog[ba_text]\" class=\"cols_96_rows_22\">".$ba_text."</textarea>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "videoid:\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_blog[ba_videoid]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGVIDEOID."\" value=\"".$ba_videoid."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "fotoid:\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_blog[ba_fotoid]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGFOTOID."\" value=\"".$ba_fotoid."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "rubrik:\n".
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
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "tags:\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_blog[ba_tags]\" class=\"size_32\" maxlength=\"".MAXLEN_BLOGTAGS."\" value=\"".$ba_tags."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "state:\n".
                           "</td>\n<td>\n".
                           "<input type=\"radio\" name=\"ba_blog[ba_state]\" value=\"".STATE_CREATED."\"";
      if ($ba_state == STATE_CREATED) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>created\n".
                           "<br><input type=\"radio\" name=\"ba_blog[ba_state]\" value=\"".STATE_EDITED."\"";
      if ($ba_state == STATE_EDITED) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>edited\n".
                           "<br><input type=\"radio\" name=\"ba_blog[ba_state]\" value=\"".STATE_APPROVAL."\"";
      if ($ba_state == STATE_APPROVAL) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>approval\n".
                           "<br><input type=\"radio\" name=\"ba_blog[ba_state]\" value=\"".STATE_PUBLISHED."\"";
      if ($ba_state == STATE_PUBLISHED) {
        $html_backend_ext .= " checked=\"checked\"";
      }
      $html_backend_ext .= "/>published\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                           "<input type=\"submit\" value=\"POST\" />\n".
                           " delete:<input type=\"checkbox\" name=\"ba_blog[]\" value=\"delete\" />\n".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // blog entry history
      $history = $this->getHistory($id);
      $html_backend_ext .= $history["inhalt"];
      $errorstring .= $history["error"];

      // liste mit älteren blog-einträgen
      $bloglist = $this->getBloglist($page);
      $html_backend_ext .= $bloglist["inhalt"];
      $errorstring .= $bloglist["error"];

      // blogroll
      $blogroll = $this->getBlogroll();
      $html_backend_ext .= $blogroll["inhalt"];
      $errorstring .= $blogroll["error"];

      // blogcategory
      $blogcategory = $this->getBlogcategory();
      $html_backend_ext .= $blogcategory["inhalt"];
      $errorstring .= $blogcategory["error"];

      // options
      $options = $this->getOptions();
      $html_backend_ext .= $options["inhalt"];
      $errorstring .= $options["error"];

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlog($ba_id, $ba_userid, $ba_date, $ba_text, $ba_videoid, $ba_fotoid, $ba_catid, $ba_tags, $ba_state, $ba_delete) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
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
          $sql = "INSERT INTO ba_blog (ba_userid, ba_datetime, ba_date, ba_text, ba_videoid, ba_fotoid, ba_catid, ba_tags, ba_state) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }

        // löschen in datenbank:
        elseif ($ba_delete) {
          $sql = "DELETE FROM ba_blog WHERE ba_id = ?";
        }

        // update in datenbank:
        else {
          $sql = "UPDATE ba_blog SET ba_userid = ?, ba_datetime = ?, ba_date = ?, ba_text = ?, ba_videoid = ?, ba_fotoid = ?, ba_catid = ?, ba_tags = ?, ba_state = ? WHERE ba_id = ?";
        }

        // mit prepare() - sql injections verhindern
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4e

          // austauschen ?????????, ? oder ?????????? durch string und int
          if ($ba_id == 0) {
            $stmt->bind_param("isssssisi", $ba_userid, $ba_datetime, $ba_date, $ba_text, $ba_videoid, $ba_fotoid, $ba_catid, $ba_tags, $ba_state);	// einfügen in datenbank
            $html_backend_ext .= "<p>blog - new</p>\n\n";
          }
          elseif ($ba_delete) {
            $stmt->bind_param("i", $ba_id);	// löschen in datenbank
            $html_backend_ext .= "<p>blog - delete</p>\n\n";
          }
          else {
            $stmt->bind_param("isssssisii", $ba_userid, $ba_datetime, $ba_date, $ba_text, $ba_videoid, $ba_fotoid, $ba_catid, $ba_tags, $ba_state, $ba_id);	// update in datenbank
            $html_backend_ext .= "<p>blog - update</p>\n\n";
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4e</p>\n\n";
        }

        $html_backend_ext .= "<p>".$count." rows changed</p>\n\n";

      }
      else {
        $errorstring .= "<p>no id!</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlogrollNew($feed) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_blogroll (ba_feed) VALUES (?)";
      $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4k

        // austauschen ? durch string
        $stmt->bind_param("s", $feed);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>done</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>blogroll error</p>\n\n";
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

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlogroll($ba_blogroll_array) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      foreach ($ba_blogroll_array as $id) {

        $sql = "DELETE FROM ba_blogroll WHERE ba_id = ?";
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
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

      $html_backend_ext .= "<p>".$count." rows deleted</p>\n\n";

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlogcategoryNew($category) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO ba_blogcategory (ba_category) VALUES (?)";
      $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4m

        // austauschen ? durch string
        $stmt->bind_param("s", $category);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>done</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>blogcategory error</p>\n\n";
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

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBlogcategory($ba_blogcategory_array) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = array("delete" => 0, "update" => 0);

      foreach ($ba_blogcategory_array as $id) {

        $sql_pt1 = "DELETE FROM ba_blogcategory WHERE ba_id = ?";
        $sql_pt2 = "UPDATE ba_blog SET ba_catid = 0 WHERE ba_catid = ?";
        $sql_array = array("delete" => $sql_pt1, "update" => $sql_pt2);

        foreach ($sql_array as $key => $sql) {

          $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
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

      $html_backend_ext .= "<p>".$count["delete"]." rows deleted</p>\n\n";
      $html_backend_ext .= "<p>".$count["update"]." rows changed</p>\n\n";

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postOptions($ba_options_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      foreach ($ba_options_array_replaced as $ba_name => $ba_value) {

        $sql = "UPDATE ba_options SET ba_value = ? WHERE ba_name = ?";
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4o

          // austauschen ?? durch int und string
          $stmt->bind_param("is", $ba_value, $ba_name);
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4o</p>\n\n";
        }

      }

      $html_backend_ext .= "<p>".$count." rows changed</p>\n\n";

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
