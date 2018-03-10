<?php
// This file is part of 'backend by andrea'
// CMS & blog software with frontend / backend
// extract thumbnail jpg or png from exif data
$thumbnail = exif_thumbnail($_REQUEST["image"], $width, $height, $type);
$mime_type = image_type_to_mime_type($type);
if ($thumbnail and ($mime_type == "image/jpeg" or $mime_type == "image/png")) {
  header("Content-Type: ".$mime_type);
  echo $thumbnail;
}
?>
