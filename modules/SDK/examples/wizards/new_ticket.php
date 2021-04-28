<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@OPER6317 crmv@96233 */

$pageTitle = $pageTitle.': '.getTranslatedString('WIZARD_NEW_TICKET');

// step 1
$currentModule = $tmod = 'Accounts';
if (!vtlib_isModuleActive($tmod)) return;
if (isPermitted($tmod, 'index') != 'yes') return;
$cv = CRMEntity::getInstance('CustomView', $tmod); // crmv@115329
$filterlist = $cv->getCustomViewCombo();

$Slv = SimpleListView::getInstance($tmod);
$Slv->entriesPerPage = 10;
$Slv->showCreate = false;
$Slv->showSuggested = false;
$Slv->showCheckboxes = false;
$Slv->extraButtonsHTML = '';
$Slv->selectFunction = 'Wizard.recordSelect1';

$list = $Slv->render();

$modinfo = array(
	'filters' => $filterlist,
	'list' => $list,
	'listid' => $Slv->listid
);
$smarty->assign('STEP1LIST', $modinfo);

// step 2
$currentModule = $tmod = 'Products';
if (!vtlib_isModuleActive($tmod)) return;
if (isPermitted($tmod, 'index') != 'yes') return;
$cv = CRMEntity::getInstance('CustomView', $tmod); // crmv@115329
$filterlist = $cv->getCustomViewCombo();

$Slv = SimpleListView::getInstance($tmod);
$Slv->entriesPerPage = 10;
$Slv->showCreate = false;
$Slv->showSuggested = false;
$Slv->showCheckboxes = false;
$Slv->extraButtonsHTML = '';
$Slv->selectFunction = 'Wizard.recordSelect1';

$list = $Slv->render();

$modinfo = array(
	'filters' => $filterlist,
	'list' => $list,
	'listid' => $Slv->listid
);
$smarty->assign('STEP2LIST', $modinfo);

// step 3
$smarty->assign('MAX_FILE_SIZE', $upload_maxsize);

// step 4
$hdfields = array('ticket_title', 'ticketpriorities', 'ticketcategories', 'description');
// retrieve them with webservices
$wsmodule = vtws_describe('HelpDesk', $current_user);
$fields = array();
foreach ($wsmodule['fields'] as $f) {
	if (in_array($f['name'], $hdfields)) {
		if ($f['type']['name'] == 'date') {
			$f['secondvalue'] = array($f['type']['format']);
		} elseif ($f['type']['name'] == 'picklist') {
			if (!empty($f['type']['name'])) {
				$value = array();
				foreach($f['type']['picklistValues'] as $v) {
					($v['value'] == $f['type']['defaultValue']) ? $selected = 'selected' : $selected = '';
					$value[] = array($v['label'],$v['value'],$selected);
				}
				$f['value'] = $value;
			}
		}
		$fields[] = $f;
	}
}
$smarty->assign('STEP3FIELDS', $fields);