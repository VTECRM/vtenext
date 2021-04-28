<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class WSAPPHandler extends VTEventHandler {

	function handleEvent($eventName, $data) {

		if($eventName == 'vte.entity.beforesave') {//crmv@207852
			// Entity is about to be saved, take required action
		}

		if($eventName == 'vte.entity.aftersave') {//crmv@207852
			// Entity has been saved, take next action
		}
	}
}

?>