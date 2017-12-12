<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2013-2018 The facileManager Team                               |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | facileManager: Easy System Administration                               |
 +-------------------------------------------------------------------------+
 | http://www.facilemanager.com/                                           |
 +-------------------------------------------------------------------------+
 | Processes main page                                                     |
 | Author: Jon LaBass                                                      |
 +-------------------------------------------------------------------------+
*/

/* Redirect to activate modules if none are active */
if ($_SESSION['module'] == $fm_name && currentUserCan('manage_modules')) {
	header('Location: ' . $menu[getParentMenuKey(_('Modules'))][4]);
}

setUserModule($_REQUEST['module']);

/* Prevent a redirect loop **/
if ($_SESSION['module'] == $fm_name) {
	unAuth('hide');
}

header('Location: ' . $GLOBALS['RELPATH']);

?>
