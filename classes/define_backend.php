<?php

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("LOGIN_TIME",600);	// zeit in s login uid cookie
define("MAXLEN_USER",32);	// login form
define("MAXLEN_PASSWORD",32);	// login form
define("MAXLEN_CODE",8);	// login form
define("MAXLEN_SHAREDSECRET",64);
define("MAXLEN_EMAIL",64);	// admin form
define("MAXLEN_FULLNAME",64);	// admin form
define("MAXLEN_BASETITLE",32);
define("MAXLEN_BASELINKS",256);
define("MAXLEN_HPELEMENT",16);
define("MAXLEN_HPCSS",32);
define("MAXLEN_HPVALUE",1024);
define("MAXLEN_PROFILETABLENAME",32);
define("MAXLEN_PROFILELANGUAGE",2);
define("MAXLEN_PROFILEVALUE",256);
define("MAXLEN_GALLERYALIAS",16);
define("MAXLEN_GALLERYTEXT",64);
define("MAXLEN_PHOTOID",8);
define("MAXLEN_PHOTOTEXT",64);
define("MAXLEN_DATETIME",20);
define("MAXLEN_BLOGHEADER",128);
define("MAXLEN_BLOGINTRO",1024);
define("MAXLEN_BLOGTEXT",11264);
define("MAXLEN_BLOGVIDEOID",32);
define("MAXLEN_BLOGPHOTOID",128);
define("MAXLEN_BLOGTAGS",128);
define("MAXLEN_BLOGCATEGORY",32);
define("MAXLEN_FEED",128);	// blogroll
define("MAXLEN_OPTIONSTR",64);
define("MAXLEN_COMMENTIP",48);
define("MAXLEN_COMMENTNAME",64);
define("MAXLEN_COMMENTMAIL",64);
define("MAXLEN_COMMENTBLOGID",8);
define("MAXLEN_COMMENTTEXT",2048);
define("MAXLEN_COMMENTCOMMENT",2048);
define("MAXSIZE_FILEUPLOAD",2097152);	// 2048*1024 (2 MB default)
define("MAXLEN_LOCALE",8);
define("ACTION_BASE","base");
define("ACTION_HOME","home");
define("ACTION_PROFILE","profile");
define("ACTION_PHOTOS","photos");
define("ACTION_BLOG","blog");
define("ACTION_COMMENT","comment");
define("ACTION_UPLOAD","upload");
define("ACTION_LANGUAGES","lang");
define("ACTION_ADMIN","admin");
define("ACTION_PASSWORD","password");
define("ACTION_LOGOUT","logout");
define("ROLE_NONE",0);
define("ROLE_EDITOR",1);
define("ROLE_MASTER",2);
define("ROLE_ADMIN",3);
define("STATE_CREATED",0);
define("STATE_EDITED",1);
define("STATE_APPROVAL",2);
define("STATE_PUBLISHED",3);
define("ELEMENT_IMAGE","image");
define("ELEMENT_PARAGRAPH","paragraph");
define("ELEMENT_TABLE_H","table+h");
define("ELEMENT_TABLE","table");
define("MB_ENCODING","UTF-8");
define("DEFAULT_LOCALE","de-DE");	// "de-DE" oder "en-US"
define("DEBUG",false);
define("DEBUG_STR","<section>\n<p>debug:\n");
define("DEBUG_STR_END","</p>\n</section>\n");

?>
