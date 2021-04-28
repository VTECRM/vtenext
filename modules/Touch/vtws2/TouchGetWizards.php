<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@99132 */

require_once('include/utils/WizardUtils.php');

class TouchGetWizards extends TouchWSClass {

	function process(&$request) {
		global $touchInst;
		
		$WU = WizardUtils::getInstance();
		$wizards = $WU->getWizards(null, true);
		
		$appWizards = array();
		if (is_array($wizards)) {
			foreach ($wizards as $wiz) {
				// exclude the modules not available in the app
				if ($wiz['module'] && !in_array($wiz['module'], $touchInst->excluded_modules)) {
					// exclude the sdk wizards
					if (!$wiz['src'] && !$wiz['template']) {
						$wiz['name'] = getTranslatedString($wiz['name']); // crmv@128989
						$appWizards[] = $wiz;
					}
				}
			}
		}
		
		return $this->success(array('wizards' => $appWizards));
	}
	
}