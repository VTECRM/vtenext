<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@83340 crmv@96155 crmv@98431 crmv@102379 crmv@102334 */

global $adb, $table_prefix, $currentModule, $current_user, $app_strings;

$outputMode = 'json';
$output = null;
$ajxaction = $_REQUEST['ajxaction'];

$MHW = ModuleHomeView::getInstance($currentModule, $current_user->id);

$homeid = intval($_REQUEST['modhomeid']);

if ($ajxaction == 'loadblock') {
	$blockid = intval($_REQUEST['blockid']);
	$blockHtml = $MHW->getBlockContent($homeid, $blockid, array('reload' => true));
	$output = array('success' => true, 'result' => $blockHtml);
	
} elseif ($ajxaction == 'loadblocks') {
	$output = array('success' => true, 'result' => array());
	
	// crmv@135195
	$opts = array();
	if ($_REQUEST['reload'] == 'true') $opts['reload'] = true;
	$blockids = array_filter(array_map('intval', explode(':', $_REQUEST['blockids'])));
	foreach ($blockids as $blockid) {
		$blockHtml = $MHW->getBlockContent($homeid, $blockid, $opts);
		$output['result'][$blockid] = $blockHtml;
	}
	// crmv@135195e
} elseif ($ajxaction == 'savesequence') {
	$blockids = array_filter(array_map('intval', explode(':', $_REQUEST['blockids'])));
	
	$MHW->reorderBlocks($homeid, $blockids);
	
	$output = array('success' => true);

} elseif ($ajxaction == 'removeblock') {
	$blockid = intval($_REQUEST['blockid']);
	$MHW->deleteBlock($homeid, $blockid);
	$output = array('success' => true);

} elseif ($ajxaction == 'confignewblock') {
	
	$type = $_REQUEST['type'];
	
	$tpls = array(
		'Chart' => 'NewChart.tpl',
		'QuickFilter' => 'NewQuickFilter.tpl',
		'Filter' => 'NewFilter.tpl',
		'Wizards' => 'NewWizards.tpl',
		'Processes' => 'NewProcesses.tpl',
	);
	
	$template = $tpls[$type];
	
	$outputMode = 'smarty';
	if ($template) {
		$smarty = new VteSmarty();
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MODHOMEID', $homeid);
		
		if ($type == 'Chart') {
			$charts = $MHW->getAvailableCharts($currentModule, $homeid); // crmv@101227
			$smarty->assign('CHARTS', $charts);
		} elseif ($type == 'QuickFilter' || $type == 'Filter') {
			require_once('modules/CustomView/CustomView.php');
			$customView = CRMEntity::getInstance('CustomView', $currentModule); // crmv@115329
			$filters = $customView->getCustomViewCombo('', false);
			$smarty->assign('QFILTERS', $filters);
		} elseif ($type == 'Wizards') {
			$WU = WizardUtils::getInstance();
			$wizards = $WU->getWizards(null, true);
			$smarty->assign('WIZARDS', $wizards);
		}
		
		$template = 'ModuleHome/'.$template;
	}

} elseif ($ajxaction == 'addblock') {
	
	$type = $_REQUEST['type'];
	
	$block = array();
	$block['type'] = $type;
	$block['size'] = 1;
	$block['title'] = '';
	
	if ($type == 'Chart') {
		$chartid = intval($_REQUEST['chartid']);
		$block['title'] = getEntityName('Charts', $chartid, true);
		$block['config'] = array('chartid' => $chartid);
		
	} elseif ($type == 'QuickFilter') {
		$cvid = intval($_REQUEST['cvid']);
		$block['config'] = array('cvid' => $cvid);

	} elseif ($type == 'Filter') {
		$cvid = intval($_REQUEST['cvid']);
		$block['size'] = 4;
		$block['config'] = array('cvid' => $cvid);
		
	} elseif ($type == 'Wizards') {
		$block['size'] = 1;
		$block['title'] = getTranslatedString('LBL_WIZARDS');
		$wizids = Zend_Json::decode($_REQUEST['wizards']);
		if (is_array($wizids) && count($wizids) > 0) {
			$wizids = array_filter(array_map('intval', $wizids));
			$block['config'] = array('wizardids' => $wizids);
		}
		
	// crmv@96233
	} elseif ($type == 'Processes') {
		$block['size'] = 1;
	// crmv@96233e

	} else {
		$block = null;
		$output = array('success' => false, 'error' => 'Block type not supported');
	}
	
	if ($block) {
		$blockid = $MHW->insertBlock($homeid, $block);
		if ($blockid > 0) {
			$output = array('success' => true, 'result' => array('blockid' => $blockid));
		} else {
			$output = array('success' => false, 'error' => 'Error while inserting the new block');
		}
	}
	
} elseif ($ajxaction == 'addview') {
	
	$name = $_REQUEST['viewname'];
	$reportid = intval($_REQUEST['reportid']);
	$cvid = intval($_REQUEST['cvid']);
	if (!empty($name)) {
		$homeid = $MHW->insertView(array(
			'name' => $name,
			'reportid' => $reportid,
			'cvid' => $cvid,
		));
		if ($homeid > 0) {
			$output = array('success' => true, 'result' => $homeid);
		} else {
			$output = array('success' => false, 'error' => 'Unable to save the view');
		}
	} else {
		$output = array('success' => false, 'error' => 'No name specified');
	}
	
// crmv@199319
} elseif ($ajxaction == 'editview') {
    $name = $_REQUEST['viewname'];
    $reportid = intval($_REQUEST['reportid']);
    $cvid = intval($_REQUEST['cvid']);
    if (!empty($name)) {
        $result = $MHW->editView(array(
            'homeid' => $homeid,
            'name' => $name,
            'reportid' => $reportid,
            'cvid' => $cvid,
        ));
        if ($result > 0) {
            $output = array('success' => true, 'result' => $homeid);
        } else {
            $output = array('success' => false, 'error' => 'Unable to edit the view');
        }
    } else {
        $output = array('success' => false, 'error' => 'No name specified');
    }

// crmv@199319e
} elseif ($ajxaction == 'removeview') {
	
	$homeid = $MHW->deleteView($homeid);
	$output = array('success' => true);
	
} elseif ($ajxaction == 'reportlist') {

	$reports = Reports::getInstance();
	$folders = $reports->getFolderList();
	foreach ($folders as $k => $foldinfo) {
		$folderid = $foldinfo['folderid'];
		$list = $reports->sgetRptsforFldr($folderid, $currentModule);
		if (count($list) > 0) {
			$folders[$k]['reports'] = $list[$folderid];
		} else {
			unset($folders[$k]);
		}
	}
	$output = array('success' => true, 'result' => $folders);
	
} elseif ($ajxaction == 'cvlist') {

	$customView = CRMEntity::getInstance('CustomView', $currentModule); // crmv@115329
	$customview_html = $customView->getCustomViewCombo('',false);
	$output = array('success' => true, 'result' => $customview_html);
}



if ($outputMode == 'json') {
	echo Zend_Json::encode($output);
	die();
} elseif ($outputMode == 'smarty' && isset($smarty) && !empty($template)) {
	$smarty->display($template);
} elseif ($outputMode == 'raw') {
	echo $output;
	die();
} elseif ($outputMode == 'include') {
	return $output;
}