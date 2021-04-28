<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@22622
class MenuViewHandler extends VTEventHandler {

	function handleEvent($eventName, $data) {
		global $adb, $current_user;

		// check irs a timcard we're saving.
		if (!($data->focus instanceof Users)) {
			return;
		}

		if($eventName == 'vte.entity.beforesave') {//crmv@207852
			
			if ($data->getId() == $current_user->id) {
				$focus = $data->getData();
				if ($focus['menu_view'] == 'Large Menu') {
					setcookie('crmvWinMaxStatus','open');
				} elseif ($focus['menu_view'] == 'Small Menu') {
					setcookie('crmvWinMaxStatus','close');
				}
			}
		}
	}
}
//crmv@22622e
?>