<?php
require_once("phpqrcode.php");
if (isset($_REQUEST["data"])) {
  QRcode::png($_REQUEST["data"]);
}
?>
