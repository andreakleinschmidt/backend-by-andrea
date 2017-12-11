<?php

// *****************************************************************************
// * atom model
// * - daten aus datenbank holen
// * - daten aufbereiten
// * - daten an controller zurückgeben
// *****************************************************************************

define("STATE_PUBLISHED",3);
define("MB_ENCODING","UTF-8");
define("ATOMID","tag:oscilloworld.de,2010:morgana81");
define("ATUMURL","http://www.oscilloworld.de/morgana81/index.php?action=blog");

class Model {

  //private $datenbank;

  // konstruktor
  public function __construct() {
    $this->datenbank = @new Database();	// @ unterdrückt fehlermeldung
    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler
      $this->datenbank->set_charset("utf8");	// change character set to utf8
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

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler 1

      // options
      $num_entries = intval(Blog::getOption_by_name("feed_num_entries"));	// = 20
      $summary_or_content = intval(Blog::getOption_by_name("feed_summary_or_content"));	// = 2
      $num_sentences = intval(Blog::getOption_by_name("feed_num_sentences_summary"));	// = 3

      // zugriff auf mysql datenbank
      $sql = "SELECT ba_date, ba_text FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." ORDER BY ba_id DESC LIMIT 0,".$num_entries;
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 2

        $first = true;

        $feed["updated"] = "";
        $feed["entry"] = array();

        // ausgabeschleife
        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

          $datum = stripslashes($this->xmlspecialchars($datensatz["ba_date"]));
          $utf8_text = $datensatz["ba_text"];	// aus db als utf-8 (für xml)
          $blogtext = stripslashes(Blog::html_tags($this->xmlspecialchars(nl2br($utf8_text)), false));	// <br /> hier als xmlspecialchars()
          $blogtext80 = stripslashes(Blog::html_tags($this->xmlspecialchars(mb_substr($utf8_text, 0, 80, MB_ENCODING)), false));	// substr problem bei trennung umlaute
          $split_array = array_slice(preg_split("/(?<=\!\s|\.\s|\:\s|\?\s)/", $utf8_text, $num_sentences+1, PREG_SPLIT_NO_EMPTY), 0, $num_sentences);
          $blogtextshort = stripslashes(Blog::html_tags($this->xmlspecialchars(nl2br(implode($split_array))), false));	// satzendzeichen als trennzeichen, anzahl sätze optional

          $jahr   = substr($datum, 6, 2);
          $monat  = substr($datum, 3, 2);
          $tag    = substr($datum, 0, 2);
          $stunde = substr($datum, 11, 2);
          $minute = substr($datum, 14, 2);
          $jmtsm = "20".$jahr.$monat.$tag.$stunde.$minute."00";

          $atomtitel = $datum." - ".$blogtext80."...";
          $atomlink = ATUMURL."#".$jmtsm;
          $atomid = ATOMID."-".$jmtsm;
          $swzeit = date(I) + 1;	// 1 bei sommerzeit, sonst 0
          $atomupdated = "20".$jahr."-".$monat."-".$tag."T".$stunde.":".$minute.":00+0".$swzeit.":00";
          if ($summary_or_content == 1) {
            // use summary
            $atomsummary = $blogtextshort;
            $atomcontent = "";
          }
          else {
            // use content
            $atomsummary = "";
            $atomcontent = $blogtext;
          }

          if ($first) {
            $feed["updated"] = $atomupdated;
          }

          $first = false;

          $feed["entry"][] = array("title" => $atomtitel, "link" => $atomlink, "id" => $atomid, "updated" => $atomupdated, "summary" => $atomsummary, "content" => $atomcontent);

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
