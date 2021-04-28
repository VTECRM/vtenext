<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@107655 */

require_once('include/utils/EmailDirectory.php'); 

class TouchGetEmailDirectory extends TouchWSClass {

	public function process(&$request) {
		global $touchInst, $touchUtils;

		$since = intval($request['since']);
		$sinceDate = ($since > 0 ? date('Y-m-d H:i:s', $since) : null);

		$emailDir = $this->getEmailDirectory($sinceDate);

		return $this->success($emailDir);
	}
	
	function getEmailDirectory($sinceDate = null) {
		global $touchInst;
		
		$list = array();
		$emailDirectory = new EmailDirectory();
		$lastUpdate = $emailDirectory->getLastUpdate();
		if ($sinceDate === null || $sinceDate < $lastUpdate) {
			$listTemp = $emailDirectory->getAll();
			// filter out unavailable modules
			foreach ($listTemp as $entry) {
				if (empty($entry['module']) || !in_array($entry['module'], $touchInst->excluded_modules)) {
					$list[] = $entry;
				}
			}
		}
		return array('entries' => $list, 'total' => count($list));
	}

}