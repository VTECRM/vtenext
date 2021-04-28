<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@59094 */

if (empty($_REQUEST['mode'])) {
	include_once("modules/$currentModule/ListView.php");
	return;
}

global $mod_strings, $app_strings, $currentModule, $current_user, $theme, $current_account, $current_folder; //crmv@203484

if (isset($_REQUEST['account']) && !empty($_REQUEST['account'])) {
	$current_account = $_REQUEST['account'];
}
if (isset($_REQUEST['folder']) && !empty($_REQUEST['folder'])) {
	$current_folder = $_REQUEST['folder'];
}

$focus = CRMEntity::getInstance($currentModule);

$tool_buttons = Button_Check($currentModule);
$smarty = new VteSmarty();

$record = $_REQUEST['record'];
$isduplicate = vtlib_purify($_REQUEST['isDuplicate']);
$tabid = getTabid($currentModule);
$category = getParentTab($currentModule);

if($record != '') {
	$focus->id = $record;
	$retrieve = $focus->retrieve_entity_info($record, $currentModule, false);
	if (in_array($retrieve,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND','LBL_OWNER_MISSING'))) {
		exit;
	}
}
$folder = $focus->column_fields['folder'];
if($isduplicate == 'true') $focus->id = '';

//crmv@47243
if (empty($current_folder) && $focus->column_fields['mtype'] == 'Link' && ($current_user->id == $focus->column_fields['assigned_user_id'] || $focus->column_fields['mvisibility'] == 'Public')) { //crmv@45122
	$current_folder = 'Links';
}
//crmv@47243e

//crmv@64383
$googleTranslateLanguages = array('en_us'=>'en','it_it'=>'it','de_de'=>'de','nl_nl'=>'nl','pt_br'=>'pt','es_es'=>'es');
$lang = $googleTranslateLanguages[$current_language];
if (empty($lang)) $lang = 'en';
$smarty->assign("LANGUAGE", $lang);
//crmv@64383e

// Identify this module as custom module.
$smarty->assign('CUSTOM_MODULE', true);

$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
// TODO: Update Single Module Instance name here.
$smarty->assign('SINGLE_MOD', 'SINGLE_'.$currentModule); 
$smarty->assign('CATEGORY', $category);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('THEME', $theme);
$smarty->assign('ID', $focus->id);
$smarty->assign('MODE', $focus->mode);

$smarty->assign('EDIT_PERMISSION', isPermitted($currentModule, 'EditView', $record));
$smarty->assign('CHECK', $tool_buttons);

// Record Change Notification : do in separate ajax call
$focus->markAsViewed($current_user->id,$_REQUEST['skip_update_flag']);
// END

$blocks = getBlocks($currentModule,'detail_view','',$focus->column_fields);
$header_block = array();
if (isset($blocks[$mod_strings['LBL_MESSAGE_INFORMATION']])) {
	foreach($blocks[$mod_strings['LBL_MESSAGE_INFORMATION']]['fields'] as $detail) {  // crmv@104568
		foreach($detail as $label => $data) {
			$header_block[$data['fldname']] = $data;
			$header_block[$data['fldname']]['label'] = $label;
		}
	}
}
$description = '';
$button_download_description = false;
$attachments_info = array();	//crmv@50773
if (isset($blocks[$mod_strings['LBL_DESCRIPTION_INFORMATION']])) {
	foreach($blocks[$mod_strings['LBL_DESCRIPTION_INFORMATION']]['fields'] as $detail) {  // crmv@104568
		foreach($detail as $label => $data) {
			if ($label == getTranslatedString('Body',$currentModule)) {
				if ((empty($data['value']) || trim($data['value']) == "<!-- begin sanitized html -->\n\n<!-- end sanitized html -->") && $focus->fetchBodyInCron != 'yes') { // crmv@110843
					$button_download_description = true;
				} else {
					// crmv@49398
					$attachments_info = $focus->getAttachmentsInfo();
					if (!empty($focus->column_fields['cleaned_body'])) {
						$description = $focus->column_fields['cleaned_body'];
						$content_ids = Zend_Json::decode($focus->column_fields['content_ids']);
					} else {
						$message_data = array('other'=>$attachments_info);
						$magicHTML = $focus->magicHTML($data['value'], $focus->column_fields['xuid'], $message_data);
						$description = $magicHTML['html'];
						$content_ids = $magicHTML['content_ids'];
						// save them 
						$focus->saveCleanedBody($focus->id, $description, $content_ids);
					}
					// crmv@49398e
				}
				break(2);
			}
		}
	}
}
//crmv@81766
if ($current_user->column_fields['internal_mailer'] == 1) {
	$description = $focus->convertInternalMailerLinks($description);
}
//crmv@81766e

$smarty->assign('HEADER_BLOCK', $header_block);
$smarty->assign('DESCRIPTION', $description);
// crmv@70254 - prepare the string for the translation
$trans_description = trim(preg_replace(array("/[ \t\x{00A0}]+/u", "/[\n\r]+/", "/\s{2,}/"), array(" ", "\n", "\n\n"), html_entity_decode(strip_tags($description), ENT_QUOTES, 'UTF-8')));
$smarty->assign('TRANS_DESCRIPTION', $trans_description);
// crmv@70254e
$smarty->assign('BUTTON_DOWNLOAD_DESCRIPTION', $button_download_description);
include('modules/Messages/DetailViewButtons.php');

// Gather the custom link information to display
include_once('vtlib/Vtecrm/Link.php');//crmv@207871
$customlink_params = Array('MODULE'=>$currentModule, 'RECORD'=>$focus->id, 'ACTION'=>vtlib_purify($_REQUEST['action']));
$smarty->assign('CUSTOM_LINKS', Vtecrm_Link::getAllByType(getTabid($currentModule), Array('DETAILVIEWWIDGET'), $customlink_params));
// END

$smarty->assign('DETAILVIEW_AJAX_EDIT', PerformancePrefs::getBoolean('DETAILVIEW_AJAX_EDIT', true));

//$specialFolders = $focus->getSpecialFolders();	// already calculated in DetailViewButtons.php
if (in_array($folder,array($specialFolders['Sent'],$specialFolders['Drafts']))) {
	$business_card = $focus->getBusinessCard('TO');
} else {
	$business_card = $focus->getBusinessCard('FROM');
	$business_card = $business_card[0];
}
$smarty->assign('FOCUS', $focus);
$smarty->assign('FOLDER', $folder);
$smarty->assign('BUSINESS_CARD', $business_card);
$attachments = $focus->getAttachments();
$smarty->assign('ATTACHMENTS', $attachments);
$attachments_other = $attachments_info;	//crmv@65648	$attachments_other = array_diff_assoc($attachments_info,$attachments);
$inline_attachments = array();	//crmv@80250
foreach ($attachments_other as $content_id => $attachment) {
	if (!in_array($content_id,$content_ids) && $attachment['parameters']['contentdisposition'] == 'inline' && $focus->isSupportedInlineFormat($attachment['parameters']['name'])) {	//crmv@80250
		$inline_attachments[$content_id] = $attachment;
	}
}
$smarty->assign('INLINE_ATTACHMENTS',$inline_attachments);

// crmv@68357
$icals = $focus->getIcals();
if (!empty($icals) && count($icals) > 0) {
	$smarty->assign('ICALS',$icals);
	$smarty->assign('USERID', $current_user->id); // crmv@174249
}
// crmv@68357e

$smarty->assign('MESSAGE_MODE',$_REQUEST['mode']);

if ($_REQUEST['mode'] == 'ListView') {
	VteSession::set('autoload_message', $record);	//crmv@50101
	$smarty->display('modules/Messages/DetailView.tpl');
//crmv@42801
} elseif ($_REQUEST['mode'] == 'Print') {
	$smarty->display('modules/Messages/Print.tpl');
} elseif ($_REQUEST['mode'] == 'Download') {
	$filename = getTranslatedString('SINGLE_Messages','Messages').'.html';
	header("Content-Type: application/octet-stream; name=\"$filename\"");
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Content-Type: application/octet-stream; name=\"$filename\"");
	echo $description;
//crmv@42801e
//crmv@44775
} elseif ($_REQUEST['mode'] == 'Detach') {
	// Gather the custom link information to display
	$hdrcustomlink_params = Array('MODULE'=>$currentModule);
	$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERSCRIPT', 'HEADERCSS'), $hdrcustomlink_params);
	$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
	$smarty->assign('HEADERCSS', $COMMONHDRLINKS['HEADERCSS']);
	// END
	$smarty->display('modules/Messages/Detach.tpl');
}
//crmv@44775e
?>