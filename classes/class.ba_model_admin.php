<?php

// *****************************************************************************
// * ba_model - admin
// * funktionen für speichern, ändern,löschen in db
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("MAXLEN_USER",32);	// admin form
define("MAXLEN_EMAIL",64);	// admin form
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
// db error 3j - ret bei backend GET admin
//
// db error 4g - stmt bei backend POST admin neu
// db error 4h - stmt bei backend POST admin
//
// admin error - bei backend POST admin neu (nicht eingefügt)

class Admin extends Model {

// *****************************************************************************
// *** html ***
// *****************************************************************************

  // user anlegen (name, email, rolle)
  private function new_user_form() {
    $new_user_form = "<p><b>new user</b></p>\n".
                     "<form action=\"backend.php\" method=\"post\">\n".
                     "<table class=\"backend\">\n".
                     "<tr>\n<td class=\"td_backend\">\n".
                     "user:\n".
                     "</td>\n<td>\n".
                     "<input type=\"text\" name=\"ba_admin_new[user]\" class=\"size_32\" maxlength=\"".MAXLEN_USER."\" />\n".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                     "email:\n".
                     "</td>\n<td>\n".
                     "<input type=\"text\" name=\"ba_admin_new[email]\" class=\"size_32\" maxlength=\"".MAXLEN_EMAIL."\" />\n".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n".
                     "role:\n".
                     "</td>\n<td>\n".
                     "<select name=\"ba_admin_new[role]\" size=\"1\">\n".
                     "<option value=\"".ROLE_NONE."\">none</option>\n".
                     "<option value=\"".ROLE_EDITOR."\">editor</option>\n".
                     "<option value=\"".ROLE_MASTER."\">master</option>\n".
                     "<option value=\"".ROLE_ADMIN."\">admin</option>\n".
                     "</select>\n".
                     "</td>\n</tr>\n<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                     "<input type=\"submit\" value=\"POST\" />\n".
                     "</td>\n</tr>\n".
                     "</table>\n".
                     "</form>\n\n";
    return $new_user_form;
  }

// *****************************************************************************
// *** funktionen ***
// *****************************************************************************

  public function getAdmin() {
    $html_backend_ext = "<p><b>admin</b></p>\n\n";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // TABLE backend (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      //                role TINYINT UNSIGNED NOT NULL,
      //                user VARCHAR(32) NOT NULL,
      //                password VARCHAR(32) NOT NULL,
      //                email VARCHAR(64) NOT NULL,
      //                last_login DATETIME NOT NULL,
      //                telegram_id INT UNSIGNED NOT NULL,
      //                use_2fa TINYINT UNSIGNED NOT NULL,
      //                last_code INT UNSIGNED NOT NULL);

      // zugriff auf mysql datenbank
      $sql = "SELECT id, role, user, email, last_login FROM backend";
      $ret = $this->datenbank->query($sql);	// liefert in return db-objekt
      if ($ret) {

        // anzeigen: user, email, letzter login, rolle
        // rolle durch auswahlliste änderbar
        // admin auswahlliste für admin ausgegraut
        // user löschen
        $html_backend_ext .= "<form action=\"backend.php\" method=\"post\">\n".
                             "<table class=\"backend\">\n".
                             "<tr>\n<th>id</th>\n<th>user</th>\n<th>email</th>\n<th>last login</th>\n<th>role</th>\n<th>del</th>\n</tr>\n";
        while ($datensatz = $ret->fetch_assoc()) {	// fetch_assoc() liefert array, solange nicht NULL (letzter datensatz)
          $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n".
                               $datensatz["id"].
                               "</td>\n<td>\n".
                               stripslashes($this->html5specialchars($datensatz["user"])).
                               "</td>\n<td>\n".
                               stripslashes($this->html5specialchars($datensatz["email"])).
                               "</td>\n<td>\n".
                               stripslashes($this->html5specialchars($datensatz["last_login"])).
                               "</td>\n<td>\n".
                               "<select name=\"ba_admin[".$datensatz["id"]."][0]\" size=\"1\"";
          if (($datensatz["id"] == $_SESSION["user_id"]) && ($_SESSION["user_role"] >= ROLE_ADMIN)) {
            // admin kann sich nicht selbst die adminrolle entziehen
            $html_backend_ext .= " disabled=\"disabled\"";
          }
          $html_backend_ext .= ">\n".
                               "<option value=\"".ROLE_NONE."\"";
          if ($datensatz["role"] == ROLE_NONE) {
            $html_backend_ext .= " selected=\"selected\"";
          }
          $html_backend_ext .= ">none</option>\n".
                               "<option value=\"".ROLE_EDITOR."\"";
          if ($datensatz["role"] == ROLE_EDITOR) {
            $html_backend_ext .= " selected=\"selected\"";
          }
          $html_backend_ext .= ">editor</option>\n".
                               "<option value=\"".ROLE_MASTER."\"";
          if ($datensatz["role"] == ROLE_MASTER) {
            $html_backend_ext .= " selected=\"selected\"";
          }
          $html_backend_ext .= ">master</option>\n".
                               "<option value=\"".ROLE_ADMIN."\"";
          if ($datensatz["role"] == ROLE_ADMIN) {
            $html_backend_ext .= " selected=\"selected\"";
          }
          $html_backend_ext .= ">admin</option>\n".
                               "</select>\n".
                               "</td>\n<td>\n".
                               "<input type=\"checkbox\" name=\"ba_admin[".$datensatz["id"]."][1]\" value=\"delete\"";
          if (($datensatz["id"] == $_SESSION["user_id"]) && ($_SESSION["user_role"] >= ROLE_ADMIN)) {
            $html_backend_ext .= " disabled=\"disabled\"";	// admin kann sich nicht selbst löschen
          }
          $html_backend_ext .= "/>\n".
                               "</td>\n</tr>\n";
        }

        $html_backend_ext .= "<tr>\n<td class=\"td_backend\">\n</td>\n<td>\n".
                             "<input type=\"submit\" value=\"POST\" />\n".
                             "</td>\n</tr>\n".
                             "</table>\n".
                             "</form>\n\n";

        $ret->close();	// db-ojekt schließen
        unset($ret);	// referenz löschen

      }
      else {
        $errorstring .= "<p>db error 3j</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    // user anlegen (name, email, rolle)
    $html_backend_ext .= $this->new_user_form();

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postAdminNew($user, $email, $role, $tmp_password) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      // einfügen in datenbank , mit prepare() - sql injections verhindern
      $sql = "INSERT INTO backend (role,user,password,email) VALUES (?, ?, ?, ?)";
      $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
      if ($stmt) {
        // wenn kein fehler 4g

        // austauschen ???? durch strings und int
        $password_hash = crypt($tmp_password);
        $stmt->bind_param("isss", $role, $user, $password_hash, $email);
        $stmt->execute();	// ausführen geänderte zeile

        if ($stmt->affected_rows == 1) {
          $html_backend_ext .= "<p>done</p>\n\n";
          mail($email, "added to backend", "Hello ".$user.", your temporary password is ".$tmp_password, "from:morgana@oscilloworld.de");	// email benachrichtigung mit passwort
        }
        else {
          $html_backend_ext .= "<p>admin error</p>\n\n";
        }

        $stmt->close();

      } // stmt

      else {
        $errorstring .= "<p>db error 4g</p>\n\n";
      }

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

  public function postAdmin($ba_admin_array_replaced) {
    $html_backend_ext = "";
    $errorstring = "";

    if (!$this->datenbank->connect_errno) {
      // wenn kein fehler

      $count = 0;

      foreach ($ba_admin_array_replaced as $id => $ba_array) {
        $role = $ba_array[0];
        $delete = $ba_array[1];

        // update oder löschen in datenbank , mit prepare() - sql injections verhindern
        if ($delete) {
          $sql = "DELETE FROM backend WHERE id = ?";
        }
        else {
          $sql = "UPDATE backend SET role = ? WHERE id = ?";
        }
        $stmt = $this->datenbank->prepare($sql);	// liefert mysqli-statement-objekt
        if ($stmt) {
          // wenn kein fehler 4h

          // austauschen ? oder ?? durch int
          if ($delete) {
            $stmt->bind_param("i", $id);
          }
          else {
            $stmt->bind_param("ii", $role, $id);
          }
          $stmt->execute();	// ausführen geänderte zeile
          $count += $stmt->affected_rows;
          $stmt->close();

        } // stmt

        else {
          $errorstring .= "<p>db error 4h</p>\n\n";
        }

      }

      $html_backend_ext .= "<p>".$count." rows changed</p>\n\n";

    } // datenbank
    else {
      $errorstring .= "<br>db error 1\n";
    }

    return array("inhalt" => $html_backend_ext, "error" => $errorstring);
  }

}

?>
