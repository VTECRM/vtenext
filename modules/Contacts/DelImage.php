<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function DelImage($id)
{		
	global $adb, $table_prefix;
	
	$query= "select {$table_prefix}_seattachmentsrel.attachmentsid from {$table_prefix}_seattachmentsrel inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid={$table_prefix}_seattachmentsrel.attachmentsid where {$table_prefix}_crmentity.setype='Contacts Image' and {$table_prefix}_seattachmentsrel.crmid=?";
	$result = $adb->pquery($query, array($id));
	$attachmentsid = $adb->query_result($result,$i,"attachmentsid");
	
	$rel_delquery="delete from {$table_prefix}_seattachmentsrel where crmid=?  and attachmentsid=?";
	$adb->pquery($rel_delquery, array($id, $attachmentsid));
	
	$crm_delquery="delete from {$table_prefix}_crmentity where crmid=?";
	$adb->pquery($crm_delquery, array($attachmentsid));

	$base_query="update {$table_prefix}_contactdetails set imagename='' where contactid=?";
	$adb->pquery($base_query, array($id));
}

function DelAttachment($id)
{
	global $adb, $table_prefix;
	
	$selresult = $adb->pquery("select name,path from {$table_prefix}_attachments where attachmentsid=?", array($id));
	unlink($adb->query_result($selresult,0,'path').$id."_".$adb->query_result($selresult,0,'name'));
	
	$query="delete from {$table_prefix}_seattachmentsrel where attachmentsid=?";
	$adb->pquery($query, array($id));
	
	$query="delete from {$table_prefix}_attachments where attachmentsid=?";
	$adb->pquery($query, array($id));
}

$id = $_REQUEST["recordid"];
if(isset($_REQUEST["attachmodule"]) && $_REQUEST["attachmodule"]=='Emails')
{
	DelAttachment($id);
}
else
{
	DelImage($id);
}
echo 'SUCCESS';
?>