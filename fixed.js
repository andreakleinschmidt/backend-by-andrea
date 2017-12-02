// fixed position nav
window.onscroll = function() {
  toggle_nav_class_attribute()
};

var nav = document.getElementsByTagName("nav")[0];	// get <nav>
var nav_offset_top = nav.offsetTop;	// get <nav> offset top position

// toggle <nav> class attribute between "fixed" (css position:fixed) and default (no class - css position:static)
function toggle_nav_class_attribute() {
  if (window.pageYOffset >= nav_offset_top) {
    nav.classList.add("fixed");		// <nav> reached top scroll position
  }
  else {
    nav.classList.remove("fixed");	// <nav> not at top scroll position
  }
}

