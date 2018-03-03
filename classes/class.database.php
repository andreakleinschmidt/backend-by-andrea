<?php

// *****************************************************************************
// * !!! kontakt zur datenbank !!!
// *****************************************************************************

define("DATABASE_INI","config/database.ini");	// pfad zur ini-datei mit login-daten für datenbank
// *** database.ini ***
// [database]
// host = ""
// user = ""
// pass = ""
// name = ""
// aes_key = ""
// ; create aes key with shell: openssl rand -hex 16
// ; store ini file in a safe space (better outside www-root)

class Database extends mysqli {

  private $hostname;
  private $username;
  private $password;
  private $database;

  public static $AES_KEY;

  // konstruktor
  public function __construct() {
    $database_ini_array = parse_ini_file(DATABASE_INI);

    $this->hostname = $database_ini_array["host"];
    $this->username = $database_ini_array["user"];
    $this->password = $database_ini_array["pass"];
    $this->database = $database_ini_array["name"];

    self::$AES_KEY = $database_ini_array["aes_key"];

    parent::__construct($this->hostname, $this->username, $this->password, $this->database);
  }

}

?>
