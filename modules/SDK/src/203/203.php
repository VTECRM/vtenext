<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $sdk_mode,$default_theme,$current_user;

$themes = get_themes();
// file=>name

switch($sdk_mode) {
	case 'insert':
		$value = $this->column_fields[$fieldname];
		if ($current_user->id == $this->id && array_key_exists($value, $themes)) {
			$default_theme = $value;
			if (array_key_exists('authenticated_user_theme', $_SESSION)) VteSession::set('authenticated_user_theme', $value);//crmv@207841
		}
		break;
	case 'detail':
		$value = $col_fields[$fieldname];
		if (empty($value)) $value = $default_theme;
		$col_fields[$fieldname] = $value;
		
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $themes[$value];
		$value_decoded = decode_html($value);
		$pickcount = count($themes);
		if ($themes && $pickcount > 0) {
			foreach ($themes as $prefix=>$themelabel) {
				$chk_val = ($value_decoded == trim($prefix)) ? "selected" : ""; 

				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($themelabel,ENT_QUOTES,$default_charset),to_html($prefix),$chk_val);	
				else
					$options[] = array($themelabel,to_html($prefix),$chk_val);	
			}
		} elseif($pickcount == 0 && count($value)) {
			$options[] =  array("<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>",$value,'selected');
		}
		$label_fld ["options"] = $options;
		break;
	case 'edit':
		if (empty($value)) $value = $default_theme;
		
		$value_decoded = decode_html($value);
		$pickcount = count($themes);
		if ($themes && $pickcount > 0) {
			foreach ($themes as $prefix=>$themelabel) {
				$chk_val = ($value_decoded == trim($prefix)) ? "selected" : "";
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($themelabel,ENT_QUOTES,$default_charset),to_html($prefix),$chk_val );	
				else
					$options[] = array($themelabel ,to_html($prefix),$chk_val );	
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
?>