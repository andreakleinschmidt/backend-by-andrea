<?php

// *****************************************************************************
// * view
// * - template laden
// * - parsen des templates
// * - mit daten ersetzen
// * - str_replace($suchmuster_was, $ersetzung_womit, $zeichenkette_wo);
// * - datenausgabe in html
// * tpl_backend.htm
// * - in backend.js rsa verschlüsselung mit public key , C = K^puk mod rsa
// *****************************************************************************

class View {

  private $template;	// name des templates (hier "default" oder "backend")
  private $content = array();	// array mit variablen (hd_titel, login, inhalt, error) für das template

  // template auswählen
  public function setTemplate($template = "default") {
    $this->template = "tpl_".$template.".htm";
  }

  // daten in array speichern
  public function setContent($key, $value) {
    $this->content[$key] = $value;
  }

  // template laden, enthält {hd_titel}, {login}, {inhalt} und {error}, daten ersetzen, template ausgeben
  public function parseTemplate($backend = false) {
    if (file_exists($this->template)) {

      $handle = fopen ($this->template, "r");	// nur lesen
      $template_out = fread($handle, filesize($this->template));
      fclose ($handle);

      $errorstring = "".$this->content["error"];

      if ($backend) {
        $debug_str = "".$this->content["debug"];
        $template_out = str_replace("{inhalt}", $this->content["inhalt"], $template_out);
        $template_out = str_replace("{error}", $errorstring, $template_out);
        $template_out = str_replace("{debug}", $debug_str, $template_out);
      }
      else {
        $hd_title_str = "morgana81".$this->content["hd_titel"];
        $template_out = str_replace("{hd_titel}", $hd_title_str, $template_out);
        $template_out = str_replace("{login}", $this->content["login"], $template_out);
        $template_out = str_replace("{inhalt}", $this->content["inhalt"], $template_out);
        $template_out = str_replace("{error}", $errorstring, $template_out);
      }

      return $template_out;      // geändertes template zurückgeben
    }
    // Template-File existiert nicht-> Fehlermeldung
    return "could not find template ".$this->template;

  }

}

?>
