function suggest(query) {
  if (query.length > 0) {
    //alert(query);
    var xmlHttp = null;
    try {
      xmlHttp = new XMLHttpRequest();
    } catch(e) {
      alert("no ajax");
    }
    if (xmlHttp) {
      var url = "suggest.php?q="+query+"&suggest="+Math.floor((Math.random()*1000)+1);
      xmlHttp.open("GET", url, true);
      xmlHttp.onreadystatechange = function () {
        if (xmlHttp.readyState == 4) {
          //alert(xmlHttp.responseText);
          document.getElementById("suggestion").innerHTML = xmlHttp.responseText;	// ausgabe
        }
      };
      xmlHttp.send(null);      
    }
  }
  else {
    document.getElementById("suggestion").innerHTML = "";
  }
}
