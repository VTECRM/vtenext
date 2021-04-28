<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@22700 crmv@170167 crmv@172994
require_once('include/utils/utils.php'); 
require_once('vtlib/Vtecrm/Module.php');//crmv@207871
require_once('vtlib/Vtecrm/Menu.php');//crmv@207871

function installCampaignStatistics() {
	global $adb;
	global $table_prefix;
	$campaignsModule = Vtecrm_Module::getInstance('Campaigns');
	
	//Mail Inviate
	$relation_id = $adb->getUniqueID($table_prefix.'_relatedlists');
	$max_sequence = 0;
	$result = $adb->query("SELECT max(sequence) as maxsequence FROM ".$table_prefix."_relatedlists WHERE tabid=$campaignsModule->id");
	if($adb->num_rows($result)) $max_sequence = $adb->query_result($result, 0, 'maxsequence');
	$sequence = $max_sequence+1;
	$adb->pquery("INSERT INTO ".$table_prefix."_relatedlists(relation_id,tabid,related_tabid,name,sequence,label,presence) VALUES(?,?,?,?,?,?,?)",
			array($relation_id,$campaignsModule->id,0,'get_statistics_sent_messages',$sequence,'Sent Messages',2));

	//Target che hanno aperto la mail
	$relation_id = $adb->getUniqueID($table_prefix.'_relatedlists');
	$max_sequence = 0;
	$result = $adb->query("SELECT max(sequence) as maxsequence FROM ".$table_prefix."_relatedlists WHERE tabid=$campaignsModule->id");
	if($adb->num_rows($result)) $max_sequence = $adb->query_result($result, 0, 'maxsequence');
	$sequence = $max_sequence+1;
	$adb->pquery("INSERT INTO ".$table_prefix."_relatedlists(relation_id,tabid,related_tabid,name,sequence,label,presence) VALUES(?,?,?,?,?,?,?)",
			array($relation_id,$campaignsModule->id,0,'get_statistics_viewed_messages',$sequence,'Viewed Messages',2));
	
	//Target che hanno cliccato almeno un link della mail
	$relation_id = $adb->getUniqueID($table_prefix.'_relatedlists');
	$max_sequence = 0;
	$result = $adb->query("SELECT max(sequence) as maxsequence FROM ".$table_prefix."_relatedlists WHERE tabid=$campaignsModule->id");
	if($adb->num_rows($result)) $max_sequence = $adb->query_result($result, 0, 'maxsequence');
	$sequence = $max_sequence+1;
	$adb->pquery("INSERT INTO ".$table_prefix."_relatedlists(relation_id,tabid,related_tabid,name,sequence,label,presence) VALUES(?,?,?,?,?,?,?)",
			array($relation_id,$campaignsModule->id,0,'get_statistics_tracked_link',$sequence,'Tracked Link',2));
	
	//Target che si sono disiscritti dalla campagna
	$relation_id = $adb->getUniqueID($table_prefix.'_relatedlists');
	$max_sequence = 0;
	$result = $adb->query("SELECT max(sequence) as maxsequence FROM ".$table_prefix."_relatedlists WHERE tabid=$campaignsModule->id");
	if($adb->num_rows($result)) $max_sequence = $adb->query_result($result, 0, 'maxsequence');
	$sequence = $max_sequence+1;
	$adb->pquery("INSERT INTO ".$table_prefix."_relatedlists(relation_id,tabid,related_tabid,name,sequence,label,presence) VALUES(?,?,?,?,?,?,?)",
			array($relation_id,$campaignsModule->id,0,'get_statistics_unsubscriptions',$sequence,'Unsubscriptions',2));

	//Suppression list (indirizzi delle mail non inviate + mail dei disiscritti)
	$relation_id = $adb->getUniqueID($table_prefix.'_relatedlists');
	$max_sequence = 0;
	$result = $adb->query("SELECT max(sequence) as maxsequence FROM ".$table_prefix."_relatedlists WHERE tabid=$campaignsModule->id");
	if($adb->num_rows($result)) $max_sequence = $adb->query_result($result, 0, 'maxsequence');
	$sequence = $max_sequence+1;
	$adb->pquery("INSERT INTO ".$table_prefix."_relatedlists(relation_id,tabid,related_tabid,name,sequence,label,presence) VALUES(?,?,?,?,?,?,?)",
			array($relation_id,$campaignsModule->id,0,'get_statistics_suppression_list',$sequence,'Suppression list',2));

	//Mail non inviate
	$relation_id = $adb->getUniqueID($table_prefix.'_relatedlists');
	$max_sequence = 0;
	$result = $adb->query("SELECT max(sequence) as maxsequence FROM ".$table_prefix."_relatedlists WHERE tabid=$campaignsModule->id");
	if($adb->num_rows($result)) $max_sequence = $adb->query_result($result, 0, 'maxsequence');
	$sequence = $max_sequence+1;
	$adb->pquery("INSERT INTO ".$table_prefix."_relatedlists(relation_id,tabid,related_tabid,name,sequence,label,presence) VALUES(?,?,?,?,?,?,?)",
			array($relation_id,$campaignsModule->id,0,'get_statistics_bounced_messages',$sequence,'Bounced Messages',2));
	
	//Failed Messages (es. record cancellati)
	$relation_id = $adb->getUniqueID($table_prefix.'_relatedlists');
	$max_sequence = 0;
	$result = $adb->query("SELECT max(sequence) as maxsequence FROM ".$table_prefix."_relatedlists WHERE tabid=$campaignsModule->id");
	if($adb->num_rows($result)) $max_sequence = $adb->query_result($result, 0, 'maxsequence');
	$sequence = $max_sequence+1;
	$adb->pquery("INSERT INTO ".$table_prefix."_relatedlists(relation_id,tabid,related_tabid,name,sequence,label,presence) VALUES(?,?,?,?,?,?,?)",
			array($relation_id,$campaignsModule->id,0,'get_statistics_failed_messages',$sequence,'Failed Messages',2));
	
	//Mail Schedulate
	$relation_id = $adb->getUniqueID($table_prefix.'_relatedlists');
	$max_sequence = 0;
	$result = $adb->query("SELECT max(sequence) as maxsequence FROM ".$table_prefix."_relatedlists WHERE tabid=$campaignsModule->id");
	if($adb->num_rows($result)) $max_sequence = $adb->query_result($result, 0, 'maxsequence');
	$sequence = $max_sequence+1;
	$adb->pquery("INSERT INTO ".$table_prefix."_relatedlists(relation_id,tabid,related_tabid,name,sequence,label,presence) VALUES(?,?,?,?,?,?,?)",
			array($relation_id,$campaignsModule->id,0,'get_statistics_message_queue',$sequence,'Message Queue',2));

}