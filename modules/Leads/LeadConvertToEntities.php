<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@29463
global $current_user, $currentModule, $theme, $app_strings,$log;
$category = getParentTab();

require_once 'include/Webservices/ConvertLead.php';
require_once 'include/utils/VtlibUtils.php';
//Getting the Parameters from the ConvertLead Form
$recordId = vtlib_purify($_REQUEST["record"]);
$leadId = vtws_getWebserviceEntityId('Leads', $recordId);

//crmv@52391
if (isModuleInstalled('Fiere') && vtlib_isModuleActive('Fiere')) {
	// check if the lead has fiere
	$fiere_ids = array();
	$res = $adb->pquery("select fieraid, leadid from {$table_prefix}_fiere inner join {$table_prefix}_crmentity on fieraid = crmid where {$table_prefix}_crmentity.deleted = 0 and {$table_prefix}_fiere.leadid = ?", array($recordId));
	if ($res) {
		for ($i=0; $i<$adb->num_rows($res); ++$i) {
			$fiere_ids[] = $adb->query_result($res, $i, 'fieraid');
		}
	}
}
if (isModuleInstalled('Telemarketing') && vtlib_isModuleActive('Telemarketing')) {
	// check if the lead has telemarketing(s)
	$tmk_ids = array();
	$res = $adb->pquery("select telemarketingid, leadid from {$table_prefix}_telemarketing inner join {$table_prefix}_crmentity on telemarketingid = crmid where {$table_prefix}_crmentity.deleted = 0 and {$table_prefix}_telemarketing.leadid = ?", array($recordId));
	if ($res) {
		for ($i=0; $i<$adb->num_rows($res); ++$i) {
			$tmk_ids[] = $adb->query_result($res, $i, 'telemarketingid');
		}
	}
}
//crmv@52391e

//make sure that either contacts or accounts is selected
if(!empty($_REQUEST['entities']))
{
	$entities=vtlib_purify($_REQUEST['entities']);

	$assigned_to = vtlib_purify($_REQUEST["c_assigntype"]);
	if ($assigned_to == "U") {
		$assigned_user_id = vtlib_purify($_REQUEST["c_assigned_user_id"]);
		$assignedTo = vtws_getWebserviceEntityId('Users', $assigned_user_id);
	} else {
		$assigned_user_id = vtlib_purify($_REQUEST["c_assigned_group_id"]);
		$assignedTo = vtws_getWebserviceEntityId('Groups', $assigned_user_id);
	}

	$transferRelatedRecordsTo = vtlib_purify($_REQUEST['transferto']);
	if (empty($transferRelatedRecordsTo))
		$transferRelatedRecordsTo = 'Contacts';


	$entityValues=array();

	$entityValues['transferRelatedRecordsTo']=$transferRelatedRecordsTo;
	$entityValues['assignedTo']=$assignedTo;
	$entityValues['leadId']=$leadId;

	global $default_charset; //crmv@60990

	if(vtlib_isModuleActive('Accounts')&& in_array('Accounts', $entities)){
		$entityValues['entities']['Accounts']['create']=true;
		$entityValues['entities']['Accounts']['name']='Accounts';
		$entityValues['entities']['Accounts']['accountname'] = html_entity_decode(vtlib_purify($_REQUEST['accountname']),ENT_QUOTES,$default_charset); //crmv@60990
		$entityValues['entities']['Accounts']['industry']= html_entity_decode(vtlib_purify($_REQUEST['industry']),ENT_QUOTES,$default_charset); //crmv@60990
	}

	if(vtlib_isModuleActive('Potentials')&& in_array('Potentials', $entities)){
		$entityValues['entities']['Potentials']['create']=true;
		$entityValues['entities']['Potentials']['name']='Potentials';
		$entityValues['entities']['Potentials']['potentialname']=  html_entity_decode(vtlib_purify($_REQUEST['potentialname']),ENT_QUOTES,$default_charset); //crmv@60990
		$entityValues['entities']['Potentials']['closingdate']=  vtlib_purify($_REQUEST['closingdate']);
		$entityValues['entities']['Potentials']['sales_stage']=  html_entity_decode(vtlib_purify($_REQUEST['sales_stage']),ENT_QUOTES,$default_charset); //crmv@60990
		$entityValues['entities']['Potentials']['amount']=  vtlib_purify($_REQUEST['amount']);
	}

	if(vtlib_isModuleActive('Contacts')&& in_array('Contacts', $entities)){
		$entityValues['entities']['Contacts']['create']=true;
		$entityValues['entities']['Contacts']['name']='Contacts';
		$entityValues['entities']['Contacts']['lastname']=  html_entity_decode(vtlib_purify($_REQUEST['lastname']),ENT_QUOTES,$default_charset); //crmv@60990
		$entityValues['entities']['Contacts']['firstname']=  html_entity_decode(vtlib_purify($_REQUEST['firstname']),ENT_QUOTES,$default_charset); //crmv@60990
		$entityValues['entities']['Contacts']['email']=  html_entity_decode(vtlib_purify($_REQUEST['email']),ENT_QUOTES,$default_charset); //crmv@60990
	}

	try{
		$result = vtws_convertlead($entityValues,$current_user);
	}catch(Exception $e){
		showError();
	}
	
	$accountIdComponents = vtws_getIdComponents($result['Accounts']);
	$accountId = $accountIdComponents[1];
	$contactIdComponents = vtws_getIdComponents($result['Contacts']);
	$contactId = $contactIdComponents[1];
	$potentialIdComponents = vtws_getIdComponents($result['Potentials']);
	$potentialId = $potentialIdComponents[1];
	
	//crmv@52391
	if (isModuleInstalled('Fiere') && vtlib_isModuleActive('Fiere')) {
		// convert fiere
		if (count($fiere_ids) > 0 && !empty($accountId)) {
			$params = array($accountId);
			$params = array_merge($params, $fiere_ids);
			$adb->pquery("update {$table_prefix}_fiere set leadid = 0, accountid = ? where fieraid in (".generateQuestionMarks($fiere_ids).')', $params);
		}
		if (count($fiere_ids) > 0 && !empty($contactId)) {
			$params = array($contactId);
			$params = array_merge($params, $fiere_ids);
			$adb->pquery("update {$table_prefix}_fiere set leadid = 0, contactid = ? where fieraid in (".generateQuestionMarks($fiere_ids).')', $params);
				
		}
	}
	if (isModuleInstalled('Telemarketing') && vtlib_isModuleActive('Telemarketing')) {
		// convert telemarketings
		if (count($tmk_ids) > 0 && !empty($accountId)) {
			$params = array($accountId);
			$params = array_merge($params, $tmk_ids);
			$adb->pquery("update {$table_prefix}_telemarketing set leadid = 0, accountid = ? where telemarketingid in (".generateQuestionMarks($tmk_ids).')', $params);
		}
	
		if (count($tmk_ids) > 0 && !empty($contactId)) {
			$params = array($contactId);
			$params = array_merge($params, $tmk_ids);
			$adb->pquery("update {$table_prefix}_telemarketing set leadid = 0, contactid = ? where telemarketingid in (".generateQuestionMarks($tmk_ids).')', $params);
				
		}
	}
	//crmv@52391e
}

if (!empty($accountId)) {
    header("Location: index.php?action=DetailView&module=Accounts&record=$accountId&parenttab=$category");
} elseif (!empty($contactId)) {
    header("Location: index.php?action=DetailView&module=Contacts&record=$contactId&parenttab=$category");
} else {
	showError();
}

function showError(){
	require_once 'include/utils/VtlibUtils.php';
	global $current_user, $currentModule, $theme, $app_strings,$log;
    echo "<link rel='stylesheet' type='text/css' href='themes/$theme/style.css'>";
    echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
    echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;'>

		<table border='0' cellpadding='5' cellspacing='0' width='98%'>
		<tbody><tr>
		<td rowspan='2' width='11%'><img src='" . resourcever('denied.gif') . "' ></td>
		<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'>
			<span class='genHeaderSmall'>". getTranslatedString('SINGLE_'.$currentModule, $currentModule)." ".
			getTranslatedString('CANNOT_CONVERT', $currentModule)  ."
		<br>
		<ul> ". getTranslatedString('LBL_FOLLOWING_ARE_POSSIBLE_REASONS', $currentModule) .":
			<li>". getTranslatedString('LBL_LEADS_FIELD_MAPPING_INCOMPLETE', $currentModule) ."</li>
			<li>". getTranslatedString('LBL_MANDATORY_FIELDS_ARE_EMPTY', $currentModule) ."</li>
		</ul>
		</span>
		</td>
		</tr>
		<tr>
		<td class='small' align='right' nowrap='nowrap'>";

    if (is_admin($current_user)) {
        echo "<a href='index.php?module=Settings&action=LeadCustomFieldMapping&parenttab=Settings'>". getTranslatedString('LBL_LEADS_FIELD_MAPPING', $currentModule) ."</a><br>"; // crmv@185774
    }

    echo "<a href='javascript:window.history.back();'>". getTranslatedString('LBL_GO_BACK', $currentModule) ."</a><br>";

    echo "</td>
               </tr>
		</tbody></table>
		</div>
                </td></tr></table>";
}
?>