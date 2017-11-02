<?php

// *****************************************************************************
// * ba_model - comment
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXLEN_COMMENTDATE",20);
define("MAXLEN_COMMENTIP",48);
define("MAXLEN_COMMENTNAME",64);
define("MAXLEN_COMMENTMAIL",64);
define("MAXLEN_COMMENTBLOGID",8);
define("MAXLEN_COMMENTTEXT",2048);
define("MAXLEN_COMMENTCOMMENT",2048);
define("MB_ENCODING","UTF-8");

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

  public function getComment($id, $page) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // TABLE ba_comment (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                   ba_date DATETIME NOT NULL,
      //                   ba_ip VARCHAR(48) NOT NULL,
      //                   ba_name VARCHAR(64) NOT NULL,
      //                   ba_mail VARCHAR(64) NOT NULL,
      //                   ba_text VARCHAR(2048) NOT NULL,
      //                   ba_comment VARCHAR(2048) NOT NULL,
      //                   ba_blogid INT UNSIGNED NOT NULL);

      // preset
      $ba_id = 0xffff;	// error
      $ba_date = "0000-00-00 00:00:00";
      $ba_ip = "";
      $ba_name = "";
      $ba_mail = "";
      $ba_text = "";
      $ba_comment = "";
      $ba_blogid = 0;

      // GET id auslesen
      if (isset($id) AND is_numeric($id)) {
        // id als zahl vorhanden und nicht NULL

        $html_backend_ext .= "<p><b>comment</b></p>\n\n";

        // zugriff auf mysql datenbank (1) , select mit prepare() , ($id aus GET)
        $sql = "SELECT ba_id, ba_date, ba_ip, ba_name, ba_mail, ba_text, ba_comment, ba_blogid FROM ba_comment WHERE ba_id = ?";
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 3g

          // austauschen ? durch int (i)
          $stmt->bind_param("i", $id);
          $stmt->execute();	// ausführen geänderte zeile

          $stmt->bind_result($datensatz["ba_id"],$datensatz["ba_date"],$datensatz["ba_ip"],$datensatz["ba_name"],$datensatz["ba_mail"],$datensatz["ba_text"],$datensatz["ba_comment"],$datensatz["ba_blogid"]);
          // mysqli-statement-objekt kennt kein fetch_assoc(), nur fetch(), kein assoc-array als rückgabe

          if ($stmt->fetch()) {
            // wenn kein fehler (id nicht vorhanden, datensatz leer)

            // preset überschreiben
            $ba_id = intval($datensatz["ba_id"]);
            $ba_date = stripslashes($this->html5specialchars($datensatz["ba_date"]));
            $ba_ip = stripslashes($this->html5specialchars($datensatz["ba_ip"]));
            $ba_name = stripslashes($this->html5specialchars($datensatz["ba_name"]));
            $ba_mail = stripslashes($this->html5specialchars($datensatz["ba_mail"]));
            $ba_text = stripslashes($this->html5specialchars($datensatz["ba_text"]));
            $ba_comment = stripslashes($this->html5specialchars($datensatz["ba_comment"]));
            $ba_blogid = intval($datensatz["ba_blogid"]);

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

        $html_backend_ext .= "<p><b>kommentar (neu)</b></p>\n\n";

        // preset teilweise überschreiben
        $ba_id = 0;
        $ba_date = date("Y-m-d H:i:s");	// return string "2015-01-24 15:57:00"
        $ba_ip = $_SERVER["REMOTE_ADDR"];
        $ba_blogid = 1;

      } // keine id

      // formular felder für kommentar eintrag , GET id oder neu , ba_id in hidden feld , ba_daten aus preset

      $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                           "<table class=\"backend\">\n".
                           "<tr>\n<td class=\"td_backend\">\n".
                           "date:\n".
                           "</td>\n<td>\n".
                           "<input type=\"hidden\" name=\"ba_comment[ba_id]\" value=\"".$ba_id."\"/>\n".
                           "<input type=\"text\" name=\"ba_comment[ba_date]\" class=\"size_32\" maxlength=\"".MAXLEN_COMMENTDATE."\" value=\"".$ba_date."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "ip:\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_comment[ba_ip]\" class=\"size_32\" maxlength=\"".MAXLEN_COMMENTIP."\" value=\"".$ba_ip."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "name:\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_comment[ba_name]\" class=\"size_32\" maxlength=\"".MAXLEN_COMMENTNAME."\" value=\"".$ba_name."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "mail:\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_comment[ba_mail]\" class=\"size_32\" maxlength=\"".MAXLEN_COMMENTMAIL."\" value=\"".$ba_mail."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "text:\n".
                           "</td>\n<td>\n".
                           "<textarea name=\"ba_comment[ba_text]\" class=\"cols_96_rows_11\">".$ba_text."</textarea>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "comment:\n".
                           "</td>\n<td>\n".
                           "<textarea name=\"ba_comment[ba_comment]\" class=\"cols_96_rows_11\">".$ba_comment."</textarea>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                           "blogid:\n".
                           "</td>\n<td>\n".
                           "<input type=\"text\" name=\"ba_comment[ba_blogid]\" class=\"size_16\" maxlength=\"".MAXLEN_COMMENTBLOGID."\" value=\"".$ba_blogid."\"/>\n".
                           "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                           "<input type=\"submit\" value=\"POST\" />\n".
                           " delete:<input type=\"checkbox\" name=\"ba_comment[]\" value=\"delete\" />\n".
                           "</td>\n</tr>\n".
                           "</table>\n".
                           "</form>\n\n";

      // liste mit älteren kommentar-einträgen

      $anzahl_eps = 20;	// anzahl einträge pro seite

      // zugriff auf mysql datenbank (2)
      $sql = "SELECT ba_id FROM ba_comment";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3h

        $anzahl_e = $ret->num_rows;	// anzahl einträge in ba_comment
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
        $sql = "SELECT ba_id, ba_date, ba_name, ba_text, ba_comment, ba_blogid FROM ba_comment ORDER BY ba_id DESC LIMIT ".$lmt_start.",".$anzahl_eps;
        $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
        if ($ret) {
          // wenn kein fehler 3i

          $html_backend_ext .= "<table class=\"backend\">\n".
                               "<tr>\n<td>\n".
                               "date - name: text (total:".$anzahl_e.")\n".
                               "</td>\n<td>\n".
                               "comment\n".
                               "</td>\n<td>\n".
                               "blogid\n".
                               "</td>\n</tr>\n";

          // ausgabeschleife
          while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

            $date = date_create($datensatz["ba_date"]);
            $ba_date = date_format($date, "d.m.y / H:i");
            $ba_name = stripslashes($this->html5specialchars($datensatz["ba_name"]));
            $ba_text = stripslashes($this->html5specialchars(mb_substr($datensatz["ba_text"], 0, 80, MB_ENCODING)));	// substr problem bei trennung umlaute
            $ba_blogid = intval($datensatz["ba_blogid"]);

            $html_backend_ext .= "<tr>\n<td>\n".
                                 "<a href=\"backend.php?action=comment&id=".$datensatz["ba_id"]."\">".$ba_date." - ".$ba_name.": ".$ba_text."...</a>\n".
                                 "</td>\n<td>\n";
            if (mb_strlen($datensatz["ba_comment"], MB_ENCODING) > 0) {
              $html_backend_ext .= "x\n";	// nur wenn vorhanden
            }
            $html_backend_ext .= "</td>\n<td>\n".$ba_blogid."</td>\n</tr>\n";
          }

          // seitenauswahl mit links und vor/zurück
          $html_backend_ext .= "<tr>\n<td>\n";

          if ($page > 1) {
            $i = $page - 1;
            $html_backend_ext .= "<a href=\"backend.php?action=comment&page=".$i."\">prev</a> \n";	// zurück
          }

          for ($i=1; $i<=$anzahl_s; $i++) {								// seitenauswahl
            if ($i == $page) {
              $html_backend_ext .= $i." \n";
            }
            else {
              $html_backend_ext .= "<a href=\"backend.php?action=comment&page=".$i."\">".$i."</a> \n";
            }
          }

          if ($page < $anzahl_s) {
            $i = $page + 1;
            $html_backend_ext .= "<a href=\"backend.php?action=comment&page=".$i."\">next</a>\n";	// vor
          }

          $html_backend_ext .= "</td>\n<td>\n</td>\n<td>\n</td>\n</tr>\n".
                               "</table>\n";

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

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postComment($ba_id, $ba_date, $ba_ip, $ba_name, $ba_mail, $ba_text, $ba_comment, $ba_blogid, $ba_delete) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      if ($ba_id != 0xffff) {

        $count = 0;

        // einfügen in datenbank:
        if ($ba_id == 0) {
          $sql = "INSERT INTO ba_comment (ba_date, ba_ip, ba_name, ba_mail, ba_text, ba_comment, ba_blogid) VALUES (?, ?, ?, ?, ?, ?, ?)";
        }

        // löschen in datenbank:
        elseif ($ba_delete) {
          $sql = "DELETE FROM ba_comment WHERE ba_id = ?";
        }

        // update in datenbank:
        else {
          $sql = "UPDATE ba_comment SET ba_date = ?, ba_ip = ?, ba_name = ?, ba_mail = ?, ba_text = ?, ba_comment = ?, ba_blogid = ? WHERE ba_id = ?";
        }

        // mit prepare() - sql injections verhindern
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4f

          // austauschen ???????, ? oder ???????? durch string und int
          if ($ba_id == 0) {
            $stmt->bind_param("ssssssi", $ba_date, $ba_ip, $ba_name, $ba_mail, $ba_text, $ba_comment, $ba_blogid);	// einfügen in datenbank
            $html_backend_ext .= "<p>comment - new</p>\n";
          }
          elseif ($ba_delete) {
            $stmt->bind_param("i", $ba_id);	// löschen in datenbank
            $html_backend_ext .= "<p>comment - delete</p>\n";
          }
          else {
            $stmt->bind_param("ssssssii", $ba_date, $ba_ip, $ba_name, $ba_mail, $ba_text, $ba_comment, $ba_blogid, $ba_id);	// update in datenbank
            $html_backend_ext .= "<p>comment - update</p>\n";
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4f</p>\n\n";
        }

        $html_backend_ext .= "<p>".$count." rows changed</p>\n\n";

      }
      else {
        $errorstring .= "<p>no id!</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
