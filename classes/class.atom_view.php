<?php

// *****************************************************************************
// * atom view
// * - template laden
// * - daten einfügen
// * - datenausgabe in xml
// *****************************************************************************

class View {

  private $template;	// name des templates (hier "default" oder "atom")
  private $content_arr = array();	// array mit variablen (feed, error) für das template

  // template auswählen
  public function setTemplate($template = "default") {
    $this->template = "tpl_".$template.".xml";
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

  // template laden, enthält feed und error, daten ersetzen, template ausgeben
  public function parseTemplate() {
    if (file_exists($this->template)) {

      $xml_tree = new DOMDocument();
      $xml_tree->formatOutput = true;
      $xml_tree->preserveWhiteSpace = false;
      $xml_tree->load($this->template);
      $root_node = $xml_tree->documentElement;

      if ($root_node->tagName == "feed") {

        $feed = $this->content_arr["feed"];
        // $feed = array("updated" => $atomupdated, "entry" => array(20))
        // array(20) => array("title" => $atomtitle,
        //                    "link" => $atomlink,
        //                    "id" => $atomid,
        //                    "updated" => $atomupdated,
        //                    "summary" => $atomsummary,
        //                    "content" => $atomcontent)

        // <updated></updated>
        $this->addTag($xml_tree, $root_node, "updated", $feed["updated"]);

        // <entry></entry>
        foreach ($feed["entry"] as $entry) {
          $entry_node = $this->addTag($xml_tree, $root_node, "entry");

          // <title type="text"></title>
          $node = $this->addTag($xml_tree, $entry_node, "title", $entry["title"]);
          $node->setAttribute("type", "text");

          // <link href=""/>
          $node = $this->addTag($xml_tree, $entry_node, "link");
          $node->setAttribute("href", $entry["link"]);

          // <id></id>
          $this->addTag($xml_tree, $entry_node, "id", $entry["id"]);

          // <updated></updated>
          $this->addTag($xml_tree, $entry_node, "updated", $entry["updated"]);

          // <summary type="text"></summary>
          if (strlen($entry["summary"]) > 0) {
            $node = $this->addTag($xml_tree, $entry_node, "summary", $entry["summary"]);
            $node->setAttribute("type", "html");
          }

          // <content type="text"></content>
          if (strlen($entry["content"]) > 0) {
            $node = $this->addTag($xml_tree, $entry_node, "content", $entry["content"]);
            $node->setAttribute("type", "html");
          }

        } // foreach entry

        $errorstring = $this->content_arr["error"];
        if (strlen($errorstring) > 0) {

          // <entry></entry>
          $entry_node = $this->addTag($xml_tree, $root_node, "entry");

          // <title type="text"></title>
          $node = $this->addTag($xml_tree, $entry_node, "title", "error");
          $node->setAttribute("type", "text");

          // <content type="text"></content>
          $node = $this->addTag($xml_tree, $entry_node, "content", $errorstring);
          $node->setAttribute("type", "text");

        } // if errorstring

      } // if xml feed

      return $xml_tree->saveXML();	// geändertes template zurückgeben

    }
    // Template-File existiert nicht-> Fehlermeldung
    return "could not find template ".$this->template;

  }

}

?>
