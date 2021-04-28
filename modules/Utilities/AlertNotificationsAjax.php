<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@98484 */
$focus = AlertNotifications::getInstance();
$mode = vtlib_purify($_REQUEST['mode']);
switch($mode) {
	case 'getlabel':
		$label = $focus->getLabel(intval($_REQUEST['id']));
		if (!$label) $label = '';
		die($label);
		break;
	case 'isseen':
		if ($current_user->id != intval($_REQUEST['userid'])) die('Not authorized'); // crmv@184240
		$isSeen = $focus->isSeen(intval($_REQUEST['id']),intval($_REQUEST['userid']));
		($isSeen) ? $return = 'yes' : $return = 'no';
		die($return);
		break;
	case 'setseen':
		if ($current_user->id != intval($_REQUEST['userid'])) die('Not authorized'); // crmv@184240
		$focus->setSeen(intval($_REQUEST['id']),intval($_REQUEST['userid']));
		break;
}