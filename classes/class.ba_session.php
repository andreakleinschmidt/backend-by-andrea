<?php

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2018 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
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
// *** session funktionen ***
// *****************************************************************************

class Session {

  // cookies löschen (auf 0 setzen, leerer string und datum in vergangenheit)
  public function del_cookies() {
    setcookie("uid", "", time()-60*60*24);	// auf client seite löschen
    unset($_COOKIE["uid"]);	// auf server seite löschen
  }

  // setze login variablen in session und client cookie
  // return debug string
  public function set_login() {
    $uid = sha1(uniqid(bin2hex(openssl_random_pseudo_bytes(8))));	// sha1 uid mit random prefix
    $ret = "sl1 uid = ".$uid."\n";

    $_SESSION["auth"] = sha1($_SERVER['HTTP_USER_AGENT'].
                             $_SERVER['REMOTE_ADDR'].
                             "backend_".VERSION.
                             $uid);
    $ret .= "sl2 auth = ".$_SESSION["auth"]."\n";

    // client id cookie setzen mit sha1 uid
    setcookie("uid",$uid, time()+LOGIN_TIME);	// client
    $_COOKIE["uid"] = $uid;	// server

    return $ret;
  }

  // login prüfen, $login setzen
  // return debug string
  public function check_login(&$login) {
    $ret = "";

    $login = false;

    // server session und client cookies prüfen
    if (isset($_SESSION["auth"], $_COOKIE["uid"])) {

      // cookies lesen
      $auth = $_SESSION["auth"];
      $uid = $_COOKIE["uid"];	// cookie mit sha1 uid alt

      $login = true;

      // authentifizierung
      $auth2 = sha1($_SERVER['HTTP_USER_AGENT'].
                    $_SERVER['REMOTE_ADDR'].
                    "backend_".VERSION.
                    $uid);

      if ($auth != $auth2) {
        $login = false;
      }

      $ret .= "002 auth = ".$auth."\n";
      $ret .= "003 auth2 = ".$auth2."\n";
      $ret .= "004 uid = ".$uid."\n";
      $ret .= "005 login = ".$login."\n";

    }

    return $ret;
  }

  // setze login variablen
  // return debug string
  public function set_user_login($user_id, $user_role, $user_login, $user_locale) {
    $_SESSION["user_id"] = $user_id;
    $_SESSION["user_role"] = $user_role;
    $_SESSION["user_name"] = $user_login;
    $_SESSION["user_locale"] = $user_locale;

    $ret = "user_id = ".$_SESSION["user_id"]."\n";
    $ret .= "user_role = ".$_SESSION["user_role"]."\n";
    $ret .= "user_name = ".$_SESSION["user_name"]."\n";
    $ret .= "user_locale = ".$_SESSION["user_locale"]."\n";

    return $ret;
  }

  // entferne login variablen
  // return debug string
  public function unset_user_login() {
    unset($_SESSION["user_id"], $_SESSION["user_role"], $_SESSION["user_name"], $_SESSION["user_locale"]);
    $ret = "unset user_id/_role/_name/_locale\n";

    return $ret;
  }

}

?>
