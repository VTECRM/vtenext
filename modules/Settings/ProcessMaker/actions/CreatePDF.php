<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@187729 */

require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901
require_once('modules/com_workflow/VTSimpleTemplate.inc');//crmv@207901
require_once('include/Webservices/DescribeObject.php');
require_once(dirname(__FILE__).'/Base.php');

class PMActionCreatePDF extends PMActionBase {
	
	var $fields = array(
		'subject' => array('label'=>'Subject','type'=>'string','uitype'=>1,'typeofdata'=>'V~M'),
		'related_to_entity' => array('label'=>'Related To Entity','type'=>'reference','uitype'=>10,'typeofdata'=>'I~M'),
		'entity_name' => array('label'=>'Entity Name','type'=>'reference','uitype'=>10,'typeofdata'=>'I~M'),
		'foldername' => array('label'=>'Folder','type'=>'picklist','uitype'=>15,'typeofdata'=>'I~M'),
		'templatename' => array('label'=>'Template','type'=>'picklist','uitype'=>15,'typeofdata'=>'I~M'),
		'language' => array('label'=>'Language','type'=>'picklist','uitype'=>15,'typeofdata'=>'I~M'),
		'assigned_user_id' => array('label'=>'Assigned To','type'=>'owner','uitype'=>53,'typeofdata'=>'I~M'),
	);
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		global $table_prefix, $adb, $current_user;		
		
		$mode = 'create';
		$module = 'PDFMaker';
		$_REQUEST['enable_editoptions'] = 'yes';
		$_REQUEST['editoptionsfieldnames'] = implode('|',array_keys($this->fields));
		$PMUtils = ProcessMakerUtils::getInstance();
		$record_involved = null;
		$error = getTranslatedString('LBL_SELECT_PDF_ENTITY', $module);
		$find_folder = false;
		$find_template = false;
		
		if ($action_id != '') {
			$metadata = $PMUtils->getMetadata($id,$elementid);
			$metadata_action = $metadata['actions'][$action_id];
			$metadata_form = $metadata_action['form'];

			if (!empty($metadata_form)) {
				if(isset($metadata_form['pdf_entity']) && !empty($metadata_form['pdf_entity'])){
					$record_involved = $metadata_form['pdf_entity'];
					list($metaid,$entity_module,$reference) = explode(':',$record_involved);
					if(isset($reference) && !empty($reference)){
						list($reference_values,$entity_module) = explode('::',$record_involved);
						if(empty($entity_module)){
							$entity_module = getSingleFieldValue($table_prefix."_fieldmodulerel", "relmodule", "fieldid", $reference);
						}
					}
					$error = '';
				}
				$mode = 'edit';
				foreach($metadata_form as $name => $value) {
					$col_fields[$name] = $value;
					$_REQUEST[$name] = $value;
				}
			}
			$smarty->assign('METADATA', $metadata_action);
		} else {
			// in create se default
			$col_fields['subject'] = 'pdf document name';
			$col_fields['language'] = 'it_it';
			$col_fields['assigned_user_id'] = $current_user->id;
			
		}

		$smarty->assign('ERROR', $error);
		$record_pick = $PMUtils->getRecordsInvolvedOptions($id, $record_involved, false, null, null, true);
		$smarty->assign('RECORDS_INVOLVED', $record_pick);

		$templates_name = array();
		$folders_name = array();

		if($mode == 'edit'){
			$templates_query = "SELECT filename FROM {$table_prefix}_pdfmaker WHERE module = ?";
			$template_res = $adb->pquery($templates_query, array($entity_module));

			if($template_res && $adb->num_rows($template_res) > 0){
				while($row = $adb->fetchByAssoc($template_res, -1, false)){
					$templates_name[] = $row['filename'];
				}
				foreach($templates_name as $template){
					if($template == $metadata_form['templatename']){
						$find_template = true;
					}
				}
				if(!$find_template){
					if(!empty($col_fields['templateid'])){
						$col_fields['templatename'] = $col_fields['templateid'];
					}
				}
			}
			
			$folder_query = "SELECT foldername FROM {$table_prefix}_crmentityfolder WHERE tabid = ? ORDER BY sequence";
			$folder_res = $adb->pquery($folder_query, array(8));
			
			if($folder_res && $adb->num_rows($folder_res) > 0){
				while($row = $adb->fetchByAssoc($folder_res, -1, false)){
					$folders_name[] = $row['foldername'];
				}
				foreach($folders_name as $folder){
					if($folder == $metadata_form['foldername']){
						$find_folder = true;
					}
				}
				if(!$find_folder){
					if(!empty($col_fields['folderid'])){
						$col_fields['foldername'] = $col_fields['folderid'];
					}
				}
			}
		}

		foreach(getMenuModuleList(true) as $index => $modules_list){
			foreach($modules_list as $module_index => $modules_values){
				$relatedmods[] = $modules_values['name'];
			}
		}
		
		
		sort($relatedmods);
		$relatedmods = array_unique($relatedmods);

		$assigned_user_id = getOutputHtml($this->fields['assigned_user_id']['uitype'], 'assigned_user_id', $this->fields['assigned_user_id']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['assigned_user_id']['typeofdata']);
		$assigned_user_id[] = 3;

		$template = getOutputHtml($this->fields['templatename']['uitype'], 'templatename', $this->fields['templatename']['label'], 100, $col_fields, 1, $module, 'edit', 1, $this->fields['templatename']['typeofdata'], array('picklistvalues'=>implode("\n",$templates_name)));
		$template[] = 4;
		
		
		$folder = getOutputHtml($this->fields['foldername']['uitype'], 'foldername', $this->fields['foldername']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['foldername']['typeofdata'], array('picklistvalues'=>implode("\n",$folders_name)));
		$folder[] = 5;
		
		$subject = getOutputHtml($this->fields['subject']['uitype'], 'subject', $this->fields['subject']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['subject']['typeofdata']);
		$subject[] = 6;

		$related_to_entity = getOutputHtml($this->fields['related_to_entity']['uitype'], 'related_to_entity', $this->fields['related_to_entity']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['related_to_entity']['typeofdata'], array('relatedmods'=>implode(',',$relatedmods)));
		$related_to_entity[] = 7;

		$languages = vtlib_getToggleLanguageInfo();
		foreach($languages as $language => $language_name){
			$langs[] = $language;
		}

		$language = getOutputHtml($this->fields['language']['uitype'], 'language', $this->fields['language']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['language']['typeofdata'], array('picklistvalues'=>implode("\n",$langs)));
		$language[] = 8;
		
		$blocks = array(
			'LBL_CREATEPDF_INFORMATION' => array(
				'blockid' => 0,
				'panelid' => 0,
				'label' => getTranslatedString('LBL_CREATEPDF_INFORMATION',$module),
				'fields' => array(
					array(
						$subject,
						$related_to_entity,
						$language,
						$assigned_user_id,
					),
				)
			),
			'LBL_CREATEPDF_CUSTOM_INFORMATION' => array(
				'blockid' => 1,
				'panelid' => 0,
				'label' => getTranslatedString('LBL_CREATEPDF_CUSTOM_INFORMATION',$module),
				'fields' => array(
					array(
						$template,
						$folder,
					),
				)
			),
		);
		
		$smarty->assign("BLOCKS",$blocks);
		$smarty->assign("MODULE",$module);
		$smarty->assign("MODE",$mode);
		$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerFieldActions());
	}
	
	function execute($engine,$actionid) {
		global $adb, $table_prefix,$current_user;
		
		$generate = true;
		require_once('modules/PDFMaker/InventoryPDF.php');
		require_once('include/mpdf/mpdf.php'); 		
		$action = $engine->vte_metadata['actions'][$actionid];
        (!empty($this->cycleRow['row']['record_id'])) ? $cycleRelatedId = $this->cycleRow['row']['record_id'] : $cycleRelatedId = null;//crmv@203075
        foreach($action['form'] as $fieldname => $fieldinfo) {
			$params[$fieldname] = $action['form'][$fieldname];
			$params[$fieldname] = $engine->replaceTags($fieldname, $action['form'][$fieldname], array(), array(), $actionid, null, false, true, $cycleRelatedId);//crmv@203075
		}

		list($metaid,$record_module,$reference) = explode(':',$action['form']['pdf_entity']);
		if(isset($reference) && !empty($reference)){
			list($reference_values,$record_module) = explode('::',$action['form']['pdf_entity']);
			if(empty($record_module)){
				$record_module = getSingleFieldValue($table_prefix."_fieldmodulerel", "relmodule", "fieldid", $reference);
			}
		}
		//crmv@203075
        if($cycleRelatedId == null)
            $record = $engine->getCrmid($metaid, null, $reference);
        else
            $record = $cycleRelatedId;
        //crmv@203075e
		$module = getSalesEntityType($record);
		
		if($record_module != $module){
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - related entity {$action['form']['pdf_entity']} doesn't exist");
			$generate = false;
		}
		if(empty($action['form']['related_to_entity']) && $generate){
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - empty related_to - can't create pdf document");
			$generate = false;
		}
		if(empty($action['form']['pdf_entity']) && $generate){
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - empty pdf entity - can't create pdf document");
			$generate = false;
		}
		if(empty($action['form']['templateid']) && $generate){
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - empty templateid - can't create pdf document");
			$generate = false;
		}
		if(empty($action['form']['folderid']) && $generate){
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - empty folderid - can't create pdf document");
			$generate = false;
		}
		if(empty($params['assigned_user_id']) && $generate){
			$params['assigned_user_id'] = $current_user->id;
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - empty assigned_user_id - new assigned_user_id = {$current_user->id}");
		}
		if(empty($params['language']) && $generate){
			$params['language'] = 'it_it';
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - empty language - new language = it_it");
		}
		if(empty($params['subject']) && $generate){
			$params['subject'] = 'pdf_document';
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - empty subject - new subject = pdf_document");
		}
		
		if (strpos($params['assigned_user_id'],'$') !== false) $params['assigned_user_id'] = $engine->replaceTags('assigned_user_id', $params['assigned_user_id'], array(), array());
		if (strpos($params['assigned_user_id'],'x')) list(,$params['assigned_user_id']) = explode('x',$params['assigned_user_id']);
		
		if (strpos($params['related_to_entity'],'x')){
			list($related_wsId,$params['related_to_entity']) = explode('x',$params['related_to_entity']);
			$related_module = getSalesEntityType($params['related_to_entity']);
		}
		if(!empty($params['templateid'])){
			$templateid_query = "SELECT templateid FROM {$table_prefix}_pdfmaker WHERE templateid = ? AND module = ?";
			$res_templateid = $adb->pquery($templateid_query, array($params['templateid'], $module));
			if($res_templateid && $adb->num_rows($res_templateid) > 0){
				$templateid = $adb->query_result($res_templateid, 0, "templateid");
			}
			else{
				$generate = false;
				$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - templateid not valid! - can't create pdf document");
			}
		}
		if(!empty($params['folderid']) && $generate){
			$folderid_query = "SELECT folderid FROM {$table_prefix}_crmentityfolder WHERE folderid = ? AND tabid = ?";
			$res_folderid = $adb->pquery($folderid_query, array($params['folderid'], 8));
			if($res_folderid && $adb->num_rows($res_folderid) > 0){
				$folderid = $adb->query_result($res_folderid, 0, "folderid");
			}
			else{
				$generate = false;
				$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - folderid not valid! - can't create pdf document");
			}
		}

		if (!empty($params['pdf_entity']) && !empty($params['related_to_entity']) && $module == $record_module && !empty($templateid) && !empty($folderid)) {
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - record involved: {$action['record_involved']} recordid: {$record}");
			$language = $params['language'];
			
			$focus = CRMEntity::getInstance('Documents');
			if(isset($params['related_to_entity']) && !empty($params['related_to_entity'])){
				$focus->parentid = $params['related_to_entity'];
				$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - relate pdf document to entityid: {$params['related_to_entity']}");
			}
			else{
				$focus->parentid = $record;
			}

			$modFocus = CRMEntity::getInstance($module);  
			if(isset($record) && !empty($record)) 
			{
				$modFocus->retrieve_entity_info_no_html($record,$module); // crmv@198553 
				$modFocus->id = $focus->parentid;
			} 

			$result=$adb->query("SELECT fieldname FROM ".$table_prefix."_field WHERE uitype=4 AND tabid=".getTabId($module));
			$fieldname=$adb->query_result($result,0,"fieldname");
			
			if(isset($modFocus->column_fields[$fieldname]) && $modFocus->column_fields[$fieldname]!="")
			{
				$file_name = generate_cool_uri($modFocus->column_fields[$fieldname]).".pdf";
			}
			else
			{
				$file_name = "doc_".$focus->parentid.date("ymdHi").".pdf";
			}

			$focus->column_fields["notes_title"] = $params['subject'].".pdf";
			$focus->column_fields["assigned_user_id"] = $params['assigned_user_id'];
			$focus->column_fields["filename"] = $file_name;
			$focus->column_fields["notecontent"] = ''; 
			$focus->column_fields["filetype"] = "application/pdf"; 
			$focus->column_fields["filesize"] = 0;
			$focus->column_fields["filelocationtype"] = ""; // crmv@198553 
			$focus->column_fields["fileversion"] = '';
			$focus->column_fields["filestatus"] = "on";
			$focus->column_fields["folderid"] = $folderid;

			$focus->save("Documents");
			$docid = $focus->id;
			
			createPDFAndSaveFile($templateid,$focus,$modFocus,$file_name,$module,$language);
			
			$adb->pquery("UPDATE {$focus->table_name} SET filelocationtype = ? WHERE {$focus->table_index} = ?",array('I',$docid)); // crmv@198553 
			
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']}  - documentid : {$docid}");
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - pdf document saved into folderid: {$folderid}");
			$engine->log("Action CreatePDF","action $actionid - {$action['action_title']} - pdf document templateid: {$templateid}");
		}	
	}
}