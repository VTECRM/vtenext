<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	require_once('300Utils.php');

	$function = $_REQUEST['function'];
	$ret = array();

	switch ($function) {
		case 'linkedListGetChanges':
			// crmv@30528 crmv@131239
			$name = vtlib_purify($_REQUEST['name']);
			$sel = $_REQUEST['sel']; // crmv@126199
			$mod = vtlib_purify($_REQUEST['modname']);
			$fieldname = Zend_Json::decode($_REQUEST['fieldname']);
			$fielddatatype = Zend_Json::decode($_REQUEST['fielddatatype']);
			$fieldinfo = array();
			if (!empty($fieldname)) {
				foreach($fieldname as $i => $fname) {
					$fieldinfo[$fname] = $fielddatatype[$i];
				}
			}
			$ret = linkedListGetChanges($name, $sel, $mod, $fieldinfo);
			// crmv@30528e crmv@131239e
			break;
		default:
			break;
	}

	die(Zend_Json::encode($ret));
?>