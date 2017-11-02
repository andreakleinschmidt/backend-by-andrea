<?php

// *****************************************************************************
// * !!! kontakt zur datenbank !!!
// *****************************************************************************

define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "botanik101");
define("DB_NAME", "backend_db");

//define("DB_HOST", "db2226.1und1.de");
//define("DB_USER", "dbo311969690");
//define("DB_PASS", "botanik101");
//define("DB_NAME", "db311969690");

class Database extends mysqli {

  private $hostname;
  private $username;
  private $password;
  private $database;

  // konstruktor
  public function __construct() {
    $this->hostname = DB_HOST;
    $this->username = DB_USER;
    $this->password = DB_PASS;
    $this->database = DB_NAME;

    parent::__construct($this->hostname, $this->username, $this->password, $this->database);
  }

}

?>
