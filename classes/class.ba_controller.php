<?php

// *****************************************************************************
// * controller
// * - GET/POST auslesen
// * - benutzereingabe (request)
// * - datenabfrage beim model
// * - datenweitergabe zum view
// *****************************************************************************

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("AUTHORIZATION_CODE","andreas-alpha-0815");
define("LOGIN_TIME",600);	// zeit in s login uid cookie
define("MAXLEN_USER",32);	// login form
define("MAXLEN_PASSWORDNONCRYPT",32);
define("MAXLEN_CHARCRYPT",8);	// ffffffff
define("MAXLEN_PASSWORD",287);	// 32 x "ffffffff" + 31 x "-"
define("MAXLEN_CODE",8);	// login form
define("MAXLEN_TELEGRAM_ID",10);	// "4294967295" 32 bit unsigned integer
define("MAXLEN_EMAIL",64);	// admin form
define("MAXLEN_HOMETEXT",256);	// aus TABLE VARCHAR(xx) , für zeichen limit
define("MAXLEN_PROFILETAG",64);
define("MAXLEN_PROFILETEXT",256);
define("MAXLEN_GALLERYALIAS",16);
define("MAXLEN_GALLERYTEXT",64);
define("MAXLEN_FOTOID",8);
define("MAXLEN_FOTOTEXT",64);
define("MAXLEN_BLOGDATE",32);
define("MAXLEN_BLOGTEXT",8192);
define("MAXLEN_BLOGVIDEOID",32);
define("MAXLEN_BLOGFOTOID",128);
define("MAXLEN_BLOGTAGS",128);
define("MAXLEN_BLOGCATEGORY",32);
define("MAXLEN_FEED",128);	// blogroll
define("MAXLEN_COMMENTDATE",20);
define("MAXLEN_COMMENTIP",48);
define("MAXLEN_COMMENTNAME",64);
define("MAXLEN_COMMENTMAIL",64);
define("MAXLEN_COMMENTBLOGID",8);
define("MAXLEN_COMMENTTEXT",2048);
define("MAXLEN_COMMENTCOMMENT",2048);
define("MAXSIZE_FILEUPLOAD",2097152);	// 2048*1024 (2 MB default)
define("ROLE_NONE",0);
define("ROLE_EDITOR",1);
define("ROLE_MASTER",2);
define("ROLE_ADMIN",3);
define("STATE_CREATED",0);
define("STATE_EDITED",1);
define("STATE_APPROVAL",2);
define("STATE_PUBLISHED",3);
define("MB_ENCODING","UTF-8");
define("DEBUG",false);
define("DEBUG_STR","<p>debug:\n");

// *****************************************************************************
// *** error list ***
// *****************************************************************************
//
// password new (1) and new (2) not equal - bei backend POST password
//
// user/password error - bei login
// password error - bei backend POST password
//
// POST error - bei login ($_POST["unbekannt"])
// POST error - bei backend POST ($_POST["unbekannt"])
//
// fatal error - primtab.txt in RSA()

class Controller {

  private $request = null;
  private $method;
  private $action;
  private $galleryid;
  private $id;
  private $page;

  // konstruktor, controller erstellen
  public function __construct($request, $method) {
    $this->request = $request;
    $this->method = !empty($method) ? $method : "GET";
    $this->user_login = !empty($request["user_login"]) ? trim($request["user_login"]) : NULL;	// überflüssige leerzeichen entfernen
    $this->password = !empty($request["password"]) ? trim($request["password"]) : NULL;	// überflüssige leerzeichen entfernen
    $this->code = !empty($request["code"]) ? (is_numeric($request["code"]) ? intval($request["code"]) : NULL) : NULL;	// string in int umwandeln
    $this->action = !empty($request["action"]) ? substr(trim($request["action"]), 0, 8) : "default";	// GET auslesen, überflüssige leerzeichen entfernen, zu langes GET abschneiden
    $this->galleryid = !empty($request["gallery"]) ? (is_numeric($request["gallery"]) ? intval($request["gallery"]) : NULL) : NULL;	// GET gallery auslesen, string in int umwandeln
    $this->id = !empty($request["id"]) ? (is_numeric($request["id"]) ? intval($request["id"]) : NULL) : NULL;	// GET id auslesen, string in int umwandeln
    $this->page = !empty($request["page"]) ? (is_numeric($request["page"]) ? intval($request["page"]) : NULL) : NULL;	// GET page auslesen, string in int umwandeln
    $this->model = new Model();	// model erstellen
  }

// *****************************************************************************
// *** funktionen ***
// *****************************************************************************

  // cookies löschen (auf 0 setzen, leerer string und datum in vergangenheit)
  public function del_cookies() {
    setcookie("uid", "", time()-60*60*24);	// auf client seite löschen
    setcookie("rsa", "", time()-60*60*24);
    setcookie("puk", "", time()-60*60*24);
    unset($_COOKIE["uid"]);	// auf server seite löschen
    unset($_COOKIE["rsa"]);
    unset($_COOKIE["puk"]);
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
  }

  // public key - verschlüsseln/verifizieren - für java-client
  // private key - entschlüsseln/signieren - php-server - geheim
  public function set_rsa($rsa, $public_key, $private_key) {
    // rsa modul und public key in client cookie
    setcookie("rsa",$rsa);	// auf client seite
    setcookie("puk",$public_key);
    $_COOKIE["rsa"] = $rsa;	// auf server seite
    $_COOKIE["puk"] = $public_key;

    // rsa modul und private key in server session (für nächsten POST aufruf)
    $_SESSION["rsa"] = $rsa;
    $_SESSION["prk"] = $private_key;
  }

  // generate random password
  public function gen_password($len = 8) {
    $char_pool = "abcdefghijklmnopqrstuvwxyz".
                 "ABCDEFGHIJKLMNOPQRSTUVWXYZ".
                 "0123456789".
                 "!$%{}=?+*~#.:-_";
    // htmlspecialchars: "&'<>
    // nicht verwendet: /()[]\'`@|,;

    if ($len > MAXLEN_PASSWORDNONCRYPT) {
      $len = MAXLEN_PASSWORDNONCRYPT;
    }

    return substr(str_shuffle($char_pool), 0, $len);	// nur ascii
  }

  // generate random alphanumeric password (mit duplikaten)
  public function gen_anum_password($len = 16) {
    $char_pool = "abcdefghijklmnopqrstuvwxyz".
                 "ABCDEFGHIJKLMNOPQRSTUVWXYZ".
                 "0123456789";

    $pwd_str = "";
    for ($i = 0; $i < $len; $i++) {
      $offset = mt_rand(0, strlen($char_pool)-1);	// nur ascii
      $pwd_str .= substr($char_pool, $offset, 1);	// nur ascii
    }

    return $pwd_str;
  }

  // berechne code (zwei-faktor-authentifizierung)
  public function get_code($secret, $ts, $n=0, $digits=8) {
    $tc = floor($ts/30)+$n;				// n: z.B n-1 -> tc-1
    $hash = hash_hmac("sha256", $tc, $secret, true);	// raw binary output
    $offset = ord(substr($hash, -1, 1)) & 0x0F;		// least 4 significant bits
    $hotp = substr($hash, $offset, 4);			// 4 bytes ab offset...
    $hotp = hexdec(bin2hex($hotp)) & 0x7FFFFFFF;	// ...MSB verwerfen (unsigned 32 bit)
    $code = $hotp % pow(10, $digits);			// code mit x ziffern
    $code = str_pad($code, $digits, "0", STR_PAD_LEFT);	// von links auffüllen mit 0, falls weniger als x ziffern
    return $code;
  }

  // zustandsmaschine, aufruf der funktionen, datenabfrage beim model, datenweitergabe zum view
  public function display() {
    $view = new View();	// view erstellen
    $view->setTemplate("backend");	// template "tpl_backend.htm" laden

    $debug_str = "";
    if (DEBUG) { $debug_str .= DEBUG_STR; }

// *****************************************************************************
// *** session ***
// *****************************************************************************

    // panic mode
    if (isset($_SESSION["panic"])) {
      $html_backend_ext = "";
      $errorstring = "";
      if (DEBUG) { $debug_str .= "<br>001 panic\n"; }
    }
    else {
      // no panic , login?

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
          $debug_str .= "<br>002 auth = ".$auth."\n";
          $debug_str .= "<br>003 auth2 = ".$auth2."\n";
          $debug_str .= "<br>004 uid = ".$uid."\n";
          $debug_str .= "<br>005 login = ".$login."\n";
        }

      }

      $RSA_obj = @new RSA();	// php stellt alle rsa komponenten

      if (!$login) {

        // POST mit verschlüsseltem password?
        if ($this->method != "POST") {
          // nein

          // php stellt alle rsa komponenten
          $rsa_debug_str = "";
          $rsa = 0;
          $public_key = 0;
          $private_key = 0;
          if (!$RSA_obj->RSA_init($rsa_debug_str, $rsa, $public_key, $private_key)) {
            $this->set_rsa($rsa, $public_key, $private_key);

// *****************************************************************************
// *** login ***
// *****************************************************************************

            // login form
            // rsa modul und public key in client cookie (document.cookie)
            // in client javascript, string in einzelne asciizeichen auseinandernehmen
            // wert einzeln verschlüsseln mit public key , C = K^e mod N
            // komplette code kette in post formular feld , 7f-7f-7f...

            $html_backend_ext = $this->model->html_form();

          }
          else {
            $errorstring = "<p>fatal error</p>\n\n";
          }

          if (DEBUG) { $debug_str .= $rsa_debug_str; }

        } // kein POST

        else {
          // verschlüsseltes password oder (optional) code in POST?

          $login_1 = false;
          $login_2 = false;

          $code = 0;

          // POST überprüfen
          if (isset($this->user_login, $this->password, $_SESSION["rsa"], $_SESSION["prk"])) {

            // rsa modul und private key aus server session (letzter aufruf login form)
            $rsa = $_SESSION["rsa"];
            $private_key = $_SESSION["prk"];
            unset($_SESSION["rsa"]);
            unset($_SESSION["prk"]);

            if (DEBUG) { $debug_str .= "<br>006 rsa = ".$rsa."\n"; }
            if (DEBUG) { $debug_str .= "<br>007 prk = ".$private_key."\n"; }

            if (DEBUG) { $debug_str .= "<br>008 user_login = ".$this->user_login."\n"; }
            if (DEBUG) { $debug_str .= "<br>008 pwd-c = ".$this->password."\n"; }

            if ($this->user_login != "" and mb_strlen($this->user_login, MB_ENCODING) <= MAXLEN_USER and $this->password != "" and $this->password != "error" and strlen($this->password) <= MAXLEN_PASSWORD and $private_key > 0 and $rsa > 0) {
              // test auf leere felder , private key und rsa vorhanden , vermeidung % 0

              // komplette code kette in post formular feld , 7f-7f-7f... , auseinandernehmen
              $pwd_array = explode("-", $this->password, MAXLEN_PASSWORDNONCRYPT);

              // passwort-teile entschlüsseln mit private key , K = C^d mod N
              foreach ($pwd_array as &$c) {
                $c = $RSA_obj->RSA_crypt(intval(substr($c,0,MAXLEN_CHARCRYPT),16), $private_key, $rsa);	// zu groß: pow($c,$private_key) % $rsa;
                $c &= 0x7f;	// "chaos bit" entfernen (ff -> 7f)
                $c = chr($c);	// ascii zeichen
              }
              unset($c);	// break reference (call by reference &$c)

              // passwort zusammensetzen
              $password = trim(implode("",$pwd_array));
              if (DEBUG) { $debug_str .= "<br>009 pwd = ".$password."\n"; }

              // vergleich mit datenbank (passwort in datenbank ist md5 mit salt)
              $ret = $this->model->check_user($this->user_login);

              if ($ret["check"] = true) {

                $user_id = $ret["user_id"];
                $user_role = $ret["user_role"];
                $user_login = $ret["user_login"];
                $password_hash = $ret["password_hash"];
                $telegram_id = $ret["telegram_id"];
                $use_2fa = $ret["use_2fa"];
                $last_code = $ret["last_code"];

                if (DEBUG) { $debug_str .= "<br>010 pwd-hash = ".$password_hash."\n"; }
                if (crypt($password, $password_hash) == $password_hash) {
                  // passwort stimmt

                  $login_1 = true;

                  // optional zwei-faktor-authentifizierung
                  if ($use_2fa > 0) {

                    $random_pwd = $this->gen_anum_password();	// generate random alphanumeric password (mit duplikaten)

                    $_SESSION["login_random_pwd"] = $random_pwd;
                    $_SESSION["login_telegram_id"] = $telegram_id;
                    $_SESSION["login_last_code"] = $last_code;

                    if (DEBUG) { $debug_str .= "<br>2fa random_pwd = ".$random_pwd."\n"; }
                    if (DEBUG) { $debug_str .= "<br>2fa telegram_id = ".$telegram_id."\n"; }
                    if (DEBUG) { $debug_str .= "<br>2fa last_code = ".$last_code."\n"; }

                    // login form 2fa
                    $html_backend_ext = $this->model->html_form_2fa($random_pwd);

                  } // $use_2fa
                  else {
                    $login_2 = true;
                  } // don't $use_2fa

                } // passwort ok

              } // check user ok

              else {
                $_SESSION["panic"] = true;	// user falsch, solange session aktiv, kein neuer versuch möglich, kein dauer-POST
                $this->del_cookies();	// cookies löschen
                unset($_SESSION["auth"]);
              }

              $errorstring = $ret["error"];

            } // user/password error

            else {
              $errorstring = "<p>user/password error</p>\n\n";
            }

            $_SESSION["login_passed"] = $login_1;

          } // password in POST

          // POST überprüfen
          elseif (isset($this->code, $_SESSION["login_passed"], $_SESSION["login_random_pwd"], $_SESSION["login_telegram_id"], $_SESSION["login_last_code"])) {

            $login_passed = $_SESSION["login_passed"];
            $random_pwd = $_SESSION["login_random_pwd"];
            $telegram_id = $_SESSION["login_telegram_id"];
            $last_code= $_SESSION["login_last_code"];

            unset($_SESSION["login_passed"]);
            unset($_SESSION["login_random_pwd"]);
            unset($_SESSION["login_telegram_id"]);
            unset($_SESSION["login_last_code"]);

            if (DEBUG) { $debug_str .= "<br>2fa first login passed (1) = ".$login_passed."\n"; }
            if (DEBUG) { $debug_str .= "<br>2fa random_pwd = ".$random_pwd."\n"; }
            if (DEBUG) { $debug_str .= "<br>2fa telegram_id = ".$telegram_id."\n"; }
            if (DEBUG) { $debug_str .= "<br>2fa last_code = ".$last_code."\n"; }

            if ($login_passed) {

              if (DEBUG) { $debug_str .= "<br>010 code = ".$this->code."\n"; }

              if ($this->code != "" and strlen($this->code) <= MAXLEN_CODE) {
                // test auf leere felder

                $unix_ts = time();	// totp - time based one time password
                $secret = sha1($random_pwd.$telegram_id);
                $own_code = $this->get_code($secret, $unix_ts);
                $own_code_n1 = $this->get_code($secret, $unix_ts, -1);
                if (DEBUG) { $debug_str .= "<br>010 own_code = ".$own_code."\n"; }
                if (DEBUG) { $debug_str .= "<br>010 own_code_n1 = ".$own_code_n1."\n"; }
                if (DEBUG) { $debug_str .= "<br>010 last_code = ".$last_code."\n"; }

                // vergleich eingegebenen code mit berechneten code und zuletzt eingegebenen code
                if ((($this->code == $own_code) or ($this->code == $own_code_n1)) and ($this->code != $last_code)) {
                  // code stimmt

                  $login_2 = true;
                  $code = $this->code;
                }

                else {
                  $_SESSION["panic"] = true;	// (optional) code falsch, solange session aktiv, kein neuer versuch möglich, kein dauer-POST
                  $this->del_cookies();	// cookies löschen
                  unset($_SESSION["auth"]);
                }

              } // code error

              else {
                $errorstring = "<p>code error</p>\n\n";
              }

            } // login

          } // code in POST

          else {
            $errorstring = "<p>POST error</p>\n\n";
          }

          // nach POST überprüfung, auswertung login variablen

          if ($login_1) {
            // password in POST
            $ret = $this->set_user_login($user_id, $user_role, $user_login);	// server session erweitern (mit variablen für user login)
            if (DEBUG) { $debug_str .= $ret; }
          }
          elseif ($login_2) {
            // code in POST, 2fa
            $login_1 = $login_passed;
            if (DEBUG) { $debug_str .= "<br>2fa first login passed (2) = ".$login_1."\n"; }
          }
          else {
            $ret = $this->unset_user_login();	// 2fa failed, user variablen aus session entfernen
            if (DEBUG) { $debug_str .= $ret; }
          }

          $login = $login_1 & $login_2;
          if ($login) {

            $ret = $this->set_login();	// server session setzen/erweitern (mit uid cookie)
            if (DEBUG) { $debug_str .= $ret; }

            $ret = $this->model->timestamp($code);	// login zeitstempel und letzter code
            $errorstring = $ret["error"];

          } // passwort und (optional) code ok

          if (DEBUG) { $debug_str .= "<br>011 login = ".$login."\n"; }

          if ($login) {
            $html_backend_ext = $this->model->html_backend($_SESSION["user_role"], $_SESSION["user_name"]);
          }

        } // POST

      } // nicht login

      else {
        // login

        unset($_SESSION["auth"]);
        session_regenerate_id();	// immer neue session id

        $ret = $this->set_login();	// server session setzen/erweitern (mit uid cookie)
        if (DEBUG) { $debug_str .= $ret; }

// *****************************************************************************
// *** backend GET ***
// *****************************************************************************

        $html_backend_ext = $this->model->html_backend($_SESSION["user_role"], $_SESSION["user_name"]);

        $ret = null;	// ["inhalt","error"]

        // GET/POST
        if ($this->method != "POST") {
          // GET

          // action überprüfen
          if ($this->action != "home" and $this->action != "profil" and $this->action != "fotos" and $this->action != "blog" and $this->action != "comment" and $this->action != "upload" and $this->action != "admin" and $this->action != "password" and $this->action != "logout") {
            $this->action = "default";
          }

          // switch anweisung, je nach GET-action

          switch($this->action) {

// *****************************************************************************
// *** backend GET home ***
// *****************************************************************************

            case "home": {

              if ($_SESSION["user_role"] >= ROLE_EDITOR) {

                $model_home = new Home();	// model erstellen
                $ret = $model_home->getHome();	// daten für home aus dem model
                $html_backend_ext .= $ret["inhalt"];
                $errorstring = $ret["error"];

              } // ROLE_EDITOR

                break;
            }

// *****************************************************************************
// *** backend GET profil ***
// *****************************************************************************

            case "profil": {

              if ($_SESSION["user_role"] >= ROLE_EDITOR) {

                $model_profil = new Profil();	// model erstellen
                $ret = $model_profil->getProfil();	// daten für profil aus dem model
                $html_backend_ext .= $ret["inhalt"];
                $errorstring = $ret["error"];

              } // ROLE_EDITOR

              break;
            }

// *****************************************************************************
// *** backend GET fotos ***
// *****************************************************************************

            case "fotos": {

              if ($_SESSION["user_role"] >= ROLE_EDITOR) {

                $model_fotos = new Fotos();	// model erstellen
                $ret = $model_fotos->getFotos($this->galleryid);	// daten für fotos aus dem model
                $html_backend_ext .= $ret["inhalt"];
                $errorstring = $ret["error"];

              } // ROLE_EDITOR

              break;
            }

// *****************************************************************************
// *** backend GET blog ***
// *****************************************************************************

            case "blog": {

              if ($_SESSION["user_role"] >= ROLE_EDITOR) {

                $model_blog = new Blog();	// model erstellen
                $ret = $model_blog->getBlog($this->id, $this->page);	// daten für blog aus dem model
                $html_backend_ext .= $ret["inhalt"];
                $errorstring = $ret["error"];

              } // ROLE_EDITOR

              break;
            }

// *****************************************************************************
// *** backend GET comment ***
// *****************************************************************************

            case "comment": {

              if ($_SESSION["user_role"] >= ROLE_MASTER) {

                $model_comment = new Comment();	// model erstellen
                $ret = $model_comment->getComment($this->id, $this->page);	// daten für comment aus dem model
                $html_backend_ext .= $ret["inhalt"];
                $errorstring = $ret["error"];

              } // ROLE_MASTER

              break;
            }

// *****************************************************************************
// *** backend GET upload ***
// *****************************************************************************

            case "upload": {

              if ($_SESSION["user_role"] >= ROLE_MASTER) {

                $model_upload = new Upload();	// model erstellen
                $ret = $model_upload->getUpload();	// daten für upload aus dem model
                $html_backend_ext .= $ret["inhalt"];

              } // ROLE_MASTER

              break;
            }

// *****************************************************************************
// *** backend GET admin ***
// *****************************************************************************

            case "admin": {

              if ($_SESSION["user_role"] >= ROLE_ADMIN) {

                $model_admin = new Admin();	// model erstellen
                $ret = $model_admin->getAdmin();	// daten für admin aus dem model
                $html_backend_ext .= $ret["inhalt"];
                $errorstring = $ret["error"];

              } // ROLE_ADMIN

              break;
            }

// *****************************************************************************
// *** backend GET password ***
// *****************************************************************************

            case "password": {

              // passwort ändern formular
              // - alt (zur überprüfung)
              // - neu
              // - neu2
              $html_backend_ext .= $this->model->password_form(true, false);	// section_start=true

              // php stellt alle rsa komponenten
              $rsa_debug_str = "";
              $rsa = 0;
              $public_key = 0;
              $private_key = 0;
              if ($RSA_obj->RSA_init($rsa_debug_str, $rsa, $public_key, $private_key)) {
                $errorstring = "<p>fatal error</p>\n\n";
              }
              else {
                $this->set_rsa($rsa, $public_key, $private_key);
              }
              if (DEBUG) { $debug_str .= $rsa_debug_str; }

              $ret = $this->model->getTwofa();	// daten für twofa aus dem model

              // zwei-faktor-authentifizierung formular
              // - telegram_id
              // - use_2fa (an/aus)
              $html_backend_ext .= $this->model->twofa_form($ret["telegram_id"], $ret["use_2fa"], false, true);	// section_end=true
              $errorstring = $ret["error"];

              break;
            }

// *****************************************************************************
// *** backend GET logout ***
// *****************************************************************************

            case "logout": {

              $html_backend_ext = "<p>logout</p>\n\n";

              $this->del_cookies();	// cookies löschen
              unset($_SESSION["auth"]);
              session_unset();
              session_destroy();

              break;
            }

            default: {
              // version

              $ret = $this->model->getVersion();	// daten für version aus dem model
              $html_backend_ext .= $ret["inhalt"];
              $errorstring = $ret["error"];

            }

          } // switch

        } // GET

// *****************************************************************************
// *** backend POST ***
// *****************************************************************************

        else {
          // POST

// *****************************************************************************
// *** backend POST password ***
// *****************************************************************************

          // POST überprüfen
          if (isset($this->request["password"], $this->request["password_new1"], $this->request["password_new2"], $_SESSION["rsa"], $_SESSION["prk"])) {
            // password in POST

            // rsa modul und private key aus server session (letzter aufruf backend password form)
            $rsa = $_SESSION["rsa"];
            $private_key = $_SESSION["prk"];
            unset($_SESSION["rsa"]);
            unset($_SESSION["prk"]);

            if (DEBUG) { $debug_str .= "<br>012 rsa = ".$rsa."\n"; }
            if (DEBUG) { $debug_str .= "<br>013 prk = ".$private_key."\n"; }

            // password überprüfen
            $password = trim($this->request["password"]);	// überflüssige leerzeichen entfernen
            $password_new1 = trim($this->request["password_new1"]);
            $password_new2 = trim($this->request["password_new2"]);
            if (DEBUG) { $debug_str .= "<br>014 pwd-c = ".$password."\n"; }
            if (DEBUG) { $debug_str .= "<br>015 pwd-c-n1 = ".$password_new1."\n"; }
            if (DEBUG) { $debug_str .= "<br>016 pwd-c-n2 = ".$password_new2."\n"; }

            if ($password != "" and $password != "error" and (strlen($password) <= MAXLEN_PASSWORD)
                and $password_new1 != "" and $password_new1 != "error" and strlen($password_new1) <= MAXLEN_PASSWORD
                and $password_new2 != "" and $password_new2 != "error" and strlen($password_new2) <= MAXLEN_PASSWORD
                and $private_key > 0 and $rsa > 0) {
              // test auf leeres passwort , private key und rsa vorhanden , vermeidung % 0

              // komplette code kette in post formular feld , 7f-7f-7f... , auseinandernehmen
              $pwd_array = explode("-", $password, MAXLEN_PASSWORDNONCRYPT);
              $pwd_array_new1 = explode("-", $password_new1, MAXLEN_PASSWORDNONCRYPT);
              $pwd_array_new2 = explode("-", $password_new2, MAXLEN_PASSWORDNONCRYPT);

              // passwort-teile entschlüsseln mit private key , K = C^d mod N
              foreach ($pwd_array as &$c) {
                $c = $RSA_obj->RSA_crypt(intval(substr($c,0,MAXLEN_CHARCRYPT),16), $private_key, $rsa);	// zu groß: pow($c,$private_key) % $rsa;
                $c &= 0x7f;	// "chaos bit" entfernen (ff -> 7f)
                $c = chr($c);	// ascii zeichen
              }
              unset($c);	// break reference (call by reference &$c)
              foreach ($pwd_array_new1 as &$c) {
                $c = $RSA_obj->RSA_crypt(intval(substr($c,0,MAXLEN_CHARCRYPT),16), $private_key, $rsa);	// zu groß: pow($c,$private_key) % $rsa;
                $c &= 0x7f;	// "chaos bit" entfernen (ff -> 7f)
                $c = chr($c);	// ascii zeichen
              }
              unset($c);	// break reference (call by reference &$c)
              foreach ($pwd_array_new2 as &$c) {
                $c = $RSA_obj->RSA_crypt(intval(substr($c,0,MAXLEN_CHARCRYPT),16), $private_key, $rsa);	// zu groß: pow($c,$private_key) % $rsa;
                $c &= 0x7f;	// "chaos bit" entfernen (ff -> 7f)
                $c = chr($c);	// ascii zeichen
              }
              unset($c);	// break reference (call by reference &$c)

              // passwort zusammensetzen
              $password = trim(implode("",$pwd_array));
              $password_new1 = trim(implode("",$pwd_array_new1));
              $password_new2 = trim(implode("",$pwd_array_new2));
              if (DEBUG) { $debug_str .= "<br>017 pwd = ".$password."\n"; }
              if (DEBUG) { $debug_str .= "<br>018 pwd-n1 = ".$password_new1."\n"; }
              if (DEBUG) { $debug_str .= "<br>019 pwd-n2 = ".$password_new2."\n"; }

              // passwort neu 1 und 2 gleich?
              if ($password_new1 == $password_new2) {

                // vergleich mit datenbank (passwort in datenbank ist md5 mit salt)
                $ret = $this->model->check_password();

                if ($ret["check"] = true) {

                  $password_hash = $ret["password_hash"];

                  if (DEBUG) { $debug_str .= "<br>020 pwd-hash = ".$password_hash."\n"; }
                  if (crypt($password, $password_hash) == $password_hash) {
                    // passwort stimmt

                    $password_new_hash = crypt($password_new1);
                    if (DEBUG) { $debug_str .= "<br>021 pwd-n-hash = ".$password_new_hash."\n"; }

                    // update in datenbank (passwort in datenbank ist md5 mit salt)
                    $ret = $this->model->update_password($password_new_hash);
                    $html_backend_ext .= $ret["inhalt"];
                    $errorstring = $ret["error"];

                  } // passwort ok

                  else {
                    $errorstring = "<p>wrong password</p>\n\n";
                  }

                } // check password ok

                else {
                  $errorstring = $ret["error"];
                }

              } // passwort neu 1 und 2 gleich?

              else {
                $errorstring = "<p>password new (1) and new (2) not equal</p>\n\n";
              }

            } // password error

            else {
              $errorstring = "<p>password error</p>\n\n";
            }

          } // password in POST

// *****************************************************************************
// *** backend POST twofa ***
// *****************************************************************************

          elseif (isset($this->request["telegram_id"])) {
            // telegram_id und use_2fa in POST

            // str zu int
            $telegram_id = intval($this->request["telegram_id"]);

            $use_2fa = 0;	// default
            if (isset($this->request["use_2fa"])) {
              // überflüssige leerzeichen entfernen
              $use_2fa_str = trim($this->request["use_2fa"]);

              if ($use_2fa_str == "yes") {
                // checked
                $use_2fa = 1;
              }
              else {
                // unchecked
                $use_2fa = 0;
              }
            } // isset use_2fa

            // update in datenbank
            $ret = $this->model->update_twofa($telegram_id, $use_2fa);
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // telegram_id und use_2fa in POST

// *****************************************************************************
// *** backend POST home ***
// *****************************************************************************

          elseif (isset($this->request["ba_home"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_home[ba_id] => ba_text

            $model_home = new Home();	// model erstellen
            $ba_home_array = $this->request["ba_home"];
            $ba_home_array_replaced = array();

            foreach ($ba_home_array as $ba_id => $ba_text) {
              $ba_text = trim($ba_text);	// überflüssige leerzeichen entfernen

              // zeichen limit
              if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_HOMETEXT) {
                $ba_text = mb_substr($ba_text, 0, MAXLEN_HOMETEXT, MB_ENCODING);
              }

              $ba_home_array_replaced[$ba_id] = $ba_text;
            }

            $ret = $model_home->postHome($ba_home_array_replaced);	// daten für home in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_home[ba_id]

// *****************************************************************************
// *** backend POST profile ***
// *****************************************************************************

          elseif (isset($this->request["ba_profile"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_profile[ba_id][ba_tag, ba_text]

            $model_profil = new Profil();	// model erstellen
            $ba_profile_array = $this->request["ba_profile"];
            $ba_profile_array_replaced = array();

            foreach ($ba_profile_array as $ba_id => $ba_array) {
              $ba_tag = trim($ba_array["ba_tag"]);	// überflüssige leerzeichen entfernen
              $ba_text = trim($ba_array["ba_text"]);

              // zeichen limit
              if (mb_strlen($ba_tag, MB_ENCODING) > MAXLEN_PROFILETAG) {
                $ba_text = mb_substr($ba_tag, 0, MAXLEN_PROFILETAG, MB_ENCODING);
              }
              if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_PROFILETEXT) {
                $ba_text = mb_substr($ba_text, 0, MAXLEN_PROFILETEXT, MB_ENCODING);
              }

              $ba_profile_array_replaced[$ba_id] = array("ba_tag" => $ba_tag, "ba_text" => $ba_text);
            }

            $ret = $model_profil->postProfil($ba_profile_array_replaced);	// daten für profile in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_profile[ba_id][ba_tag, ba_text]

// *****************************************************************************
// *** backend POST galerie (neu) ***
// *****************************************************************************

          elseif (isset($this->request["ba_gallery_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_gallery_new[ba_alias]		MAXLEN_GALLERYALIAS
            // ba_gallery_new[ba_text]		MAXLEN_GALLERYTEXT
            // ba_gallery_new[ba_order]		ASC DESC

            $model_fotos = new Fotos();	// model erstellen
            $ba_gallery_new_array = $this->request["ba_gallery_new"];

            // überflüssige leerzeichen entfernen
            $ba_alias = trim($ba_gallery_new_array["ba_alias"]);
            $ba_text = trim($ba_gallery_new_array["ba_text"]);
            $ba_order = trim($ba_gallery_new_array["ba_order"]);

            // zeichen limit
            if (strlen($ba_alias) > MAXLEN_GALLERYALIAS) {
              $ba_alias = substr($ba_alias, 0, MAXLEN_GALLERYALIAS);
            }

            // zeichen limit
            if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_GALLERYTEXT) {
              $ba_text = mb_substr($ba_text, 0, MAXLEN_GALLERYTEXT, MB_ENCODING);
            }

            // nur ASC oder DESC, sonst ASC
            $ba_order = strtoupper($ba_order);
            if ($ba_order != "ASC" and $ba_order != "DESC") {
              $ba_order = "ASC";
            }

            $ret = $model_fotos->postGalleryNew($ba_alias, $ba_text, $ba_order);	// daten für galerie (neu) in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_gallery_new[ba_text]

// *****************************************************************************
// *** backend POST galerie ***
// *****************************************************************************

          elseif (isset($this->request["ba_gallery"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_gallery[ba_id][ba_alias]	MAXLEN_GALLERYALIAS
            // ba_gallery[ba_id][ba_text]	MAXLEN_GALLERYTEXT
            // ba_gallery[ba_id][ba_order]	ASC DESC
            // ba_gallery[ba_id]["delete"]

            $model_fotos = new Fotos();	// model erstellen
            $ba_gallery_array = $this->request["ba_gallery"];
            $ba_gallery_array_replaced = array();

            foreach ($ba_gallery_array as $ba_id => $ba_array) {
              // überflüssige leerzeichen entfernen, str zu int
              $ba_id = intval($ba_id);
              $ba_alias = trim($ba_array["ba_alias"]);
              $ba_text = trim($ba_array["ba_text"]);
              $ba_order = trim($ba_array["ba_order"]);

              // zeichen limit
              if (strlen($ba_alias) > MAXLEN_GALLERYALIAS) {
                $ba_alias = substr($ba_alias, 0, MAXLEN_GALLERYALIAS);
              }

              // zeichen limit
              if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_GALLERYTEXT) {
                $ba_text = mb_substr($ba_text, 0, MAXLEN_GALLERYTEXT, MB_ENCODING);
              }

              // nur ASC oder DESC, sonst ASC
              $ba_order = strtoupper($ba_order);
              if ($ba_order != "ASC" and $ba_order != "DESC") {
                $ba_order = "ASC";
              }

              $ba_delete = in_array("delete", $ba_array);	// in array nach string suchen

              $ba_gallery_array_replaced[$ba_id] = array("ba_alias" => $ba_alias, "ba_text" => $ba_text, "ba_order" => $ba_order, "delete" => $ba_delete);
            }

            $ret = $model_fotos->postGallery($ba_gallery_array_replaced);	// daten für galerie in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_gallery[ba_id][ba_text]

// *****************************************************************************
// *** backend POST fotos (neu) ***
// *****************************************************************************

          elseif (isset($this->request["ba_fotos_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_fotos_new[ba_galleryid]
            // ba_fotos_new[ba_fotoid]		MAXLEN_FOTOID
            // ba_fotos_new[ba_text]		MAXLEN_FOTOTEXT
            // ba_fotos_new["sperrlist"]
            // ba_fotos_new["hide"]

            $model_fotos = new Fotos();	// model erstellen
            $ba_fotos_new_array = $this->request["ba_fotos_new"];

            // überflüssige leerzeichen entfernen, str zu int
            $ba_galleryid = intval($ba_fotos_new_array["ba_galleryid"]);
            $ba_fotoid = trim($ba_fotos_new_array["ba_fotoid"]);
            $ba_text = trim($ba_fotos_new_array["ba_text"]);

            // zeichen limit
            if (strlen($ba_fotoid) > MAXLEN_FOTOID) {
              $ba_fotoid = substr($ba_fotoid, 0, MAXLEN_FOTOID);
            }
            if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_FOTOTEXT) {
              $ba_text = mb_substr($ba_text, 0, MAXLEN_FOTOTEXT, MB_ENCODING);
            }

            $ba_sperrlist = 0;
            $ba_hide = 0;
            if (in_array("sperrlist", $ba_fotos_new_array)) {	// in array nach string suchen
              $ba_sperrlist = 1;
            }
            if (in_array("hide", $ba_fotos_new_array)) {
              $ba_hide = 1;
            }

            $ret = $model_fotos->postFotosNew($ba_galleryid, $ba_fotoid, $ba_text, $ba_sperrlist, $ba_hide);	// daten für fotos (neu) in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_fotos_new[ba_text]

// *****************************************************************************
// *** backend POST fotos ***
// *****************************************************************************

          elseif (isset($this->request["ba_fotos"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_fotos[ba_id][ba_galleryid]
            // ba_fotos[ba_id][ba_fotoid]	MAXLEN_FOTOID
            // ba_fotos[ba_id][ba_text]		MAXLEN_FOTOTEXT
            // ba_fotos[ba_id]["sperrlist"]
            // ba_fotos[ba_id]["hide"]
            // ba_fotos[ba_id]["delete"]

            $model_fotos = new Fotos();	// model erstellen
            $ba_fotos_array = $this->request["ba_fotos"];
            $ba_fotos_array_replaced = array();

            foreach ($ba_fotos_array as $ba_id => $ba_array) {

              // überflüssige leerzeichen entfernen, str zu int
              $ba_id = intval($ba_id);
              $ba_galleryid = intval($ba_array["ba_galleryid"]);
              $ba_fotoid = trim($ba_array["ba_fotoid"]);
              $ba_text = trim($ba_array["ba_text"]);

              // zeichen limit
              if (strlen($ba_fotoid) > MAXLEN_FOTOID) {
                $ba_fotoid = substr($ba_fotoid, 0, MAXLEN_FOTOID);
              }
              if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_FOTOTEXT) {
                $ba_text = mb_substr($ba_text, 0, MAXLEN_FOTOTEXT, MB_ENCODING);
              }

              $ba_sperrlist = 0;
              $ba_hide = 0;
              $ba_delete = in_array("delete", $ba_array);	// in array nach string suchen
              if (in_array("sperrlist", $ba_array)) {
                $ba_sperrlist = 1;
              }
              if (in_array("hide", $ba_array)) {
                $ba_hide = 1;
              }

              $ba_fotos_array_replaced[$ba_id] = array("ba_galleryid" => $ba_galleryid, "ba_fotoid" => $ba_fotoid, "ba_text" => $ba_text, "sperrlist" => $ba_sperrlist, "hide" => $ba_hide, "delete" => $ba_delete);
            }

            $ret = $model_fotos->postFotos($ba_fotos_array_replaced);	// daten für fotos in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_fotos[ba_id][ba_text]

// *****************************************************************************
// *** backend POST blog ***
// *****************************************************************************

          elseif (isset($this->request["ba_blog"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_blog[ba_id, ba_userid, ba_date, ba_text, ba_videoid, ba_fotoid, ba_catid, ba_tags, ba_state, "delete"]
            // ba_id == 0 -> neuer blog eintrag
            // ba_id == 0xffff -> error

            $model_blog = new Blog();	// model erstellen
            $ba_blog_array = $this->request["ba_blog"];

            $ba_id = $ba_blog_array["ba_id"];
            $ba_userid = $ba_blog_array["ba_userid"];

            // überflüssige leerzeichen entfernen
            $ba_date = trim($ba_blog_array["ba_date"]);
            $ba_text = trim($ba_blog_array["ba_text"]);
            $ba_videoid = trim($ba_blog_array["ba_videoid"]);
            $ba_fotoid = trim($ba_blog_array["ba_fotoid"]);
            $ba_tags = trim($ba_blog_array["ba_tags"]);

            // str zu int
            $ba_catid = intval($ba_blog_array["ba_catid"]);
            $ba_state = intval($ba_blog_array["ba_state"]);

            // zeichen limit
            if (strlen($ba_date) > MAXLEN_BLOGDATE) {
              $ba_date = substr($ba_date, 0, MAXLEN_BLOGDATE);
            }
            if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_BLOGTEXT) {
              $ba_text = mb_substr($ba_text, 0, MAXLEN_BLOGTEXT, MB_ENCODING);
            }
            if (strlen($ba_videoid) > MAXLEN_BLOGVIDEOID) {
              $ba_videoid = substr($ba_videoid, 0, MAXLEN_BLOGVIDEOID);
            }
            if (strlen($ba_fotoid) > MAXLEN_BLOGFOTOID) {
              $ba_fotoid = substr($ba_fotoid, 0, MAXLEN_BLOGFOTOID);
            }
            if (mb_strlen($ba_tags, MB_ENCODING) > MAXLEN_BLOGTAGS) {
              $ba_tags = mb_substr($ba_tags, 0, MAXLEN_BLOGTAGS, MB_ENCODING);
            }

            // nur definierte states, sonst 0 (STATE_CREATED)
            $defined_states = array(STATE_CREATED, STATE_EDITED, STATE_APPROVAL, STATE_PUBLISHED);
            if (!in_array($ba_state, $defined_states)) {
              $ba_state = STATE_CREATED;
            }

            $ba_delete = in_array("delete", $ba_blog_array);	// in array nach string suchen

            $ret = $model_blog->postBlog($ba_id, $ba_userid, $ba_date, $ba_text, $ba_videoid, $ba_fotoid, $ba_catid, $ba_tags, $ba_state, $ba_delete);	// daten für blog in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_blog[]

// *****************************************************************************
// *** backend POST blogroll (neu) ***
// *****************************************************************************

          elseif (isset($this->request["ba_blogroll_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_blogroll_new[feed]

            $model_blog = new Blog();	// model erstellen
            $ba_blogroll_new_array = $this->request["ba_blogroll_new"];

            // überflüssige leerzeichen entfernen
            $feed = trim($ba_blogroll_new_array["feed"]);

            // zeichen limit
            if (strlen($feed) > MAXLEN_FEED) {
              $feed = substr($feed, 0, MAXLEN_FEED);
            }

            $ret = $model_blog->postBlogrollNew($feed);	// daten für blogroll (neu) in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_blogroll_new[]

// *****************************************************************************
// *** backend POST blogroll ***
// *****************************************************************************

          elseif (isset($this->request["ba_blogroll"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_blogroll[id]

            $model_blog = new Blog();	// model erstellen
            $ba_blogroll_array = $this->request["ba_blogroll"];
            $ret = $model_blog->postBlogroll($ba_blogroll_array);	// daten für blogroll in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_blogroll[]

// *****************************************************************************
// *** backend POST blogcategory (neu) ***
// *****************************************************************************

          elseif (isset($this->request["ba_blogcategory_new"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_blogcategory_new[category]

            $model_blog = new Blog();	// model erstellen
            $ba_blogcategory_new_array = $this->request["ba_blogcategory_new"];

            // überflüssige leerzeichen entfernen
            $category = trim($ba_blogcategory_new_array["category"]);

            // zeichen limit
            if (strlen($category) > MAXLEN_BLOGCATEGORY) {
              $category = substr($category, 0, MAXLEN_BLOGCATEGORY);
            }

            $ret = $model_blog->postBlogcategoryNew($category);	// daten für blogcategory (neu) in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_blogcategory_new[]

// *****************************************************************************
// *** backend POST blogcategory ***
// *****************************************************************************

          elseif (isset($this->request["ba_blogcategory"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_blogcategory[id]

            $model_blog = new Blog();	// model erstellen
            $ba_blogcategory_array = $this->request["ba_blogcategory"];
            $ret = $model_blog->postBlogcategory($ba_blogcategory_array);	// daten für blogcategory in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_blogcategory[]

// *****************************************************************************
// *** backend POST options ***
// *****************************************************************************

          elseif (isset($this->request["ba_options"]) and ($_SESSION["user_role"] >= ROLE_EDITOR)) {
            // ba_options[ba_name][ba_value]

            $model_blog = new Blog();	// model erstellen
            $ba_options_array = $this->request["ba_options"];
            $ba_options_array_replaced = array();

            foreach ($ba_options_array as $ba_name => $ba_value) {

              // überflüssige leerzeichen entfernen, str zu int
              $ba_name = trim($ba_name);
              $ba_value = intval($ba_value);

              $ba_options_array_replaced[$ba_name] = $ba_value;
            }

            $ret = $model_blog->postOptions($ba_options_array_replaced);	// daten für options in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_options[ba_name][ba_value]

// *****************************************************************************
// *** backend POST comment ***
// *****************************************************************************

          elseif (isset($this->request["ba_comment"]) and ($_SESSION["user_role"] >= ROLE_MASTER)) {
            // ba_comment[ba_id, ba_date, ba_ip, ba_name, ba_mail, ba_text, ba_comment, ba_blogid, ba_state, "delete"]
            // ba_id == 0 -> neuer kommentar eintrag
            // ba_id == 0xffff -> error

            $model_comment = new Comment();	// model erstellen
            $ba_comment_array = $this->request["ba_comment"];

            $ba_id = $ba_comment_array["ba_id"];

            // überflüssige leerzeichen entfernen, str zu int
            $ba_date = trim($ba_comment_array["ba_date"]);
            $ba_ip = trim($ba_comment_array["ba_ip"]);
            $ba_name = trim($ba_comment_array["ba_name"]);
            $ba_mail = trim($ba_comment_array["ba_mail"]);
            $ba_text = trim($ba_comment_array["ba_text"]);
            $ba_comment = trim($ba_comment_array["ba_comment"]);
            $ba_blogid = intval($ba_comment_array["ba_blogid"]);
            $ba_state = intval($ba_comment_array["ba_state"]);

            // zeichen limit
            if (strlen($ba_date) > MAXLEN_COMMENTDATE) {
              $ba_date = substr($ba_date, 0, MAXLEN_COMMENTDATE);
            }
            if (strlen($ba_ip) > MAXLEN_COMMENTIP) {
              $ba_ip = substr($ba_ip, 0, MAXLEN_COMMENTIP);
            }
            if (mb_strlen($ba_name, MB_ENCODING) > MAXLEN_COMMENTNAME) {
              $ba_name = mb_substr($ba_name, 0, MAXLEN_COMMENTNAME, MB_ENCODING);
            }
            if (strlen($ba_mail) > MAXLEN_COMMENTMAIL) {
              $ba_mail = substr($ba_mail, 0, MAXLEN_COMMENTMAIL);
            }
            if (mb_strlen($ba_text, MB_ENCODING) > MAXLEN_COMMENTTEXT) {
              $ba_text = mb_substr($ba_text, 0, MAXLEN_COMMENTTEXT, MB_ENCODING);
            }
            if (mb_strlen($ba_comment, MB_ENCODING) > MAXLEN_COMMENTCOMMENT) {
              $ba_comment = mb_substr($ba_comment, 0, MAXLEN_COMMENTCOMMENT, MB_ENCODING);
            }

            // nur definierte states, sonst 0 (STATE_CREATED)
            $defined_states = array(STATE_CREATED, STATE_EDITED, STATE_APPROVAL, STATE_PUBLISHED);
            if (!in_array($ba_state, $defined_states)) {
              $ba_state = STATE_CREATED;
            }

            $ba_delete = in_array("delete", $ba_comment_array);	// in array nach string suchen

            $ret = $model_comment->postComment($ba_id, $ba_date, $ba_ip, $ba_name, $ba_mail, $ba_text, $ba_comment, $ba_blogid, $ba_state, $ba_delete);	// daten für comment in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_comment[]

// *****************************************************************************
// *** backend POST upload ***
// *****************************************************************************

          elseif (isset($_FILES["upfile"]["tmp_name"]) and ($_FILES["upfile"]["type"]=="image/jpeg" or $_FILES["upfile"]["type"]=="video/mp4") and $_FILES["upfile"]["size"] < MAXSIZE_FILEUPLOAD and ($_SESSION["user_role"] >= ROLE_MASTER)) {
            // nur *.jpg oder *.mp4, 2 MB default

            $model_upload = new Upload();	// model erstellen
            $ret = $model_upload->postUpload($_FILES["upfile"]["tmp_name"], $_FILES["upfile"]["name"]);	// daten für upload in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          }

// *****************************************************************************
// *** backend POST admin (neu) ***
// *****************************************************************************

          elseif (isset($this->request["ba_admin_new"]) and ($_SESSION["user_role"] >= ROLE_ADMIN)) {
            // ba_admin_new[user]
            // ba_admin_new[email]
            // ba_admin_new[role]

            $model_admin = new Admin();	// model erstellen
            $ba_admin_new_array = $this->request["ba_admin_new"];

            // überflüssige leerzeichen entfernen, str zu int
            $user = trim($ba_admin_new_array["user"]);
            $email = trim($ba_admin_new_array["email"]);
            $role = intval($ba_admin_new_array["role"]);

            // zeichen limit
            if (mb_strlen($user, MB_ENCODING) > MAXLEN_USER) {
              $user = mb_substr($user, 0, MAXLEN_USER, MB_ENCODING);
            }
            if (strlen($email) > MAXLEN_EMAIL) {
              $email = substr($email, 0, MAXLEN_EMAIL);
            }

            // größe limit
            if ($role > ROLE_ADMIN or $role < ROLE_NONE) {
              $role = ROLE_NONE;
            }

            // automatisches passwort für email benachrichtigung
            $tmp_password = $this->gen_password();

            $ret = $model_admin->postAdminNew($user, $email, $role, $tmp_password);	// daten für admin (neu) in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_admin_new[]

// *****************************************************************************
// *** backend POST admin ***
// *****************************************************************************

          elseif (isset($this->request["ba_admin"]) and ($_SESSION["user_role"] >= ROLE_ADMIN)) {
            // ba_admin[id][0] = role
            // ba_admin[id][1] = "delete"

            $model_admin = new Admin();	// model erstellen
            $ba_admin_array = $this->request["ba_admin"];
            $ba_admin_array_replaced = array();

            foreach ($ba_admin_array as $id => $ba_array) {
              $role = intval($ba_array[0]);	// str zu int

              // größe limit
              if ($role > ROLE_ADMIN or $role < ROLE_NONE) {
                $role = ROLE_NONE;
              }

              $delete = in_array("delete", $ba_array);	// in array nach string suchen

              $ba_admin_array_replaced[$id] = array($role, $delete);
            }

            $ret = $model_admin->postAdmin($ba_admin_array_replaced);	// daten für admin in das model
            $html_backend_ext .= $ret["inhalt"];
            $errorstring = $ret["error"];

          } // ba_admin[id][]

          else {
            $errorstring = "<p>POST error</p>\n\n";
          }

        } // POST

      } // else login

    } // no panic

    if (DEBUG) { $debug_str .= "</p>\n"; }

    // setze inhalt, falls string vorhanden, sonst leer
    $view->setContent("inhalt", isset($html_backend_ext) ? $html_backend_ext : "");
    $view->setContent("error", isset($errorstring) ? $errorstring : "");
    $view->setContent("debug", $debug_str);

    return $view->parseTemplate(true);	// ausgabe geändertes template, mit backend flag
  }

}

?>
