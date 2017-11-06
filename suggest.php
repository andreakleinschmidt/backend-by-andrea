<?php
header("Content-Type: application/xhtml+xml; charset=utf-8");
session_start();

/* xmlHttp.responseText für GET blog formular suche
 * parameter: q (query), suggest (zufallszahl 1...1000)
 * ausgabe in <div id="suggestion"></div>
 */

define("FILENAME","suggest.txt");

// wrapper htmlspecialchars()
function xhtmlspecialchars($str) {
  return htmlspecialchars($str, ENT_COMPAT | ENT_XHTML, "UTF-8");	// als utf-8 (für xmlhttp)
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  // GET auslesen

  if (isset($_GET["q"]) and isset($_GET["suggest"])) {
    $query = trim($_GET["q"]);	// überflüssige leerzeichen entfernen, als utf-8 (für xmlhttp)
    $query = mb_strtolower(mb_substr($query, 0, 64, "UTF-8"), "UTF-8");	// zu langes GET abschneiden, alles kleinbuchstaben, substr problem bei trennung umlaute
    $suggest = intval($_GET["suggest"]);	// string zu int

    if (mb_strlen($query, "UTF-8") > 0 and $suggest > 0 and $suggest <= 1000) {
      // query nicht leer, suggest ok, strlen problem bei umlaute

      if (!isset($_SESSION["suggest_tab"])) {
        // falls session variable noch nicht existiert

        if (file_exists(FILENAME)) {
          // datei mit wortvorschlägen einlesen

          if ($handle = fopen (FILENAME, "r")) {
            // 64 byte char , while not eof
            while (($buffer = fgets($handle, 64)) !== false) {
              $suggest_tab[] = mb_convert_encoding(trim($buffer), "UTF-8");	// als utf-8 (für xmlhttp)
            }
            fclose($handle);
            $_SESSION["suggest_tab"] = $suggest_tab;	// in SESSION speichern
          } // handle
          else {
            echo "suggest handle error";
          }

        } // suggest.txt
        else {
          echo "suggest file error";
        }

      } // neue session variable
      else {
        // alte session variable
        $suggest_tab = $_SESSION["suggest_tab"];	// aus SESSION lesen
      }

      // tabelle mit wortvorschlägen
      if (isset($suggest_tab) and count($suggest_tab) > 0) {
        foreach($suggest_tab as $suggestion) {
          if ($query == mb_strtolower(mb_substr($suggestion, 0, mb_strlen($query, "UTF-8"), "UTF-8"), "UTF-8")) {
            echo "<a href=\"index.php?".http_build_query(array("action" => "blog", "q" => $suggestion), "", "&amp;", PHP_QUERY_RFC3986)."\">".stripslashes(xhtmlspecialchars($suggestion))."</a><br>\n";
            // vergleich der strings als utf-8, ausgabe als utf-8 (für xmlhttp), übergabe in link als utf-8, strlen problem bei umlaute, substr problem bei trennung umlaute
          }
        }
      } // suggest_tab
      else {
        "suggest table error";
      }

    } // query und suggest ok
    else {
      echo "query or suggest error";
    }

  } // GET q, suggest
  else {
    echo "query or suggest not set";
  }

} // GET
else {
  echo "GET error";
}

?>
