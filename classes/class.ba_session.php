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
    $uid = md5(uniqid());	// md5 uid
    $ret = "<br>sl1 uid = ".$uid."\n";

    $_SESSION["auth"] = md5($_SERVER['HTTP_USER_AGENT'].
                            $_SERVER['REMOTE_ADDR'].
                            AUTHORIZATION_CODE.
                            $uid);
    $ret .= "<br>sl2 auth = ".$_SESSION["auth"]."\n";

    // client id cookie setzen mit md5 uid
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
      $uid = $_COOKIE["uid"];	// cookie mit md5 uid alt

      $login = true;

      // authentifizierung
      $auth2 = md5($_SERVER['HTTP_USER_AGENT'].
                   $_SERVER['REMOTE_ADDR'].
                   AUTHORIZATION_CODE.
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
  public function set_user_login($user_id, $user_role, $user_login) {
    $_SESSION["user_id"] = $user_id;
    $_SESSION["user_role"] = $user_role;
    $_SESSION["user_name"] = $user_login;

    $ret = "<br />user_id = ".$_SESSION["user_id"]."\n";
    $ret .= "<br />user_role = ".$_SESSION["user_role"]."\n";
    $ret .= "<br />user_name = ".$_SESSION["user_name"]."\n";

    return $ret;
  }

  // entferne login variablen
  // return debug string
  public function unset_user_login() {
    unset($_SESSION["user_id"], $_SESSION["user_role"], $_SESSION["user_name"]);
    $ret = "<br />unset user_id/_role/_name\n";

    return $ret;
  }

}

?>
