<?php

// *****************************************************************************
// * atom model
// * - daten aus datenbank holen
// * - daten aufbereiten
// * - daten an controller zurückgeben
// *****************************************************************************

//define("STATE_PUBLISHED",3);

class Model {

  //private $database;

  // konstruktor
  public function __construct() {
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
      $num_entries = Blog::check_zero(Blog::getOption_by_name("feed_num_entries"));	// = 20
      $use_summary = boolval(Blog::getOption_by_name("feed_use_summary"));	// = 0 (use content)
      $num_sentences = Blog::check_zero(Blog::getOption_by_name("feed_num_sentences_summary"));	// = 3
      $feed["id"] = stripslashes($this->xmlspecialchars(Blog::getOption_by_name("feed_id", true)));	// als string
      $feed["url"] = stripslashes($this->xmlspecialchars(Blog::getOption_by_name("feed_url", true)));	// als string
      $feed["title"] = stripslashes($this->xmlspecialchars(Blog::getOption_by_name("feed_title", true)));	// als string
      $feed["subtitle"] = stripslashes($this->xmlspecialchars(Blog::getOption_by_name("feed_subtitle", true)));	// als string
      $feed["author"] = stripslashes($this->xmlspecialchars(Blog::getOption_by_name("feed_author", true)));	// als string

      // zugriff auf mysql datenbank
      $sql = "SELECT ba_datetime, ba_header, ba_intro, ba_text FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." ORDER BY ba_id DESC LIMIT 0,".$num_entries;
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 2

        $first = true;

        $feed["updated"] = "";
        $feed["entry"] = array();

        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

          $datetime = Blog::check_datetime(date_create_from_format("Y-m-d H:i:s", $dataset["ba_datetime"]));	// "YYYY-MM-DD HH:MM:SS"
          $blogheader = stripslashes($this->xmlspecialchars($dataset["ba_header"]));
          $blogintro = stripslashes(Blog::html_tags($this->xmlspecialchars(nl2br($dataset["ba_intro"])), false));	// <br /> hier als xmlspecialchars()
          $blogtext = stripslashes(Blog::html_tags($this->xmlspecialchars(nl2br($dataset["ba_text"])), false));	// <br /> hier als xmlspecialchars()

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
          $datetime_anchor = date_format($datetime, "YmdHis");
          $atomlink = $feed["url"]."#".$datetime_anchor;
          $atomid = $feed["id"]."-".$datetime_anchor;
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
