<?php

// *****************************************************************************
// *** define ***
// *****************************************************************************

define("AUTHORIZATION_CODE","andreas-alpha-0815");
define("LOGIN_TIME",600);	// zeit in s login uid cookie
define("MAXLEN_USER",32);	// login form
define("MAXLEN_PASSWORD",32);	// login form
define("MAXLEN_CODE",8);	// login form
define("MAXLEN_TELEGRAM_ID",10);	// "4294967295" 32 bit unsigned integer
define("MAXLEN_EMAIL",64);	// admin form
define("MAXLEN_FULLNAME",64);	// admin form
define("MAXLEN_HOMETEXT",256);	// aus TABLE VARCHAR(xx) , für zeichen limit
define("MAXLEN_PROFILETAG",64);
define("MAXLEN_PROFILETEXT",256);
define("MAXLEN_GALLERYALIAS",16);
define("MAXLEN_GALLERYTEXT",64);
define("MAXLEN_FOTOID",8);
define("MAXLEN_FOTOTEXT",64);
define("MAXLEN_BLOGDATE",32);
define("MAXLEN_BLOGTEXT",8192);
define("MAXLEN_BLOGVIDEOID",32);
define("MAXLEN_BLOGFOTOID",128);
define("MAXLEN_BLOGTAGS",128);
define("MAXLEN_BLOGCATEGORY",32);
define("MAXLEN_FEED",128);	// blogroll
define("MAXLEN_COMMENTDATE",20);
define("MAXLEN_COMMENTIP",48);
define("MAXLEN_COMMENTNAME",64);
define("MAXLEN_COMMENTMAIL",64);
define("MAXLEN_COMMENTBLOGID",8);
define("MAXLEN_COMMENTTEXT",2048);
define("MAXLEN_COMMENTCOMMENT",2048);
define("MAXSIZE_FILEUPLOAD",2097152);	// 2048*1024 (2 MB default)
define("ROLE_NONE",0);
define("ROLE_EDITOR",1);
define("ROLE_MASTER",2);
define("ROLE_ADMIN",3);
define("STATE_CREATED",0);
define("STATE_EDITED",1);
define("STATE_APPROVAL",2);
define("STATE_PUBLISHED",3);
define("MB_ENCODING","UTF-8");
define("DEBUG",false);
define("DEBUG_STR","<section>\n<p>debug:\n");

?>
