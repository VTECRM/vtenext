<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@183872 */

global $current_user;
$queryGenerator = QueryGenerator::getInstance('Processes',$current_user);
$customView = CRMEntity::getInstance('CustomView','Processes');
$viewid = $customView->getViewIdByName('Pending','Processes',$current_user->id);
if (!empty($viewid)) {
	$queryGenerator->initForCustomViewById($viewid);
	if (!empty($queryGenerator->getModuleFields())) {
		$result = $adb->querySlave('BadgeCount',replaceSelectQuery($queryGenerator->getQuery(),'count(*) as cnt')); // crmv@185894
		if ($result) {
			echo $noofrows = $adb->query_result($result,0,'cnt');
			return; // exit the file
		}
	}
}
echo 0;
return; // exit the file