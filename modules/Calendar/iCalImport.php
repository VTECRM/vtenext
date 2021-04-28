<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@38878 */

define('_BENNU_VERSION', '0.1');
// crmv@68357
require_once('modules/Calendar/iCal/includeAll.php');
require_once('modules/Calendar/iCalLastImport.php');
// crmv@68357e

global $import_dir,$current_user,$mod_strings,$app_strings,$currentModule;
global $table_prefix;
global $root_directory;

if($_REQUEST['step']!='undo'){
	$last_import = new iCalLastImport();
	$last_import->clearRecords($current_user->id);
	$file_details = $_FILES['ics_file'];
	$binFile = $table_prefix.'_import'.date('YmdHis');
	$file = $import_dir.''.$binFile;
	$filetmp_name = $file_details['tmp_name'];
	$upload_status = move_uploaded_file($filetmp_name,$file);

	$skip_fields = array(
		'Events'=>array('duration_hours'),
		'Calendar'=>array('eventstatus')
	);
	$required_fields = array();

	$modules = array('Events','Calendar');
	foreach($modules as $module){
		$calendar = CRMEntity::getInstance('Calendar');
		$calendar->initRequiredFields($module);
		$val = array_keys($calendar->required_fields);
		$required_fields[$module] = array_diff($val,$skip_fields[$module]);
	}
	
	$count = array();
	$i = 0;
	
	$config = array( "unique_id" => "VTECRM", "directory" => $root_directory.'cache/import/', "filename" => $binFile);
	$vcalendar = new VTEvcalendar( $config );
	$vcalendar->parse();
	$module = 'Events';
	while( $event = $vcalendar->getComponent("vevent")) {
		$count[$module]++;
		$calendar = CRMEntity::getInstance('Calendar');
		$res = $vcalendar->generateArray($event,"vevent");
		$calendar->column_fields = array_merge($calendar->column_fields,$res);
		$calendar->column_fields['assigned_user_id'] = $current_user->id;
		$skip_record = false;
		foreach($required_fields[$module] as $key){
			if(empty($calendar->column_fields[$key])){
				$skip_count[$module]++;
				$skip_record = true;
				break;
			}
		}
		if($skip_record === true) {
			continue;
		}
		$calendar->save('Calendar');
		$last_import = new iCalLastImport();
		$last_import->setFields(array('id'=>$i,
									'userid' => $current_user->id,
									'entitytype' => 'Calendar',
									'crmid' => $calendar->id));
		$last_import->save();
		$i++;
		$valarm = $event->getProperty('valarm');
		if(!empty($valarm)){
			$calendar->activity_reminder($calendar->id,$calendar->column_fields['reminder_time'],0,'','');
		}
	}
	
	$module = 'Calendar';
	while( $todo = $vcalendar->getComponent("vtodo")) {
		$count[$module]++;
		$calendar = CRMEntity::getInstance('Calendar');
		$calendar->column_fields = $vcalendar->generateArray($todo,"vevent");
		$calendar->column_fields['assigned_user_id'] = $current_user->id;
		$skip_record = false;
		foreach($required_fields[$module] as $key){
			if(empty($calendar->column_fields[$key])){
				$skip_count[$module]++;
				$skip_record = true;
				break;
			}
		}
		if($skip_record === true) {
			continue;
		}
		$calendar->save('Calendar');
		$last_import = new iCalLastImport();
		$last_import->setFields(array('id'=>$i,
									'userid' => $current_user->id,
									'entitytype' => 'Calendar',
									'crmid' => $calendar->id));
		$last_import->save();
		$i++;
		$valarm = $todo->getProperty('valarm');
		if(!empty($valarm)){
			$calendar->activity_reminder($calendar->id,$calendar->column_fields['reminder_time'],0,'','');
		}
	}
	
	unlink($file);

	$smarty = new VteSmarty(); // crmv@81193

	$smarty->assign("MOD", $mod_strings);
	$smarty->assign("APP", $app_strings);
	$smarty->assign("IMP", $import_mod_strings);
	$smarty->assign("THEME", $theme);
	$smarty->assign("IMAGE_PATH", $image_path);
	$parent_tab = vtlib_purify(VteSession::get('import_parenttab'));
	if(empty($parent_Tab)){
		$parent_tab = getParentTab();
	}
	$smarty->assign("MODULE", vtlib_purify($_REQUEST['module']));
	$smarty->assign("SINGLE_MOD", vtlib_purify($_REQUEST['module']));
	$smarty->assign("CATEGORY", $parent_tab);

	$smarty->display("Buttons_List1.tpl");

	$imported_events = $count['Events'] - $skip_count['Events'];
	$imported_tasks = $count['Calendar'] - $skip_count['Calendar'];
	 $message= "<b>".$mod_strings['LBL_SUCCESS']."</b>"
	 			."<br><br>" .$mod_strings['LBL_SUCCESS_EVENTS_1']."  $imported_events"
	 			."<br><br>" .$mod_strings['LBL_SKIPPED_EVENTS_1'].$skip_count['Events']
	 			."<br><br>" .$mod_strings['LBL_SUCCESS_CALENDAR_1']."  $imported_tasks"
	 			."<br><br>" .$mod_strings['LBL_SKIPPED_CALENDAR_1'].$skip_count['Calendar']
	 			."<br><br>";

	$smarty->assign("MESSAGE", $message);
	$smarty->assign("RETURN_MODULE", $currentModule);
	$smarty->assign("RETURN_ACTION", 'ListView');
	$smarty->assign("MODULE", $currentModule);
	$smarty->assign("MODULENAME", $currentModule);
	$smarty->display("iCalImport.tpl");

} else {
	$smarty = new VteSmarty(); // crmv@81193

	$smarty->assign("MOD", $mod_strings);
	$smarty->assign("APP", $app_strings);
	$smarty->assign("IMP", $import_mod_strings);
	$smarty->assign("THEME", $theme);
	$smarty->assign("IMAGE_PATH", $image_path);
	$parent_tab = vtlib_purify(VteSession::get('import_parenttab'));
	if(empty($parent_Tab)){
		$parent_tab = getParentTab();
	}
	$smarty->assign("MODULE", vtlib_purify($_REQUEST['module']));
	$smarty->assign("SINGLE_MOD", vtlib_purify($_REQUEST['module']));
	$smarty->assign("CATEGORY", $parent_tab);

	$smarty->display("Buttons_List1.tpl");

	$last_import = new iCalLastImport();
	$ret_value = $last_import->undo('Calendar', $current_user->id);

	if(!empty($ret_value)){
	 $message= "<b>".$mod_strings['LBL_SUCCESS']."</b>"
	 			."<br><br>" .$mod_strings['LBL_LAST_IMPORT_UNDONE']." ";
	} else {
	 $message= "<b>".$mod_strings['LBL_FAILURE']."</b>"
	 			."<br><br>" .$mod_strings['LBL_NO_IMPORT_TO_UNDO']." ";
	}

	$smarty->assign("MESSAGE", $message);
	$smarty->assign("UNDO", 'yes');
	$smarty->assign("RETURN_MODULE", $currentModule);
	$smarty->assign("RETURN_ACTION", 'ListView');
	$smarty->assign("MODULE", $currentModule);
	$smarty->assign("MODULENAME", $currentModule);
	$smarty->display("iCalImport.tpl");
}
?>