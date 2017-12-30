<?php

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
  private $content_arr = array();	// array mit variablen (hd_title_ext, login, content, error) für das template

  // template auswählen
  public function setTemplate($template = "default") {
    $this->template = "tpl_".$template.".htm";
  }

  // daten in array speichern
  public function setContent($key, $value) {
    $this->content_arr[$key] = $value;
  }

  // template laden, enthält {hd_title_ext}, {login}, {content} und {error}, daten ersetzen, template ausgeben
  public function parseTemplate($backend = false) {
    if (file_exists($this->template)) {

      $handle = fopen ($this->template, "r");	// nur lesen
      $template_out = fread($handle, filesize($this->template));
      fclose ($handle);

      $errorstring = "".$this->content_arr["error"];

      if ($backend) {
        $debug_str = "".$this->content_arr["debug"];
        $template_out = str_replace("{content}", $this->content_arr["content"], $template_out);
        $template_out = str_replace("{error}", $errorstring, $template_out);
        $template_out = str_replace("{debug}", $debug_str, $template_out);
      }
      else {
        $template_out = str_replace("{hd_title_ext}", $this->content_arr["hd_title_ext"], $template_out);
        $template_out = str_replace("{login}", $this->content_arr["login"], $template_out);
        $template_out = str_replace("{content}", $this->content_arr["content"], $template_out);
        $template_out = str_replace("{error}", $errorstring, $template_out);
      }

      return $template_out;      // geändertes template zurückgeben
    }
    // Template-File existiert nicht-> Fehlermeldung
    return "could not find template ".$this->template;

  }

}

?>
