<?php

// *****************************************************************************
// * ba_model - base
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

//define("MAXLEN_BASETITLE",32);
//define("MAXLEN_BASELINKS",256);

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 3 - ret bei backend GET base
//
// db error 4 - stmt bei backend POST base

class Base extends Model {

  public function __construct() {
    parent::__construct();
      // $this->database
      // $this->language
  }

  public function getBase() {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      // TABLE ba_base (ba_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                ba_title VARCHAR(32) NOT NULL,
      //                ba_nav VARCHAR(32) NOT NULL,
      //                ba_nav_links VARCHAR(256) NOT NULL,
      //                ba_startpage VARCHAR(8) NOT NULL);

      $html_backend_ext .= "<p id=\"base\"><b>".$this->language["HEADER_BASE"]."</b></p>\n\n";

      // zugriff auf mysql datenbank (1)
      $sql = "SELECT ba_title, ba_nav, ba_nav_links, ba_startpage FROM ba_base LIMIT 1";
      $ret = $this->database->query($sql);	// liefert in return db-objekt
      if ($ret) {
        // wenn kein fehler 3

        $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                             "<table class=\"backend\">\n";

        $dataset = $ret->fetch_assoc();	// fetch_assoc() liefert array
        $ba_title = stripslashes($this->html5specialchars($dataset["ba_title"]));
        $ba_nav = trim($dataset["ba_nav"]);
        $ba_nav_links = trim($dataset["ba_nav_links"]);
        $ba_startpage = trim($dataset["ba_startpage"]);

        $ba_nav_arr = explode(",", $ba_nav);


        $html_backend_ext .= "<tr>\n<td class=\"td_backend\">".
                             $this->language["PROMPT_TITLE"].
                             "</td>\n<td>".
                             "<input type=\"text\" name=\"ba_base[ba_title]\" class=\"size_32\" maxlength=\"".MAXLEN_BASETITLE."\" value=\"".$ba_title."\"/>".
                             "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                             $this->language["PROMPT_NAVIGATION"].
                             "</td>\n<td>\n".
                             "<input type=\"checkbox\" name=\"ba_base[ba_nav][]\" value=\"".ACTION_HOME."\"";
        if (in_array(ACTION_HOME, $ba_nav_arr)) {
          $html_backend_ext .= " checked=\"checked\"";
        }
        $html_backend_ext .= " />".$this->language["FRONTEND_NAV_HOME"]."<br>\n".
                             "<input type=\"checkbox\" name=\"ba_base[ba_nav][]\" value=\"".ACTION_PROFILE."\"";
        if (in_array(ACTION_PROFILE, $ba_nav_arr)) {
          $html_backend_ext .= " checked=\"checked\"";
        }
        $html_backend_ext .= " />".$this->language["FRONTEND_NAV_PROFILE"]."<br>\n".
                             "<input type=\"checkbox\" name=\"ba_base[ba_nav][]\" value=\"".ACTION_PHOTOS."\"";
        if (in_array(ACTION_PHOTOS, $ba_nav_arr)) {
          $html_backend_ext .= " checked=\"checked\"";
        }
        $html_backend_ext .= " />".$this->language["FRONTEND_NAV_PHOTOS"]."<br>\n".
                             "<input type=\"checkbox\" name=\"ba_base[ba_nav][]\" value=\"".ACTION_BLOG."\"";
        if (in_array(ACTION_BLOG, $ba_nav_arr)) {
          $html_backend_ext .= " checked=\"checked\"";
        }
        $html_backend_ext .= " />".$this->language["FRONTEND_NAV_BLOG"]."\n".
                             "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                             $this->language["PROMPT_NAV_LINKS"].
                             "</td>\n<td>\n".
                             "<input type=\"text\" name=\"ba_base[ba_nav_links]\" class=\"size_48\" maxlength=\"".MAXLEN_BASELINKS."\" value=\"".$ba_nav_links."\"/> 'url|name,...'".
                             "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">".
                             $this->language["PROMPT_STARTPAGE"].
                             "</td>\n<td>\n".
                             "<input type=\"radio\" name=\"ba_base[ba_startpage]\" value=\"".ACTION_HOME."\"";
        if ($ba_startpage == ACTION_HOME) {
          $html_backend_ext .= " checked=\"checked\"";
        }
        $html_backend_ext .= " />".$this->language["FRONTEND_NAV_HOME"]."<br>\n".
                             "<input type=\"radio\" name=\"ba_base[ba_startpage]\" value=\"".ACTION_PROFILE."\"";
        if ($ba_startpage == ACTION_PROFILE) {
          $html_backend_ext .= " checked=\"checked\"";
        }
        $html_backend_ext .= " />".$this->language["FRONTEND_NAV_PROFILE"]."<br>\n".
                             "<input type=\"radio\" name=\"ba_base[ba_startpage]\" value=\"".ACTION_PHOTOS."\"";
        if ($ba_startpage == ACTION_PHOTOS) {
          $html_backend_ext .= " checked=\"checked\"";
        }
        $html_backend_ext .= " />".$this->language["FRONTEND_NAV_PHOTOS"]."<br>\n".
                             "<input type=\"radio\" name=\"ba_base[ba_startpage]\" value=\"".ACTION_BLOG."\"";
        if ($ba_startpage == ACTION_BLOG) {
          $html_backend_ext .= " checked=\"checked\"";
        }
        $html_backend_ext .= " />".$this->language["FRONTEND_NAV_BLOG"]."\n".
                             "<tr>\n<td class=\"td_backend\"></td>\n<td>".
                             "<input type=\"submit\" value=\"".$this->language["BUTTON_POST"]."\" />".
                             "</td>\n</tr>\n".
                             "</table>\n".
                             "</form>\n\n";

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3</p>\n\n";
      }

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

  public function postBase($ba_title, $ba_nav, $ba_nav_links, $ba_startpage) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->database->connect_errno) {
      // wenn kein fehler

      $html_backend_ext .= "<section>\n\n";

      $count = 0;

      // update in datenbank , mit prepare() - sql injections verhindern
      $sql = "UPDATE ba_base SET ba_title = ?, ba_nav = ?, ba_nav_links = ?, ba_startpage = ? WHERE ba_id = 1";
      $stmt = $this->database->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4

        // austauschen ???? durch strings
        $stmt->bind_param("ssss", $ba_title, $ba_nav, $ba_nav_links, $ba_startpage);
        $stmt->execute();	// ausführen geänderte zeile
        $count += $stmt->affected_rows;
        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4</p>\n\n";
      }

      $html_backend_ext .= "<p>".$count." ".$this->language["MSG_ROWS_CHANGED"]."</p>\n\n";

      $html_backend_ext .= "</section>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("content" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
