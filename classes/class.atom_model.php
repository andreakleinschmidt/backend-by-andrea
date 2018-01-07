<?php

// *****************************************************************************
// * atom model
// * - daten aus datenbank holen
// * - daten aufbereiten
// * - daten an controller zurückgeben
// *****************************************************************************

//define("STATE_PUBLISHED",3);
//define("MB_ENCODING","UTF-8");

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
      $feed_id =  stripslashes($this->xmlspecialchars(Blog::getOption_by_name("feed_id", true)));	// = "tag:oscilloworld.de,2010:morgana81"
      $feed_url = stripslashes($this->xmlspecialchars(Blog::getOption_by_name("feed_url", true)));	// = "http://www.oscilloworld.de/morgana81/index.php?action=blog"

      // zugriff auf mysql datenbank
      $sql = "SELECT ba_date, ba_header, ba_intro, ba_text FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." ORDER BY ba_id DESC LIMIT 0,".$num_entries;
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 2

        $first = true;

        $feed["updated"] = "";
        $feed["entry"] = array();

        // ausgabeschleife
        while ($dataset = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

          $datum = stripslashes($this->xmlspecialchars($dataset["ba_date"]));
          $blogheader = stripslashes($this->xmlspecialchars($dataset["ba_header"]));
          $blogintro = stripslashes(Blog::html_tags($this->xmlspecialchars(nl2br($dataset["ba_intro"])), false));	// <br /> hier als xmlspecialchars()
          $blogtext = stripslashes(Blog::html_tags($this->xmlspecialchars(nl2br($dataset["ba_text"])), false));	// <br /> hier als xmlspecialchars()

          if (empty($blogheader)) {
            $blogtext80 = stripslashes(Blog::html_tags($this->xmlspecialchars(mb_substr($dataset["ba_text"], 0, 80, MB_ENCODING)), false));	// substr problem bei trennung umlaute
          }
          else {
            $blogtext80 = stripslashes(Blog::html_tags($this->xmlspecialchars(mb_substr($dataset["ba_header"], 0, 80, MB_ENCODING)), false));	// substr problem bei trennung umlaute
          }

          if (empty($blogintro)) {
            $split_array = array_slice(preg_split("/(?<=\!\s|\.\s|\:\s|\?\s)/", $dataset["ba_text"], $num_sentences+1, PREG_SPLIT_NO_EMPTY), 0, $num_sentences);
          }
          else {
            $split_array = array_slice(preg_split("/(?<=\!\s|\.\s|\:\s|\?\s)/", $dataset["ba_intro"], $num_sentences+1, PREG_SPLIT_NO_EMPTY), 0, $num_sentences);
          }
          $blogtextshort = stripslashes(Blog::html_tags($this->xmlspecialchars(nl2br(implode($split_array))), false));	// satzendzeichen als trennzeichen, anzahl sätze optional

          $blogtextcontent_arr = array();
          if (!empty($blogintro)) {
            $blogtextcontent_arr[] = $this->xmlspecialchars("<p>").$blogintro.$this->xmlspecialchars("</p>");
          }
          if (!empty($blogtext)) {
            $blogtextcontent_arr[] = $this->xmlspecialchars("<p>").$blogtext.$this->xmlspecialchars("</p>");
          }
          $blogtextcontent = implode("\n", $blogtextcontent_arr);

          $jahr   = substr($datum, 6, 2);
          $monat  = substr($datum, 3, 2);
          $tag    = substr($datum, 0, 2);
          $stunde = substr($datum, 11, 2);
          $minute = substr($datum, 14, 2);
          $jmtsm = "20".$jahr.$monat.$tag.$stunde.$minute."00";

          $atomtitle = $datum." - ".$blogtext80."...";
          $atomlink = $feed_url."#".$jmtsm;
          $atomid = $feed_id."-".$jmtsm;
          $swzeit = date(I) + 1;	// 1 bei sommerzeit, sonst 0
          $atomupdated = "20".$jahr."-".$monat."-".$tag."T".$stunde.":".$minute.":00+0".$swzeit.":00";
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
