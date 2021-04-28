<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42752 crmv@43864 */

require_once('modules/Popup/Popup.php');

global $theme, $current_language, $app_strings, $mod_strings;

$from_module = vtlib_purify($_REQUEST['from_module']);
$from_crmid = intval($_REQUEST['from_crmid']);
$callback_close = vtlib_purify($_REQUEST['callback_close']);

$focus = Popup::getInstance();

// now pretend to be in Calendar
$currentModule = 'Calendar';
// and get the mod strings of Calendar
$mod_strings = return_module_language($current_language, $currentModule);

$focus->populateRequestForEdit($from_module, $from_crmid, 'Calendar');

$_REQUEST['hide_menus'] = 'true';

$smarty = new VteSmarty();

$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('THEME', $theme);

if ($_REQUEST['activity_mode'] == 'Events') {
	$smarty->assign('PAGE_TITLE', getTranslatedString('LBL_ADD', $currentModule));
} else {
	$smarty->assign('PAGE_TITLE', getTranslatedString('LBL_ADD_TODO', $currentModule));
}

$smarty->assign('HEADER_Z_INDEX', 10);
$smarty->display('SmallHeader.tpl');

// trick to pretend we are in calendar
$_REQUEST['module'] = $currentModule;
$_REQUEST['hide_button_list'] = '1';
$smarty->display('Buttons_List_Edit.tpl');
$smarty->display('modules/SDK/src/Reference/Autocomplete.tpl');

// extra hidden inputs to be created
$extraInputs = array(
	'messageid' => $messageId,
	'ajaxCalendar' => 'onlyJson',
	'from_module' => $from_module,
	'from_crmid' => $from_crmid,
	'callback_close' => $callback_close,
);

// some useful js
echo '<script type="text/javascript" src="'.resourcever("modules/Calendar/script.js").'"></script>'."\n"; // crmv@168932
echo '<script type="text/javascript" src="'.resourcever("modules/SDK/SDK.js").'"></script>'."\n"; // crmv@168932
echo '<script type="text/javascript" src="modules/'.$from_module.'/'.$from_module.'.js"></script>'."\n";
echo '<script type="text/javascript" src="modules/Popup/Popup.js"></script>'."\n";
echo '<script type="text/javascript">var gVTModule = "'.$currentModule.'";</script>'."\n"; // crmv@104853

//crmv@62447
if ($_REQUEST['specify_date'] == 'true'){
	$_REQUEST['date_start'] = getValidDBInsertDateValue(vtlib_purify($_REQUEST['datestart'])); // crmv@183810
	$_REQUEST['due_date'] = getValidDBInsertDateValue(vtlib_purify($_REQUEST['dateend'])); // crmv@183810
	$_REQUEST['time_start'] = str_pad(vtlib_purify($_REQUEST['h_start']),2,'0',STR_PAD_LEFT).":".str_pad(vtlib_purify($_REQUEST['m_start']),2,'0',STR_PAD_LEFT);
	$_REQUEST['time_end'] = str_pad(vtlib_purify($_REQUEST['h_end']),2,'0',STR_PAD_LEFT).":".str_pad(vtlib_purify($_REQUEST['m_end']),2,'0',STR_PAD_LEFT);
}
//crmv@62447e
include('modules/Calendar/EditView.php');

?>
<form id="extraInputs" name="extraInputs">
<?php
	foreach ($extraInputs as $iname=>$ival) {
		echo '<input type="hidden" id="'.$iname.'" name="'.$iname.'" value="'.$ival.'" />'."\n";
	}
?>
</form>
<script type="text/javascript">
	/* replace handlers */

	(function() {
		window.empty_search_str = "<?php echo getTranslatedString('LBL_SEARCH_STRING'); ?>"; //crmv@66008
 		var from_module = "<?php echo $from_module; ?>";
		var allBtns = jQuery('#Buttons_List_4 input'),
			btnSave = jQuery(allBtns[0]),
			btnCanc = jQuery(allBtns[1]);

		btnSave.attr('onclick', '');
		if (from_module == 'Messages') {
			btnSave.click(function() {
				LPOP.saveEvent(saveEventCallback);
			});
		} else {
			btnSave.click(LPOP.saveEvent);
		}

		btnCanc.attr('onclick', '');
		btnCanc.click(function() {
			closePopup();
		});
		//crmv@62447
		<?php if ($_REQUEST['fast_save'] == 'true'){ ?>
			btnSave.click();
		<?php } ?>	
		//crmv@62447e
	})();
	
	LPOP.setGlobalVars();	//crmv@44462
</script>