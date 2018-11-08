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

define("MAXLEN_COMMENTNAME",64);
define("MAXLEN_COMMENTMAIL",64);
define("MAXLEN_COMMENTURL",128);
define("MAXLEN_COMMENTTEXT",2048);
define("ACTION_HOME","home");
define("ACTION_PROFILE","profile");
define("ACTION_PHOTOS","photos");
define("ACTION_BLOG","blog");
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
define("FEED_PNG","feed-icon-14x14.png");	// feed.png
define("DEBUG",false);

?>
