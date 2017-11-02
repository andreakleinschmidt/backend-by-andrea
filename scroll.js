var div_id = "fotoleiste";
var offset = 0;
var speed = 50;
var step = 10;
var i;

function scrolloffset() {
  if (document.cookie) {
    var c = document.cookie;
    var a = c.split(";");
    for (i=0; i<a.length; i++) {
      o = a[i];
      if (o.includes("offset")) {
        offset = o.slice(o.indexOf("=")+1,o.length);
      }
    }
  }
  document.getElementById(div_id).scrollTop=offset;
  scroll_mwheel_init(); // add mouse wheel function
}

function scrolldown() {
  i = setInterval("document.getElementById(div_id).scrollTop+=step",speed);
}

function scrollup() {
  i = setInterval("document.getElementById(div_id).scrollTop-=step",speed);
}

function scrollstop() {
  clearInterval(i);
  document.cookie = "offset=" + document.getElementById(div_id).scrollTop;
}

function mwheel(event){
  if (!event) {
    event = window.event; // IE
  }
  if (event.wheelDelta) {
    scrolling = -event.wheelDelta; // IE, +up -down
  }
  else if (event.detail) {
    scrolling = event.detail; // mozilla, +down -up
  }
  // prevent default:
  if (event.preventDefault) {
    event.preventDefault();
    event.returnValue = false;
  }
  if (scrolling > 0) {
    document.getElementById(div_id).scrollTop+=2*step;
  }
  else if (scrolling < 0) {
    document.getElementById(div_id).scrollTop-=2*step;
  }
  document.cookie = "offset=" + document.getElementById(div_id).scrollTop;
}

function scroll_mwheel_init() {
  // mozilla: element.addEventListener
  if (document.getElementById(div_id).addEventListener) {
    document.getElementById(div_id).addEventListener("DOMMouseScroll", mwheel, false);
  }
  // IE: element.onmousewheel
  document.getElementById(div_id).onmousewheel = mwheel;
}

