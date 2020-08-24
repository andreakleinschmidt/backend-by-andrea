function insert_tag(textarea_id,button_id) {
  var textarea = document.getElementById(textarea_id);
  textarea.focus();
  // supported browsers only
  if (typeof textarea.selectionStart == "undefied") {
    alert("browser not supported");
  }
  else {
    // get tag
    var tag_start;
    var tag_end;
    switch(button_id) {
      case "bold":
        tag_start = "~bold{";
        tag_end = "}";
        break;
      case "italic":
        tag_start = "~italic{";
        tag_end = "}";
        break;
      case "monospace":
        tag_start = "~monospace{";
        tag_end = "}";
        break;
      case "link":
        tag_start = "~link{";
        tag_end = "|...}";
        break;
      case "list":
        tag_start = "~list{";
        tag_end = "|...}";
        break;
      case "image":
        tag_start = "~image{";
        tag_end = "}";
        break;
      default:
        tag_start = "";
        tag_end = "";
    }
    // get selection
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var selected_text = textarea.value.substring(start, end); // substring(from, to)
    // insert format tag
    textarea.value = textarea.value.substr(0, start) + tag_start + selected_text + tag_end + textarea.value.substr(end); // substr(from, length)
    // change cursor position
    var cursor_pos;
    if (selected_text.length == 0) {
      cursor_pos = start + tag_start.length;
    }
    else {
      cursor_pos = start + tag_start.length + selected_text.length; // + tag_end.length;
    }
    textarea.selectionStart = cursor_pos;
    textarea.selectionEnd = cursor_pos;
  }
}
