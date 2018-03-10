<?php
// This file is part of 'backend by andrea'
// CMS & blog software with frontend / backend
require_once("phpqrcode.php");
if (isset($_REQUEST["data"])) {
  QRcode::png($_REQUEST["data"]);
}
?>
