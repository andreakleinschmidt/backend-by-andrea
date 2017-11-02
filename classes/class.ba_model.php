<?php

// *****************************************************************************
// * ba_model
// * - html bausteine
// * - daten aus datenbank holen
// * - daten aufbereiten
// * - daten an controller zurückgeben
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXLEN_USER",32);	// login form
define("MAXLEN_PASSWORD",287);	// 32 x "ffffffff" + 31 x "-"
define("MAXLEN_CODE",8);	// login form
define("MAXLEN_TELEGRAM_ID",10);	// "4294967295" 32 bit unsigned integer
define("ROLE_NONE",0);
define("ROLE_EDITOR",1);
define("ROLE_MASTER",2);
define("ROLE_ADMIN",3);

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// db error 1 - kontakt zur datenbank
//
// db error 2a - stmt bei login user/password
// db error 2b - stmt bei backend POST password
// db error 2c - stmt bei login timestamp

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

// *****************************************************************************
// *** html ***
// *****************************************************************************

  public function html_form() {
    $html_form = "<form name=\"pwd_form\" action=\"backend.php\" method=\"post\">\n".
                 "<table class=\"backend\">\n".
                 "<tr>\n<td class=\"td_backend\">\n".
                 "user:\n".
                 "</td>\n<td>\n".
                 "<input type=\"text\" name=\"user_login\" class=\"size_16\" maxlength=\"".MAXLEN_USER."\" />\n".
                 "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                 "password:\n".
                 "</td>\n<td>\n".
                 "<input type=\"password\" name=\"password\" class=\"size_16\" maxlength=\"".MAXLEN_PASSWORD."\" />\n".
                 "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                 "</td>\n<td>\n".
                 "<input type=\"submit\" value=\"send\" onclick=\"encrypt()\" />\n".
                 "</td>\n</tr>\n".
                 "</table>\n".
                 "</form>\n\n";
    return $html_form;
  }

  public function html_form_2fa($random_pwd) {
    $html_form = "<form name=\"pwd_form\" action=\"backend.php\" method=\"post\">\n".
                 "<table class=\"backend\">\n".
                 "<tr>\n<td class=\"td_backend\">\n".
                 "random:\n".
                 "</td>\n<td>\n".
                 "<img src=\"qrcode.php\" alt=\"".$random_pwd."\">\n".
                 "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                 "code:\n".
                 "</td>\n<td>\n".
                 "<input type=\"text\" name=\"code\" class=\"size_16\" maxlength=\"".MAXLEN_CODE."\" />\n".
                 "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                 "</td>\n<td>\n".
                 "<input type=\"submit\" value=\"send\" />\n".
                 "</td>\n</tr>\n".
                 "</table>\n".
                 "</form>\n\n";
    return $html_form;
  }

  // passwort ändern formular
  // - alt (zur überprüfung)
  // - neu
  // - neu2
  public function password_form() {
    $password_form = "<p><b>password</b></p>\n".
                     "<form name=\"pwd_form\" action=\"backend.php\" method=\"post\">\n".
                     "<table class=\"backend\">\n".
                     "<tr>\n<td class=\"td_backend\">\n".
                     "password:\n".
                     "</td>\n<td>\n".
                     "<input type=\"password\" name=\"password\" class=\"size_16\" maxlength=\"".MAXLEN_PASSWORD."\" />\n".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                     "password new (1):\n".
                     "</td>\n<td>\n".
                     "<input type=\"password\" name=\"password_new1\" class=\"size_16\" maxlength=\"".MAXLEN_PASSWORD."\" />\n".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                     "password new (2):\n".
                     "</td>\n<td>\n".
                     "<input type=\"password\" name=\"password_new2\" class=\"size_16\" maxlength=\"".MAXLEN_PASSWORD."\" />\n".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                     "</td>\n<td>\n".
                     "<input type=\"submit\" value=\"send\" onclick=\"encrypt2()\" />\n".
                     "</td>\n</tr>\n".
                     "</table>\n".
                     "</form>\n\n";
    return $password_form;
  }

  // zwei-faktor-authentifizierung formular
  // - telegram_id
  // - use_2fa (an/aus)
  public function twofa_form($telegram_id, $use_2fa) {
    $twofa_form = "<p><b>two factor authentication</b></p>\n".
                  "<form name=\"twofa_form\" action=\"backend.php\" method=\"post\">\n".
                  "<table class=\"backend\">\n".
                  "<tr>\n<td class=\"td_backend\">\n".
                  "telegram_id:\n".
                  "</td>\n<td>\n".
                  "<input type=\"text\" name=\"telegram_id\" class=\"size_16\" maxlength=\"".MAXLEN_TELEGRAM_ID."\" value=\"".stripslashes($this->html5specialchars($telegram_id))."\"/>\n".
                  "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                  "use 2fa:\n".
                  "</td>\n<td>\n".
                  "<input type=\"checkbox\" name=\"use_2fa\" value=\"yes\"";
    if ($use_2fa > 0) {
      $twofa_form .= " checked=\"checked\"";
                }
    $twofa_form .= " />\n".
                   "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                   "</td>\n<td>\n".
                   "<input type=\"submit\" value=\"send\" />\n".
                   "</td>\n</tr>\n".
                   "</table>\n".
                   "</form>\n\n";
    return $twofa_form;
  }

// *****************************************************************************
// *** funktionen ***
// *****************************************************************************

  // wrapper htmlspecialchars()
  public function html5specialchars($str) {
    return htmlspecialchars($str, ENT_COMPAT | ENT_HTML5, "UTF-8");
  }

  // echo $html_backend individuell mit user_name und links nach user_role
  public function html_backend($user_role, $user_name) {
    $ret = "<nav>\n\n".
           "[".stripslashes($this->html5specialchars($user_name))."]\n".
           "<a href=\"backend.php\">backend:</a>\n";
    if ($user_role >= ROLE_EDITOR) {
      $ret .= "<a href=\"backend.php?action=home\">home</a>\n";
    }
    if ($user_role >= ROLE_EDITOR) {
      $ret .= "<a href=\"backend.php?action=profil\">profil</a>\n";
    }
    if ($user_role >= ROLE_EDITOR) {
      $ret .= "<a href=\"backend.php?action=fotos\">fotos</a>\n";
    }
    if ($user_role >= ROLE_EDITOR) {
      $ret .= "<a href=\"backend.php?action=blog\">blog</a>\n";
    }
    if ($user_role >= ROLE_MASTER) {
      $ret .= "<a href=\"backend.php?action=comment\">comment</a>\n";
    }
    if ($user_role >= ROLE_MASTER) {
      $ret .= "<a href=\"backend.php?action=upload\">upload</a>\n";
    }
    if ($user_role >= ROLE_ADMIN) {
      $ret .= "<a href=\"backend.php?action=admin\">admin</a>\n";
    }
    $ret .= "<a href=\"backend.php?action=password\">password</a>\n".
            "<a href=\"backend.php?action=logout\">logout</a>\n\n".
            "</nav>\n\n";

    return $ret;
  }

  // vergleich mit datenbank (passwort in datenbank ist md5 mit salt)
  public function check_user($user_login) {
    $check = false;
    $errorstring = "";

    $user_id = 0;
    $user_role = 0;
    $password_hash = 0;
    $telegram_id = 0;
    $use_2fa = 0;
    $last_code = 0;

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // mit prepare() - sql injections verhindern
      $sql = "SELECT id, role, user, password, telegram_id, use_2fa, last_code FROM backend WHERE user = ?";
      $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 2a

        // austauschen ? durch user string (s)
        $stmt->bind_param("s", $user_login);
        $stmt->execute();	// ausführen geänderte zeile
        $stmt->store_result();	// sonst $datenbank->error "Commands out of sync; you can't run this command now" bei stmt_opt

        $stmt->bind_result($user_id, $user_role, $user_login, $password_hash, $telegram_id, $use_2fa, $last_code);	// ausgabe in $user_id, user_role, $user_login, $password_hash, $telegram_id, $use_2fa, $last_code
        // fetch liefert wert oder NULL (user aus SELECT stimmt nicht überein)
        if ($stmt->fetch()) {
          // SELECT lieferte wert, user stimmt

            $check = true;

          }

      } // stmt

      else {
        $errorstring .=  "<p>db error 2a</p>\n\n";
      }

      $stmt->close();

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n\n";
    }

    return array("check" => $check, "user_id" => $user_id, "user_role" => $user_role, "user_login" => $user_login, "password_hash" => $password_hash, "telegram_id" => $telegram_id, "use_2fa" => $use_2fa, "last_code" => $last_code, "error" => $errorstring);
  }

  // login zeitstempel
  public function timestamp($code) {
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // zugriff auf mysql datenbank
      // mit prepare() - sql injections verhindern
      $sql_ts = "UPDATE backend SET last_login = NOW(), last_code = ? WHERE id = ?";
      $stmt_ts = $this->datenbank->prepare($sql_ts);	// liefert mysqli-statement-objekt
      if ($stmt_ts) {
        // wenn kein fehler 2c

        // austauschen ?? durch code und user_id aus session variable (ii)
        $stmt_ts->bind_param("ii", $code, $_SESSION["user_id"]);
        $stmt_ts->execute();	// ausführen geänderte zeile
        $stmt_ts->close();

      } // stmt_ts

      else {
        $errorstring .=  "<p>db error 2c</p>\n\n";	// db error 2c - stmt bei login timestamp
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n\n";
    }

    return array("error" => $errorstring);
  }

  // vergleich mit datenbank (passwort in datenbank ist md5 mit salt)
  public function check_password() {
    $check = false;
    $errorstring = "";

    $password_hash = 0;

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // mit prepare() - sql injections verhindern
      $sql_select = "SELECT password FROM backend WHERE id = ?";
      $stmt_select = $this->datenbank->prepare($sql_select);	// liefert mysqli-statement-objekt
      if ($stmt_select) {
        // wenn kein fehler 2b1

        // austauschen ? durch id int (i)
        $stmt_select->bind_param("i", $_SESSION["user_id"]);
        $stmt_select->execute();	// ausführen geänderte zeile
        $stmt_select->store_result();	// sonst $datenbank->error "Commands out of sync; you can't run this command now" bei stmt_opt

        $stmt_select->bind_result($password_hash);	// ausgabe in $password_hash
        // fetch liefert wert oder NULL
        if ($stmt_select->fetch()) {
          // SELECT lieferte wert

          $check = true;

        } // fetch ok

        else {
          $errorstring .= "<p>id error</p>\n\n";
        }

        $stmt_select->close();

      } // stmt_select

      else {
        $errorstring .= "<p>db error 2b1</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n\n";
    }

    return array("check" => $check, "password_hash" => $password_hash, "error" => $errorstring);
  }

  // update in datenbank (passwort in datenbank ist md5 mit salt)
  public function update_password($password_new_hash) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // mit prepare() - sql injections verhindern
      $sql_update = "UPDATE backend SET password = ? WHERE id = ?";
      $stmt_update = $this->datenbank->prepare($sql_update);	// liefert mysqli-statement-objekt
      if ($stmt_update) {
        // wenn kein fehler 2b2

        // austauschen ?? durch password_hash string (s) und int (i)
        $stmt_update->bind_param("si", $password_new_hash, $_SESSION["user_id"]);
        $stmt_update->execute();	// ausführen geänderte zeile

        if ($stmt_update->affected_rows == 1) {
          $html_backend_ext .= "<p>done</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>no change</p>\n\n";
        }

        $stmt_update->close();

      } // stmt_select

      else {
        $errorstring .= "<p>db error 2b2</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  // telegram_id und use_2fa aus datenbank
  public function getTwofa() {
    $result = false;
    $errorstring = "";

    $telegram_id = 0;
    $use_2fa = 0;

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // mit prepare() - sql injections verhindern
      $sql_select = "SELECT telegram_id, use_2fa FROM backend WHERE id = ?";
      $stmt_select = $this->datenbank->prepare($sql_select);	// liefert mysqli-statement-objekt
      if ($stmt_select) {
        // wenn kein fehler 2b3

        // austauschen ? durch id int (i)
        $stmt_select->bind_param("i", $_SESSION["user_id"]);
        $stmt_select->execute();	// ausführen geänderte zeile
        $stmt_select->store_result();	// sonst $datenbank->error "Commands out of sync; you can't run this command now" bei stmt_opt

        $stmt_select->bind_result($telegram_id, $use_2fa);	// ausgabe in $telegram_id, $use_2fa
        // fetch liefert wert oder NULL
        if ($stmt_select->fetch()) {

          $result = true;

        } // fetch ok


        else {
          $errorstring .= "<p>id error</p>\n\n";
        }

        $stmt_select->close();

      } // stmt_select

      else {
        $errorstring .= "<p>db error 2b3</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n\n";
    }

    return array("result" => $result, "telegram_id" => $telegram_id, "use_2fa" => $use_2fa, "error" => $errorstring);
  }

  // update telegram_id und use_2fa in datenbank
  public function update_twofa($telegram_id, $use_2fa) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // mit prepare() - sql injections verhindern
      $sql_update = "UPDATE backend SET telegram_id = ?, use_2fa = ? WHERE id = ?";
      $stmt_update = $this->datenbank->prepare($sql_update);	// liefert mysqli-statement-objekt
      if ($stmt_update) {
        // wenn kein fehler 2b4

        // austauschen ??? durch 3x int (i)
        $stmt_update->bind_param("iii", $telegram_id, $use_2fa, $_SESSION["user_id"]);
        $stmt_update->execute();	// ausführen geänderte zeile

        if ($stmt_update->affected_rows == 1) {
          $html_backend_ext .= "<p>done</p>\n\n";
        }
        else {
          $html_backend_ext .= "<p>no change</p>\n\n";
        }

        $stmt_update->close();

      } // stmt_select

      else {
        $errorstring .= "<p>db error 2b4</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
