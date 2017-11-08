<?php
header("Content-Type: text/xml; charset=utf-8");

define("STATE_PUBLISHED",3);

// (require wie include, aber abbruch/fatal error  bei nicht-einbinden)
require_once("classes/class.database.php");

// wrapper htmlspecialchars()
function xmlspecialchars($str) {
  return htmlspecialchars($str, ENT_COMPAT | ENT_XML1, "UTF-8");	// als utf-8 (für xml)
}

// ersetze tag kommandos im blogtext ~cmd{content} mit html tags <a>, <b>, <i>
function html_tags($text_str, $tag_flag, $encoding="UTF-8") {
  for ($start=0; mb_strpos($text_str, "~", $start, $encoding); $start++) {
    // suche tilde, abbruch der schleife wenn keine tilde mehr in text_str vorhanden (strpos return false)

    $start   = mb_strpos($text_str, "~", $start, $encoding);
    $brace   = mb_strpos($text_str, "{", $start, $encoding);
    $stop    = mb_strpos($text_str, "}", $start, $encoding);

    if ($brace AND $stop) {
      // nur ausführen wenn {} gefunden
      $cmd     = mb_substr($text_str, $start+1, $brace-$start-1, $encoding);
      $content = mb_substr($text_str, $brace+1, $stop-$brace-1 , $encoding);

      switch ($cmd) {

        case "link":

          if (mb_strlen($content, $encoding) > 0 AND $tag_flag) {
            $link = explode("|", $content);
            if (count($link) == 2) {
              $tag_str = "<a href=\"".$link[0]."\">".$link[1]."</a>";
            }
            else {
              $tag_str = "<a href=\"".$link[0]."\">".$link[0]."</a>";
            }
          }
          elseif (mb_strlen($content, $encoding) > 0 AND !$tag_flag) {
            $link = explode("|", $content);
            if (count($link) == 2) {
              $tag_str = $link[1];
            }
            else {
              $tag_str = $link[0];
            }
          }
          else {
            $tag_str = "";
          }
          break;

        case "bold":

          if (mb_strlen($content, $encoding) > 0 AND $tag_flag) {
            $tag_str = "<b>".$content."</b>";
          }
          elseif (mb_strlen($content, $encoding) > 0 AND !$tag_flag) {
            $tag_str = $content;
          }
          else {
            $tag_str = "";
          }
          break;

        case "italic":

          if (mb_strlen($content, $encoding) > 0 AND $tag_flag) {
            $tag_str = "<i>".$content."</i>";
          }
          elseif (mb_strlen($content, $encoding) > 0 AND !$tag_flag) {
            $tag_str = $content;
          }
          else {
            $tag_str = "";
          }
          break;

        default:
          $tag_str = $cmd.$content;

      } // switch

      $text_str = mb_substr($text_str, 0, $start, $encoding).$tag_str.mb_substr($text_str, $stop+1, NULL, $encoding);
      // mb_substr_replace($text_str, $tag_str, $start, $stop-$start+1);

    } // if

  } // for

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
      $blogtext = stripslashes(html_tags(xmlspecialchars($utf8_text), false));
      $blogtext80 = stripslashes(html_tags(xmlspecialchars(mb_substr($utf8_text, 0, 80, "UTF-8")), false));	// substr problem bei trennung umlaute

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
