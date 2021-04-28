<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@98570 crmv@113771 */
if (!function_exists('replaceParamViewFunction213')) {
	function replaceParamViewFunction213($onclick,$view) {
		$params_string = substr($onclick,strpos($onclick,'(')+1);
		$params_string = substr($params_string,0,strpos($params_string,')'));
		$params = array();
		if (!empty($params_string)) {
			$params_string = explode(',',$params_string);
			if (!empty($params_string)) {
				foreach($params_string as $t) {
					$t = trim($t);
					if ($t == 'view') {
						$t = "'$view'";
					}
					$params[] = $t;
				}
			}
		}
		return substr($onclick,0,strpos($onclick,'(')+1).implode(',',$params).substr($onclick,strpos($onclick,')'));
	}
}
if (!function_exists('getFieldInfo213')) {
	function getFieldInfo213($module, $fieldname) {
		global $adb, $table_prefix, $showfullusername;
		$info = array();
		$fieldinfo = $adb->pquery("select info from {$table_prefix}_field
			inner join {$table_prefix}_fieldinfo on {$table_prefix}_field.fieldid = {$table_prefix}_fieldinfo.fieldid
			where tabid = ? and fieldname = ?", array(getTabid($module),$fieldname));
		if ($fieldinfo && $adb->num_rows($fieldinfo) > 0) {
			$info = Zend_Json::decode($adb->query_result_no_html($fieldinfo,0,'info'));
		}
		return $info;
	}
}

global $sdk_mode,$default_charset;
switch($sdk_mode) {
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		if (!empty($dynaform_info)) {
			$label_fld[] = array(
				'onclick'=>replaceParamViewFunction213($dynaform_info['onclick'],$sdk_mode),
				'code'=>html_entity_decode($dynaform_info['code'],ENT_COMPAT,$default_charset)
			);
		} else {
			$info = getFieldInfo213($module, $fieldname);
			$label_fld[] = array(
				'onclick'=>replaceParamViewFunction213($info['onclick'],$sdk_mode),
				'code'=>html_entity_decode($info['code'],ENT_COMPAT,$default_charset)
			);
		}
		break;
	case 'edit':
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		if (!empty($dynaform_info)) {
			$fieldvalue[] = replaceParamViewFunction213($dynaform_info['onclick'],$sdk_mode);
			$fieldvalue[] = html_entity_decode($dynaform_info['code'],ENT_COMPAT,$default_charset);
		} else {
			$info = getFieldInfo213($module_name, $fieldname);
			$fieldvalue[] = replaceParamViewFunction213($info['onclick'],$sdk_mode);
			$fieldvalue[] = html_entity_decode($info['code'],ENT_COMPAT,$default_charset);
		}
		break;
	case 'relatedlist':
	case 'list':
		// TODO
		$value = '';
		break;
}
?>