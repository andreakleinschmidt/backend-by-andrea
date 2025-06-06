<?php

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2025 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
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
// * atom model
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

  public function getFeed() {
    $feed = array();
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler 1

      // options
      $model_blog = new Blog();
      $num_entries = Blog::check_zero($model_blog->getOption_by_name("feed_num_entries"));	// = 20
      $use_summary = boolval($model_blog->getOption_by_name("feed_use_summary"));	// = 0 (use content)
      $num_sentences = Blog::check_zero($model_blog->getOption_by_name("feed_num_sentences_summary"));	// = 3
      $feed["id"] = stripslashes($this->xmlspecialchars($model_blog->getOption_by_name("feed_id", true)));	// als string
      $feed["url"] = stripslashes($this->xmlspecialchars($model_blog->getOption_by_name("feed_url", true)));	// als string
      $feed["title"] = stripslashes($this->xmlspecialchars($model_blog->getOption_by_name("feed_title", true)));	// als string
      $feed["subtitle"] = stripslashes($this->xmlspecialchars($model_blog->getOption_by_name("feed_subtitle", true)));	// als string
      $feed["author"] = stripslashes($this->xmlspecialchars($model_blog->getOption_by_name("feed_author", true)));	// als string

      // zugriff auf mysql datenbank
      $sql = "SELECT ba_datetime, ba_alias, ba_header, ba_intro, ba_text FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." ORDER BY ba_id DESC LIMIT 0,".$num_entries;
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 2

        $first = true;

        $feed["updated"] = "";
        $feed["entry"] = array();

        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

          $datetime = Blog::check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["ba_datetime"]));	// "YYYY-MM-DD HH:MM:SS"
          $blogalias = trim(rawurlencode($dataset["ba_alias"]));
          $blogheader = stripslashes($this->xmlspecialchars($dataset["ba_header"]));
          $blogintro = stripslashes(Blog::html_tags($this->xmlspecialchars(nl2br($dataset["ba_intro"])), false));	// <br /> hier als xmlspecialchars()
          $blogtext = stripslashes(Blog::html_tags($this->xmlspecialchars(Blog::nl2br_extended($dataset["ba_text"])), false));	// <br /> hier als xmlspecialchars()

          if (empty($blogheader)) {
            $to_split = $dataset["ba_text"];
          }
          else {
            $to_split = $dataset["ba_header"];
          }
          $split_str = preg_split("/(?<=\!\s|\.\s|\:\s|\?\s)/", $to_split, 2, PREG_SPLIT_NO_EMPTY)[0];
          $blogtitle = stripslashes(Blog::html_tags($this->xmlspecialchars($split_str), false));	// satzendzeichen als trennzeichen, nur erster satz

          if (empty($blogintro)) {
            $to_split = $dataset["ba_text"];
          }
          else {
            $to_split = $dataset["ba_intro"];
          }
          $split_array = array_slice(preg_split("/(?<=\!\s|\.\s|\:\s|\?\s)/", $to_split, $num_sentences+1, PREG_SPLIT_NO_EMPTY), 0, $num_sentences);
          $blogtextshort = stripslashes(Blog::html_tags($this->xmlspecialchars(nl2br(implode($split_array))), false));	// satzendzeichen als trennzeichen, anzahl sätze optional

          $blogtextcontent_arr = array();
          if (!empty($blogintro)) {
            $blogtextcontent_arr[] = $this->xmlspecialchars("<p>").$blogintro.$this->xmlspecialchars("</p>");
          }
          if (!empty($blogtext)) {
            $blogtextcontent_arr[] = $this->xmlspecialchars("<p>").$blogtext.$this->xmlspecialchars("</p>");
          }
          $blogtextcontent = implode("\n", $blogtextcontent_arr);

          $atomtitle = $blogtitle;
          $permalink = date_format($datetime, "Y/m/").$blogalias;
          $atomlink = $feed["url"].$permalink."/";
          $atomid = $feed["id"].":".date_format($datetime, "YmdHis");
          $atomupdated = date_format($datetime, "Y-m-d\TH:i:sP");	// YYYY-MM-DD'T'HH:MM:SS+0[1,2]:00
          if ($use_summary) {
            // use summary
            $atomsummary = $blogtextshort;
            $atomcontent = "";
          }
          else {
            // use content
            $atomsummary = "";
            $atomcontent = $blogtextcontent;
          }

          if ($first) {
            $feed["updated"] = $atomupdated;
          }

          $first = false;

          $feed["entry"][] = array("title" => $atomtitle, "link" => $atomlink, "id" => $atomid, "updated" => $atomupdated, "summary" => $atomsummary, "content" => $atomcontent);

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

    return array("feed" => $feed, "error" => $errorstring);
  }

}

?>
