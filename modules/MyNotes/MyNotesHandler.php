<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class MyNotesHandler extends VTEventHandler {

	function handleEvent($eventName, $data) {
		
		if (!($data->focus instanceof MyNotes)) {
			return;
		}

		if($eventName == 'vte.entity.beforesave') {//crmv@207852
			$column_fields = $data->getData();
			if ($column_fields['subject'] == '') {
				global $listview_max_textlength;
				$temp_val = trim($column_fields['description']);
				if(strlen($temp_val) > $listview_max_textlength) {
					$temp_val = substr($temp_val,0,$listview_max_textlength).'...';
				}
				$data->set('subject',$temp_val);
			}
		}
	}
}
?>