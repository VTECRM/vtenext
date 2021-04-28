<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * @author MAK
 */
 //crmv@add retrieve info
global $currentModule;
if($ajaxaction == "LOADRELATEDLIST") {
	//crmv@25809
	if ($_REQUEST['onlycount'] == 'true'){
		global $relationId;
		$relationId =  vtlib_purify($_REQUEST['relation_id']);
		if(!empty($relationId) && ((int)$relationId) > 0) {
			$recordid =  vtlib_purify($_REQUEST['record']);
			$actions = vtlib_purify($_REQUEST['actions']);
			$header = vtlib_purify($_REQUEST['header']);
			$modObj = CRMEntity::getInstance($currentModule);
//			$modObj->retrieve_entity_info($recordid,$currentModule);
			$modObj->id = $recordid;
			$relationInfo = getRelatedListInfoById($relationId, $recordid); //crmv@150751
			$relatedModule = getTabModuleName($relationInfo['relatedTabId']);
			$function_name = $relationInfo['functionName'];
			$relatedListData = $modObj->$function_name($recordid, getTabid($currentModule),
					$relationInfo['relatedTabId'], $actions);
			$ret_data = Array(
				'count'=>$relatedListData['count'],
				'tabid'=>$relationInfo['relatedTabId']
			);
			echo Zend_Json::encode($ret_data);
			exit;	
		}
	}
	else{
		//crmv@25809e
		//crmv@481398
		global $relationId;
		$relationId =  vtlib_purify($_REQUEST['relation_id']);
		if(!empty($relationId) && ((int)$relationId) > 0) {
			$recordid =  vtlib_purify($_REQUEST['record']);
			if(VteSession::getArray(array('rlvs', $currentModule, $relationId, 'currentRecord')) != $recordid) {
				$resetCookie = true;
			} else {
				$resetCookie = false;
			}
			VteSession::setArray(array('rlvs', $currentModule, $relationId, 'currentRecord'), $recordid);	
			$actions = vtlib_purify($_REQUEST['actions']);
			$header = vtlib_purify($_REQUEST['header']);
			$modObj->id = $recordid;
//			$modObj->retrieve_entity_info($recordid,$currentModule);
			 //crmv@add retrieve info end
			$relationInfo = getRelatedListInfoById($relationId, $recordid); //crmv@150751
			$relatedModule = getTabModuleName($relationInfo['relatedTabId']);
			$function_name = $relationInfo['functionName'];
			$relatedListData = $modObj->$function_name($recordid, getTabid($currentModule),
					$relationInfo['relatedTabId'], $actions);
			global $theme, $mod_strings, $app_strings;
			$theme_path="themes/".$theme."/";
			$image_path=$theme_path."images/";
			$smarty = new VteSmarty();
			// vtlib customization: Related module could be disabled, check it
			if(is_array($relatedListData)) {
				if( ($relatedModule == "Contacts" || $relatedModule == "Leads" ||
						$relatedModule == "Accounts") && $currentModule == 'Campaigns' && 
						!$resetCookie) {
					//TODO for 5.3 this should be COOKIE not REQUEST, change here else where
					// this logic is used for listview checkbox selection propogation.
					$checkedRecordIdString = $_COOKIE[$relatedModule.'_all'];
					$checkedRecordIdString = rtrim($checkedRecordIdString);
					$checkedRecordIdList = explode(';', $checkedRecordIdString);
					$relatedListData["checked"]=array();
					if (isset($relatedListData['entries'])) {
						foreach($relatedListData['entries'] as $key=>$val) {
							if(in_array($key,$checkedRecordIdList)) {
								$relatedListData["checked"][$key] = 'checked';
							} else {
								$relatedListData["checked"][$key] = '';
							}
						}
					}
					$smarty->assign("SELECTED_RECORD_LIST", $checkedRecordIdString);
				} else {
					$smarty->assign('RESET_COOKIE', $resetCookie);
				}
			}
			// END
			require_once('include/ListView/RelatedListViewSession.php');
			RelatedListViewSession::addRelatedModuleToSession($relationId,$header);
			$smarty->assign("MOD", $mod_strings);
			$smarty->assign("APP", $app_strings);
			$smarty->assign("THEME", $theme);
			$smarty->assign("IMAGE_PATH", $image_path);
			$smarty->assign("ID",$recordid);
			$smarty->assign("MODULE",$currentModule);
			$smarty->assign("RELATED_MODULE",$relatedModule);
			$smarty->assign("HEADER",$header);
			$smarty->assign("RELATEDLISTDATA", $relatedListData);
			$smarty->assign("RELATIONID", $relationId); //crmv@62415
			$smarty->assign("FIXED", ($_REQUEST['fixed'] == 'true')); // crmv@104568
			$smarty->display("RelatedListDataContents.tpl");
		}
		//crmv@25809
	}
	//crmv@25809e
	//crmv@481398e
}else if($ajaxaction == "DISABLEMODULE"){
	$relationId = vtlib_purify($_REQUEST['relation_id']);
	if(!empty($relationId) && ((int)$relationId) > 0) {
		$header = vtlib_purify($_REQUEST['header']);
		require_once('include/ListView/RelatedListViewSession.php');
		RelatedListViewSession::removeRelatedModuleFromSession($relationId,$header);
	}
	echo "SUCCESS";
}

?>