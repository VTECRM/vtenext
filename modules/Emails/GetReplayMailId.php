<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@2043m

global $adb, $onlyquery,$table_prefix;
$record = '';
$focus = CRMEntity::getInstance('HelpDesk');
if ($_REQUEST['user'] == 'mailconverter') {
	$focus->retrieve_entity_info($_REQUEST['record'],'HelpDesk');
	if ($focus->column_fields['helpdesk_from'] == '') {
		die('helpdesk_from_empty');
	}
}
$onlyquery = true;
$focus->get_messages_list($_REQUEST['record'], 13, getTabid('Messages'));
$onlyquery = false;
$query = substr(VteSession::get('messages_listquery'),0,strpos(VteSession::get('messages_listquery'),'ORDER BY')).' ORDER BY '.$table_prefix.'_messages.messagesid DESC'; //crmv@175796
$result = $adb->limitQuery($query,0,1);
if ($result && $adb->num_rows($result) > 0) {
	$record = $adb->query_result($result,0,'crmid');
}
echo $record;
exit;
?>