<?php
session_start();
require_once("phpqrcode.php");
if (isset($_SESSION["login_random_pwd"])) {
  QRcode::png($_SESSION["login_random_pwd"]);
}
?>
