<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@104180 crmv@106857 crmv@112297 crmv@115268 */

require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
require_once('include/BaseClasses.php');

class TableFieldUtils extends SDKExtendableUniqueClass {

	protected $pmutils;
	protected $pdynaform;
	
	public function __construct() {
		
		$this->pmutils = ProcessMakerUtils::getInstance();
		$this->pdynaform = ProcessDynaForm::getInstance();
	}
	
	public function getColumnsFromProcess($recordid, $processid, $runid, $fieldname) {
		$focus = CRMEntity::getInstance('Processes');
		$r = $focus->retrieve_entity_info($recordid, 'Processes', false);
		if ($r) return array();
		$data = $this->pmutils->retrieve($processid);
		$fields = $this->pdynaform->getFields($focus, $processid);
		$colfield = $fields[$fieldname];
		$columns = Zend_Json::decode($colfield['columns']);
		return $columns;
	}

	public function generateRowVars($mode, $fieldname, $rowno, $columns, $values = array(), $rowid='') {
		// $permissions are set in:
		// ProcessDynaForm::getConditionalPermissions for
		//  - applyConditionals() (edit mode in dynaforms)
		//  - addBlockInformation() (detail mode in dynaforms)
		// ProcessMakerUtils::getConditionalPermissions for the edit mode in modules
		global $edit_view_conditionals_mode;
		$cache = RCache::getInstance();
		$permissions = $cache->get('conditional_permissions');

		if (is_array($columns)) {
			if (stripos($fieldname,'ml') !== false) {
				$modulelightid = str_replace('ml','',$fieldname);
				$module = 'ModLight'.$modulelightid;
				$add_index_to_values_keys = false;
				$add_index_to_columns_fieldname = false;
				$convert_dynaform_picklistvalues = false;
				$convert_dynaform_reference = false;	//crmv@131239
				$convert_dynaform_users = false;	//crmv@155093
				$add_index_to_output_function = false;
				if ($edit_view_conditionals_mode) {
					$add_index_to_values_keys = false;
					$add_index_to_columns_fieldname = true;
				}
			} else {
				$module = 'Processes';
				$add_index_to_values_keys = true;
				$add_index_to_columns_fieldname = true;
				$convert_dynaform_picklistvalues = true;
				$convert_dynaform_reference = true;	//crmv@131239
				$convert_dynaform_users = true;	//crmv@155093
				$add_index_to_output_function = true;
			}
			// alter the field names so they are different for each row
			if (!empty($values) && $add_index_to_values_keys) {
				$values_tmp = $values;
				$values = array();
				foreach ($values_tmp as $col => $value) {
					$values[$fieldname.'_'.$col.'_'.$rowno] = $value;
				}
			}
			foreach ($columns as &$col) {
				$dynaform_info = array();
				$fname = $col['fieldname'];
				$row_fieldname = $fieldname.'_'.$fname.'_'.$rowno;
				if (array_key_exists($fname, $values)) {
					$col['fieldvalue'] = $values[$fname];
				}
				($add_index_to_columns_fieldname) ? $column_fieldname = $row_fieldname : $column_fieldname = $fname;
				if ($convert_dynaform_picklistvalues && in_array($col['uitype'],array(15,33))) {
					$dynaform_info['picklistvalues'] = $col['picklistvalues'];
				}
				//crmv@131239
				if ($convert_dynaform_reference && in_array($col['uitype'],array(10))) {
					$dynaform_info['relatedmods'] = $col['relatedmods'];
				}
				//crmv@131239e
				//crmv@155093
				if ($convert_dynaform_users && in_array($col['uitype'],array(50))) {
					$dynaform_info['users'] = $col['users'];
				}
				//crmv@155093e
				if ($mode == 'detail') {
					$colinfo = getDetailViewOutputHtml($col['uitype'], ($add_index_to_output_function)?$column_fieldname:$fname, $col['label'], $values, $col['generatedtype'], '', $module, $dynaform_info); // crmv@206140
					($col['readonly'] == 100) ? $readonly = 100 : $readonly = 99;
					if (isset($permissions[$row_fieldname])) {
						($permissions[$row_fieldname]['readonly'] == 100) ? $readonly = 100 : $readonly = 99;
					}
					$col['keyid'] = $colinfo[2];
					$col['keyval'] = $colinfo[1];
					//$col['keytblname'] = $fieldtablename;
					$col['keyfldname'] = $row_fieldname;
					//$col['keyfldid'] = $fieldid;
					$col['keyoptions'] = $colinfo["options"];
					$col['keysecid'] = $colinfo["secid"];
					$col['keyseclink'] = $colinfo["link"];
					$col['keycursymb'] = $colinfo["cursymb"];
					$col['keysalut'] = $colinfo["salut"];
					$col['keyaccess'] = $colinfo["notaccess"];
					$col['keyadmin'] = $colinfo["isadmin"];
					$col['keyreadonly'] = $readonly;
					$col['display_type'] = $col['displaytype']; // crmv@206140
					$col['keymandatory'] = $col['mandatory'];
				} else { // ''(create) and 'edit'
					$ModuleMakerGenerator = new ProcessModuleMakerGenerator();
					$typeofdata = $ModuleMakerGenerator->getTODForField($col);
					if (isset($permissions[$row_fieldname])) {
						$col['mandatory'] = $permissions[$row_fieldname]['mandatory'];
						$col['readonly'] = $permissions[$row_fieldname]['readonly'];
					}
					if ($col['mandatory']) $typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);
					$colinfo = getOutputHtml($col['uitype'], ($add_index_to_output_function)?$column_fieldname:$fname, $col['label'], '', $values, $col['generatedtype'], $module, $mode, $col['readonly'], $typeofdata, $dynaform_info); // crmv@206140
					$col['uitype'] = $colinfo[0][0];
					$col['fldlabel'] = $colinfo[1][0];
					$col['fldlabel_sel'] = $colinfo[1][1];
					$col['fldlabel_combo'] = $colinfo[1][2];
					$col['fldname'] = $row_fieldname;
					$col['fldvalue'] = $colinfo[3][0];
					$col['secondvalue'] = $colinfo[3][1];
					$col['thirdvalue'] = $colinfo[3][2];
					$col['readonly'] = $colinfo[4];
					$col['typeofdata'] = $typeofdata;
					$col['isadmin'] = $colinfo[6];
					$col['keyfldid'] = $colinfo[7];
				}
			}
		}
		$vars = array(
			'ROWNO' => $rowno,
			'ROWID' => $rowid,
			'PARENTFIELD' => $fieldname,
			'COLUMNS' => $columns,
			'MODE' => $mode,
		);
		return $vars;
	}
	
	public function generateHtmlRowsEdit($module, $fieldname, $columns, $values, $single_line, $readonly) {
		global $mod_strings, $app_strings, $theme;
		
		$smarty = new VteSmarty();
		$smarty->assign("MOD",$mod_strings);
		$smarty->assign("APP",$app_strings);
		$smarty->assign("THEME",$theme);
		$smarty->assign("MODULE",$module);
		$smarty->assign("IMAGE_PATH", "themes/$theme/images/");
		$smarty->assign("SHOW_ACTIONS", (!in_array($readonly,array(99,100))));
		$smarty->assign("CANDELETEROWS", (!in_array($readonly,array(99,100))));
		
		$this->calculateSize($columns,$smarty); //crmv@136397 crmv@184897
		
		$html = '';
		$typeofdata = array();
		if (is_array($values)) {
			foreach ($values as $rowno => $row) {
				$vars = $this->generateRowVars('edit', $fieldname, $rowno, $columns, $row['row'], $row['id']);
				if ($vars) {
					foreach ($vars as $vname => $value) {
						$smarty->assign($vname, $value);
						if ($vname == 'COLUMNS' && !empty($value)) {
							foreach($value as $info) {
								$typeofdata[$rowno][$fieldname.'_'.$info['fieldname'].'_'.$rowno] = $info['typeofdata'];
							}
						}
					}
					$smarty->assign('SINGLE_LINE', $single_line);
					$htmlrow = $smarty->fetch('modules/SDK/src/220/Row.tpl');
					$html .= $htmlrow;
				}
			}
		}
		return array('html'=>$html,'typeofdata'=>$typeofdata);
	}
	
	public function generateHtmlRowsDetail($fieldname, $columns, $values, $single_line) {
		global $mod_strings, $app_strings, $theme;
		
		$smarty = new VteSmarty();
		$smarty->assign("MOD",$mod_strings);
		$smarty->assign("APP",$app_strings);
		$smarty->assign("THEME", $theme);
		$smarty->assign("IMAGE_PATH", "themes/$theme/images/");
		$smarty->assign("SHOW_ACTIONS", false);
		$smarty->assign("CANDELETEROWS", false);
		
		$this->calculateSize($columns,$smarty); //crmv@136397 crmv@184897
	
		$html = '';
		if (is_array($values)) {
			foreach ($values as $rowno => $row) {
				$vars = $this->generateRowVars('detail', $fieldname, $rowno, $columns, $row['row'], $row['id']);
				if ($vars) {
					foreach ($vars as $vname => $value) {
						$smarty->assign($vname, $value);
					}
					$smarty->assign('SINGLE_LINE', $single_line);
					$htmlrow = $smarty->fetch('modules/SDK/src/220/Row.tpl');
					$html .= $htmlrow;
				}
			}
		}
		return $html;
	}
	
	// crmv@136397 crmv@184897
	function calculateSize($columns,&$smarty){
		$colsPerLine = $widthPerLine = array();
		$lineIdx = 0;
		foreach($columns as $column) {
			$colsPerLine[$lineIdx]++;
			if ($column['newline'] == 1) {
				if ($colsPerLine[$lineIdx] != 0) {
					$widthPerLine[$lineIdx] = round(100 / $colsPerLine[$lineIdx]);
					$lineIdx++;
				}
			}
		}
		// crmv@207271
		if ($colsPerLine[$lineIdx] != 0) {
			$widthPerLine[$lineIdx] = round(100 / $colsPerLine[$lineIdx]);
		}
		// crmv@207271e
		$smarty->assign("COLSPERLINE", $colsPerLine);
		$smarty->assign("WIDTHPERLINE", $widthPerLine);
	}
	// crmv@136397e crmv@184897e
}