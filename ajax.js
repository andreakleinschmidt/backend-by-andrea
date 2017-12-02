function ajax(id,content) {
  if ((id.length > 0) && (content.length > 0)) {
    //alert(id+": "+content);
    var xmlHttp = null;
    try {
      xmlHttp = new XMLHttpRequest();
    } catch(e) {
      alert("no ajax");
    }
    if (xmlHttp) {
      var randnum = Math.floor((Math.random()*1000)+1);
      switch (id) {
        case "suggest":
          var url = "ajax.php?id="+id+"&q="+content+"&request="+randnum;
          var elementid = "suggestion";
          break;
        case "blogfoto":
          var url = "ajax.php?id="+id+"&fotoid="+content+"&request="+randnum;
          var elementid = "foto_"+content;
          break;
        default:
          var url = "";
          var elementid = "";
      }
      xmlHttp.open("GET", url, true);
      xmlHttp.onreadystatechange = function () {
        if (xmlHttp.readyState == 4) {
          //alert(xmlHttp.responseText);
          document.getElementById(elementid).innerHTML = xmlHttp.responseText;	// ausgabe
        }
      };
      xmlHttp.send(null);
    }
  }
  else {
    document.getElementById(elementid).innerHTML = "";
  }
}
