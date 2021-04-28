<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $sdk_mode;

/* crmv@201442 */

require_once('modules/SDK/src/310/310Utils.php');

$CFU = new CountriesFieldUtils();
$fieldid = getFieldid(getTabid2($module_name ?: $module), $fieldname ?: $fieldName);
$fieldinfo = $CFU->getFieldInfo($fieldid);
$countries = $CFU->getAllValues($fieldid);
if ($fieldinfo['show_flags']) $CFU->addUnicodeFlags($countries);
$default_country = $CFU->getDefaultCountry($fieldid, $sdk_mode);

$recordid = $col_fields['record_id'];

switch($sdk_mode) {
	case 'detail':
		$value = $col_fields[$fieldname];
		$col_fields[$fieldname] = $value;
		
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $countries[$value];
		$pickcount = count($countries);
		if ($countries && $pickcount > 0) {
			foreach ($countries as $alpha2code=>$name) {
				$chk_val = ($value == $alpha2code) ? "selected" : "";
				$options[] = array($name,$alpha2code,$chk_val);
			}
		} elseif($pickcount == 0 && count($value)) {
			$options[] =  array("<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>",$value,'selected');
		}
		$label_fld["options"] = $options;
		break;
	case 'edit':
		if (!$recordid) {
			// set the default in creation
			if (empty($value) && !empty($default_country)) $value = $default_country;
		}
		$pickcount = count($countries);
		if ($countries && $pickcount > 0) {
			foreach ($countries as $alpha2code=>$name) {
				$chk_val = ($value == $alpha2code) ? "selected" : "";
				$options[] = array($name,$alpha2code,$chk_val);
			}
		}
		if($pickcount == 0 && count($value)) {
			$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$value,'selected');
		}
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $options;
		break;
	case 'relatedlist':
	case 'list':
		$value = $countries[$sdk_value];
		break;
}