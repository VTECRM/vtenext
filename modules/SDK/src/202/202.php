<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $sdk_mode, $default_language, $current_user, $root_directory;	//crmv@30166

$languages = vtlib_getToggleLanguageInfo();

switch($sdk_mode) {
	case 'insert':
		if (!empty($fldvalue) && $module == 'Users') { // crmv@151474
			if ($current_user->id == $this->id && array_key_exists($fldvalue, $languages)) {	//crmv@30166
				$default_language = $fldvalue;
				if (array_key_exists('authenticated_user_language', $_SESSION)) VteSession::set('authenticated_user_language', $fldvalue);
				VteSession::remove('sdk_js_lang');
			}
			//crmv@35153
			if (is_admin($this)) {
				$priv_file = $root_directory.'config.inc.php';
				$userfile = file_get_contents($priv_file);
				$userfile = preg_replace("/default_language\s*=\s*['\"][^'\"]+['\"]\s*;/", "default_language = '$fldvalue';", $userfile);
				file_put_contents($priv_file, $userfile);
			}
			//crmv@35153e
		}
		break;
	case 'detail':
		$value = $col_fields[$fieldname];
		if (empty($value)) $value = $default_language;
		$col_fields[$fieldname] = $value;
		
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $languages[$value]['label'];
		$value_decoded = decode_html($value);
		$pickcount = count($languages);
		if ($languages && $pickcount > 0) {
			foreach ($languages as $prefix=>$langinfo) {
				if($langinfo['active'] != 1) continue;  //crmv@170407
				
				$langlabel = $langinfo['label'];
				$chk_val = ($value_decoded == trim($prefix)) ? "selected" : "";
				
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($langlabel,ENT_QUOTES,$default_charset),to_html($prefix),$chk_val);
				else
					$options[] = array($langlabel,to_html($prefix),$chk_val);
			}
		} elseif($pickcount == 0 && count($value)) {
			$options[] =  array("<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>",$value,'selected');
		}
		$label_fld ["options"] = $options;
		break;
	case 'edit':
		if (empty($value)) $value = $default_language;
		$valuelabel = $languages[$value]['label'];
		
		$value_decoded = decode_html($value);
		$pickcount = count($languages);
		if ($languages && $pickcount > 0) {
			foreach ($languages as $prefix=>$langinfo) {
				if($langinfo['active'] != 1) continue;  //crmv@170407
				$langlabel = $langinfo['label'];
				$chk_val = ($value_decoded == trim($prefix)) ? "selected" : "";
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($langlabel,ENT_QUOTES,$default_charset),to_html($prefix),$chk_val );
				else
					$options[] = array($langlabel ,to_html($prefix),$chk_val );
			}
		}
		if($pickcount == 0 && count($value))
			$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$value,'selected');
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $options;
		break;
	case 'relatedlist':
	case 'list':
		//$value = getTranslatedString($sdk_value,$sdk_value);
		break;
}