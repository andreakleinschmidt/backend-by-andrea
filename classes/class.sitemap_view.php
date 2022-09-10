<?php

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2022 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
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
// * sitemap view
// * - template laden
// * - daten einfügen
// * - datenausgabe in xml
// *****************************************************************************

class View {

  private $template;	// name des templates (hier "tpl_sitemap.xml")
  private $content_arr = array();	// array mit variablen (urlset, error) für das template

  // template auswählen
  public function setTemplate() {
    $this->template = DEFAULT_XML_TEMPLATE;
  }

  // daten in array speichern
  public function setContent($key, $value) {
    $this->content_arr[$key] = $value;
  }

  // DOM tag als child in parent einfügen
  private function addTag($xml_tree, $parent, $tagname, $value=NULL) {
    $node = $xml_tree->createElement($tagname);
    $node = $parent->appendChild($node);
    if (isset($value)) {
      $node->nodeValue = $value;
    }
    return $node;
  }

  // template laden, enthält urlset und error, daten ersetzen, template ausgeben
  public function parseTemplate() {
    if (file_exists($this->template)) {

      $xml_tree = new DOMDocument();
      $xml_tree->formatOutput = true;
      $xml_tree->preserveWhiteSpace = false;
      $xml_tree->load($this->template);
      $root_node = $xml_tree->documentElement;

      if ($root_node->tagName == "urlset") {

        $urlset = $this->content_arr["urlset"];
        // $urlset = array("url" => array(3))
        // array(3) => array("loc" => $loc,
        //                   "lastmod" = $lastmod,
        //                   "changefreq" => $changefreq)

        // <url></url>
        foreach ($urlset["url"] as $url) {
          $url_node = $this->addTag($xml_tree, $root_node, "url");

          // <loc></loc>
          $server_protocol = !empty($_SERVER["HTTPS"]) ? "https://" : "http://";	// https wenn server parameter nicht leer, sonst http
          $loc = $server_protocol.$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"])."/".$url["loc"];
          $this->addTag($xml_tree, $url_node, "loc", $loc);

          //<lastmod></lastmod>
          $this->addTag($xml_tree, $url_node, "lastmod", $url["lastmod"]);

          //<changefreq></changefreq>
          $this->addTag($xml_tree, $url_node, "changefreq", $url["changefreq"]);

        } // foreach url

        $errorstring = $this->content_arr["error"];
        if (strlen($errorstring) > 0) {

          $xml_tree->createComment(stripslashes($this->xmlspecialchars($errorstring)));

        } // if errorstring

      } // if xml feed

      return $xml_tree->saveXML();	// geändertes template zurückgeben

    }
    // Template-File existiert nicht-> Fehlermeldung
    return "could not find template ".$this->template;

  }

}

?>
