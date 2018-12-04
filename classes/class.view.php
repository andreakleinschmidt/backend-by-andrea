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
// * view
// * - template laden
// * - parsen des templates
// * - mit daten ersetzen
// * - str_replace($suchmuster_was, $ersetzung_womit, $zeichenkette_wo);
// * - datenausgabe in html
// *****************************************************************************

class View {

  private $template;	// name des templates (hier "default" oder "backend")
  private $content_arr = array();	// array mit variablen (hd_title, menue, login, content, error, footer) für das template

  // template auswählen
  public function setTemplate($template = "default") {
    $this->template = "tpl_".$template.".htm";
  }

  // daten in array speichern
  public function setContent($key, $value) {
    $this->content_arr[$key] = $value;
  }

  // template laden, enthält {hd_title}, {hd_description}, {hd_author}, {feed_title}, {menue}, {login}, {content}, {error} und {footer}, daten ersetzen, template ausgeben
  public function parseTemplate($backend = false) {
    if (file_exists($this->template)) {

      $handle = fopen($this->template, "r");	// nur lesen
      $template_out = fread($handle, filesize($this->template));
      fclose($handle);

      $errorstring = "";
      if (array_key_exists("error", $this->content_arr)) {
        if (strlen($this->content_arr["error"]) > 0) {
          $errorstring = "".$this->content_arr["error"];
          $errorstring = "<p>".nl2br($errorstring)."</p>\n\n";
        }
      }

      $debug_str = "";
      if (array_key_exists("debug", $this->content_arr)) {
        if (strlen($this->content_arr["debug"]) > 0) {
          $debug_str = "debug:\n".$this->content_arr["debug"];
          $debug_str = "<p>".nl2br($debug_str)."</p>\n\n";
        }
      }

      if ($backend) {
        $template_out = str_replace("{content}", $this->content_arr["content"], $template_out);
        $template_out = str_replace("{error}", $errorstring, $template_out);
        $template_out = str_replace("{debug}", $debug_str, $template_out);
      }
      else {
        $template_out = str_replace("{language_code}", $this->content_arr["language_code"], $template_out);
        $template_out = str_replace("{hd_title}", $this->content_arr["hd_title"], $template_out);
        $template_out = str_replace("{hd_description}", $this->content_arr["hd_description"], $template_out);
        $template_out = str_replace("{hd_author}", $this->content_arr["hd_author"], $template_out);
        $template_out = str_replace("{feed_title}", $this->content_arr["feed_title"], $template_out);
        $template_out = str_replace("{menue}", $this->content_arr["menue"], $template_out);
        $template_out = str_replace("{login}", $this->content_arr["login"], $template_out);
        $template_out = str_replace("{content}", $this->content_arr["content"], $template_out);
        $template_out = str_replace("{footer}", $this->content_arr["footer"], $template_out);
        $template_out = str_replace("{error}", $errorstring, $template_out);
      }

      return $template_out;      // geändertes template zurückgeben
    }
    // Template-File existiert nicht-> Fehlermeldung
    return "could not find template ".$this->template;

  }

}

?>
