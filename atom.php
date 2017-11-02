<?php
header("Content-Type: text/xml; charset=utf-8");

define("STATE_PUBLISHED",3);

// (require wie include, aber abbruch/fatal error  bei nicht-einbinden)
require_once("classes/class.database.php");

// wrapper htmlspecialchars()
function xmlspecialchars($str) {
  return htmlspecialchars($str, ENT_COMPAT | ENT_XML1, "UTF-8");	// als utf-8 (für xml)
}

// ersetze links im blogtext [|] mit html links <a>
function html_link($text_str, $option, $encoding="UTF-8") {
  for ($start=0; mb_strpos($text_str,"[",$start,$encoding); $start++) {
    $start= mb_strpos($text_str,"[",$start,$encoding);
    $stop = mb_strpos($text_str,"]",$start,$encoding);
    $link = explode("|", mb_substr($text_str, $start+1, $stop-$start-1, $encoding));
    if (count($link) == 2 AND $option == 1) {
      $link_str = "<a href=\"".$link[0]."\">".$link[1]."</a>";
    }
    elseif (count($link) == 1 AND $option == 1) {
      $link_str = "<a href=\"".$link[0]."\">".$link[0]."</a>";
    }
    elseif (count($link) == 2 AND $option == 0) {
      $link_str = $link[1];
    }
    elseif (count($link) == 1 AND $option == 0) {
      $link_str = $link[0];
    }
    else {
      $link_str = "";
    }
    $text_str = mb_substr($text_str, 0, $start, $encoding).$link_str.mb_substr($text_str, $stop+1, NULL, $encoding);
    // mb_substr_replace($text_str, $link_str, $start, $stop-$start+1);
  }
  return $text_str;
}

$datenbank = @new Database();	// @ unterdrückt fehlermeldung
if (!mysqli_connect_errno()) {
  // wenn kein fehler 1

  $datenbank->set_charset("utf8");	// change character set to utf8

  // zugriff auf mysql datenbank
  $sql = "SELECT ba_date, ba_text FROM ba_blog WHERE ba_state >= ".STATE_PUBLISHED." ORDER BY ba_id DESC LIMIT 0,20";
  $ret = $datenbank->query($sql);	// liefert in return db-objekt
  if ($ret) {
    // wenn kein fehler 2

    $atomid = "tag:oscilloworld.de,2010:morgana81";

    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n\n";

    echo "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n\n";

    echo "  <title type=\"text\">morgana81 atom feed</title>\n";
    echo "  <subtitle type=\"text\">gothic transgender morgana81 - profile, photos and blog</subtitle>";
    echo "  <link href=\"http://www.oscilloworld.de/morgana81\" rel=\"alternate\"/>\n";
    echo "  <link href=\"http://www.oscilloworld.de/morgana81/atom.php\" rel=\"self\"/>\n";
    echo "  <id>".$atomid."</id>\n\n";

    echo "  <author>\n";
    echo "    <name>Morgana LaGoth</name>\n";
    echo "  </author>\n\n";

    $first = true;

    // ausgabeschleife
    while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)

      $datum = stripslashes(xmlspecialchars($datensatz["ba_date"]));
      $utf8_text = $datensatz["ba_text"];	// aus db als utf-8 (für xml)
      $blogtext = stripslashes(html_link(xmlspecialchars($utf8_text),0));
      $blogtext80 = stripslashes(html_link(xmlspecialchars(mb_substr($utf8_text, 0, 80, "UTF-8")),0));	// substr problem bei trennung umlaute

      $jahr   = substr($datum, 6, 2);
      $monat  = substr($datum, 3, 2);
      $tag    = substr($datum, 0, 2);
      $stunde = substr($datum, 11, 2);
      $minute = substr($datum, 14, 2);
      $jmtsm = "20".$jahr.$monat.$tag.$stunde.$minute."00";

      $atomtitel = $datum." - ".$blogtext80."...";
      $atomid2 = $atomid."-".$jmtsm;
      $swzeit = date(I) + 1;	// 1 bei sommerzeit, sonst 0
      $atomupdated = "20".$jahr."-".$monat."-".$tag."T".$stunde.":".$minute.":00+0".$swzeit.":00";
      $atomsummary = $blogtext;
      $atomcontent = $blogtext;

      if ($first) {
        echo "  <updated>".$atomupdated."</updated>\n\n";
      }

      $first = false;

      echo "  <entry>\n\n";

      echo "    <title type=\"text\">".$atomtitel."</title>\n";
      echo "    <link href=\"http://www.oscilloworld.de/morgana81/index.php?action=blog#".$jmtsm."\"/>\n";
      echo "    <id>".$atomid2."</id>\n";
      echo "    <updated>".$atomupdated."</updated>\n";
      echo "    <summary type=\"text\">".$atomsummary."</summary>\n";
      echo "    <content type=\"text\">".$atomcontent."</content>\n\n";

      echo "  </entry>\n\n";

    }

    echo "</feed>\n";

    $ret->close();	// db-ojekt schließen
    unset($ret);	// referenz löschen

  }
  else {
    echo "error 2\n";
  }

}
else {
  echo "error 1\n";
}

?>
