<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * crmv@39110
 */
global $sdk_mode;
global $default_charset; // crmv@97841

switch($sdk_mode) {
	// crmv@171890
	case 'insert':
		$sdk_skipfield = true; // crmv@166458
		break;
	case 'insert.after':
		$adb->updateClob($tablename,$columname,$table_index_column."=".$this->id,$fldvalue); // crmv@136499
		break;
	// crmv@171890e
	case 'detail':
		$val = $col_fields[$fieldname];
		$label_fld[] = getTranslatedString($fieldlabel,$module);
		if ($_REQUEST['file'] == 'DetailViewBlocks') {
			$label_fld[] = $val; //crmv@81269 - no need to convert chars here, thay have to stay like they were in the db
		} else {
			// WTF??!!!! Why on earth these 2 cases are different?
			$label_fld[] = html_entity_decode($val, ENT_COMPAT, $default_charset);
		}
		break;
	case 'edit':
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$purehtml = html_entity_decode($value, ENT_COMPAT, $default_charset);
		$purehtml = str_ireplace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $purehtml); // crmv@166458 - ckeditor bug!!
		$fieldvalue[] = $purehtml;
		break;
	case 'relatedlist':
		$recordId = $entity_id;
	case 'list':
		$sdk_value = ($sdk_mode == 'list' ? $rawValue : $field_val);
		$entity_id = $recordId;
		if (!empty($sdk_value)) {
			$tmp_val = preg_replace("/(<\/?)(\w+)([^>]*>)/i","",$sdk_value);
			$tmp_val = trim(html_entity_decode($tmp_val, ENT_QUOTES, $default_charset));
			$value = textlength_check(strip_tags($tmp_val));
			if ($sdk_value != '' && strlen($tmp_val) > $listview_max_textlength) {
				$value .= '&nbsp;<a href="javascript:;"><img onmouseout="jQuery(\'#content_'.$fieldname.'_'.$entity_id.'\').hide();" onmouseover="jQuery(\'#content_'.$fieldname.'_'.$entity_id.'\').show();" src="'.resourcever('readmore.png').'" border="0"></a>';
				$value .= '<div id="content_'.$fieldname.'_'.$entity_id.'" class="layerPopup" style="width:300px;z-index:10000001;display:none;position:absolute;" onmouseout="jQuery(\'#content_'.$fieldname.'_'.$entity_id.'\').hide();" onmouseover="jQuery(\'#content_'.$fieldname.'_'.$entity_id.'\').show();">
				<table style="background-color:#F2F2F2;" align="center" border="0" cellpadding="5" cellspacing="0" width="100%">
				<tr><td class="small">'.$tmp_val.'</td></tr>
				</table></div>';
			}
		} else {
			$value = '';
		}
		break;
	//crmv@65492 - 28
	case 'pdfmaker':
		// crmv@171512
		if ($_REQUEST['file'] == 'SendPDFMail' || $_REQUEST['action'] == 'CreatePDFFromTemplate') { //avoid formatting in send email pdf and export
			$value = $sdk_value;
		} elseif ($_REQUEST['action'] == 'SavePDFDoc') {
			$value = html_entity_decode($sdk_value, ENT_COMPAT, $default_charset);
		} else {
			$value = htmlentities($sdk_value);
		}
		// crmv@171512e
		break;
	case 'report':
		$fieldvalue = strip_tags(htmlspecialchars_decode($sdk_value));
		break;
	//crmv@65492e - 28
}

?>