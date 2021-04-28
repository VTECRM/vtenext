<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@42707

function vtws_getmenulist() {
	global $current_user;

	$menulist = array();

	$menu_module_list = getMenuModuleList();

	$menulist = array(
		'visible' => $menu_module_list[0],
		'others' => $menu_module_list[1],
	);

	return $menulist;
}


?>