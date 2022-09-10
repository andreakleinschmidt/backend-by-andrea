<?php

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2022 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
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
// * sitemap model
// * - daten aus datenbank holen
// * - daten aufbereiten
// * - daten an controller zurückgeben
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("STATE_PUBLISHED",3);

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
// db error 2 - ret bei query

class Model {

  //private $database;

  // konstruktor
  public function __construct() {
    // datenbank:
    $this->database = @new Database();	// @ unterdrückt fehlermeldung
    if (!$this->database->connect_errno) {
      // wenn kein fehler
      $this->database->set_charset("utf8");	// change character set to utf8
    }
  }

  // wrapper htmlspecialchars()
  public function xmlspecialchars($str) {
    return htmlspecialchars($str, ENT_COMPAT | ENT_XML1, "UTF-8");	// als utf-8 (für xml)
  }

// *****************************************************************************
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

  public function getUrlset() {
    $urlset = array();
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler 1

      // zugriff auf mysql datenbank
      $sql = "SELECT t.ba_id AS ba_id, t.ba_datetime AS ba_datetime, t.ba_alias AS ba_alias, t.history_datetime AS history_datetime, TIMESTAMPDIFF(MINUTE, t.history_datetime, NOW()) AS zeitdifferenz FROM (SELECT ba_blog.ba_id, ba_blog.ba_datetime, ba_blog.ba_alias, ba_blog_history.history_datetime FROM ba_blog INNER JOIN ba_blog_history ON ba_blog.ba_id = ba_blog_history.ba_blogid WHERE ba_blog.ba_state >= ".STATE_PUBLISHED." ORDER BY ba_blog.ba_id DESC, ba_blog_history.history_id DESC) AS t GROUP BY t.ba_id ORDER BY t.ba_id DESC";

      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 2

        $urlset["url"] = array();

        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

          $datetime = Blog::check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["ba_datetime"]));	// "YYYY-MM-DD HH:MM:SS"
          $blogalias = trim(rawurlencode($dataset["ba_alias"]));
          $permalink = date_format($datetime, "Y/m/").$blogalias;
          $loc = "blog/".$permalink."/";
          $history_datetime = date_create_from_format("Y-m-d H:i:s", $dataset["history_datetime"]);	// "YYYY-MM-DD HH:MM:SS"
          $lastmod = date_format($history_datetime, "Y-m-d");	// "YYYY-MM-DD"
          $passed_minutes = $dataset["zeitdifferenz"];	// vergangene minuten seit letzter änderung und jetzt

          if ($passed_minutes <= 2*60) {
            // kleiner gleich 2 stunden
            $changefreq = CHANGEFREQ_ALWAYS;
          }
          elseif ($passed_minutes <= 2*(60*24)) {
            // größer 2 stunden (und kleiner gleich 2 tage)
            $changefreq = CHANGEFREQ_HOURLY;
          }
          elseif ($passed_minutes <= 2*(60*24*7)) {
            // größer 2 tage (und kleiner gleich 2 wochen)
            $changefreq = CHANGEFREQ_DAILY;
          }
          elseif ($passed_minutes <= 2*(60*24*30)) {
            // größer 2 wochen (und kleiner gleich 2 monate)
            $changefreq = CHANGEFREQ_WEEKLY;
          }
          elseif ($passed_minutes <= 2*(60*24*365)) {
            // größer 2 monate (und kleiner gleich 2 jahre)
            $changefreq = CHANGEFREQ_MONTHLY;
          }
          elseif ($passed_minutes <= 5*(60*24*365)) {
            // größer 2 jahre (und kleiner gleich 5 jahre)
            $changefreq = CHANGEFREQ_YEARLY;
          }
          else {
            // größer 5 jahre
            $changefreq = CHANGEFREQ_NEVER;
          }

          $urlset["url"][] = array("loc" => $loc, "lastmod" => $lastmod, "changefreq" => $changefreq);

        } // while

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "db error 2\n";
      }

    } // datenbank
    else {
      $errorstring .= "db error 1\n";
    }

    return array("urlset" => $urlset, "error" => $errorstring);
  }

}

?>
