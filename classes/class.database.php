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
