<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $app_strings;
global $mod_strings;
global $currentModule;
global $current_language;
global $theme,$table_prefix;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$recprefix = vtlib_purify($_REQUEST['recprefix']);
//crmv@16312
$mode = $_REQUEST['mode'];
$validInput = validateAlphaNumericInput($recprefix);
if(!empty($recprefix) && ! $validInput) {
    $recprefix = '';
    $mode='';
    $STATUSMSG = "<font color='red'>".$mod_strings['LBL_UPDATE']." ".$mod_strings['LBL_FAILED']."</font>";
}
//crmv@16312 end
$recnumber = vtlib_purify($_REQUEST['recnumber']);

$module_array=getCRMSupportedModules();
$smarty = new VteSmarty();
if(count($module_array) <= 0) {
	$smarty->assign("EMPTY", 'true');
}
else{
	
	$selectedModule = vtlib_purify($_REQUEST['selmodule']);
	if($selectedModule == '') $selectedModule = key($module_array);
	
	if(array_key_exists($selectedModule, $module_array)) {
		$focus = CRMEntity::getInstance($selectedModule);
	}
	if($mode == 'UPDATESETTINGS') {
		if(isset($focus)) {
	
			$status = $focus->setModuleSeqNumber('configure', $selectedModule, $recprefix, $recnumber);
			if($status === false) {
				$STATUSMSG = "<font color='red'>".$mod_strings['LBL_UPDATE']." ".$mod_strings['LBL_FAILED']."</font> $recprefix$recnum ".$mod_strings['LBL_IN_USE'];
			} else {
				$STATUSMSG = "<font color='green'>".$mod_strings['LBL_UPDATE']." ".$mod_strings['LBL_DONE']."</font>";
			}
		}
	} else if($mode == 'UPDATEBULKEXISTING') {
		if(isset($focus)) {
			$resultinfo = $focus->updateMissingSeqNumber($selectedModule);
			
			if(!empty($resultinfo)) {
				$usefontcolor = 'green';
				if($resultinfo['totalrecords'] != $resultinfo['updatedrecords']) $usefontcolor = 'red';
				
				$STATUSMSG = "<font color='$usefontcolor'>" . 
					$mod_strings['LBL_TOTAL'] . $resultinfo['totalrecords'] . ", " . 
					$mod_strings['LBL_UPDATE'] . ' ' . $mod_strings['LBL_DONE'] . ':' . $resultinfo['updatedrecords'] . 
					"</font>";
			}		
			$seqinfo = $focus->getModuleSeqInfo($selectedModule);
			$recprefix = $seqinfo[0];
			$recnumber = $seqinfo[1];
		}
	} else {
		if(isset($focus)) {
			$seqinfo = $focus->getModuleSeqInfo($selectedModule);
			$recprefix = $seqinfo[0];
			$recnumber = $seqinfo[1];
		}
	}
}

$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);

$smarty->assign("MODULES",$module_array);
$smarty->assign("SELMODULE",$selectedModule);
$smarty->assign("MODNUM_PREFIX",$recprefix);
$smarty->assign("MODNUM", $recnumber);
$smarty->assign("STATUSMSG", $STATUSMSG);

if($_REQUEST['ajax'] == 'true') $smarty->display('Settings/CustomModEntityNoInfo.tpl');
else $smarty->display('Settings/CustomModEntityNo.tpl');

function getCRMSupportedModules()
{
	global $adb, $table_prefix;
	$sql="select tabid,name from {$table_prefix}_tab where isentitytype = 1 and presence = 0 and tabid in(select distinct tabid from {$table_prefix}_field where uitype='4')";
	$result = $adb->query($sql);
	while($moduleinfo=$adb->fetch_array($result)) {
		$modulelist[$moduleinfo['name']] = getTranslatedString($moduleinfo['name'], $moduleinfo['name']);
	}
	asort($modulelist);
	return $modulelist;
}
