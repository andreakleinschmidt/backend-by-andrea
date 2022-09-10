<?php
header("Content-Type: text/xml; charset=utf-8");

/*
 * This file is part of 'backend by andrea'
 * 'backend
 *      by andrea'
 *
 * CMS & blog software with frontend / backend
 *
 * This program is distributed under GNU GPL 3
 * Copyright (C) 2010-2022 Andrea Kleinschmidt <ak81 at oscilloworld dot de>
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
// * sitemap.php
// * - klassen einbinden
// * - controller aufrufen
// *****************************************************************************

// (require wie include, aber abbruch/fatal error  bei nicht-einbinden)
require_once("classes/define.php");
require_once("classes/define_sitemap.php");
require_once("classes/class.database.php");
require_once("classes/class.sitemap_controller.php");
require_once("classes/class.sitemap_model.php");
require_once("classes/class.model_blog.php");
require_once("classes/class.sitemap_view.php");

$controller = new Controller();	// controller erstellen
echo $controller->display();	// inhalt ausgeben

?>
