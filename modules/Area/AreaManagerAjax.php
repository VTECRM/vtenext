<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43942 crmv@54707 */

$function = vtlib_purify($_REQUEST['function']);

$areaManager = AreaManager::getInstance();

switch($function){
	case 'propagateLayout':
		$areaManager->$function();
		break;
	case 'blockLayout':
		$areaManager->$function(vtlib_purify($_REQUEST['value']));
		break;
}
exit;
?>