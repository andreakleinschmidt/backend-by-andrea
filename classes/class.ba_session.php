<?php

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
    $ret = "<br>sl1 uid = ".$uid."\n";

    $_SESSION["auth"] = sha1($_SERVER['HTTP_USER_AGENT'].
                             $_SERVER['REMOTE_ADDR'].
                             "backend_".VERSION.
                             $uid);
    $ret .= "<br>sl2 auth = ".$_SESSION["auth"]."\n";

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

      if (DEBUG) {
        $ret .= "<br>002 auth = ".$auth."\n";
        $ret .= "<br>003 auth2 = ".$auth2."\n";
        $ret .= "<br>004 uid = ".$uid."\n";
        $ret .= "<br>005 login = ".$login."\n";
      }

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

    $ret = "<br>user_id = ".$_SESSION["user_id"]."\n";
    $ret .= "<br>user_role = ".$_SESSION["user_role"]."\n";
    $ret .= "<br>user_name = ".$_SESSION["user_name"]."\n";
    $ret .= "<br>user_locale = ".$_SESSION["user_locale"]."\n";

    return $ret;
  }

  // entferne login variablen
  // return debug string
  public function unset_user_login() {
    unset($_SESSION["user_id"], $_SESSION["user_role"], $_SESSION["user_name"], $_SESSION["user_locale"]);
    $ret = "<br>unset user_id/_role/_name/_locale\n";

    return $ret;
  }

}

?>
