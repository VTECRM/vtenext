<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 crmv@97566 crmv@115268 crmv@163905 */

require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');

global $mod_strings, $app_strings, $theme, $upload_badext, $default_charset, $current_user;	//crmv@147720

$PMUtils = ProcessMakerUtils::getInstance();
$mode = $_REQUEST['mode'];
$sub_template = '';

// crmv@189903
if ($mode == 'modeler') {
	require_once('include/utils/PageHeader.php');
	$pageHeader = VTEPageHeader::getInstance();
	$smarty = $pageHeader->initSmarty();
} else {
	$smarty = new VteSmarty();
}
// crmv@189903e
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");
$smarty->assign("MODE", $mode);
// crmv@190834
$smarty->assign("SETTINGS_FIELD_TITLE", $mod_strings['LBL_PROCESS_MAKER']);
$smarty->assign("SETTINGS_FIELD_DESC", $mod_strings['LBL_PROCESS_MAKER_DESC']);
// crmv@190834e

switch($mode) {
	//crmv@100972 crmv@147720
	case 'create':
		if (!empty($_REQUEST['err'])) {
			$smarty->assign("DATA", array('name'=>$_REQUEST['name'],'description'=>$_REQUEST['description']));
			$smarty->assign("ERROR", $_REQUEST['err']);
		}
		$sub_template = 'Settings/ProcessMaker/Create.tpl';
		break;
	case 'import':
		$id = vtlib_purify($_REQUEST['id']);
		if (!empty($id)) $upload_ext = array('vtebpmn'); else $upload_ext = array();	// if update allow only vtebpmn
		$err = '';
		$check = $PMUtils->checkUploadBPMN($err, $upload_ext);
		if ($check) {
			if (empty($id)) {	// import new
				if (isset($_FILES['bpmnfile']['tmp_name']) && !empty($_FILES['bpmnfile']['tmp_name'])) {
					$data = $PMUtils->readUploadedFile(file_get_contents($_FILES['bpmnfile']['tmp_name']));
					$smarty->assign("FILE", $data['file']);
					$smarty->assign("VERSION", $data['version']);
					$smarty->assign("XML", htmlentities($data['bpmn'],ENT_QUOTES,$default_charset));
				}
				$smarty->assign("NAME", vtlib_purify($_REQUEST['name']));
				$smarty->assign("DESCRIPTION", vtlib_purify($_REQUEST['description']));
				$buttons = '
				<div class="morphsuitlink" style="float:left; height:34px; font-size:14px; padding-top:7px; padding-left:10px">
					'.$mod_strings['LBL_SETTINGS'].'</a> &gt; '.$mod_strings['LBL_PROCESS_MAKER'].'
				</div>
				<div style="float:right; padding-right:5px">
					<input type="button" style="background-color:white" id="save-button" class="crmbutton small edit" value="'.$app_strings['LBL_SAVE_LABEL'].'" title="'.$app_strings['LBL_SAVE_LABEL'].'" />
					<input type="button" style="background-color:white" onclick="window.location.href=\'index.php?module=Settings&action=ProcessMaker\'" class="crmbutton small edit" value="'.$mod_strings['LBL_CANCEL_BUTTON'].'" title="'.$mod_strings['LBL_CANCEL_BUTTON'].'" />
					<img id="logo" src="'.get_logo('header').'" alt="{$APP.LBL_BROWSER_TITLE}" title="'.$app_strings['LBL_BROWSER_TITLE'].'" border=0 style="padding:1px 0px 3px 0px; max-height:34px">
				</div>';
				$smarty->assign("BUTTON_LIST", $buttons);
				$smarty->display('Settings/ProcessMaker/Modeler.tpl');
			} else {	// update from upload
				if (empty($_REQUEST['cachefile'])) {
					
					$retrieve = $PMUtils->retrieve($id);
					$data = $PMUtils->readUploadedFile(file_get_contents($_FILES['bpmnfile']['tmp_name']));
					if (!empty($data['metarec'])) $data['metarec'] = Zend_Json::decode($data['metarec']);
					if (!empty($data['dynameta'])) $data['dynameta'] = Zend_Json::decode($data['dynameta']);
					if (!empty($data['system_versions'])) $data['system_versions'] = Zend_Json::decode($data['system_versions']);
					
					// the new process version must be higher of the current one
					if ($PMUtils->compareVersionNumber($data['version'],'<=',$retrieve['version'])) {
						VteSession::set('vtealert',getTranslatedString('LBL_NO_PROCESS_UPDATED_WRONG_VERSION','Settings'));
						header("Location: index.php?module=Settings&action=ProcessMaker");
						exit;
					}
					$different_system_versions_founded = false;
					require_once('include/utils/UserInfoUtil.php');
					$userInfoUtils = UserInfoUtils::getInstance();
					$current_system_versions = $userInfoUtils->getCurrentVersionNumbers(array('tabs','roles','profiles','conditionals'),array('tabs'=>$PMUtils->getRecordsInvolvedModules($id,true,true)));
					// compare only tabs
					foreach($current_system_versions['tabs'] as $module => $info) {
						if ($PMUtils->compareVersionNumber($info['number'],'<=',$data['system_versions']['tabs'][$module]['number'])) {
							// continue
						} else {
							// the module in the new process version has a higher version
							$different_system_versions_founded = true;
						}
					}
					file_put_contents("cache/upload/upload_processmaker_{$current_user->id}.vtebpmn",file_get_contents($_FILES['bpmnfile']['tmp_name'])); // save file in cache/upload
					if ($different_system_versions_founded) {
						header("Location: index.php?module=Settings&action=ProcessMaker&show_confirm_different_system_versions=yes&id={$id}&current_version={$retrieve['version']}");
					} else {
						header("Location: index.php?module=Settings&action=ProcessMaker&check_increment_version=yes&id={$id}&current_version={$retrieve['version']}");
					}
					exit;
				} elseif ($_REQUEST['cachefile'] == 'yes') {
					$data = $PMUtils->readUploadedFile(file_get_contents("cache/upload/upload_processmaker_{$current_user->id}.vtebpmn"));
					if (!empty($data['metarec'])) $data['metarec'] = Zend_Json::decode($data['metarec']);
					if (!empty($data['dynameta'])) $data['dynameta'] = Zend_Json::decode($data['dynameta']);
				}
				if (!empty($data)) {
					@unlink("cache/upload/upload_processmaker_{$current_user->id}.vtebpmn");
					$PMUtils->historicizeSaveForceVersion($id,($_REQUEST['force_version'] == 'true')); //crmv@155375 save previously structure
					$PMUtils->save($data,$id,true,false);
					$adb->pquery("update {$PMUtils->table_name} set pending_changes=? where id=?", array(0, $id));
					header("Location: index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&parenttab=Settings&mode=detail&id={$id}");
					exit;
				}
			}
		} else {
			if (empty($id)) { // import new
				header("Location: index.php?module=Settings&action=ProcessMaker&parenttab=Settings&mode=create&name={$_REQUEST['name']}&description={$_REQUEST['description']}&err={$err}");
			} else {	// update
				VteSession::set('vtealert',$err);
				header("Location: index.php?module=Settings&action=ProcessMaker");
			}
		}
		exit;
		break;
	case 'detail':
		$id = vtlib_purify($_REQUEST['id']);
		
		$PMUtils->clearProcessMakerCache($id);	//crmv@153321_5 clear session
		
		$data = $PMUtils->retrieve($id);
		$smarty->assign("DATA", $data);
		$smarty->assign("TABLE_NAME", $PMUtils->table_name);
		$smarty->assign("default_charset", $default_charset);
		
		include_once('vtlib/Vtecrm/Link.php');
		$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERSCRIPT'));
		$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
		$smarty->assign('HEAD_INCLUDE',"icons,jquery,jquery_plugins,jquery_ui,fancybox,prototype,jscalendar,sdk_headers");
		
		// crmv@177151
		$buttons = '
		<div class="morphsuitlink" style="float:left; height:34px; font-size:14px; padding-top:7px; padding-left:10px">
			'.$mod_strings['LBL_SETTINGS'].'</a> &gt; '.$mod_strings['LBL_PROCESS_MAKER'].'
		</div>
		<div style="float:right; padding-right:5px" class="processes_btn_div">
			<div id="status" style="display:none; float:left; position:relative; top:6px; right:5px;"><i class="dataloader light" data-loader="circle"></i></div> <!-- crmv@167915 -->
			<input type="button" onclick="ProcessMakerScript.incrementVersion('.$id.',\''.$data['version'].'\',function(version){jQuery(\'[name=pm_version]\').parent().parent().find(\'span\').text(version);})" class="crmbutton small edit" value="'.$mod_strings['LBL_INCREMENT_VERSION'].'" title="'.$mod_strings['LBL_INCREMENT_VERSION'].'">
			<input type="button" onclick="ProcessMakerScript.modeler('.$id.')" class="crmbutton small edit" value="'.$app_strings['LBL_EDIT'].' '.$mod_strings['LBL_PM_MODELER'].'" title="'.$app_strings['LBL_EDIT'].' '.$mod_strings['LBL_PM_MODELER'].'">
			<input type="button" onclick="ProcessMakerScript.backToList(\''.$app_strings['Active'].'\')" class="crmbutton small edit" value="'.$app_strings['LBL_BACK'].'" title="'.$app_strings['LBL_BACK'].'">
			<img id="logo" src="'.get_logo('header').'" alt="{$APP.LBL_BROWSER_TITLE}" title="'.$app_strings['LBL_BROWSER_TITLE'].'" border=0 style="padding:1px 0px 3px 0px; max-height:34px">
		</div>';
		//		{* TODO <input type="button" onclick="ProcessMakerScript.manageOtherRecords({$DATA.id})" class="crmbutton small edit" value='{$MOD.LBL_PROCESS_MAKER_MANAGE_OTHER_RECORD}' title='{$MOD.LBL_PROCESS_MAKER_MANAGE_OTHER_RECORD}'> *}
		// crmv@177151e
		$smarty->assign("BUTTON_LIST", $buttons);
		
		$smarty->display('Settings/ProcessMaker/Detail.tpl');
		exit;
		break;
	case 'modeler':
		$id = vtlib_purify($_REQUEST['id']);
		$smarty->assign("PROCESSMAKERID", $id);
		// crmv@177151 crmv@189903
		$buttons = '
		<div class="morphsuitlink" style="float:left; height:34px; font-size:14px; padding-top:7px; padding-left:10px">
			'.$mod_strings['LBL_SETTINGS'].'</a> &gt; '.$mod_strings['LBL_PROCESS_MAKER'].'
		</div>
		<div style="float:right; padding-right:5px" class="processes_btn_div">
			<input type="button" id="save-button" class="crmbutton small edit" value="'.$app_strings['LBL_SAVE_LABEL'].'" title="'.$app_strings['LBL_SAVE_LABEL'].'" />
			<input type="button" onclick="window.location.href=\'index.php?module=Settings&action=ProcessMaker\'" class="crmbutton small edit" value="'.$mod_strings['LBL_CANCEL_BUTTON'].'" title="'.$mod_strings['LBL_CANCEL_BUTTON'].'" />
			<img id="logo" src="'.get_logo('header').'" alt="{$APP.LBL_BROWSER_TITLE}" title="'.$app_strings['LBL_BROWSER_TITLE'].'" border=0 style="padding:1px 0px 3px 0px; max-height:34px">
		</div>';
		// crmv@177151e crmv@189903e
		$smarty->assign("BUTTON_LIST", $buttons);
		$smarty->display('Settings/ProcessMaker/Modeler.tpl');
		exit;
		break;
	case 'save_model':
		global $current_user;
		$id = vtlib_purify($_REQUEST['id']);
		$xml = $_REQUEST['xml'];
		if (empty($id)) {	// new (create / import)
			$data = array();
			$values = Zend_Json::decode($_REQUEST['values']);
			$file = $values['file'];
			if (stripos($file,'<vtebpmn>') !== false) {
				$data = $PMUtils->readUploadedFile($file);
				if (!empty($data['metarec'])) $data['metarec'] = Zend_Json::decode($data['metarec']);
				if (!empty($data['dynameta'])) $data['dynameta'] = Zend_Json::decode($data['dynameta']);
			}
			$data['name'] = $values['name'];
			$data['description'] = $values['description'];
			$data['bpmn'] = $xml;
			$id = $PMUtils->save($data,null,false,false);
		} else {	// update model
			//crmv@155375
			$PMUtils->saveVersion($id); //crmv@101057 save previously structure
			$adb->pquery("update {$PMUtils->table_name} set xml=?, pending_changes=?, structure=null where id=?", array($xml, 1, $id));
			//crmv@155375e
		}
		echo $id;
		exit;
		break;
	case 'increment_version';
		echo $PMUtils->incrementVersion(vtlib_purify($_REQUEST['id']),($_REQUEST['force_version'] == 'true'));	//crmv@150751
		exit;
		break;
	//crmv@100972e crmv@147720e
	//crmv@150751
	case 'check_increment_version';
		echo $PMUtils->checkIncrementVersion(vtlib_purify($_REQUEST['id']));
		exit;
		break;
	//crmv@150751e
	case 'check_pending_changes':
		$id = vtlib_purify($_REQUEST['id']);
		$data = $PMUtils->retrieve($id);
		echo $data['pending_changes'];
		exit;
		break;
	case 'download':
		$id = vtlib_purify($_REQUEST['id']);
		$format = vtlib_purify($_REQUEST['format']);
		//crmv@150751
		$xml_version = vtlib_purify($_REQUEST['xml_version']);
		$data = $PMUtils->retrieve($id, $xml_version);
		//crmv@150751e
		if ($format == 'bpmn') {
			$filename = $data['name'].'.bpmn';
			$fileContent = $data['xml'];
		} elseif ($format == 'vtebpmn') {
			$filename = $data['name'].'.vtebpmn';
			$filename = sanitizeUploadFileName($filename, $upload_badext);	//crmv@147720
			
			$xml = new SimpleXMLElement('<vtebpmn/>');
			$xml->addChild('name',$data['name']);
			$xml->addChild('description',$data['description']);
			$xml->addChild('version',$data['version']);	//crmv@147720
			$xml->addChild('bpmn',base64_encode($data['xml']));
			$PMUtils->clearSubProcesses($id,$data['vte_metadata'],$data['structure']);	//crmv@97575 crmv@136524 crmv@150751
			$xml->addChild('vte_metadata',base64_encode($data['vte_metadata']));
			$xml->addChild('structure',base64_encode($data['structure']));
			$xml->addChild('helper',base64_encode($data['helper']));
			
			$metarec = array();
			$result = $adb->pquery("select * from {$table_prefix}_processmaker_metarec where processid = ?", array($id));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$metarec[] = $row;
				}
			}
			$xml->addChild('metarec',base64_encode(Zend_Json::encode($metarec)));
			
			$dynameta = array();
			$result = $adb->pquery("select * from {$table_prefix}_process_dynaform_meta where processid = ?", array($id));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$dynameta[] = $row;
				}
			}
			$xml->addChild('dynameta',base64_encode(Zend_Json::encode($dynameta)));
			
			require_once('include/utils/UserInfoUtil.php');
			$userInfoUtils = UserInfoUtils::getInstance();
			$system_versions = $userInfoUtils->getCurrentVersionNumbers(array('tabs','roles','profiles','conditionals'),array('tabs'=>$PMUtils->getRecordsInvolvedModules($id,true,true)));
			$xml->addChild('system_versions',base64_encode(Zend_Json::encode($system_versions)));
			
			$fileContent = $xml->asXML();
		}
		$fileType = 'application/octet-stream';
		function_exists('mb_strlen') ? $filesize = mb_strlen($fileContent, '8bit') : $filesize = strlen($fileContent);
		
		header("Content-type: $fileType");
		header("Content-length: $filesize");
		header("Cache-Control: private");
		header("Content-Disposition: attachment; filename={$filename}");
		header("Content-Description: PHP Generated Data");
		echo $fileContent; exit;
		break;
	case 'load_metadata':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = $_REQUEST['elementid'];
		$req_structure = Zend_Json::decode($_REQUEST['structure']);
		
		$data = $PMUtils->retrieve($id);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		$vte_metadata_arr = $vte_metadata[$elementid];
		$helper = Zend_Json::decode($data['helper']);
		$helper_arr = $helper[$elementid];
		if (empty($helper_arr)) $helper_arr['active'] = 'on';	// default helper active
		$structure = Zend_Json::decode($data['structure']);
		
		if (!isset($req_structure['text']) || !isset($req_structure['type'])) {
			$req_structure = $structure['shapes'][$elementid];
		}
		$type = $PMUtils->formatType($req_structure['type']);
		$subType = $PMUtils->formatType($req_structure['subType']);
		$type_map = $PMUtils->getMetadataTypes($type,$req_structure);
		$type_tpl = $type_map['tpl'];
		if (empty($type_tpl)) {
			$error = $type;
			if (!empty($subType)) $error .= "($subType)";
			$error .= ' not implemented';
			die($error);
		}
		$engineType = $PMUtils->getEngineType($req_structure);
		$title = $PMUtils->getElementTitle($req_structure);
		$smarty->assign("PAGE_TITLE", $title);
		$smarty->assign("PAGE_RIGHT_TITLE", $elementid);
		$smarty->assign("HEADER_Z_INDEX", 1);
		$smarty->assign("ID", $elementid);
		$smarty->assign("PROCESSID", $id);
		$buttons['save'] = '<input type="button" onclick="ProcessMakerScript.saveMetadata(\''.$id.'\',\''.$elementid.'\',\''.$engineType.'\')" class="crmbutton small save" value="'.$app_strings['LBL_SAVE_BUTTON_LABEL'].'" title="'.$app_strings['LBL_SAVE_BUTTON_LABEL'].'">';
		$buttons['cancel'] = '<input type="button" onclick="ProcessMakerScript.closeMetadata(\''.$elementid.'\');" class="crmbutton small cancel" value="'.$mod_strings['LBL_CANCEL_BUTTON'].'" title="'.$mod_strings['LBL_CANCEL_BUTTON'].'">';
		//crmv@99316
		if ($engineType == 'Action') {
			$buttons['advanced'] = '<input type="button" onclick="ProcessMakerScript.advancedMetadataSettings(\''.$id.'\',\''.$elementid.'\',true);" class="crmbutton small" value="'.$mod_strings['LBL_PM_ADVANCED_ACTIONS'].'..." title="'.$mod_strings['LBL_PM_ADVANCED_ACTIONS'].'...">';
		}
		//crmv@99316e
		
		if (isset($type_map['php'])) include($type_map['php']);
		
		$smarty->assign("METADATA", $vte_metadata_arr);
		$smarty->assign("HELPER", $helper_arr);
		
		$buttons = '
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%" style="padding:5px"></td>
			 	<td align="right" style="padding: 5px;" nowrap>
					<span class="indicatorMetadata" style="display:none;"><i class="dataloader" data-loader="circle" style="vertical-align:middle;"></i></span>&nbsp;
					'.implode('',$buttons).'
				</td>
			 </tr>
			 </table>';
		$smarty->assign("BUTTON_LIST", $buttons);
		
		//crmv@96450 retrieve dynaform
		require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
		$MMUtils = new ModuleMakerUtils();
		$MMSteps = new ProcessModuleMakerSteps($MMUtils);
		$smarty->assign("STEPVARS", $helper_arr['dynaform']);
		$smarty->assign("NEWFIELDS", $MMSteps->getNewFields());
		$smarty->assign("NEWTABLEFIELDCOLUMNS", $MMSteps->getNewTableFieldColumns()); // crmv@102879
		$smarty->assign("PROCESSMAKERMODE", true);
		//crmv@96450e
		
		include_once('vtlib/Vtecrm/Link.php');
		$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERSCRIPT'));
		$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
		$smarty->assign('HEAD_INCLUDE',"icons,jquery,jquery_plugins,jquery_ui,fancybox,prototype,jscalendar,sdk_headers");
		
		$smarty->display($type_tpl);
		exit;
		break;
	case 'savemetadata':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		//crmv@96450
		require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
		$MMUtils = new ModuleMakerUtils();
		$MMSteps = new ProcessModuleMakerSteps($MMUtils);
		$dynaform = Zend_Json::decode($_REQUEST['mmaker']);
		$MMSteps->preprocessStepVars('ajax', 2, 0, $dynaform);
		$dynaform = $MMSteps->extractStepVars($dynaform);
		//crmv@96450e
		//crmv@153321_5
		$formatted_metadata = $PMUtils->formatMetadata($id,$elementid,vtlib_purify($_REQUEST['vte_metadata']),vtlib_purify($_REQUEST['helper']),$dynaform);
		$formatted_helper = Zend_Json::decode($formatted_metadata['helper']);
		$formatted_helper_json = Zend_Json::encode($formatted_helper[$elementid]);
		
		$data = $PMUtils->retrieve($id);
		$saved_helper = Zend_Json::decode($data['helper']);
		$saved_helper_json = Zend_Json::encode($saved_helper[$elementid]);
		
		if ($saved_helper_json != 'null' && $saved_helper_json != $formatted_helper_json) $PMUtils->clearCache('processmaker_entity_options_'.$id);	// clear session
		//crmv@153321_5e
		$PMUtils->saveMetadata($id,$elementid,vtlib_purify($_REQUEST['vte_metadata']),vtlib_purify($_REQUEST['helper']),$dynaform);	//crmv@96450
		echo 'SUCCESS'; exit;
		break;
	case 'save_structure':
		$id = vtlib_purify($_REQUEST['id']);
		$PMUtils->saveStructure($id,vtlib_purify($_REQUEST['structure']));
		echo 'SUCCESS'; exit;
		break;
	case 'editaction':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$action_type = $_REQUEST['action_type'];
		$action_id = $_REQUEST['action_id'];
		// crmv@102879
		$action_options = array();
		// at the moment, only the cycle has options
		if ($action_type == 'Cycle' || $action_type == 'CycleRelated') {//crmv@203075
			$action_options['cycle_field'] = vtlib_purify($_REQUEST['cycle_field']);
			$action_options['cycle_action'] = vtlib_purify($_REQUEST['cycle_action']);
		}
		$PMUtils->actionEdit($id,$elementid,$action_type,$action_id, $action_options);
		// crmv@102879e
		exit;
		break;
	case 'saveaction':
		$result = $PMUtils->actionSave($_REQUEST);
		die($result);
		break;
	case 'deleteaction':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$action_id = $_REQUEST['action_id'];
		$result = $PMUtils->actionDelete($id,$elementid,$action_id);
		die($result);
		break;
	case 'manage_other_records':
		require_once('modules/Settings/ProcessMaker/ProcessMakerPopup.php');
		$popup = ProcessMakerPopup::getInstance();
		
		$smarty->assign("PAGE_TITLE", $mod_strings['LBL_PROCESS_MAKER_MANAGE_OTHER_RECORD']);
		$smarty->assign("HEADER_Z_INDEX", 1);
		$smarty->assign('LINK_MODULES', $popup->getModules());
		
		// TODO extraInputs per gestire funzione ritorno list, togliere checkbox e tasto Aggiungi oppure usarli meglio
		// TODO blocco sotto stile wizard
		
		$smarty->display('Settings/ProcessMaker/ManageOtherRecords.tpl');
		exit;
		break;
	case 'recurrence_preview':
		$vte_metadata = Zend_Json::decode($_REQUEST['vte_metadata']);
		$preview = $PMUtils->previewTimerStart($vte_metadata);
		$smarty->assign("PREVIEWS", $preview);
		$smarty->display('Settings/ProcessMaker/Metadata/TimerStartPreviewRecurrences.tpl');
		exit;
		break;
	case 'checktimerstart':
		if (isset($_REQUEST['vte_metadata'])) {
			$vte_metadata = Zend_Json::decode($_REQUEST['vte_metadata']);
		} elseif (!empty($_REQUEST['id'])) {
			$processmakerid = vtlib_purify($_REQUEST['id']);
			$startElementid = '';
			$isTimerProcess = $PMUtils->isTimerProcess($processmakerid,$startElementid);
			if ($isTimerProcess) {
				$data = $PMUtils->retrieve($processmakerid);
				$vte_metadata = Zend_Json::decode($data['vte_metadata']);
				$vte_metadata = $vte_metadata[$startElementid];
			} else {
				exit;
			}
		}
		$date_start = getValidDBInsertDateValue($vte_metadata['date_start']).' '.$vte_metadata['starthr'].':'.$vte_metadata['startmin'];
		($vte_metadata['date_end_mass_edit_check'] == 'on') ? $date_end = getValidDBInsertDateValue($vte_metadata['date_end']).' '.$vte_metadata['endhr'].':'.$vte_metadata['endmin'] : $date_end = false;
		if (strtotime($date_start) < time()) {
			echo getTranslatedString('LBL_PM_CHECK_TIMER_START_DATE','Settings');
		} elseif (!empty($date_end) && strtotime($date_start) > strtotime($date_end)) {
			echo getTranslatedString('LBL_PM_CHECK_TIMER_START_GREATER_THAN_END','Settings');
		}
		exit;
		break;
	//crmv@96450 retrieve dynaform
	case 'openimportdynaformblocks':
		$processmakerid = vtlib_purify($_REQUEST['id']);
		$elementid = $_REQUEST['elementid'];
		//crmv@160837 some code removed
		
		$data = $PMUtils->retrieve($processmakerid);
		$helper = Zend_Json::decode($data['helper']);
		$structure = Zend_Json::decode($data['structure']);
		
		require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
		$MMUtils = new ModuleMakerUtils();
		$MMSteps = new ProcessModuleMakerSteps($MMUtils);
		$titles = array();
		$stepvars = array();
		if (!empty($helper)) {
			unset($helper[$elementid]);
			foreach($helper as $dyna_elementid => $h) {
				if (!empty($h['dynaform']['mmaker_blocks'])) {
					$titles[$dyna_elementid] = $PMUtils->getElementTitle($structure['shapes'][$dyna_elementid]);
					$stepvars[$dyna_elementid] = $h['dynaform'];
				}
			}
		}
		$buttons = '
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%" style="padding:5px"></td>
			 	<td align="right" style="padding: 5px;" nowrap>
					<span class="indicatorMetadata" style="display:none;"><i class="dataloader" data-loader="circle" style="vertical-align:middle;"></i></span>&nbsp;
					<input type="button" onclick="ProcessHelperScript.importDynaformBlocks(\''.$processmakerid.'\',\''.$elementid.'\')" class="crmbutton small save" value="'.$app_strings['LBL_SAVE_BUTTON_LABEL'].'" title="'.$app_strings['LBL_SAVE_BUTTON_LABEL'].'">
					<input type="button" onclick="closePopup();" class="crmbutton small cancel" value="'.$mod_strings['LBL_CANCEL_BUTTON'].'" title="'.$mod_strings['LBL_CANCEL_BUTTON'].'">
				</td>
			 </tr>
			 </table>';
		$smarty->assign("BUTTON_LIST", $buttons);
		$smarty->assign("PAGE_TITLE", $mod_strings['LBL_PM_IMPORT_DYNAFORM_BLOCK_TITLE']); //crmv@160837
		$smarty->assign("PAGE_RIGHT_TITLE", $elementid);
		$smarty->assign("HEADER_Z_INDEX", 1);
		$smarty->assign("TITLES", $titles);
		$smarty->assign("STEPVARS_ARR", $stepvars);
		$smarty->assign("PROCESSMAKERMODE", true);
		$smarty->assign("LAYOUT_READONLY", true); //crmv@160837
		//crmv@160837 some code removed
		
		$smarty->display('Settings/ProcessMaker/Metadata/ImportDynaformBlocks.tpl');
		exit;
		break;
	case 'importdynaformblocks':
		$processmakerid = vtlib_purify($_REQUEST['id']);
		$elementid = $_REQUEST['elementid'];
		$dynaformblocks = $_REQUEST['dynaformblocks'];
		if (!empty($dynaformblocks)) {
			$data = $PMUtils->retrieve($processmakerid);
			$helper = Zend_Json::decode($data['helper']);
			
			require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
			$MMUtils = new ModuleMakerUtils();
			$MMSteps = new ProcessModuleMakerSteps($MMUtils);
			$dynaform = $_REQUEST['mmaker']; //crmv@160837
			$MMSteps->preprocessStepVars('ajax', 2, 0, $dynaform);
			$final_dynaform = $MMSteps->extractStepVars($dynaform);
			
			if (empty($final_dynaform['mmaker_lastfieldid'])) $final_dynaform['mmaker_lastfieldid'] = 0;
			foreach($dynaformblocks as $dynaformblock) {
				$dynaform_elementid = substr($dynaformblock,0,strrpos($dynaformblock,'_'));
				$blockno = substr($dynaformblock,strrpos($dynaformblock,'_')+1);
				$dynaform = $helper[$dynaform_elementid]['dynaform'];
				if (!empty($dynaform['mmaker_blocks'][$blockno])) {
					if (!empty($dynaform['mmaker_blocks'][$blockno]['fields'])) {
						foreach($dynaform['mmaker_blocks'][$blockno]['fields'] as &$field) {
							$final_dynaform['mmaker_lastfieldid']++;
							$field['fieldname'] = 'vcf_'.$final_dynaform['mmaker_lastfieldid'];
						}
					}
					$final_dynaform['mmaker_blocks'][] = $dynaform['mmaker_blocks'][$blockno];
				}
			}
			
			$smarty->assign("STEPVARS", $final_dynaform);
			$smarty->assign("NEWFIELDS", $MMSteps->getNewFields());
			$smarty->assign("NEWTABLEFIELDCOLUMNS", $MMSteps->getNewTableFieldColumns()); // crmv@102879 crmv@160837
			$smarty->assign("PROCESSMAKERMODE", true);
			$smarty->display('Settings/ModuleMaker/Step2Fields.tpl');
		}
		exit;
		break;
	//crmv@96450e
	//crmv@160837
	case 'openimportmoduleblocks':
		$processmakerid = vtlib_purify($_REQUEST['id']);
		$elementid = $_REQUEST['elementid'];
		
		$buttons = '
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%" style="padding:5px"></td>
			 	<td align="right" style="padding: 5px;" nowrap>
					<span class="indicatorMetadata" style="display:none;"><i class="dataloader" data-loader="circle" style="vertical-align:middle;"></i></span>&nbsp;
					<input type="button" onclick="ProcessHelperScript.importModuleBlocks(\''.$processmakerid.'\',\''.$elementid.'\')" class="crmbutton small save" value="'.$app_strings['LBL_SAVE_BUTTON_LABEL'].'" title="'.$app_strings['LBL_SAVE_BUTTON_LABEL'].'">
					<input type="button" onclick="closePopup();" class="crmbutton small cancel" value="'.$mod_strings['LBL_CANCEL_BUTTON'].'" title="'.$mod_strings['LBL_CANCEL_BUTTON'].'">
				</td>
			 </tr>
			 </table>';
		$smarty->assign("BUTTON_LIST", $buttons);
		$smarty->assign("PAGE_TITLE", $mod_strings['LBL_PM_IMPORT_MODULE_BLOCK_TITLE']);
		$smarty->assign("PAGE_RIGHT_TITLE", $elementid);
		$smarty->assign("ELEMENTID", $elementid);
		$smarty->assign("HEADER_Z_INDEX", 1);
		$smarty->assign("TITLES", $titles);
		$smarty->assign("ID", $processmakerid);
		
		$records_pick = $PMUtils->getRecordsInvolvedOptions($processmakerid, '', false, null, null, true);	//crmv@135190
		$smarty->assign("RECORDS_INVOLVED", $records_pick);
		
		$smarty->display('Settings/ProcessMaker/Metadata/ImportModuleBlocks.tpl');
		exit;
		break;
	case 'loadmoduleblocks':
		$processmakerid = vtlib_purify($_REQUEST['id']);
		$record_involved = vtlib_purify($_REQUEST['record_involved']);
		
		if (empty($_REQUEST['record_involved'])) return;
		
		// support multiple reference
		list($metaid,$module,$reference,,$reference_module) = explode(':',$record_involved);
		if (!empty($reference)) {
			if (!empty($reference_module)) {
				$module = $reference_module;
			} else {
				$result = $adb->pquery("select relmodule from {$table_prefix}_fieldmodulerel where fieldid = ?", array($reference));
				if ($result && $adb->num_rows($result) > 0) {
					$module = $adb->query_result($result,0,'relmodule');
				} else {
					// field is not a reference
					return;
				}
			}
			$result = $adb->pquery("select fieldname from {$table_prefix}_field where fieldid = ?", array($reference));
			if ($result && $adb->num_rows($result) > 0) {
				$reference_fieldname = $adb->query_result($result,0,'fieldname');
			}
		}
		
		require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
		ob_start(); require_once('modules/Settings/ModuleMaker/ModuleMakerAjax.php'); ob_end_clean(); // hide echo
		$MMUtils = new ModuleMakerUtils();
		$MMSteps = new ProcessModuleMakerSteps($MMUtils);
		$MMGen = new ModuleMakerGenerator($MMUtils, $MMSteps);
		$MMajax = new ModuleMakerAjax($MMUtils, $MMSteps, $MMGen);
		
		$vars = array();
		$MMSteps->preprocessStepVars('ajax', 2, 0, $vars);
		
		// add to vars the configuration of the module
		require_once("vtlib/Vtecrm/Package.php");
		require_once("vtlib/Vtecrm/Module.php");
		$package = new Vtecrm_Package();
		$package->_export_write_mode = 'string';
		$moduleInstance = Vtecrm_Module::getInstance($module);
		$package->__initExport($module, $moduleInstance);
		$package->export_Module($moduleInstance);
		$package->__finishExport();
		$xml_string = $package->getManifestString();
		$modulenode = simplexml_load_string($xml_string);
		
		if (!empty($modulenode->panels) && !empty($modulenode->panels->panel)) {
			$panel_i = 0; $block_i = 0; $field_i = 0;
			foreach($modulenode->panels->panel as $panelnode) {
				$MMajax->addPanel($vars, getTranslatedString(strval($panelnode->label),$module));
				
				if(!empty($panelnode->blocks) && !empty($panelnode->blocks->block)) {
					foreach($panelnode->blocks->block as $blocknode) {
						$MMajax->addBlock($vars, getTranslatedString(strval($blocknode->label),$module), $panel_i, $block_i);
						
						if(!empty($blocknode->fields) && !empty($blocknode->fields->field)) {
							foreach($blocknode->fields->field as $fieldnode) {
								$fieldno = '';
								$default = '';
								$properties = $MMUtils->getModuleMakerFieldProperties($module, strval($fieldnode->fieldname), $fieldno);
								if ($fieldnode->uitype != 220) {
									if (empty($reference)) {
										$default = "\${$metaid}-{$fieldnode->fieldname}";
									} else {
										$default = "\${$metaid}-({$reference_fieldname} : ({$module}) {$fieldnode->fieldname})";
									}
								}
								$MMajax->addField($vars, $block_i, $fieldno, $properties, array(
									'uitype' => strval($fieldnode->uitype),
									'fieldname' => strval($fieldnode->fieldname),
									'readonly' => 99,
									'default' => $default,
								));
								$field_i++;
							}
						}
						$block_i++;
					}
				}
				$panel_i++;
			}
		}
		// end
		
		$stepvars = $MMSteps->extractStepVars($vars);
		$smarty->assign("STEPVARS", $stepvars);
		
		$smarty->assign("PROCESSMAKERMODE", true);
		$smarty->assign("MODE", 'loadmoduleblocks');
		$smarty->assign("LAYOUT_READONLY", true);
		
		echo '<form id="module_maker_form" method="POST">';
		$smarty->display('Settings/ProcessMaker/Metadata/HelperFields.tpl');
		echo '</form>';
		exit;
		break;
	case 'importmoduleblocks':
		$processmakerid = vtlib_purify($_REQUEST['id']);
		$elementid = $_REQUEST['elementid'];
		$module_mmaker = $_REQUEST['module_mmaker'];
		$dynaform = $_REQUEST['mmaker'];
		if (!empty($module_mmaker)) {
			$data = $PMUtils->retrieve($processmakerid);
			$helper = Zend_Json::decode($data['helper']);
			
			require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
			$MMUtils = new ModuleMakerUtils();
			$MMSteps = new ProcessModuleMakerSteps($MMUtils);
			$MMSteps->preprocessStepVars('ajax', 2, 0, $dynaform);
			$final_dynaform = $MMSteps->extractStepVars($dynaform);
			
			if (empty($final_dynaform['mmaker_lastfieldid'])) $final_dynaform['mmaker_lastfieldid'] = 0;
			
			$MMSteps->preprocessStepVars('ajax', 2, 0, $module_mmaker);
			$new_blocks = $MMSteps->extractStepVars($module_mmaker);
			if (!empty($new_blocks['mmaker_blocks'])) {
				foreach($new_blocks['mmaker_blocks'] as $b) {
					unset($b['panelno']);
					if (!empty($b['fields'])) {
						foreach($b['fields'] as &$field) {
							$final_dynaform['mmaker_lastfieldid']++;
							$field['fieldname'] = 'vcf_'.$final_dynaform['mmaker_lastfieldid'];
							if ($field['uitype'] == '300') $field['uitype'] = '15';
						}
					}
					$final_dynaform['mmaker_blocks'][] = $b;
				}
			}
			
			$smarty->assign("STEPVARS", $final_dynaform);
			$smarty->assign("NEWFIELDS", $MMSteps->getNewFields());
			$smarty->assign("NEWTABLEFIELDCOLUMNS", $MMSteps->getNewTableFieldColumns()); // crmv@102879
			$smarty->assign("PROCESSMAKERMODE", true);
			$smarty->display('Settings/ModuleMaker/Step2Fields.tpl');
		}
		exit;
		break;
	//crmv@160837e
	//crmv@99316 crmv@112297
	case 'advanced_metadata':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = $_REQUEST['elementid'];
		
		$data = $PMUtils->retrieve($id);
		$structure = Zend_Json::decode($data['structure']);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		$vte_metadata_arr = $vte_metadata[$elementid];
		
		$title = $PMUtils->getElementTitle($structure['shapes'][$elementid]);
		$smarty->assign("PAGE_TITLE", $title.': '.$mod_strings['LBL_PM_ADVANCED_ACTIONS']);
		$smarty->assign("PAGE_RIGHT_TITLE", $elementid);
		$smarty->assign("HEADER_Z_INDEX", 1);
		$smarty->assign("ID", $elementid);
		$smarty->assign("PROCESSID", $id);
		$buttons = '
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%" style="padding:5px"></td>
			 	<td align="right" style="padding: 5px;" nowrap>
					<span class="indicatorMetadata" style="display:none;"><i class="dataloader" data-loader="circle" style="vertical-align:middle;"></i></span>&nbsp;
					<input type="button" onclick="ProcessMakerScript.closeAdvMetadata(\''.$id.'\',\''.$elementid.'\');" class="crmbutton small" value="'.$app_strings['LBL_BACK'].'" title="'.$app_strings['LBL_BACK'].'">
				</td>
			 </tr>
			 </table>';
		$smarty->assign("BUTTON_LIST", $buttons);
		$smarty->assign("METADATA", $vte_metadata_arr);
		
		//crmv@100731
		$smarty->assign("ADV_RECORD_INVOLVED", $PMUtils->getRecordsInvolvedOptions($id, ''));
		$_REQUEST['enable_editoptions'] = 'yes';
		//crmv@160843
		$_REQUEST['editoptionsfieldnames'] = 'assigned_user_id';
		$adv_assignedto = getOutputHtml(53,'assigned_user_id','LBL_ASSIGNED_TO',100,array(),1,'Settings','',1,'I~M');
		$adv_assignedto[3][2]['skip_advanced_type_option'] = true;
		$smarty->assign('ADV_ASSIGNEDTO', $adv_assignedto);
		//crmv@160843e
		$adv_permissions_list = $vte_metadata_arr['advanced_permissions'];
		if (!empty($adv_permissions_list)) {
			foreach($adv_permissions_list as &$ap) {
				$ap['record_involved_display'] = $PMUtils->getRecordsInvolvedLabel($id,substr($ap['record_involved'],0,strpos($ap['record_involved'],':')));
				$ap['resource_display'] = $PMUtils->getTranslatedProcessResource($id,$ap['resource']);
				if ($ap['permission'] == 'rw')
					$ap['permission_display'] = getTranslatedString('Read/Write','Settings');
				elseif ($ap['permission'] == 'ro')
					$ap['permission_display'] = getTranslatedString('Read Only ','Settings');
			}
		}
		$smarty->assign('ADV_PERMISSIONS_LIST',$adv_permissions_list);
		
		$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerFieldActions());
		
		$involvedRecords = $PMUtils->getRecordsInvolved($id,true);
		$smarty->assign('JSON_INVOLVED_RECORDS',Zend_Json::encode($involvedRecords));
		
		require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$dynaFormOptions = $processDynaFormObj->getFieldsOptions($id,true);
		$smarty->assign('JSON_DYNAFORM_OPTIONS',Zend_Json::encode($dynaFormOptions));
		//crmv@100731e
		
		//crmv@100591
		$elementsActors = $PMUtils->getElementsActors($id);
		$smarty->assign('JSON_ELEMENTS_ACTORS',Zend_Json::encode($elementsActors));
		//crmv@100591e
		
		$smarty->display('Settings/ProcessMaker/Metadata/Advanced.tpl');
		exit;
		break;
	case 'edit_dynaform_conditional':
	case 'edit_conditional':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = $_REQUEST['elementid'];
		$ruleid = $_REQUEST['ruleid'];
		(empty($ruleid)) ? $mmode = '' : $mmode = 'edit';
		$smarty->assign("MMODE", $mmode);
		$smarty->assign("ID", $elementid);
		$smarty->assign("PROCESSID", $id);
		$smarty->assign("RULEID", $ruleid);
		
		$data = $PMUtils->retrieve($id);
		$structure = Zend_Json::decode($data['structure']);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		$vte_metadata_arr = $vte_metadata[$elementid];
		
		if ($mode == 'edit_dynaform_conditional') {
			$conditionals = $vte_metadata_arr['dfconditionals'];
			$save_function = 'saveDynaFormConditional';
			$close_function = 'closeDynaFormConditional';
			$smarty->assign("SAVE_MODE", 'save_dynaform_conditional');
		} else {
			$conditionals = $vte_metadata_arr['conditionals'];
			$save_function = 'saveConditional';
			$close_function = 'closeConditional';
			$smarty->assign("SAVE_MODE", 'save_conditional');
		}
		
		$title = $PMUtils->getElementTitle($structure['shapes'][$elementid]);
		($mmode == '') ? $title .= ' > '.getTranslatedString('LBL_CREATE_NEW_CONDITIONAL','Conditionals') : $title .= ' > '.getTranslatedString('LBL_EDIT');
		$smarty->assign("PAGE_TITLE", $title);
		$smarty->assign("PAGE_RIGHT_TITLE", $elementid);
		$smarty->assign("HEADER_Z_INDEX", 1);
		$buttons = '
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%" style="padding:5px"></td>
			 	<td align="right" style="padding: 5px;" nowrap>
					<span class="indicatorMetadata" style="display:none;"><i class="dataloader" data-loader="circle" style="vertical-align:middle;"></i></span>&nbsp;
					<input type="button" onclick="ProcessMakerScript.'.$save_function.'(\''.$id.'\',\''.$elementid.'\',\''.$ruleid.'\')" class="crmbutton small save" value="'.$app_strings['LBL_SAVE_BUTTON_LABEL'].'" title="'.$app_strings['LBL_SAVE_BUTTON_LABEL'].'">
					<input type="button" onclick="ProcessMakerScript.'.$close_function.'(\''.$id.'\',\''.$elementid.'\',\''.$ruleid.'\');" class="crmbutton small cancel" value="'.$mod_strings['LBL_CANCEL_BUTTON'].'" title="'.$mod_strings['LBL_CANCEL_BUTTON'].'">
				</td>
			 </tr>
			 </table>';
		$smarty->assign("BUTTON_LIST", $buttons);
		
		if ($mmode == 'edit') {
			$smarty->assign("TITLE", $conditionals[$ruleid]['title']);
			$smarty->assign("RULES", $conditionals[$ruleid]['rules']);
			$smarty->assign("CONDITIONS", Zend_Json::encode($conditionals[$ruleid]['conditions']));
			$role_grp_check = $conditionals[$ruleid]['role_grp_check'];
			$fpofv_saved = $conditionals[$ruleid]['fpofv'];
		}
		
		$roleDetails=getAllRoleDetails();
		unset($roleDetails['H1']);
		$grpDetails=getAllGroupName();
		$role_grp_check_picklist = '<select id="role_grp_check" name="role_grp_check" class="detailedViewTextBox">';
		($role_grp_check == 'ALL') ? $selected = "selected" : $selected = "";
		$role_grp_check_picklist .= '<option value="ALL" '.$selected.'>'.getTranslatedString('NO_CONDITIONS','Conditionals').'</option>';
		foreach($roleDetails as $roleid=>$rolename) {
			('roles::'.$roleid == $role_grp_check) ? $selected = "selected" : $selected = "";
			$role_grp_check_picklist .='<option value="roles::'.$roleid.'" '.$selected.'>'.getTranslatedString('LBL_ROLES','Conditionals').'::'.$rolename[0].'</option>';
		}
		foreach($roleDetails as $roleid=>$rolename) {
			('rs::'.$roleid == $role_grp_check) ? $selected = "selected" : $selected = "";
			$role_grp_check_picklist .='<option value="rs::'.$roleid.'" '.$selected.'>'.getTranslatedString('LBL_ROLES_SUBORDINATES','Conditionals').'::'.$rolename[0].'</option>';
		}
		foreach($grpDetails as $groupid=>$groupname) {
			('groups::'.$groupid == $role_grp_check) ? $selected = "selected" : $selected = "";
			$role_grp_check_picklist .='<option value="groups::'.$groupid.'" '.$selected.'>'.getTranslatedString('LBL_GROUP','Conditionals').'::'.$groupname.'</option>';
		}
		$role_grp_check_picklist .= '</select>';
		$smarty->assign("ROLE_GRP_CHECK_PICKLIST",$role_grp_check_picklist);
		
		if ($mode == 'edit_dynaform_conditional') {
			$result = $adb->pquery("select id from {$table_prefix}_process_dynaform_meta where processid = ? and elementid = ?", array($id,$elementid));
			if ($result && $adb->num_rows($result) > 0) {
				$smarty->assign("METAID", $adb->query_result($result,0,'id'));
				
				require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
				$processDynaFormObj = ProcessDynaForm::getInstance();
				$blocks = $processDynaFormObj->getStructure($id, $elementid);
				$fpofv_value_options = array();
				if (!empty($blocks)) {
					foreach($blocks as $block) {
						if(is_array($block['fields']) && !empty($block['fields'])){ // crmv@179124
							foreach($block['fields'] as $field) {
								//crmv@106857
								if ($field['uitype'] == 220) {
									$fieldname = $field['fieldname'];
									$label = $field['label'];
									$fpofv_value_options['$'.$fieldname] = $label;
									$fpofv[] = array(
										'FpofvBlockLabel'=>$block['label'],
										'TaskField'=>$fieldname,
										'TaskFieldLabel'=>$label,
										'FpovValueActive'=>$fpofv_saved[$fieldname]['FpovValueActive'],
										'FpovValueStr'=>$fpofv_saved[$fieldname]['FpovValueStr'],
										'FpovManaged'=>$fpofv_saved[$fieldname]['FpovManaged'],
										'FpovReadPermission'=>$fpofv_saved[$fieldname]['FpovReadPermission'],
										'FpovWritePermission'=>$fpofv_saved[$fieldname]['FpovWritePermission'],
										'FpovMandatoryPermission'=>$fpofv_saved[$fieldname]['FpovMandatoryPermission'],
										'HideFpovValue'=>true,
										// crmv@190916 removed code
									);
									if (!empty($field['columns'])) {
										$columns = Zend_Json::decode($field['columns']);
										foreach($columns as $column) {
											$fieldname = $field['fieldname'].'::'.$column['fieldname'];
											$label = $field['label'].': '.$column['label'];
											$fpofv_value_options['$'.$fieldname] = $label;
											$fpofv[] = array(
												'FpofvBlockLabel'=>$block['label'],
												'TaskField'=>$fieldname,
												'TaskFieldLabel'=>$label,
												'FpovValueActive'=>$fpofv_saved[$fieldname]['FpovValueActive'],
												'FpovValueStr'=>$fpofv_saved[$fieldname]['FpovValueStr'],
												'FpovManaged'=>$fpofv_saved[$fieldname]['FpovManaged'],
												'FpovReadPermission'=>$fpofv_saved[$fieldname]['FpovReadPermission'],
												'FpovWritePermission'=>$fpofv_saved[$fieldname]['FpovWritePermission'],
												'FpovMandatoryPermission'=>$fpofv_saved[$fieldname]['FpovMandatoryPermission'],
											);
										}
									}
								} else {
									$fieldname = $field['fieldname'];
									$label = $field['label'];
									$fpofv_value_options['$'.$fieldname] = $label;
									$fpofv[] = array(
										'FpofvBlockLabel'=>$block['label'],
										'TaskField'=>$fieldname,
										'TaskFieldLabel'=>$label,
										'FpovValueActive'=>$fpofv_saved[$fieldname]['FpovValueActive'],
										'FpovValueStr'=>$fpofv_saved[$fieldname]['FpovValueStr'],
										'FpovManaged'=>$fpofv_saved[$fieldname]['FpovManaged'],
										'FpovReadPermission'=>$fpofv_saved[$fieldname]['FpovReadPermission'],
										'FpovWritePermission'=>$fpofv_saved[$fieldname]['FpovWritePermission'],
										'FpovMandatoryPermission'=>$fpofv_saved[$fieldname]['FpovMandatoryPermission'],
									);
								}
								//crmv@106857e
							}
						}
					}
					$smarty->assign("FPOFV_PIECE_DATA", $fpofv);
					$smarty->assign("FPOFV_VALUE_OPTIONS", $fpofv_value_options);
					$smarty->assign('SDK_CUSTOM_FUNCTIONS', SDK::getFormattedProcessMakerFieldActions());
				}
			} else {
				$buttons = '
					<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td width="100%" style="padding:5px"></td>
					 	<td align="right" style="padding: 5px;" nowrap>
							<span class="indicatorMetadata" style="display:none;"><i class="dataloader" data-loader="circle" style="vertical-align:middle;"></i></span>&nbsp;
							<input type="button" onclick="ProcessMakerScript.'.$close_function.'(\''.$id.'\',\''.$elementid.'\',\''.$ruleid.'\');" class="crmbutton small" value="'.$app_strings['LBL_BACK'].'" title="'.$app_strings['LBL_BACKN'].'">
						</td>
					 </tr>
					 </table>';
				$smarty->assign("BUTTON_LIST", $buttons);
				$smarty->assign("ERROR", getTranslatedString('LBL_NONE_DYNAFORM_CONDITIONAL','Settings'));
			}
			$smarty->display('Settings/ProcessMaker/Metadata/DynaformConditional.tpl');
		} else {
			if (empty($ruleid)) {
				$smarty->assign('FIELD_PERMISSIONS_DISPLAY','none');
			} else {
				require_once('include/utils/ModLightUtils.php');
				$MLUtils = ModLightUtils::getInstance();
				
				$smarty->assign('FIELD_PERMISSIONS_DISPLAY','block');
				$moduleName = $conditionals[$ruleid]['moduleName'];
				list($metaid,$module) = explode(':',$moduleName);
				
				$conditionals_obj = CRMEntity::getInstance('Conditionals');
				$FpofvData = $conditionals_obj->wui_getFpofvData('',$module);
				$fpofv = array();
				foreach($FpofvData as $tmp) {
					if ($tmp['uitype'] == 220) {
						$fieldname = $tmp['FpofvChkFieldName'];
						$label = $tmp['FpofvChkFieldLabel'];
						$fpofv_value_options['$'.$fieldname] = $label;
						$fpofv[] = array(
							'FpofvBlockLabel'=>getTranslatedString($tmp['FpofvBlockLabel'],$module),
							'TaskField'=>$fieldname,
							'TaskFieldLabel'=>getTranslatedString($label,$module),
							'FpovValueActive'=>$fpofv_saved[$fieldname]['FpovValueActive'],
							'FpovValueStr'=>$fpofv_saved[$fieldname]['FpovValueStr'],
							'FpovManaged'=>$fpofv_saved[$fieldname]['FpovManaged'],
							'FpovReadPermission'=>$fpofv_saved[$fieldname]['FpovReadPermission'],
							'FpovWritePermission'=>$fpofv_saved[$fieldname]['FpovWritePermission'],
							'FpovMandatoryPermission'=>$fpofv_saved[$fieldname]['FpovMandatoryPermission'],
							'HideFpovValue'=>$tmp['HideFpovValue'],
							'HideFpovManaged'=>$tmp['HideFpovManaged'],
							'HideFpovReadPermission'=>$tmp['HideFpovReadPermission'],
							'HideFpovWritePermission'=>$tmp['HideFpovWritePermission'],
							'HideFpovMandatoryPermission'=>$tmp['HideFpovMandatoryPermission'],
						);
						$columns = $MLUtils->getColumns($module, $tmp['FpofvChkFieldName']);
						if (!empty($columns)) {
							foreach($columns as $column) {
								$fieldname = $tmp['FpofvChkFieldName'].'::'.$column['fieldname'];
								$label = $tmp['FpofvChkFieldLabel'].': '.$column['label'];
								$fpofv_value_options['$'.$fieldname] = $label;
								$fpofv[] = array(
									'FpofvBlockLabel'=>getTranslatedString($tmp['FpofvBlockLabel'],$module),
									'TaskField'=>$fieldname,
									'TaskFieldLabel'=>getTranslatedString($label,$module),
									'FpovValueActive'=>$fpofv_saved[$fieldname]['FpovValueActive'],
									'FpovValueStr'=>$fpofv_saved[$fieldname]['FpovValueStr'],
									'FpovManaged'=>$fpofv_saved[$fieldname]['FpovManaged'],
									'FpovReadPermission'=>$fpofv_saved[$fieldname]['FpovReadPermission'],
									'FpovWritePermission'=>$fpofv_saved[$fieldname]['FpovWritePermission'],
									'FpovMandatoryPermission'=>$fpofv_saved[$fieldname]['FpovMandatoryPermission'],
								);
							}
						}
					} else {
						$fieldname = $tmp['FpofvChkFieldName'];
						$label = $tmp['FpofvChkFieldLabel'];
						$fpofv_value_options['$'.$fieldname] = $label;
						$fpofv[] = array(
							'FpofvBlockLabel'=>getTranslatedString($tmp['FpofvBlockLabel'],$module),
							'TaskField'=>$fieldname,
							'TaskFieldLabel'=>getTranslatedString($label,$module),
							'FpovValueActive'=>$fpofv_saved[$fieldname]['FpovValueActive'],
							'FpovValueStr'=>$fpofv_saved[$fieldname]['FpovValueStr'],
							'FpovManaged'=>$fpofv_saved[$fieldname]['FpovManaged'],
							'FpovReadPermission'=>$fpofv_saved[$fieldname]['FpovReadPermission'],
							'FpovWritePermission'=>$fpofv_saved[$fieldname]['FpovWritePermission'],
							'FpovMandatoryPermission'=>$fpofv_saved[$fieldname]['FpovMandatoryPermission'],
							'HideFpovValue'=>$tmp['HideFpovValue'],
							'HideFpovManaged'=>$tmp['HideFpovManaged'],
							'HideFpovReadPermission'=>$tmp['HideFpovReadPermission'],
							'HideFpovWritePermission'=>$tmp['HideFpovWritePermission'],
							'HideFpovMandatoryPermission'=>$tmp['HideFpovMandatoryPermission'],
						);
					}
				}
				$smarty->assign("FPOFV_PIECE_DATA", $fpofv);
				$smarty->assign("FPOFV_VALUE_OPTIONS", $fpofv_value_options);
				$smarty->assign('SDK_CUSTOM_FUNCTIONS', SDK::getFormattedProcessMakerFieldActions());
			}
			$modules = $PMUtils->getRecordsInvolvedOptions($id, $moduleName);
			$smarty->assign("moduleNames", $modules);
			
			$smarty->display('Settings/ProcessMaker/Metadata/Conditional.tpl');
		}
		exit;
		break;
	case 'load_field_permissions_table':
		require_once('include/utils/ModLightUtils.php');
		$MLUtils = ModLightUtils::getInstance();
		
		$chk_module = $_REQUEST['chk_module'];
		$conditionals_obj = CRMEntity::getInstance('Conditionals');
		$fpofv = array();
		$fpofv_value_options = array();
		if (!empty($chk_module)) {
			$FpofvData = $conditionals_obj->wui_getFpofvData('',$chk_module);
			foreach($FpofvData as $tmp) {
				if ($tmp['uitype'] == 220) {
					$fieldname = $tmp['FpofvChkFieldName'];
					$label = $tmp['FpofvChkFieldLabel'];
					$fpofv_value_options['$'.$fieldname] = $label;
					$fpofv[] = array(
						'FpofvBlockLabel'=>getTranslatedString($tmp['FpofvBlockLabel'],$chk_module),
						'TaskField'=>$fieldname,
						'TaskFieldLabel'=>getTranslatedString($label,$chk_module),
						'HideFpovValue'=>$tmp['HideFpovValue'],
						'HideFpovManaged'=>$tmp['HideFpovManaged'],
						'HideFpovReadPermission'=>$tmp['HideFpovReadPermission'],
						'HideFpovWritePermission'=>$tmp['HideFpovWritePermission'],
						'HideFpovMandatoryPermission'=>$tmp['HideFpovMandatoryPermission'],
					);
					$columns = $MLUtils->getColumns($chk_module, $tmp['FpofvChkFieldName']);
					if (!empty($columns)) {
						foreach($columns as $column) {
							$fieldname = $tmp['FpofvChkFieldName'].'::'.$column['fieldname'];
							$label = $tmp['FpofvChkFieldLabel'].': '.$column['label'];
							$fpofv_value_options['$'.$fieldname] = $label;
							$fpofv[] = array(
								'FpofvBlockLabel'=>getTranslatedString($tmp['FpofvBlockLabel'],$chk_module),
								'TaskField'=>$fieldname,
								'TaskFieldLabel'=>getTranslatedString($label,$chk_module),
							);
						}
					}
				} else {
					$fieldname = $tmp['FpofvChkFieldName'];
					$label = $tmp['FpofvChkFieldLabel'];
					$fpofv_value_options['$'.$fieldname] = $label;
					$fpofv[] = array(
						'FpofvBlockLabel'=>getTranslatedString($tmp['FpofvBlockLabel'],$chk_module),
						'TaskField'=>$fieldname,
						'TaskFieldLabel'=>getTranslatedString($label,$chk_module),
						'HideFpovValue'=>$tmp['HideFpovValue'],
						'HideFpovManaged'=>$tmp['HideFpovManaged'],
						'HideFpovReadPermission'=>$tmp['HideFpovReadPermission'],
						'HideFpovWritePermission'=>$tmp['HideFpovWritePermission'],
						'HideFpovMandatoryPermission'=>$tmp['HideFpovMandatoryPermission'],
					);
				}
			}
		}
		$smarty->assign("FPOFV_PIECE_DATA", $fpofv);
		$smarty->assign("FPOFV_VALUE_OPTIONS", $fpofv_value_options);
		$smarty->assign('SDK_CUSTOM_FUNCTIONS', SDK::getFormattedProcessMakerFieldActions());
		$smarty->display('Settings/ProcessMaker/Metadata/ConditionalFieldTable.tpl');
		exit;
		break;
	case 'save_dynaform_conditional':
	case 'save_conditional':
		$id = vtlib_purify($_REQUEST['processmakerid']);
		$elementid = $_REQUEST['elementid'];
		$ruleid = $_REQUEST['ruleid'];
		$metaid = $_REQUEST['metaid'];
		$conditions = Zend_Json::decode($_REQUEST['conditions']);
		($mode == 'save_dynaform_conditional') ? $item = 'dfconditionals' : $item = 'conditionals';
		
		$fpofv = array();
		foreach($_REQUEST as $k => $v) {
			$perms = array('FpovValueActive','FpovValueStr','FpovManaged','FpovReadPermission','FpovWritePermission','FpovMandatoryPermission');
			foreach($perms as $perm) {
				if (strpos($k,$perm) !== false) {
					list($tmp,$fieldname) = explode($perm,$k);
					if (!empty($fieldname)) {
						$fpofv[$fieldname][$perm] = $v;
					}
				}
			}
		}
		foreach($fpofv as $fieldname => $info) {
			if ($info['FpovValueActive'] != '1') unset($fpofv[$fieldname]['FpovValueStr']);
			if (empty($fpofv[$fieldname])) unset($fpofv[$fieldname]);
		}
		$conditionals = array(
			'title'=>$_REQUEST['title'],
			'role_grp_check'=>$_REQUEST['role_grp_check'],
			'conditions'=>$conditions,
			'fpofv'=>$fpofv,
		);
		if ($mode == 'save_conditional') {
			$conditionals['moduleName'] = $_REQUEST['moduleName'];
		}
		
		$data = $PMUtils->retrieve($id);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		if (empty($ruleid)) {
			$ruleid = 1;
			if (!empty($vte_metadata[$elementid][$item])) {
				end($vte_metadata[$elementid][$item]);
				$ruleid = key($vte_metadata[$elementid][$item])+1;
			}
		}
		$vte_metadata[$elementid][$item][$ruleid] = $conditionals;
		$PMUtils->saveMetadata($id,$elementid,Zend_Json::encode($vte_metadata[$elementid]));
		exit;
		break;
	case 'delete_dynaform_conditional':
	case 'delete_conditional':
		($mode == 'delete_dynaform_conditional') ? $item = 'dfconditionals' : $item = 'conditionals';
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$ruleid = vtlib_purify($_REQUEST['ruleid']);
		
		$data = $PMUtils->retrieve($id);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		unset($vte_metadata[$elementid][$item][$ruleid]);
		$PMUtils->saveMetadata($id,$elementid,Zend_Json::encode($vte_metadata[$elementid]));
		exit;
		break;
	//crmv@99316e crmv@112297e
	//crmv@100731
	case 'add_advanced_permission':
		$id = vtlib_purify($_REQUEST['processmakerid']);
		$elementid = $_REQUEST['elementid'];
		$data = $PMUtils->retrieve($id);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		$vte_metadata[$elementid]['advanced_permissions'][] = array(
			'record_involved'=>$_REQUEST['record_involved'],
			'resource_type'=>$_REQUEST['resource_type'],
			'resource'=>$_REQUEST['resource'],
			'permission'=>$_REQUEST['permission'],
		);
		$PMUtils->saveMetadata($id,$elementid,Zend_Json::encode($vte_metadata[$elementid]));
		exit;
		break;
	case 'delete_advanced_permission':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$ruleid = vtlib_purify($_REQUEST['ruleid']);
		
		$data = $PMUtils->retrieve($id);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		unset($vte_metadata[$elementid]['advanced_permissions'][$ruleid]);
		$PMUtils->saveMetadata($id,$elementid,Zend_Json::encode($vte_metadata[$elementid]));
		exit;
		break;
	//crmv@100731e
	case 'CheckActiveProcesses':
		global $mod_strings;
		$ckeckProcesses = $PMUtils->checkActiveProcesses();
		$success = $ckeckProcesses;
		if (!$success) {
			$limit = $PMUtils->limit_processes;
			$message = sprintf($mod_strings['LBL_PM_LIMIT_EXCEEDED'], $limit);
		}
		echo Zend_Json::encode(array('success' => $success, 'message' => $message));
		exit;
	//crmv@106856
	case 'open_advanced_field_assignment':
		$processmakerid = vtlib_purify($_REQUEST['processid']);
		$elementid = $_REQUEST['elementid'];
		$actionid = $_REQUEST['actionid'];
		$fieldname = $_REQUEST['fieldname'];
		$form_module = $_REQUEST['form_module'];
		
		$smarty->assign("PROCESSID", $processmakerid);
		$smarty->assign("ELEMENTID", $elementid);
		$smarty->assign("ACTIONID", $actionid);
		$smarty->assign("FIELDNAME", $fieldname);
		$smarty->assign("FORM_MODULE", $form_module);
		
		$smarty->assign("PAGE_TITLE", $mod_strings['LBL_PM_ADVANCED_FIELD_ASSIGNMENT']);
		$smarty->assign("HEADER_Z_INDEX", 1);
		$buttons = '
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%" style="padding:5px"></td>
			 	<td align="right" style="padding: 5px;" nowrap>
					<span class="indicatorMetadata" style="display:none;"><i class="dataloader" data-loader="circle" style="vertical-align:middle;"></i></span>&nbsp;
					<input type="button" onclick="ActionTaskScript.saveAdvancedFieldAssignment(\''.$processmakerid.'\',\''.$elementid.'\',\''.$actionid.'\',\''.$fieldname.'\');" class="crmbutton small save" value="'.$app_strings['LBL_SAVE_LABEL'].'" title="'.$app_strings['LBL_SAVE_LABEL'].'">
					<input type="button" onclick="ActionTaskScript.closeAdvancedFieldAssignment(\''.$processmakerid.'\',\''.$elementid.'\',\''.$actionid.'\',\''.$fieldname.'\');" class="crmbutton small delete" value="'.$app_strings['LBL_CANCEL_BUTTON_LABEL'].'" title="'.$app_strings['LBL_CANCEL_BUTTON_LABEL'].'">
				</td>
			 </tr>
			 </table>';
		$smarty->assign("BUTTON_LIST", $buttons);
		
		$data = $PMUtils->retrieve($processmakerid);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		$helper = Zend_Json::decode($data['helper']);
		//crmv@183346
		if ($form_module == 'ModNotifications') {
			$actionType = $PMUtils->getActionTypes('ModNotification');
			require_once($actionType['php_file']);
			$action = new $actionType['class']($actionOptions);
			$uitype = $action->fields[$fieldname]['uitype'];
		} else {
			$formModuleInstance = Vtecrm_Module::getInstance($form_module);
			$result = $adb->pquery("SELECT uitype FROM {$table_prefix}_field WHERE tabid = ? and fieldname = ?", array($formModuleInstance->id,$fieldname));
			if ($result && $adb->num_rows($result) > 0) {
				$uitype = $adb->query_result($result,0,'uitype');
			}
		}
		if ($uitype == 50 || $uitype == 52) $uitype = 51; //crmv@160843
		//crmv@183346e
		//crmv@160843
		$storage = $_REQUEST['storage'];
		if ($storage == 'db') {
			if ($form_module == 'Processes') {
				$rules = $helper[$elementid]['advanced_field_assignment'][$fieldname];
			} else {
				$rules = $vte_metadata[$elementid]['actions'][$actionid]['advanced_field_assignment'][$fieldname];
			}
			$PMUtils->setAdvancedFieldAssignment($fieldname,$rules);
		} elseif ($storage == 'session') {
			$rules = $PMUtils->getAdvancedFieldAssignment($fieldname);
		}
		$PMUtils->addConditionTranslations($rules, $processmakerid);
		
		global $noof_group_rows, $current_user;
		$_REQUEST['enable_editoptions'] = 'yes';
		$_REQUEST['editoptionsfieldnames'] = array();
		get_group_options();
		if (!empty($rules)) {
			foreach($rules as $i => &$rule) {
				$rule = getOutputHtml($uitype,'assigned_user_id'.$i,$rule['conditions_translate'],100,array('assigned_user_id'.$i=>$rule['value']),1,'Settings','',1,'I~M');
				// unset advanced type option
				if ($uitype == 53) {
					$rule[3][2]['skip_advanced_type_option'] = true;
				} else {
					unset($rule[3][1]['type_options'][2]);
				}
				$_REQUEST['sdk_params_'.'assigned_user_id'.$i] = $rule['sdk_params'];
				$_REQUEST['editoptionsfieldnames'][] = 'assigned_user_id'.$i;
			}
		}
		$_REQUEST['editoptionsfieldnames'] = implode('|',$_REQUEST['editoptionsfieldnames']);
		//crmv@160843e
		$smarty->assign("RULES", $rules);
		
		$smarty->display('Settings/ProcessMaker/Metadata/AdvancedFieldAssignment.tpl');
		exit;
	case 'open_advanced_field_assignment_condition':
		$ruleid = vtlib_purify($_REQUEST['ruleid']);
		$processid = vtlib_purify($_REQUEST['processid']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$actionid = vtlib_purify($_REQUEST['actionid']);
		$fieldname = vtlib_purify($_REQUEST['fieldname']);
		$form_module = vtlib_purify($_REQUEST['form_module']);
		$smarty->assign("PROCESSID", $processid);
		$smarty->assign("ELEMENTID", $elementid);
		$smarty->assign("ACTIONID", $actionid);
		$smarty->assign("FIELDNAME", $fieldname);
		
		$current_entity = '';
		if (isset($ruleid)) {
			$rules = $PMUtils->getAdvancedFieldAssignment($fieldname);
			$current_entity = $rules[$ruleid]['meta_record'];
			$smarty->assign("CONDITIONS", Zend_Json::encode($rules[$ruleid]['conditions']));
		}
		$modules = $PMUtils->getRecordsInvolvedOptions($processid, $current_entity);
		//crmv@96450
		require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$dynaforms = $processDynaFormObj->getOptions($processid, $current_entity);
		if (!empty($dynaforms)) $modules = array_merge($modules,$dynaforms);
		//crmv@96450e
		$smarty->assign("moduleNames", $modules);
		
		$smarty->assign("PAGE_TITLE", $mod_strings['LBL_PM_ADVANCED_FIELD_ASSIGNMENT'].': '.$mod_strings['LBL_NEW_CONDITION_BUTTON_LABEL']);
		$smarty->assign("HEADER_Z_INDEX", 1);
		$buttons = '
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%" style="padding:5px"></td>
			 	<td align="right" style="padding: 5px;" nowrap>
					<span class="indicatorMetadata" style="display:none;"><i class="dataloader" data-loader="circle" style="vertical-align:middle;"></i></span>&nbsp;
					<input type="button" onclick="ActionTaskScript.saveAdvancedFieldAssignmentCondition(\''.$processid.'\',\''.$elementid.'\',\''.$actionid.'\',\''.$fieldname.'\',\''.$form_module.'\',\''.$ruleid.'\');" class="crmbutton small save" value="'.$app_strings['LBL_SAVE_LABEL'].'" title="'.$app_strings['LBL_SAVE_LABEL'].'">
					<input type="button" onclick="ActionTaskScript.closeAdvancedFieldAssignmentCondition();" class="crmbutton small delete" value="'.$app_strings['LBL_CANCEL_BUTTON_LABEL'].'" title="'.$app_strings['LBL_CANCEL_BUTTON_LABEL'].'">
				</td>
			 </tr>
			 </table>';
		$smarty->assign("BUTTON_LIST", $buttons);
		
		$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerTaskConditions());
		
		$smarty->display('Settings/ProcessMaker/Metadata/AdvancedFieldAssignmentCondition.tpl');
		exit;
	case 'save_advanced_field_assignment_condition':
		$ruleid = vtlib_purify($_REQUEST['ruleid']);
		$fieldname = $_REQUEST['fieldname'];
		$meta_record = $_REQUEST['meta_record'];
		$conditions = $_REQUEST['conditions'];
		$PMUtils->saveAdvancedFieldAssignment($fieldname,'condition',array($ruleid,$meta_record,$conditions));
		exit;
	case 'save_advanced_field_assignment_values':
		$fieldname = $_REQUEST['fieldname'];
		$PMUtils->saveAdvancedFieldAssignment($fieldname,'values',array($_REQUEST['form']));
		exit;
	case 'delete_advanced_field_assignment':
		$PMUtils->removeAdvancedFieldAssignment($_REQUEST['processid'],$_REQUEST['elementid'],$_REQUEST['actionid'],$_REQUEST['fieldname'],$_REQUEST['ruleid']);
		exit;
	//crmv@106856e
	//crmv@113775
	case 'load_potential_relations':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$record1 = vtlib_purify($_REQUEST['record1']);
		list($metaid1,$module1) = explode(':',$record1);
		
		$relationManager = RelationManager::getInstance();
		$recordsInvolved = $PMUtils->getRecordsInvolved($id);
		$values = array(''=>array(getTranslatedString('LBL_PLEASE_SELECT'),''));
		$values = $PMUtils->getRecordsInvolvedOptions($id, $record1, false, null, null, true); // crmv@191351
		$smarty->assign("RECORDPICK", $values);
		$smarty->assign("ENTITY", '2');
		$smarty->display('Settings/ProcessMaker/actions/RelateRecord.tpl');
		exit;
	//crmv@113775e
	//crmv@121416 crmv@173186
	case 'logs':
		header('location: index.php?module=Settings&action=SettingsAjax&file=LogView&log=processes');
		exit;
	//crmv@121416e crmv@173186e
	//crmv@126184
	case 'load_relation_nton':
		// crmv@191351
		global $table_prefix;
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$record1 = vtlib_purify($_REQUEST['record1']);
		list($metaid1,$module1,$reference1,$meta_processid1,$relatedModule1) = explode(':',$record1);
		$recordModule1 = getSalesEntityType($record1);
		if (!empty($relatedModule1)) {
			if ($relatedModule1 != $recordModule1) {
				return;
			}
			$module1 = $relatedModule1;
		} elseif (!empty($reference1)) {
			$module1 = getSingleFieldValue("{$table_prefix}_fieldmodulerel", "relmodule", "fieldid", $reference1);
		}
		if ($record1 !== false) {
			
			$RM = RelationManager::getInstance();
			$relations = $RM->getRelations($module1, ModuleRelation::$TYPE_NTON, array(), $PMUtils->modules_excluded_link);
			
			$values = array(''=>array(getTranslatedString('LBL_PLEASE_SELECT'),''));
			foreach ($relations as $rel) {
				$relmod = $rel->getSecondModule();
				$values[$relmod] = array(getTranslatedString($relmod, $relmod), '');
			}
			
			$smarty->assign("STATICRECORD", '1');
			$smarty->assign("RECORDPICK", $values);
			$smarty->assign("ENTITY", '2');
			$smarty->display('Settings/ProcessMaker/actions/RelateRecord.tpl');
		}
		// crmv@191351e
		exit;
	case 'load_static_related':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$record1 = vtlib_purify($_REQUEST['record1']);
		list($metaid1,$module1) = explode(':',$record1);
		$module2 = vtlib_purify($_REQUEST['relmodule']);
		
		$list = array_filter(explode(',',$_REQUEST['sel_static_records']));
		
		require_once('modules/Settings/ProcessMaker/actions/RelateStatic.php');
		$RelStat = PMActionRelateStatic::getInstance();
		$RelStat->renderRelated($smarty, $module1, $module2, $list);
		$smarty->display('Settings/ProcessMaker/actions/RelatedRecordList.tpl');
		exit;
	//crmv@126184e
	//crmv@185548
	case 'load_entity_relations':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$record1 = vtlib_purify($_REQUEST['record1']);
		list($metaid1,$module1) = explode(':',$record1);
		$record_pick_1 = array();
		$record_pick_2 = array();
		
		$record_pick_1 = $PMUtils->getRecordsInvolvedOptions($id, $record1, false, null, null, true);
		if (!empty($record1)) {
			$record_pick_2 = $PMUtils->getRecordsInvolvedOptions($id, null, false, null, null, true);	//crmv@135190
		}
		else{
			$records_involved = $record_pick_1;
			foreach($records_involved as $single_module__involved => $single_record__involved){
				if($single_record__involved[1] == 'selected'){
					unset($records_involved[$single_module__involved]);
				}
			}
			$record_pick_2 = $records_involved;
		}
		$mode = 'create';
		foreach($record_pick_1 as $module_list1 => $current_list1){
			foreach($current_list1 as $current_module1 => $current_value1){
				if(isset($current_value1[$record1])){
					$record_pick_1[$module_list1][$current_module1][$record1][1] = 'selected';
					$mode = 'edit';
					break;
				}
			}
		}
		foreach($record_pick_2 as $module_list2 => $current_list2){
			foreach($current_list2 as $current_module2 => $current_value2){
				if($current_value2 == 'selected'){
					$mode = 'edit';
					break;
				}
			}
		}
		
		$smarty->assign("RECORDS_INVOLVED", $record_pick_2);
		$smarty->assign("ENTITY", '2');
		$smarty->assign("MODE", $mode);
		$smarty->assign("SHOW", 1);
		$smarty->display('Settings/ProcessMaker/actions/TransferRelateRecord.tpl');
		exit;
	case 'reload_module_list':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$record1 = vtlib_purify($_REQUEST['record1']);
		$mode = vtlib_purify($_REQUEST['entity_mode']);
		list($metaid1,$module1,$reference1) = explode(':',$record1);
		$record2 = vtlib_purify($_REQUEST['record2']);
		list($metaid2,$module2,$reference2) = explode(':',$record2);
		
		if(!empty($reference1)){
			$module1 = getSingleFieldValue($table_prefix.'_fieldmodulerel', 'relmodule', 'fieldid', $reference1);
		}
		if(!empty($reference2)){
			$module2 = getSingleFieldValue($table_prefix.'_fieldmodulerel', 'relmodule', 'fieldid', $reference2);
		}
		
		$RM = RelationManager::getInstance();
		$related_modules1 = $RM->getRelations($module1);
		$related_modules2 = $RM->getRelations($module2);
		
		$PMUtils = ProcessMakerUtils::getInstance();
		
		$rel_modules1 = $PMUtils->get_all_related_modules($related_modules1);
		$rel_modules2 = $PMUtils->get_all_related_modules($related_modules2);
		
		$rel_modules = array_intersect($rel_modules1, $rel_modules2);
		$rel_modules = array_unique($rel_modules);
		foreach($rel_modules as $related_modules => $module){
			$modules_list[] = $module;
		}
		
		$metadata_action['modules'] = Zend_Json::encode($modules_list);
		
		$rel_modules_list = Zend_Json::decode($metadata_action['modules']);
		$vte_metadata[$elementid]['actions'][$action_id]['modules'] = $rel_modules_list;
		if(!empty($rel_modules_list)){
			foreach($rel_modules_list as $module_index => $module){
				if($module == $module1 || $module == $module2){
					unset($rel_modules_list[$module_index]);
				}
			}
			
			foreach($metadata_action as $parameters => $value){
				if(in_array($parameters, $rel_modules_list)){
					if($value === 'on'){
						$selected_modules[] = $parameters;
					}
				}
			}
			
		}
		
		if($mode == 'edit'){
			$show_pick2 = false;
			$show_list = true;
		}
		else{
			$show_pick2 = false;
			$show_list = false;
		}
		
		$smarty->assign("MODE", $mode);
		$smarty->assign("RELOAD", false);
		$smarty->assign("SHOWPICK2", $show_pick2);
		$smarty->assign("SHOW_LIST", $show_list);
		$smarty->assign("MODULES_LIST", $rel_modules_list);
		$smarty->assign("SELECTED_MODULES_LIST", $selected_modules);
		$smarty->display('Settings/ProcessMaker/actions/TransferRelations.tpl');
		exit;
	//crmv@185548e
	//crmv@126696
	case 'select_nl_recipients':
		global $current_language;
		$camp_strings = get_lang_strings('Newsletter', $current_language);
		$pageTitle = getTranslatedString('ChooseRecipients', 'Newsletter');
		
		$smarty->assign("MOD",$camp_strings);
		$smarty->assign('PAGE_TITLE', $pageTitle);
		
		$focusNewsletter = CRMEntity::getInstance('Newsletter'); //crmv@181281
		$target_modinfo = array();
		foreach ($focusNewsletter->target_modules as $tmod) { //crmv@181281
			if (!vtlib_isModuleActive($tmod)) continue; //crmv@48990
			if (isPermitted($tmod, 'index') != 'yes') continue;
			$cv = CRMEntity::getInstance('CustomView', $tmod); // crmv@115329
			$filterlist = $cv->getCustomViewCombo();
			
			$Slv = SimpleListView::getInstance($tmod);
			$Slv->entriesPerPage = 10;
			$Slv->showCreate = false;
			$Slv->showSuggested = false;
			$Slv->showCheckboxes = false;
			$Slv->selectFunction = 'nlwRecordSelect';
			$list = $Slv->render();
			
			$modinfo = array(
				'filters' => $filterlist,
				'list' => $list,
				'listid' => $Slv->listid
			);
			$target_modinfo[$tmod] = $modinfo;
		}
		
		$smarty->assign('TARGET_MODS', $target_modinfo);
		
		$smarty->assign('HEADER_Z_INDEX', 100);
		
		$smarty->display('Settings/ProcessMaker/actions/SelectNLRecipients.tpl');
		exit;
		break;
	case 'load_nl_template':
		$res = $adb->pquery("SELECT * FROM {$table_prefix}_emailtemplates WHERE templateid = ?", array($_REQUEST['templateid']));
		$templateinfo = $adb->fetchByAssoc($res, -1, false);
		echo Zend_Json::encode($templateinfo);
		exit;
		break;
	//crmv@126696e
	// crmv@187729
	case 'reload_create_pdf':
		$id = vtlib_purify($_REQUEST['id']);
		$elementid = vtlib_purify($_REQUEST['elementid']);
		$pdf_entity = vtlib_purify($_REQUEST['pdf_entity']);
		$entity_mode = vtlib_purify($_REQUEST['entity_mode']);
		$json_result = "";
		$related_field_modules = array();
		$error = "";
		list($metaid,$module,$reference) = explode(':',$pdf_entity);
		if(isset($reference) && !empty($reference)){
			list($reference_values,$module) = explode('::',$pdf_entity);
			if(empty($module)){
				$module = getSingleFieldValue($table_prefix."_fieldmodulerel", "relmodule", "fieldid", $reference);
			}
		}
		
		$fields = array(
			'foldername' => array('label'=>'Folder','type'=>'picklist','uitype'=>15,'typeofdata'=>'I~M'),
			'templatename' => array('label'=>'Template','type'=>'picklist','uitype'=>15,'typeofdata'=>'I~M'),
		);
		
		$templates_name = array();
		$folders_name = array();
		
		$templates_query = "SELECT filename FROM {$table_prefix}_pdfmaker WHERE module = ?";
		$template_res = $adb->pquery($templates_query, array($module));
		if($template_res && $adb->num_rows($template_res) > 0){
			while($row = $adb->fetchByAssoc($template_res, -1, false)){
				$templates_name[] = $row['filename'];
			}
		}
		
		$folder_query = "SELECT foldername FROM {$table_prefix}_crmentityfolder WHERE tabid = ? ORDER BY sequence";
		$folder_res = $adb->pquery($folder_query, array(8));
		if($folder_res && $adb->num_rows($folder_res) > 0){
			while($row = $adb->fetchByAssoc($folder_res, -1, false)){
				$folders_name[] = $row['foldername'];
			}
		}
		
		$template = getOutputHtml($fields['templatename']['uitype'], 'templatename', $fields['templatename']['label'], 100, $col_fields, 1, $module, 'edit', 1, $fields['templatename']['typeofdata'], array('picklistvalues'=>implode("\n",$templates_name)));
		$template[] = 4;
		
		$folder = getOutputHtml($fields['foldername']['uitype'], 'foldername', $fields['foldername']['label'], 100, $col_fields, 1, $module, '', 1, $fields['foldername']['typeofdata'], array('picklistvalues'=>implode("\n",$folders_name)));
		$folder[] = 5;
		
		$blocks = array(
			'LBL_CREATEPDF_INFORMATION' => array(
				'blockid' => 1,
				'panelid' => 0,
				'label' => getTranslatedString('LBL_CREATEPDF_INFORMATION','PDFMaker'),
				'fields' => array(
					array(
						$template,
						$folder,
					),
				)
			),
		);
		if(count($templates_name) == 0){
			$error = getTranslatedString('LBL_NO_TEMPLATE','PDFMaker');
		}
		$result = array("blocks" => $blocks, "templates" => $templates_name, "folders" => $folders_name, "error" => $error);
		
		$json_result = Zend_Json::encode($result);
		echo $json_result;
		exit;
	// crmv@187729e
	//crmv@153321_5
	case 'get_cache':
		$item = vtlib_purify($_REQUEST['item']);
		$cache = $PMUtils->getCache($item);
		echo Zend_Json::encode($cache);
		exit;
		break;
	case 'set_cache':
		$item = vtlib_purify($_REQUEST['item']);
		$value = Zend_Json::decode($_REQUEST['value']);
		$PMUtils->setCache($item,$value);
		exit;
		break;
	//crmv@153321_5e
	default:
		if ($mode == 'delete') {
			$id = vtlib_purify($_REQUEST['id']);
			$PMUtils->delete($id);
			$smarty->assign("MODE", '');
		/*
		} elseif ($mode == 'save') {
			$id = vtlib_purify($_REQUEST['id']);
			$PMUtils->edit($id,$_REQUEST);
		*/
		}
		$limit_exceeded = $PMUtils->limitProcessesExceeded();
		if ($limit_exceeded !== false) {
			global $adb, $table_prefix;
			$result = $adb->limitpQuery("select id from {$table_prefix}_processmaker where active = ?",0,($limit_exceeded-$PMUtils->limit_processes),array(1));
			if ($result && $adb->num_rows($result) > 0) {
				$ids = array();
				while($row=$adb->fetchByAssoc($result)) {
					$ids[] = $row['id'];
				}
				$adb->pquery("update {$table_prefix}_processmaker set active = ? where id in (".generateQuestionMarks($ids).")",array(0,$ids));
			}
		}
		// load list
		$smarty->assign("HEADER", $PMUtils->getHeaderList());
		$smarty->assign("LIST", $PMUtils->getList());
		//crmv@185705
		global $current_language;
		$smarty->assign("CURRENT_LANGUAGE", $current_language);
		//crmv@185705e
		//crmv@121416
		$VP = VTEProperties::getInstance();
		$smarty->assign("SHOW_LOGS_BUTTON", ($VP->get('settings.process_manager.show_logs_button') == 1));
		//crmv@121416e
		$smarty->assign("LIST_TABLE_PROP", array(50,4,'asc')); // crmv@190834
		$sub_template = 'Settings/ProcessMaker/List.tpl';
		break;
}

$smarty->assign("SUB_TEMPLATE", $sub_template);
$smarty->display('Settings/ProcessMaker/ProcessMaker.tpl');