<?php

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2018 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
 *
 * This program includes a MERGED version of PHP QR Code library
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
define("MAXLEN_BASEDESCRIPTION",128);
define("MAXLEN_BASEAUTHOR",64);
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
