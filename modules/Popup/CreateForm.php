<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42752 crmv@43050 crmv@43448 crmv@43864 crmv@121616 */

require_once('modules/Popup/Popup.php');

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$from_module = vtlib_purify($_REQUEST['from_module']);
$from_crmid = intval($_REQUEST['from_crmid']);

$mod = str_replace('.', '', vtlib_purify($_REQUEST['mod']));
$linktomessage = ($_REQUEST['linktomessage'] == 1);

$email = trim($_REQUEST['original_email']);
$name = trim($_REQUEST['original_name']);

$callback_create = vtlib_purify($_REQUEST['callback_create']);

if (isPermitted($mod, 'EditView') != 'yes') die('<p>Not authorized</p>');

// related ids
try {
	$relatedIds = Zend_Json::decode($_REQUEST['relevant_ids']);
	if (empty($relatedIds)) $relatedIds = array();
} catch (Exception $e) {
	$relatedIds = array();
}

$popup = Popup::getInstance();
$focus = CRMEntity::getInstance($from_module);
$focus->id = $from_crmid; // crmv@81136

//crmv@168655
if ($from_module == 'Messages' && !empty($from_crmid) && empty($relatedIds)) {
	$focus->retrieve_entity_info($from_crmid, $from_module, false);
	$sugg = $focus->getSuggestedRelIds();
	$relatedIds = $sugg['idlist'];
	$email = $sugg['email'];
	$name = $sugg['name'];
	echo '<script type="text/javascript">jQuery(\'#relevant_ids\').val(\''.Zend_Json::encode($relatedIds).'\');jQuery(\'#original_email\').val(\''.$email.'\');jQuery(\'#original_name\').val(\''.addslashes($name).'\');</script>'; // crmv@204169
}
//crmv@168655e

$popup->populateRequestForEdit($from_module, $from_crmid, $mod);
if (method_exists($focus, 'getPopupQCreateValues')) {
	// TODO: sposta questo in Popup.php
	$presetFields = $focus->getPopupQCreateValues($mod, $relatedIds, $email, $name);
}

// put values into request
if (is_array($presetFields))
foreach ($presetFields as $k=>$v) {
	$_REQUEST[$k] = $v;
}

unset($_REQUEST['record']);
$_REQUEST['module'] = $currentModule = $module = $mod;
$_REQUEST['return_module'] = $from_module;
$_REQUEST['action'] = 'EditView';
$_REQUEST['hide_button_list'] = 1;

$label_back = ($_REQUEST['popup_mode'] == 'onlycreate' ? getTranslatedString('LBL_CANCEL_BUTTON_LABEL') : getTranslatedString('LBL_BACK'));
($_REQUEST['show_create_note'] == 'yes') ? $notes = sprintf(getTranslatedString('LBL_POPUP_RECORDS_NOT_SELECTABLE'),getTranslatedString($currentModule,$currentModule)) : $notes = '';	//crmv@46678

$popup->addOtherParams($_REQUEST);	//crmv@47104

require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
$PMUtils = ProcessMakerUtils::getInstance();
$showRunProcessesButton = $PMUtils->showRunProcessesButton($currentModule);
$confirmCallbackCreate = ($popup->confirmCallbackCreate)?'true':'false';
?>
<table border="0" cellspacing="0" cellpadding="0" width=100%"><tr>
	<?php if (!empty($notes)) { ?>
	<td nowrap>
		<span class="helpmessagebox" style="font-style: italic;"><?php echo $notes; ?></span>
	</td>
	<?php } ?>
	<td width="100%" align="right">
		<input class="crmbutton small save" onclick="document.EditView.run_processes.value=''; <?php echo "{$callback_create}('$mod',null,$confirmCallbackCreate)"; ?>" type="button" title="<?php echo getTranslatedString('LBL_SAVE_BUTTON_TITLE'); ?>" value="<?php echo getTranslatedString('LBL_SAVE_BUTTON_LABEL'); ?>">
		<?php if ($showRunProcessesButton) { ?><button class="crmbutton small save" onclick="document.EditView.run_processes.value='yes'; <?php echo "{$callback_create}('$mod',null,$confirmCallbackCreate)"; ?>" type="button" title="<?php echo getTranslatedString('LBL_SAVE_AND_RUN_PROCESSES_BUTTON_TITLE','Processes'); ?>" style="min-width: 70px"><?php echo getTranslatedString('LBL_SAVE_AND_RUN_PROCESSES_BUTTON_LABEL','Processes'); ?> <i class="button-image icon-module icon-processes valign-bottom" data-first-letter="P"></i></button><?php } ?>
		<input class="crmbutton small cancel" onclick="LPOP.create_cancel()" type="button" title="<?php echo $label_back ?>" value="<?php echo $label_back ?>">
	</td>
</tr></table>
<?php
//crmv@sdk-18501
include_once('vtlib/Vtecrm/Link.php');//crmv@207871
$hdrcustomlink_params = Array('MODULE'=>$currentModule);
$COMMONHDRLINKS = Vtecrm_Link::getAllByType(getTabid($currentModule), Array('HEADERSCRIPT'), $hdrcustomlink_params);
foreach ($COMMONHDRLINKS['HEADERSCRIPT'] as $HEADERSCRIPT) {
	echo  '<script type="text/javascript" src="'.$HEADERSCRIPT->linkurl.'"></script>';
}
//crmv@sdk-18501e
echo '<script type="text/javascript" src="'.resourcever("modules/{$currentModule}/{$currentModule}.js").'"></script>'; // crmv@171939

$sdk_custom_file = 'EditView';
if (isModuleInstalled('SDK')) {
    $tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
    if (!empty($tmp_sdk_custom_file)) {
    	$sdk_custom_file = $tmp_sdk_custom_file;
    }
}
require("modules/$currentModule/$sdk_custom_file.php");
?>