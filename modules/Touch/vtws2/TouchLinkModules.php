<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchLinkModules extends TouchWSClass {

	function process(&$request) {
		global $currentModule, $touchInst;
		
		$module_from = vtlib_purify($request['module_from']);
		$crmid_from = intval($request['crmid_from']);
		$module_to = vtlib_purify($request['module_to']);
		$crmid_to = vtlib_purify($request['crmid_to']);
		
		// some basic validation
		if (empty($module_from)) return $this->error('First Module is empty');
		if (empty($module_to)) return $this->error('Second Module is empty');
		if (empty($crmid_from)) return $this->error('First ID is empty');
		if (empty($crmid_to)) return $this->error('Second ID is empty');
		
		if (in_array($module_from, $touchInst->excluded_modules)) return $this->error("Module $module_from is not allowed");
		if (in_array($module_to, $touchInst->excluded_modules)) return $this->error("Module $module_to is not allowed");
		
		if ($module_from == 'Events') $module_from = 'Calendar';
		if ($module_to == 'Events') $module_to = 'Calendar';
		
		// fix for Messages:
		if ($module_to == 'Messages') {
			$module_to = $module_from;
			$module_from = 'Messages';
			$t = $crmid_from;
			$crmid_from = $crmid_to;
			$crmid_to = $t;
		}
		
		$module = $currentModule = $module_from;

		$includeFile = 'updateRelations';
		if (isModuleInstalled('SDK') && class_exists('SDK')) {
			$tmp_sdk_custom_file = SDK::getFile($currentModule,$includeFile);
			if (!empty($tmp_sdk_custom_file)) {
				$includeFile = str_replace('..', '', $tmp_sdk_custom_file);
			}
		}

		$file = "modules/$currentModule/{$includeFile}.php";
		if (!is_readable($file)) {
			$file = "modules/VteCore/updateRelations.php";
		}
		
		unset($_REQUEST['mode']);
		$_REQUEST['parentid'] = $crmid_from;
		$_REQUEST['destination_module'] = $module_to;
		$_REQUEST['idlist'] = $crmid_to;
		$_REQUEST['no_redirect'] = true;

		try {
			require($file);
			
			// crmv@81032 save also the documents for messages
			if ($module_from == 'Messages') {
				$this->linkMessageAttachments($crmid_from, $module_to, $crmid_to);
			}
			// crmv@81032e

			$success = true;
		} catch (Exception $e) {
			$success = false;
			$message = $e->getMessage();
		}

		if ($success) {
			return $this->success();
		} else {
			return $this->error($message);
		}
	}

	// crmv@81032
	function linkMessageAttachments($messagesid, $module_to, $crmid_to) {
		$focus = CRMEntity::getInstance('Messages');
		$focus->id = $messagesid;

		$atts = $focus->getAttachmentsInfo();
		foreach ($atts as $contentid=>$att) {
			$focus->saveDocument($messagesid,$contentid,$crmid_to,$module_to);
		}
	}
	// crmv@81032e
}
