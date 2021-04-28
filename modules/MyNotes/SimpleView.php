<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@172994

require_once('include/ListView/SimpleListView.php');
global $app_strings, $currentModule, $theme, $default_charset;

$Slv = SimpleListView::getInstance($currentModule); // fake module, but works as well
$Slv->entriesPerPage = 20;
$Slv->maxFields = 2;
$Slv->showFilters = false;
$Slv->showSuggested = false;
$Slv->showCheckboxes = false;
$Slv->selectFunction = 'MyNotesSV.select';
$Slv->template = "modules/{$currentModule}/SimpleListView.tpl";
$list = $Slv->render();

$smarty = new VteSmarty();

$page_title = getTranslatedString($currentModule);
$smarty->assign('BROWSER_TITLE', $page_title);
$smarty->assign('PAGE_TITLE', $page_title);
$small_page_buttons = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
 		<tr height="34">
 			<td align="right" width="20%">';
 				if(isPermitted($currentModule, 'EditView') == 'yes')
					$small_page_buttons .= '<input type="button" name="button" title="'.getTranslatedString('LBL_CREATE').'" value="'.getTranslatedString('LBL_CREATE').'" class="crmbutton small save" onClick="MyNotesSV.create(\''.$currentModule.'\')">';
$small_page_buttons .= '</td>
 			<td align="right" width="80%">
 				<div id="Buttons_List_Detail">
 					<table cellspacing="0" cellpadding="0" border="0" width="100%">
						<tr valign="middle" height="34">
							<td width="100%" align="right" style="padding-right:4px;">
								<div style="float:right;">
							';
							 	if(isPermitted($currentModule, 'Delete') == 'yes')
									$small_page_buttons .= '<input id="deleteNoteButton" type="button" value="'.getTranslatedString('LBL_DELETE').'" title="'.getTranslatedString('LBL_DELETE').'" style="display:none;" class="detailviewbutton crmbutton small delete" onclick="MyNotesSV.delete(\''.$currentModule.'\',\'DetailView\',\'Delete\',\''.getTranslatedString('NTC_DELETE_CONFIRMATION').'\')">';
								if(isPermitted($currentModule, 'EditView') == 'yes')
									$small_page_buttons .= '<input id="saveNoteButton" type="button" title="'.getTranslatedString('LBL_SAVE_BUTTON_TITLE').'" value="'.getTranslatedString('LBL_SAVE_BUTTON_LABEL').'" style="display:none;" class="editviewbutton crmbutton small save" onclick="MyNotesSV.save(\''.$currentModule.'\')">';
$small_page_buttons .= '		</div>
								<div id="status" style="display:none;float:right;padding:5px 5px 0px 0px;"><i class="dataloader" data-loader="circle"></i></div>
								<div id="vtbusy_info" style="display:none;float:right;padding:5px 5px 0px 0px;"><i class="dataloader" data-loader="circle"></i></div>
							</td>
						</tr>
					</table>
 				</div>
 			</td>
 		</tr>
 	</table>';
$smarty->assign('BUTTON_LIST', $small_page_buttons);

$smarty->assign('APP', $app_strings);
$smarty->assign('THEME', $theme);
$smarty->assign('DEFAULT_CHARSET', $default_charset);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('LIST', $list);
$smarty->assign('LISTID', $Slv->listid);
//crmv@103870
$JSGlobals = ( function_exists('getJSGlobalVars') ? getJSGlobalVars() : array() );
$smarty->assign('JS_GLOBAL_VARS', Zend_Json::encode($JSGlobals));
//crmv@103870e

if (!empty($_REQUEST['record'])) {
	VteSession::set('mynote_selected', $_REQUEST['record']);
}
if (!VteSession::isEmpty('mynote_selected')) {
	$smarty->assign('MYNOTE_SELECTED', VteSession::get('mynote_selected'));
} else {
	$crmids = $Slv->getCrmids();
	if (!empty($crmids)) {
		$smarty->assign('MYNOTE_SELECTED', $crmids[0]);
	}
}

$smarty->display("modules/{$currentModule}/SimpleView.tpl");
?>