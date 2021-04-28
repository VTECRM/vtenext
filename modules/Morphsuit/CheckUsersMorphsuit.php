<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Morphsuit/utils/MorphsuitUtils.php');
if (!vtlib_isModuleActive('Morphsuit')
	|| (vtlib_isModuleActive('Morphsuit') && checkUsersMorphsuit($_REQUEST['userid'],$_REQUEST['mode'],$_REQUEST['user_status']))) {
	die('yes');
} else {
	die('no');
}
?>